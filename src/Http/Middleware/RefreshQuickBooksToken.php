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
use Spinen\QuickBooks\Client as QBClient;

class RefreshQuickBooksToken
{
    public function __construct(protected QBClient $qb) {}

    public function handle(Request $request, Closure $next)
    {
        try {
            $token = $request->user()?->quickBooksToken;

            if ($token && $this->isExpiringSoon($token)) {
                $this->qb->refreshTokens($request->user());
            }
        } catch (\Exception $e) {
            logger()->warning('QBO token refresh failed: ' . $e->getMessage());
        }

        return $next($request);
    }

    protected function isExpiringSoon($token): bool
    {
        $buffer = config('quickbooks-mcp.token_refresh_buffer_minutes', 5);
        return now()->addMinutes($buffer)->isAfter(
            $token->access_token_expires_at ?? now()
        );
    }
}
