<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2026 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Raju\QuickBooksMcp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Raju\QuickBooksMcp\Models\QuickBooksConnection;
use Raju\QuickBooksMcp\Services\QuickBooksDataServiceFactory;

class RefreshQuickBooksToken
{
    public function __construct(protected QuickBooksDataServiceFactory $factory) {}

    public function handle(Request $request, Closure $next)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return $next($request);
            }

            $bufferMinutes = config('quickbooks-mcp.token_refresh_buffer_minutes', 5);

            $connection = QuickBooksConnection::where('user_id', $user->getKey())
                ->where('active', true)
                ->latest('last_used_at')
                ->first();

            if ($connection && $connection->isAccessTokenExpiringSoon($bufferMinutes)) {
                $dataService = $this->factory->makeFromConnection($connection);
                $newToken    = $dataService->getOAuth2LoginHelper()->refreshToken();
                $dataService->updateOAuth2Token($newToken);
                $this->factory->persistToken($connection, $newToken);
            }
        } catch (\Exception $e) {
            logger()->warning('QBO token refresh failed: ' . $e->getMessage());
        }

        return $next($request);
    }
}
