<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2026 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Raju\QuickBooksMcp;

use Illuminate\Support\ServiceProvider;
use Raju\QuickBooksMcp\Services\QuickBooksService;

class QuickBooksMcpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/quickbooks-mcp.php', 'quickbooks-mcp');

        $this->app->singleton(QuickBooksService::class, function ($app) {
            return new QuickBooksService(
                $app->make(\Spinen\QuickBooks\Client::class)
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/quickbooks-mcp.php' => config_path('quickbooks-mcp.php'),
            ], 'quickbooks-mcp-config');

            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], 'quickbooks-mcp-migrations');

            // Routes are NOT auto-loaded. Developer must publish and register manually.
            $this->publishes([
                __DIR__ . '/../routes/quickbooks-mcp.php' => base_path('routes/quickbooks-mcp.php'),
            ], 'quickbooks-mcp-routes');

            $this->publishes([
                __DIR__ . '/../config/quickbooks-mcp.php' => config_path('quickbooks-mcp.php'),
                __DIR__ . '/../database/migrations/'       => database_path('migrations'),
                __DIR__ . '/../routes/quickbooks-mcp.php'  => base_path('routes/quickbooks-mcp.php'),
            ]);
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
