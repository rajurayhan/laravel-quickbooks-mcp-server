# QuickBooks Online MCP Server — Laravel Package

## Project overview

Build a first-party PHP Laravel Composer package that exposes QuickBooks Online (QBO) as a Model Context Protocol (MCP) server. AI clients (Claude, n8n, Cursor, etc.) connect to it over HTTP and can perform full CRUD operations on QBO entities using natural language.

This package is the first PHP/Laravel QuickBooks MCP implementation in the ecosystem. It mirrors the official Intuit Node.js MCP server (`intuit/quickbooks-online-mcp-server`) in tool coverage, but adds:
- Remote HTTP transport (not local stdio)
- Multi-tenant support (multiple QBO companies per installation)
- Name-to-ID resolution (agents use names, not internal IDs)
- Production environment support from day one
- Laravel-native auth (Sanctum + Passport)
- Full OAuth 2.0 flow — any SaaS user can connect their own QBO company

---

## Compatibility

This package is designed to work with **any Laravel 11+ application** regardless of which auth driver it uses. It makes no assumption about whether the host app uses Passport, Sanctum, or a custom guard.

The only hard requirements on the host app are:
- A `users` table with an `id` column (standard Laravel)
- The `HasQuickBooksToken` trait from `spinen/laravel-quickbooks-client` on the `User` model
- At least one configured auth guard that resolves `auth()->user()` from a Bearer token

Everything else — which guard, which token driver, how tokens are issued — is left to the host app.

| Layer | Package |
|---|---|
| MCP server | `laravel/mcp` (official Laravel package) |
| QBO API client | `spinen/laravel-quickbooks-client` (wraps `quickbooks/v3-php-sdk`) |
| MCP auth | `laravel/sanctum` (token) or `laravel/passport` (OAuth 2.1) |
| PHP | >= 8.2 |
| Laravel | >= 11.x |

---

## Package identity

```json
{
  "name": "rajurayhan/laravel-quickbooks-mcp-server",
  "description": "QuickBooks Online MCP server for Laravel — expose QBO as AI-callable tools",
  "type": "library",
  "license": "MIT",
  "authors": [
    {
      "name": "Raju Rayhan",
      "homepage": "https://github.com/rajurayhan"
    }
  ],
  "require": {
    "php": "^8.2",
    "laravel/framework": "^11.0|^12.0",
    "laravel/mcp": "^1.0",
    "spinen/laravel-quickbooks-client": "^4.0",
    "guzzlehttp/guzzle": "^7.0"
  },
  "suggest": {
    "laravel/passport": "Required if using auth guard 'api' (Passport OAuth2).",
    "laravel/sanctum": "Required if using auth guard 'sanctum'."
  },
  "autoload": {
    "psr-4": {
      "Raju\\QuickBooksMcp\\": "src/"
    }
  }
}
```

---

## Repository structure

```
src/
├── QuickBooksMcpServiceProvider.php
├── Server/
│   └── QuickBooksServer.php
├── Tools/
│   ├── Account/
│   │   ├── CreateAccountTool.php
│   │   ├── SearchAccountsTool.php
│   │   └── UpdateAccountTool.php
│   ├── Bill/
│   │   ├── CreateBillTool.php
│   │   ├── DeleteBillTool.php
│   │   ├── GetBillTool.php
│   │   ├── SearchBillsTool.php
│   │   └── UpdateBillTool.php
│   ├── BillPayment/
│   │   ├── CreateBillPaymentTool.php
│   │   ├── DeleteBillPaymentTool.php
│   │   ├── GetBillPaymentTool.php
│   │   ├── SearchBillPaymentsTool.php
│   │   └── UpdateBillPaymentTool.php
│   ├── Customer/
│   │   ├── CreateCustomerTool.php
│   │   ├── DeleteCustomerTool.php
│   │   ├── GetCustomerTool.php
│   │   ├── SearchCustomersTool.php
│   │   └── UpdateCustomerTool.php
│   ├── Employee/
│   │   ├── CreateEmployeeTool.php
│   │   ├── GetEmployeeTool.php
│   │   ├── SearchEmployeesTool.php
│   │   └── UpdateEmployeeTool.php
│   ├── Estimate/
│   │   ├── CreateEstimateTool.php
│   │   ├── DeleteEstimateTool.php
│   │   ├── GetEstimateTool.php
│   │   ├── SearchEstimatesTool.php
│   │   └── UpdateEstimateTool.php
│   ├── Invoice/
│   │   ├── CreateInvoiceTool.php
│   │   ├── ReadInvoiceTool.php
│   │   ├── SearchInvoicesTool.php
│   │   └── UpdateInvoiceTool.php
│   ├── Item/
│   │   ├── CreateItemTool.php
│   │   ├── ReadItemTool.php
│   │   ├── SearchItemsTool.php
│   │   └── UpdateItemTool.php
│   ├── JournalEntry/
│   │   ├── CreateJournalEntryTool.php
│   │   ├── DeleteJournalEntryTool.php
│   │   ├── GetJournalEntryTool.php
│   │   ├── SearchJournalEntriesTool.php
│   │   └── UpdateJournalEntryTool.php
│   ├── Purchase/
│   │   ├── CreatePurchaseTool.php
│   │   ├── DeletePurchaseTool.php
│   │   ├── GetPurchaseTool.php
│   │   ├── SearchPurchasesTool.php
│   │   └── UpdatePurchaseTool.php
│   └── Vendor/
│       ├── CreateVendorTool.php
│       ├── DeleteVendorTool.php
│       ├── GetVendorTool.php
│       ├── SearchVendorsTool.php
│       └── UpdateVendorTool.php
├── Services/
│   └── QuickBooksService.php
├── Concerns/
│   └── ResolvesEntityNames.php
├── Http/
│   ├── Controllers/
│   │   └── QuickBooksOAuthController.php
│   └── Middleware/
│       ├── ResolveQuickBooksRealm.php
│       └── RefreshQuickBooksToken.php
├── Models/
│   └── QuickBooksConnection.php
└── Exceptions/
    ├── QuickBooksAuthException.php
    └── QuickBooksToolException.php

config/
└── quickbooks-mcp.php

routes/
└── quickbooks-mcp.php      ← publishable stub, not auto-loaded

database/migrations/
├── xxxx_create_quickbooks_connections_table.php

tests/
├── Feature/
│   └── Tools/
│       ├── CustomerToolsTest.php
│       ├── InvoiceToolsTest.php
│       └── ...
└── Unit/
    └── Services/
        └── QuickBooksServiceTest.php
```

---

## Service provider

`src/QuickBooksMcpServiceProvider.php`

```php
<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2025 Raju Rayhan
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
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
```

---

## Config file

`config/quickbooks-mcp.php`

```php
<?php

return [
    /*
     * The route path where the MCP server is exposed.
     * This is used inside your published routes/quickbooks-mcp.php.
     * Example: https://yourdomain.com/mcp/quickbooks
     */
    'path' => env('QBO_MCP_PATH', 'mcp/quickbooks'),

    /*
     * Multi-tenant mode.
     * When true, realm_id is resolved automatically from the authenticated user.
     * When false, uses the authenticated user's linked QBO token.
     */
    'multi_tenant' => env('QBO_MCP_MULTI_TENANT', true),

    /*
     * Default result limit for search tools.
     */
    'search_limit'     => env('QBO_SEARCH_LIMIT', 20),

    /*
     * Maximum result limit allowed for search tools.
     */
    'search_limit_max' => env('QBO_SEARCH_LIMIT_MAX', 100),

    /*
     * QBO environment: 'production' or 'development' (sandbox)
     */
    'environment' => env('QUICKBOOKS_DATA_SOURCE', 'production'),

    /*
     * OAuth redirect URI — must match exactly what is registered
     * in your Intuit Developer app settings.
     */
    'redirect_uri' => env('QUICKBOOKS_REDIRECT_URI', env('APP_URL') . '/quickbooks/callback'),

    /*
     * Minutes before QBO access token expiry to proactively refresh.
     */
    'token_refresh_buffer_minutes' => env('QBO_TOKEN_REFRESH_BUFFER', 5),
];
```

---

## Database migrations

Only one migration is needed from this package. The `personal_access_tokens` table does not need any changes — Passport manages its own `oauth_access_tokens` table and the `realm_id` scoping is handled via the `quickbooks_connections` user lookup, not token metadata.

`database/migrations/xxxx_create_quickbooks_connections_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quickbooks_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('realm_id')->unique();
            $table->string('company_name')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quickbooks_connections');
    }
};
```


---

## Models

`src/Models/QuickBooksConnection.php`

```php
<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2025 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Raju\QuickBooksMcp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuickBooksConnection extends Model
{
    protected $fillable = [
        'user_id', 'realm_id', 'company_name',
        'active', 'connected_at', 'last_used_at',
    ];

    protected $casts = [
        'active'       => 'boolean',
        'connected_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }
}
```

---

## OAuth controllers

There is only one controller in this package. The MCP endpoint auth is handled entirely by your existing Passport Authorization Code flow — no token issuance controller is needed here.

### QuickBooksOAuthController

`src/Http/Controllers/QuickBooksOAuthController.php`

```php
<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2025 Raju Rayhan
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
```


---

## Middleware

### ResolveQuickBooksRealm

`src/Http/Middleware/ResolveQuickBooksRealm.php`

```php
<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2025 Raju Rayhan
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
        $token = $request->user()?->currentAccessToken();

        if (!$token || empty($token->realm_id)) {
            throw new QuickBooksAuthException(
                'This MCP token is not linked to a QuickBooks connection. ' .
                'Please issue a new token via POST /quickbooks/mcp-token.'
            );
        }

        $connection = QuickBooksConnection::where('realm_id', $token->realm_id)
            ->where('active', true)
            ->first();

        if (!$connection) {
            throw new QuickBooksAuthException(
                'The QuickBooks connection for this token has been disconnected. ' .
                'Please reconnect at /quickbooks/connect.'
            );
        }

        app()->instance(
            QuickBooksService::class,
            $this->qb->forRealm($token->realm_id)
        );

        $connection->update(['last_used_at' => now()]);

        return $next($request);
    }
}
```

### RefreshQuickBooksToken

`src/Http/Middleware/RefreshQuickBooksToken.php`

```php
<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2025 Raju Rayhan
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
```

---

## Route registration

`routes/mcp.php`

```php
<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2025 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
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
// Guard is configurable — defaults to 'api' (Passport) but works with
// 'sanctum' or any custom guard the host app has configured.
$guard = config('quickbooks-mcp.auth_guard', 'api');

Route::middleware([
    'api',
    "auth:{$guard}",
    ResolveQuickBooksRealm::class,
    RefreshQuickBooksToken::class,
])->group(function () {
    Mcp::server(QuickBooksServer::class)->at(config('quickbooks-mcp.path'));
});
```

---

## MCP server class

`src/Server/QuickBooksServer.php`

```php
<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2025 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Raju\QuickBooksMcp\Server;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Raju\QuickBooksMcp\Tools\Account\CreateAccountTool;
use Raju\QuickBooksMcp\Tools\Account\SearchAccountsTool;
use Raju\QuickBooksMcp\Tools\Account\UpdateAccountTool;
use Raju\QuickBooksMcp\Tools\Bill\CreateBillTool;
use Raju\QuickBooksMcp\Tools\Bill\DeleteBillTool;
use Raju\QuickBooksMcp\Tools\Bill\GetBillTool;
use Raju\QuickBooksMcp\Tools\Bill\SearchBillsTool;
use Raju\QuickBooksMcp\Tools\Bill\UpdateBillTool;
use Raju\QuickBooksMcp\Tools\BillPayment\CreateBillPaymentTool;
use Raju\QuickBooksMcp\Tools\BillPayment\DeleteBillPaymentTool;
use Raju\QuickBooksMcp\Tools\BillPayment\GetBillPaymentTool;
use Raju\QuickBooksMcp\Tools\BillPayment\SearchBillPaymentsTool;
use Raju\QuickBooksMcp\Tools\BillPayment\UpdateBillPaymentTool;
use Raju\QuickBooksMcp\Tools\Customer\CreateCustomerTool;
use Raju\QuickBooksMcp\Tools\Customer\DeleteCustomerTool;
use Raju\QuickBooksMcp\Tools\Customer\GetCustomerTool;
use Raju\QuickBooksMcp\Tools\Customer\SearchCustomersTool;
use Raju\QuickBooksMcp\Tools\Customer\UpdateCustomerTool;
use Raju\QuickBooksMcp\Tools\Employee\CreateEmployeeTool;
use Raju\QuickBooksMcp\Tools\Employee\GetEmployeeTool;
use Raju\QuickBooksMcp\Tools\Employee\SearchEmployeesTool;
use Raju\QuickBooksMcp\Tools\Employee\UpdateEmployeeTool;
use Raju\QuickBooksMcp\Tools\Estimate\CreateEstimateTool;
use Raju\QuickBooksMcp\Tools\Estimate\DeleteEstimateTool;
use Raju\QuickBooksMcp\Tools\Estimate\GetEstimateTool;
use Raju\QuickBooksMcp\Tools\Estimate\SearchEstimatesTool;
use Raju\QuickBooksMcp\Tools\Estimate\UpdateEstimateTool;
use Raju\QuickBooksMcp\Tools\Invoice\CreateInvoiceTool;
use Raju\QuickBooksMcp\Tools\Invoice\ReadInvoiceTool;
use Raju\QuickBooksMcp\Tools\Invoice\SearchInvoicesTool;
use Raju\QuickBooksMcp\Tools\Invoice\UpdateInvoiceTool;
use Raju\QuickBooksMcp\Tools\Item\CreateItemTool;
use Raju\QuickBooksMcp\Tools\Item\ReadItemTool;
use Raju\QuickBooksMcp\Tools\Item\SearchItemsTool;
use Raju\QuickBooksMcp\Tools\Item\UpdateItemTool;
use Raju\QuickBooksMcp\Tools\JournalEntry\CreateJournalEntryTool;
use Raju\QuickBooksMcp\Tools\JournalEntry\DeleteJournalEntryTool;
use Raju\QuickBooksMcp\Tools\JournalEntry\GetJournalEntryTool;
use Raju\QuickBooksMcp\Tools\JournalEntry\SearchJournalEntriesTool;
use Raju\QuickBooksMcp\Tools\JournalEntry\UpdateJournalEntryTool;
use Raju\QuickBooksMcp\Tools\Purchase\CreatePurchaseTool;
use Raju\QuickBooksMcp\Tools\Purchase\DeletePurchaseTool;
use Raju\QuickBooksMcp\Tools\Purchase\GetPurchaseTool;
use Raju\QuickBooksMcp\Tools\Purchase\SearchPurchasesTool;
use Raju\QuickBooksMcp\Tools\Purchase\UpdatePurchaseTool;
use Raju\QuickBooksMcp\Tools\Vendor\CreateVendorTool;
use Raju\QuickBooksMcp\Tools\Vendor\DeleteVendorTool;
use Raju\QuickBooksMcp\Tools\Vendor\GetVendorTool;
use Raju\QuickBooksMcp\Tools\Vendor\SearchVendorsTool;
use Raju\QuickBooksMcp\Tools\Vendor\UpdateVendorTool;

#[Name('QuickBooks Online')]
#[Version('1.0.0')]
#[Instructions(
    'This MCP server provides full access to QuickBooks Online. ' .
    'You can manage customers, vendors, invoices, bills, estimates, purchases, ' .
    'employees, items, accounts, journal entries, and payments. ' .
    'For search tools, pass human-readable names — ID resolution is handled automatically. ' .
    'All write operations require confirmation of key fields before executing.'
)]
class QuickBooksServer extends Server
{
    protected array $tools = [
        CreateAccountTool::class,
        SearchAccountsTool::class,
        UpdateAccountTool::class,
        CreateBillTool::class,
        DeleteBillTool::class,
        GetBillTool::class,
        SearchBillsTool::class,
        UpdateBillTool::class,
        CreateBillPaymentTool::class,
        DeleteBillPaymentTool::class,
        GetBillPaymentTool::class,
        SearchBillPaymentsTool::class,
        UpdateBillPaymentTool::class,
        CreateCustomerTool::class,
        DeleteCustomerTool::class,
        GetCustomerTool::class,
        SearchCustomersTool::class,
        UpdateCustomerTool::class,
        CreateEmployeeTool::class,
        GetEmployeeTool::class,
        SearchEmployeesTool::class,
        UpdateEmployeeTool::class,
        CreateEstimateTool::class,
        DeleteEstimateTool::class,
        GetEstimateTool::class,
        SearchEstimatesTool::class,
        UpdateEstimateTool::class,
        CreateInvoiceTool::class,
        ReadInvoiceTool::class,
        SearchInvoicesTool::class,
        UpdateInvoiceTool::class,
        CreateItemTool::class,
        ReadItemTool::class,
        SearchItemsTool::class,
        UpdateItemTool::class,
        CreateJournalEntryTool::class,
        DeleteJournalEntryTool::class,
        GetJournalEntryTool::class,
        SearchJournalEntriesTool::class,
        UpdateJournalEntryTool::class,
        CreatePurchaseTool::class,
        DeletePurchaseTool::class,
        GetPurchaseTool::class,
        SearchPurchasesTool::class,
        UpdatePurchaseTool::class,
        CreateVendorTool::class,
        DeleteVendorTool::class,
        GetVendorTool::class,
        SearchVendorsTool::class,
        UpdateVendorTool::class,
    ];
}
```

---

## QuickBooksService

`src/Services/QuickBooksService.php`

```php
<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2025 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Raju\QuickBooksMcp\Services;

use Spinen\QuickBooks\Client as QBClient;
use QuickBooksOnline\API\DataService\DataService;
use Raju\QuickBooksMcp\Exceptions\QuickBooksAuthException;
use Raju\QuickBooksMcp\Exceptions\QuickBooksToolException;

class QuickBooksService
{
    protected QBClient $client;
    protected ?string $realmId = null;

    public function __construct(QBClient $client)
    {
        $this->client = $client;
    }

    public function forRealm(string $realmId): static
    {
        $clone = clone $this;
        $clone->realmId = $realmId;
        return $clone;
    }

    public function dataService(): DataService
    {
        try {
            return $this->client->getDataService();
        } catch (\Exception $e) {
            throw new QuickBooksAuthException(
                'Failed to connect to QuickBooks: ' . $e->getMessage()
            );
        }
    }

    public function query(string $sql): array
    {
        $ds = $this->dataService();
        $results = $ds->Query($sql);
        $this->throwIfError($ds);
        return $results ?? [];
    }

    public function findById(string $entityType, string $id): mixed
    {
        $ds = $this->dataService();
        $result = $ds->FindById($entityType, $id);
        $this->throwIfError($ds);
        return $result;
    }

    public function create(mixed $entity): mixed
    {
        $ds = $this->dataService();
        $result = $ds->Add($entity);
        $this->throwIfError($ds);
        return $result;
    }

    public function update(mixed $entity): mixed
    {
        $ds = $this->dataService();
        $result = $ds->Update($entity);
        $this->throwIfError($ds);
        return $result;
    }

    public function delete(mixed $entity): mixed
    {
        $ds = $this->dataService();
        $result = $ds->Delete($entity);
        $this->throwIfError($ds);
        return $result;
    }

    public function resolveCustomerId(string $name): ?string
    {
        $results = $this->query(
            "SELECT Id FROM Customer WHERE DisplayName = '{$this->escape($name)}' MAXRESULTS 1"
        );
        return $results[0]->Id ?? null;
    }

    public function resolveVendorId(string $name): ?string
    {
        $results = $this->query(
            "SELECT Id FROM Vendor WHERE DisplayName = '{$this->escape($name)}' MAXRESULTS 1"
        );
        return $results[0]->Id ?? null;
    }

    public function resolveAccountId(string $name): ?string
    {
        $results = $this->query(
            "SELECT Id FROM Account WHERE Name = '{$this->escape($name)}' MAXRESULTS 1"
        );
        return $results[0]->Id ?? null;
    }

    public function resolveItemId(string $name): ?string
    {
        $results = $this->query(
            "SELECT Id FROM Item WHERE Name = '{$this->escape($name)}' MAXRESULTS 1"
        );
        return $results[0]->Id ?? null;
    }

    protected function throwIfError(DataService $ds): void
    {
        $error = $ds->getLastError();
        if ($error) {
            throw new QuickBooksToolException(
                $error->getIntuitErrorMessage() ?? 'Unknown QuickBooks error',
                (int) ($error->getIntuitErrorCode() ?? 0)
            );
        }
    }

    protected function escape(string $value): string
    {
        return str_replace("'", "\\'", $value);
    }
}
```

---

## ResolvesEntityNames concern

`src/Concerns/ResolvesEntityNames.php`

```php
<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2025 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Raju\QuickBooksMcp\Concerns;

use Raju\QuickBooksMcp\Services\QuickBooksService;
use Raju\QuickBooksMcp\Exceptions\QuickBooksToolException;

trait ResolvesEntityNames
{
    protected function resolveCustomer(string $nameOrId, QuickBooksService $qb): string
    {
        if (is_numeric($nameOrId)) return $nameOrId;
        $id = $qb->resolveCustomerId($nameOrId);
        if (!$id) {
            throw new QuickBooksToolException(
                "Customer \"{$nameOrId}\" not found. Use search_customers to find the correct name."
            );
        }
        return $id;
    }

    protected function resolveVendor(string $nameOrId, QuickBooksService $qb): string
    {
        if (is_numeric($nameOrId)) return $nameOrId;
        $id = $qb->resolveVendorId($nameOrId);
        if (!$id) {
            throw new QuickBooksToolException(
                "Vendor \"{$nameOrId}\" not found. Use search_vendors to find the correct name."
            );
        }
        return $id;
    }

    protected function resolveAccount(string $nameOrId, QuickBooksService $qb): string
    {
        if (is_numeric($nameOrId)) return $nameOrId;
        $id = $qb->resolveAccountId($nameOrId);
        if (!$id) {
            throw new QuickBooksToolException(
                "Account \"{$nameOrId}\" not found. Use search_accounts to find the correct name."
            );
        }
        return $id;
    }

    protected function resolveItem(string $nameOrId, QuickBooksService $qb): string
    {
        if (is_numeric($nameOrId)) return $nameOrId;
        $id = $qb->resolveItemId($nameOrId);
        if (!$id) {
            throw new QuickBooksToolException(
                "Item \"{$nameOrId}\" not found. Use search_items to find the correct name."
            );
        }
        return $id;
    }
}
```

---

## Tool implementation patterns

### Pattern 1 — Search tool

```php
<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2025 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Raju\QuickBooksMcp\Tools\Customer;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description('Search QuickBooks customers by name, email, or company name.')]
class SearchCustomersTool extends Tool
{
    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query'  => ['type' => 'string',  'description' => 'Name, email, or company to search for'],
                'active' => ['type' => 'boolean', 'description' => 'Filter active only (default: true)'],
                'limit'  => ['type' => 'integer', 'description' => 'Max results, 1–100 (default: 20)'],
            ],
            'required' => ['query'],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $query        = $request->get('query');
            $active       = $request->get('active', true);
            $limit        = min((int) $request->get('limit', 20), 100);
            $escaped      = str_replace("'", "\\'", $query);
            $activeClause = $active ? "AND Active = true" : "";

            $sql = "SELECT * FROM Customer
                    WHERE (DisplayName LIKE '%{$escaped}%'
                        OR CompanyName LIKE '%{$escaped}%'
                        OR PrimaryEmailAddr = '{$escaped}')
                    {$activeClause}
                    MAXRESULTS {$limit}";

            $results = $qb->query($sql);

            if (empty($results)) {
                return Response::text("No customers found matching \"{$query}\".");
            }

            $lines = collect($results)->map(fn($c) => implode(' | ', array_filter([
                "ID: {$c->Id}",
                $c->DisplayName,
                $c->CompanyName ?? null,
                $c->PrimaryEmailAddr->Address ?? null,
                isset($c->Balance) ? "Balance: {$c->Balance}" : null,
            ])))->join("\n");

            return Response::text("Found " . count($results) . " customer(s):\n\n{$lines}");

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
```

### Pattern 2 — Get tool

```php
<?php
namespace Raju\QuickBooksMcp\Tools\Customer;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description('Get a single QuickBooks customer by their ID.')]
class GetCustomerTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id' => ['type' => 'string', 'description' => 'QuickBooks customer ID'],
            ],
            'required' => ['id'],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $customer = $qb->findById('Customer', $request->get('id'));

            if (!$customer) {
                return Response::text("Customer ID {$request->get('id')} not found.");
            }

            return Response::text(json_encode([
                'id'         => $customer->Id,
                'name'       => $customer->DisplayName,
                'company'    => $customer->CompanyName ?? null,
                'email'      => $customer->PrimaryEmailAddr->Address ?? null,
                'phone'      => $customer->PrimaryPhone->FreeFormNumber ?? null,
                'balance'    => $customer->Balance ?? 0,
                'active'     => $customer->Active,
                'sync_token' => $customer->SyncToken,
            ], JSON_PRETTY_PRINT));

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
```

### Pattern 3 — Create tool (with name resolution)

```php
<?php
namespace Raju\QuickBooksMcp\Tools\Bill;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use QuickBooksOnline\API\Facades\QBOFacade;
use Raju\QuickBooksMcp\Concerns\ResolvesEntityNames;
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description(
    'Create a new bill (accounts payable) in QuickBooks. ' .
    'Pass vendor name or ID. Line items need account name or ID and amount.'
)]
class CreateBillTool extends Tool
{
    use ResolvesEntityNames;

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'vendor'     => ['type' => 'string', 'description' => 'Vendor name or ID'],
                'txn_date'   => ['type' => 'string', 'description' => 'Transaction date (YYYY-MM-DD). Defaults to today.'],
                'due_date'   => ['type' => 'string', 'description' => 'Due date (YYYY-MM-DD)'],
                'line_items' => [
                    'type'  => 'array',
                    'items' => [
                        'type'       => 'object',
                        'properties' => [
                            'account'     => ['type' => 'string', 'description' => 'Account name or ID'],
                            'amount'      => ['type' => 'number', 'description' => 'Line amount'],
                            'description' => ['type' => 'string', 'description' => 'Line description (optional)'],
                        ],
                        'required' => ['account', 'amount'],
                    ],
                ],
                'memo' => ['type' => 'string', 'description' => 'Private memo (optional)'],
            ],
            'required' => ['vendor', 'line_items'],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $vendorId = $this->resolveVendor($request->get('vendor'), $qb);

            $lines = [];
            foreach ($request->get('line_items') as $item) {
                $accountId = $this->resolveAccount($item['account'], $qb);
                $line = QBOFacade::create('Line');
                $line->Amount = $item['amount'];
                $line->DetailType = 'AccountBasedExpenseLineDetail';
                $detail = QBOFacade::create('AccountBasedExpenseLineDetail');
                $detail->AccountRef = QBOFacade::create('ReferenceType');
                $detail->AccountRef->value = $accountId;
                if (!empty($item['description'])) {
                    $line->Description = $item['description'];
                }
                $line->AccountBasedExpenseLineDetail = $detail;
                $lines[] = $line;
            }

            $bill = QBOFacade::create('Bill');
            $bill->VendorRef = QBOFacade::create('ReferenceType');
            $bill->VendorRef->value = $vendorId;
            $bill->Line = $lines;
            $bill->TxnDate = $request->get('txn_date', now()->toDateString());
            if ($request->get('due_date')) $bill->DueDate = $request->get('due_date');
            if ($request->get('memo'))     $bill->PrivateNote = $request->get('memo');

            $created = $qb->create($bill);

            return Response::text(
                "Bill created.\nID: {$created->Id} | Total: {$created->TotalAmt} | Due: {$created->DueDate}"
            );

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
```

### Pattern 4 — Read tool (rich document, Invoice and Item only)

```php
<?php
namespace Raju\QuickBooksMcp\Tools\Invoice;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description(
    'Read a full invoice document including all line items, customer info, payment status, and amounts.'
)]
class ReadInvoiceTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id' => ['type' => 'string', 'description' => 'QuickBooks invoice ID'],
            ],
            'required' => ['id'],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $invoice = $qb->findById('Invoice', $request->get('id'));

            if (!$invoice) {
                return Response::text("Invoice ID {$request->get('id')} not found.");
            }

            $lines = collect($invoice->Line ?? [])->map(fn($line) => array_filter([
                'description' => $line->Description ?? null,
                'amount'      => $line->Amount ?? null,
                'qty'         => $line->SalesItemLineDetail?->Qty ?? null,
                'unit_price'  => $line->SalesItemLineDetail?->UnitPrice ?? null,
                'item_id'     => $line->SalesItemLineDetail?->ItemRef?->value ?? null,
                'item_name'   => $line->SalesItemLineDetail?->ItemRef?->name ?? null,
            ]))->values()->toArray();

            return Response::text(json_encode([
                'id'            => $invoice->Id,
                'doc_number'    => $invoice->DocNumber ?? null,
                'sync_token'    => $invoice->SyncToken,
                'customer_id'   => $invoice->CustomerRef->value ?? null,
                'customer_name' => $invoice->CustomerRef->name ?? null,
                'txn_date'      => $invoice->TxnDate ?? null,
                'due_date'      => $invoice->DueDate ?? null,
                'status'        => $invoice->EmailStatus ?? null,
                'balance'       => $invoice->Balance ?? 0,
                'total'         => $invoice->TotalAmt ?? 0,
                'line_items'    => $lines,
                'memo'          => $invoice->CustomerMemo->value ?? null,
                'email'         => $invoice->BillEmail->Address ?? null,
            ], JSON_PRETTY_PRINT));

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
```

### Pattern 5 — Update tool

```php
<?php
namespace Raju\QuickBooksMcp\Tools\Customer;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description('Update an existing QuickBooks customer. Only pass fields you want to change.')]
class UpdateCustomerTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id'           => ['type' => 'string',  'description' => 'QuickBooks customer ID (required)'],
                'display_name' => ['type' => 'string',  'description' => 'Display name'],
                'company_name' => ['type' => 'string',  'description' => 'Company name'],
                'email'        => ['type' => 'string',  'description' => 'Primary email address'],
                'phone'        => ['type' => 'string',  'description' => 'Primary phone number'],
                'active'       => ['type' => 'boolean', 'description' => 'Active status'],
            ],
            'required' => ['id'],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $customer = $qb->findById('Customer', $request->get('id'));

            if (!$customer) {
                return Response::text("Customer ID {$request->get('id')} not found.");
            }

            if ($request->has('display_name')) $customer->DisplayName = $request->get('display_name');
            if ($request->has('company_name')) $customer->CompanyName = $request->get('company_name');
            if ($request->has('active'))       $customer->Active = $request->get('active');

            if ($request->has('email')) {
                $customer->PrimaryEmailAddr ??= new \stdClass();
                $customer->PrimaryEmailAddr->Address = $request->get('email');
            }
            if ($request->has('phone')) {
                $customer->PrimaryPhone ??= new \stdClass();
                $customer->PrimaryPhone->FreeFormNumber = $request->get('phone');
            }

            $updated = $qb->update($customer);

            return Response::text(
                "Customer updated.\nID: {$updated->Id} | Name: {$updated->DisplayName}"
            );

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
```

### Pattern 6 — Delete tool

```php
<?php
namespace Raju\QuickBooksMcp\Tools\Customer;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description(
    'Deactivate a QuickBooks customer. ' .
    'QBO does not permanently delete customers — this sets Active = false.'
)]
class DeleteCustomerTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id' => ['type' => 'string', 'description' => 'QuickBooks customer ID'],
            ],
            'required' => ['id'],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $customer = $qb->findById('Customer', $request->get('id'));

            if (!$customer) {
                return Response::text("Customer ID {$request->get('id')} not found.");
            }

            $customer->Active = false;
            $qb->update($customer);

            return Response::text(
                "Customer {$customer->DisplayName} (ID: {$customer->Id}) has been deactivated."
            );

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
```

---

## Complete tool inventory

49 tools across 11 entities. Build each following the matching pattern above.

| Entity | Tools | Pattern |
|---|---|---|
| Account | create, search, update | P3, P1, P5 |
| Bill | create, get, search, update, delete | P3, P2, P1, P5, P6 |
| BillPayment | create, get, search, update, delete | P3, P2, P1, P5, P6 |
| Customer | create, get, search, update, delete | P3, P2, P1, P5, P6 |
| Employee | create, get, search, update | P3, P2, P1, P5 |
| Estimate | create, get, search, update, delete | P3, P2, P1, P5, P6 |
| Invoice | create, **read**, search, update | P3, **P4**, P1, P5 — NO delete |
| Item | create, **read**, search, update | P3, **P4**, P1, P5 — NO delete |
| JournalEntry | create, get, search, update, delete | P3, P2, P1, P5, P6 |
| Purchase | create, get, search, update, delete | P3, P2, P1, P5, P6 |
| Vendor | create, get, search, update, delete | P3, P2, P1, P5, P6 |

---

## Entity-specific field reference

### Customer
`DisplayName`, `CompanyName`, `GivenName`, `FamilyName`, `PrimaryEmailAddr.Address`, `PrimaryPhone.FreeFormNumber`, `BillAddr`, `Active`, `Balance`, `Id`, `SyncToken`

### Vendor
`DisplayName`, `CompanyName`, `PrimaryEmailAddr.Address`, `PrimaryPhone.FreeFormNumber`, `AcctNum`, `TaxIdentifier`, `Active`, `Balance`, `Id`, `SyncToken`

### Invoice
`CustomerRef.value`, `TxnDate`, `DueDate`, `Line[]` (SalesItemLineDetail), `BillEmail.Address`, `CustomerMemo.value`, `EmailStatus`, `TotalAmt`, `Balance`, `DocNumber`, `Id`, `SyncToken`

### Invoice line item
```json
{
  "Amount": 100.00,
  "DetailType": "SalesItemLineDetail",
  "SalesItemLineDetail": {
    "ItemRef": { "value": "ITEM_ID" },
    "Qty": 2,
    "UnitPrice": 50.00
  }
}
```

### Bill
`VendorRef.value`, `TxnDate`, `DueDate`, `Line[]` (AccountBasedExpenseLineDetail), `TotalAmt`, `Balance`, `PrivateNote`, `Id`, `SyncToken`

### Bill line item
```json
{
  "Amount": 250.00,
  "DetailType": "AccountBasedExpenseLineDetail",
  "AccountBasedExpenseLineDetail": {
    "AccountRef": { "value": "ACCOUNT_ID" }
  }
}
```

### Purchase
`PaymentType` (Cash/Check/CreditCard), `AccountRef.value`, `EntityRef.value` (vendor ID), `TxnDate`, `Line[]`, `PrivateNote`, `TotalAmt`, `Id`, `SyncToken`

### Estimate
`CustomerRef.value`, `TxnDate`, `ExpirationDate`, `Line[]` (SalesItemLineDetail), `TxnStatus` (Pending/Accepted/Closed/Rejected), `TotalAmt`, `Id`, `SyncToken`

### JournalEntry line item
```json
{
  "Amount": 500.00,
  "DetailType": "JournalEntryLineDetail",
  "JournalEntryLineDetail": {
    "PostingType": "Debit",
    "AccountRef": { "value": "ACCOUNT_ID" }
  }
}
```

### Item
`Name`, `Type` (Inventory/Service/NonInventory), `UnitPrice`, `IncomeAccountRef.value`, `ExpenseAccountRef.value`, `Active`, `Id`, `SyncToken`

### Account
`Name`, `AccountType`, `AccountSubType`, `AcctNum`, `Description`, `Active`, `Id`, `SyncToken`

### Employee
`GivenName`, `FamilyName`, `DisplayName`, `PrimaryEmailAddr.Address`, `PrimaryPhone.FreeFormNumber`, `Active`, `Id`, `SyncToken`

### BillPayment
`VendorRef.value`, `PayType` (Check/CreditCard), `TotalAmt`, `TxnDate`, `Line[]` (linked Bill IDs), `Id`, `SyncToken`

---

## QBO query syntax reference

```sql
SELECT * FROM Customer WHERE DisplayName LIKE '%Acme%' MAXRESULTS 20
SELECT * FROM Invoice WHERE EmailStatus = 'NeedToSend' AND Balance > '0' MAXRESULTS 50
SELECT * FROM Bill WHERE TxnDate >= '2024-01-01' AND TxnDate <= '2024-12-31'
SELECT Id FROM Vendor WHERE DisplayName = 'Office Depot'
SELECT * FROM Invoice ORDER BY TxnDate DESC MAXRESULTS 10
SELECT * FROM Purchase WHERE PaymentType = 'CreditCard' AND TxnDate >= '2024-01-01'
```

All field names are case-sensitive. String values are single-quoted. Date format is `YYYY-MM-DD`.

---

## .env variables

```dotenv
# Intuit app credentials (from developer.intuit.com — shared across all tenants)
QUICKBOOKS_CLIENT_ID=your_intuit_app_client_id
QUICKBOOKS_CLIENT_SECRET=your_intuit_app_client_secret
QUICKBOOKS_REDIRECT_URI=https://yourdomain.com/quickbooks/callback
QUICKBOOKS_SCOPE=com.intuit.quickbooks.accounting
QUICKBOOKS_DATA_SOURCE=production   # or: development (sandbox)

# MCP server settings
QBO_MCP_PATH=mcp/quickbooks
QBO_TOKEN_REFRESH_BUFFER=5

# Set this to match the auth guard your Laravel app uses for Bearer tokens:
#   'api'     — if your app uses Laravel Passport
#   'sanctum' — if your app uses Laravel Sanctum
QBO_MCP_GUARD=api

# No per-user tokens in .env — those live in the database
```

---

## Full OAuth user journey

```
1. User logs into your SaaS platform (Laravel auth)

2. User visits GET /quickbooks/connect
   → Redirected to Intuit consent screen
   → Grants access to their QBO company
   → Redirected back to GET /quickbooks/callback

3. Callback handler:
   → Exchanges auth code for access_token + refresh_token
   → spinen stores tokens in quickbooks_tokens (keyed to user_id)
   → Package creates quickbooks_connections record (realm_id + company_name)

4. User connects the MCP server in your SaaS UI using your existing
   Passport Authorization Code flow — the same flow already used for
   your other connected tools in Claude.
   → User gets a Passport Bearer token (oauth_access_tokens)
   → No separate MCP token step needed

5. AI agent (Claude) is configured with:
   MCP URL:  https://yourdomain.com/mcp/quickbooks
   Header:   Authorization: Bearer <passport-access-token>

6. Every MCP tool call:
   → Passport authenticates the Bearer token (auth:api guard)
   → ResolveQuickBooksRealm looks up the user's active quickbooks_connections row
   → RefreshQuickBooksToken silently refreshes QBO tokens if near expiry
   → QuickBooksService is bound to the correct QBO company
   → Tool runs — zero additional params needed from the agent

7. Disconnect QBO:
   DELETE /quickbooks/disconnect  Body: { "realm_id": "123456789" }
   (Passport token management is handled by your existing app — not this package)
```

---

## Installation instructions

```bash
# 1. Install via Composer
composer require rajurayhan/laravel-quickbooks-mcp-server

# 2. Publish all package assets
php artisan vendor:publish --tag=quickbooks-mcp-config     # → config/quickbooks-mcp.php
php artisan vendor:publish --tag=quickbooks-mcp-migrations # → database/migrations/
php artisan vendor:publish --tag=quickbooks-mcp-routes     # → routes/quickbooks-mcp.php

# Or publish everything at once:
php artisan vendor:publish --provider="Raju\QuickBooksMcp\QuickBooksMcpServiceProvider"

# 3. Run migrations
php artisan migrate

# 4. Configure .env
#    Set your Intuit app credentials and QBO_MCP_GUARD to match your auth driver.

# 5. Register the published routes in your application.
#    Option A — in app/Providers/AppServiceProvider.php:
#
#      public function boot(): void
#      {
#          Route::middleware('web')
#              ->group(base_path('routes/quickbooks-mcp.php'));
#      }
#
#    Option B — at the bottom of routes/web.php or routes/api.php:
#
#      require base_path('routes/quickbooks-mcp.php');

# 6. Edit routes/quickbooks-mcp.php:
#    - Change 'auth:api' to your app's guard ('auth:sanctum', etc.)
#    - Adjust route prefix and middleware to match your conventions

# 7. Register the Intuit OAuth callback URI at developer.intuit.com.
#    It must match the /quickbooks/callback route exactly as defined
#    in your published routes file.

# 8. Add HasQuickBooksToken trait to your User model:
#    use Spinen\QuickBooks\HasQuickBooksToken;

# 9. Connect the MCP URL in your AI client:
#    MCP URL: https://yourdomain.com/mcp/quickbooks  (or your configured path)
```

---

## Cursor instructions

When building this package follow these rules precisely:

1. **All PHP namespaces are `Raju\QuickBooksMcp\...`** — never `RajuRayhan\`, never `Sulus\`.
2. The `composer.json` package name is `rajurayhan/laravel-quickbooks-mcp-server` and the PSR-4 autoload root is `"Raju\\QuickBooksMcp\\": "src/"`.
3. Every file must include the copyright header block matching the style in `ExchangeMailServiceProvider.php` from the author's existing packages.
4. Every tool class lives in `src/Tools/{Entity}/` and extends `Laravel\Mcp\Server\Tool`.
5. Every tool has exactly two public methods: `schema()` returning a JSON Schema array, and `handle(Request $request, QuickBooksService $qb): Response`.
6. Use the `#[Description('...')]` attribute on the class. Make descriptions specific and action-oriented — this is what the AI reads when choosing a tool.
7. Search tools always accept `query`, `limit` (default 20, max 100), and optionally entity-specific filters.
8. Get/Read tools accept only `id` as the required field.
9. Create/Update tools that reference a vendor, customer, account, or item use the `ResolvesEntityNames` trait.
10. Update tools must call `findById()` first to obtain the current `SyncToken` before calling `update()`. QBO rejects updates without a valid SyncToken.
11. Delete behaviour by entity type:
    - **Soft-delete** (set `Active = false`, then `update()`): Customer, Vendor, Employee, Item, Account
    - **Hard-delete** (call `$qb->delete($entity)`): Bill, BillPayment, Estimate, JournalEntry, Purchase
12. There is NO `DeleteInvoiceTool` and NO `DeleteItemTool`. Do not create them.
13. Invoice and Item use `ReadXxxTool` (Pattern 4, returns full document with line items). All other entities use `GetXxxTool` (Pattern 2, returns flat record).
14. Every `handle()` method wraps all logic in a try/catch and returns `Response::text("Error: " . $e->getMessage())` on failure — never let exceptions bubble up.
15. Routes are **never auto-loaded** by the service provider. `loadRoutesFrom()` must NOT be called anywhere in the package. Routes are published as a stub to `routes/quickbooks-mcp.php` and the developer registers them manually.
16. The published route stub must include clear comments explaining: (a) how to register it in `AppServiceProvider` or a routes file, (b) which line to change for the auth guard, and (c) that the callback URI must match developer.intuit.com exactly.
17. The route file is published under the tag `quickbooks-mcp-routes`. Config under `quickbooks-mcp-config`. Migrations under `quickbooks-mcp-migrations`. All three tags must also be covered by the provider-level publish (no tag) so `vendor:publish --provider=...` publishes everything at once.
18. The MCP route guard is left as `auth:api` in the stub as a sensible default with a comment. It is NOT read from config in the stub — the developer changes it directly in the published file. The `auth_guard` config key is removed.
19. The package does NOT hardcode any route prefix, middleware group, or guard. All of those live in the published stub that the developer owns.
17. There is NO `QuickBooksMcpTokenController` and no `POST /quickbooks/mcp-token` route. Token issuance is handled entirely by the host application's existing auth setup.
18. The `ResolveQuickBooksRealm` middleware resolves realm by looking up `QuickBooksConnection` for `auth()->user()->id`. It does NOT read realm from token metadata. A user has exactly one active QBO connection.
19. The `RefreshQuickBooksToken` middleware handles QBO token refresh. Tool classes never call refresh logic directly.
20. Never store QBO `access_token` or `refresh_token` in `.env`. They live in the `quickbooks_tokens` table managed by `spinen/laravel-quickbooks-client`.
21. `QuickBooksOAuthController::callback()` calls `$this->qb->parseRedirectURL()` from the spinen client — do not re-implement token exchange manually.
22. The only migration this package ships is `create_quickbooks_connections_table`. Do not create or modify any Passport, Sanctum, or other auth token tables.
23. Tool classes never accept or handle a `realm_id` parameter — realm resolution is fully transparent via middleware.