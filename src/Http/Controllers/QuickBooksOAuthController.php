<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2026 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Raju\QuickBooksMcp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Raju\QuickBooksMcp\Models\QuickBooksConnection;
use Raju\QuickBooksMcp\Services\QuickBooksDataServiceFactory;

class QuickBooksOAuthController extends Controller
{
    public function __construct(protected QuickBooksDataServiceFactory $factory) {}

    /**
     * Redirect the user to Intuit's OAuth consent screen.
     * GET /quickbooks/connect
     */
    public function redirect(Request $request)
    {
        $state = bin2hex(random_bytes(16));
        session(['qbo_oauth_state' => $state]);

        $helper  = $this->factory->makeForAuth()->getOAuth2LoginHelper();
        $authUrl = $helper->getAuthorizationCodeURL() . '&state=' . $state;

        return redirect($authUrl);
    }

    /**
     * Handle Intuit's OAuth callback.
     * GET /quickbooks/callback
     */
    public function callback(Request $request)
    {
        if ($request->get('state') !== session('qbo_oauth_state')) {
            return response()->json(['error' => 'Invalid OAuth state.'], 422);
        }

        if ($request->has('error')) {
            return response()->json([
                'error'   => 'QuickBooks authorization denied.',
                'details' => $request->get('error_description'),
            ], 422);
        }

        try {
            $dataService = $this->factory->makeForAuth();
            $helper      = $dataService->getOAuth2LoginHelper();

            $token = $helper->exchangeAuthorizationCodeForToken(
                $request->get('code'),
                $request->get('realmId')
            );

            $dataService->updateOAuth2Token($token);

            $realmId = $token->getRealmID() ?: $request->get('realmId');

            $connection = QuickBooksConnection::updateOrCreate(
                ['realm_id' => $realmId],
                [
                    'user_id'      => auth()->id(),
                    'active'       => true,
                    'connected_at' => now(),
                ]
            );

            $this->factory->persistToken($connection, $token);

            $companyName = $this->fetchCompanyName($dataService);
            $connection->update(['company_name' => $companyName]);

            return response()->json([
                'message'      => 'QuickBooks connected successfully.',
                'realm_id'     => $realmId,
                'company_name' => $companyName,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to complete QuickBooks OAuth: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Disconnect a QBO company.
     * DELETE /quickbooks/disconnect
     */
    public function disconnect(Request $request)
    {
        $connection = QuickBooksConnection::where('realm_id', $request->get('realm_id'))
            ->where('user_id', auth()->id())
            ->first();

        if ($connection) {
            try {
                $helper = $this->factory->makeFromConnection($connection)->getOAuth2LoginHelper();
                $helper->revokeToken($connection->access_token);
            } catch (\Exception) {
                // Token may already be expired — continue with local cleanup
            }

            $connection->delete();
        }

        return response()->json(['message' => 'QuickBooks disconnected.']);
    }

    /**
     * List all active QBO connections for the authenticated user.
     * GET /quickbooks/connections
     */
    public function connections(Request $request)
    {
        return response()->json(
            QuickBooksConnection::where('user_id', auth()->id())
                ->where('active', true)
                ->get(['realm_id', 'company_name', 'connected_at', 'last_used_at'])
        );
    }

    protected function fetchCompanyName(\QuickBooksOnline\API\DataService\DataService $dataService): ?string
    {
        try {
            return $dataService->getCompanyInfo()?->CompanyName ?? null;
        } catch (\Exception) {
            return null;
        }
    }
}
