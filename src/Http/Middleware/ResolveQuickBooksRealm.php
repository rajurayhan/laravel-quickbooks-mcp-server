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
use Raju\QuickBooksMcp\Services\QuickBooksService;
use Raju\QuickBooksMcp\Exceptions\QuickBooksAuthException;

class ResolveQuickBooksRealm
{
    public function __construct(protected QuickBooksService $qb) {}

    public function handle(Request $request, Closure $next)
    {
        $connection = QuickBooksConnection::where('user_id', auth()->id())
            ->where('active', true)
            ->first();

        if (!$connection) {
            throw new QuickBooksAuthException(
                'No active QuickBooks connection found for this account. ' .
                'Please connect at /quickbooks/connect.'
            );
        }

        app()->instance(
            QuickBooksService::class,
            $this->qb->forRealm($connection->realm_id)
        );

        $connection->update(['last_used_at' => now()]);

        return $next($request);
    }
}
