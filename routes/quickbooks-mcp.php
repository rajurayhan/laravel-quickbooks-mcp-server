<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2026 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/*
 |--------------------------------------------------------------------------
 | QuickBooks MCP Routes
 |--------------------------------------------------------------------------
 |
 | After publishing this file, register it in your application:
 |
 | Option A — in app/Providers/AppServiceProvider.php:
 |
 |     public function boot(): void
 |     {
 |         Route::middleware('web')
 |             ->group(base_path('routes/quickbooks-mcp.php'));
 |     }
 |
 | Option B — at the bottom of routes/web.php or routes/api.php:
 |
 |     require base_path('routes/quickbooks-mcp.php');
 |
 |--------------------------------------------------------------------------
 | Auth Guard
 |--------------------------------------------------------------------------
 |
 | Change 'api' on the MCP route group to match your application's guard:
 |   'api'     — if your app uses Laravel Passport
 |   'sanctum' — if your app uses Laravel Sanctum
 |
 |--------------------------------------------------------------------------
 | Intuit OAuth Callback URI
 |--------------------------------------------------------------------------
 |
 | The /quickbooks/callback route below must be registered at:
 |   https://developer.intuit.com → Your App → Keys & OAuth → Redirect URIs
 |
 | It must match the full URL exactly (e.g. https://yourdomain.com/quickbooks/callback).
 |
 */

use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;
use Raju\QuickBooksMcp\Http\Controllers\QuickBooksOAuthController;
use Raju\QuickBooksMcp\Http\Middleware\ResolveQuickBooksRealm;
use Raju\QuickBooksMcp\Http\Middleware\RefreshQuickBooksToken;
use Raju\QuickBooksMcp\Server\QuickBooksServer;

// QBO OAuth connection routes (web session — user connects via your SaaS UI)
Route::middleware(['web', 'auth'])->prefix('quickbooks')->group(function () {
    Route::get('/connect',       [QuickBooksOAuthController::class, 'redirect']);
    Route::get('/callback',      [QuickBooksOAuthController::class, 'callback']);
    Route::delete('/disconnect', [QuickBooksOAuthController::class, 'disconnect']);
    Route::get('/connections',   [QuickBooksOAuthController::class, 'connections']);
});

// MCP server endpoint
// Change 'api' below to match your application's Bearer token guard:
//   'api'     — Laravel Passport (default)
//   'sanctum' — Laravel Sanctum
Route::middleware([
    'api',
    'auth:api',
    ResolveQuickBooksRealm::class,
    RefreshQuickBooksToken::class,
])->group(function () {
    Mcp::server(QuickBooksServer::class)->at(config('quickbooks-mcp.path'));
});
