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
use Spinen\QuickBooks\Client as QBClient;

class QuickBooksOAuthController extends Controller
{
    public function __construct(protected QBClient $qb) {}

    /**
     * Redirect the user to Intuit's OAuth consent screen.
     * GET /quickbooks/connect
     */
    public function redirect(Request $request)
    {
        $state = bin2hex(random_bytes(16));
        session(['qbo_oauth_state' => $state]);

        return redirect($this->qb->getAuthorizationCodeURL($state));
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
            // Exchange code for tokens — spinen stores them in quickbooks_tokens
            $this->qb->parseRedirectURL($request->all(), auth()->user());

            $realmId     = $request->get('realmId');
            $companyName = $this->fetchCompanyName();

            QuickBooksConnection::updateOrCreate(
                ['realm_id' => $realmId],
                [
                    'user_id'      => auth()->id(),
                    'company_name' => $companyName,
                    'active'       => true,
                    'connected_at' => now(),
                ]
            );

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
        try {
            $this->qb->revokeToken(auth()->user());
        } catch (\Exception) {
            // Token may already be expired — continue with local cleanup
        }

        QuickBooksConnection::where('realm_id', $request->get('realm_id'))
            ->where('user_id', auth()->id())
            ->delete();

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

    protected function fetchCompanyName(): ?string
    {
        try {
            return $this->qb->getDataService()->getCompanyInfo()?->CompanyName ?? null;
        } catch (\Exception) {
            return null;
        }
    }
}
