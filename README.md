# Laravel QuickBooks MCP

A first-party PHP/Laravel Composer package that exposes **QuickBooks Online (QBO)** as a **Model Context Protocol (MCP) server**. AI clients — Claude, Cursor, n8n, and others — connect over HTTP and perform full CRUD operations on QBO entities using natural language.

This is the first PHP/Laravel QuickBooks MCP implementation in the ecosystem.

---

## Features

- **50 MCP tools** covering 11 QBO entities (customers, vendors, invoices, bills, estimates, purchases, employees, items, accounts, journal entries, and bill payments)
- **Remote HTTP transport** — not local stdio, so it works with any hosted AI client
- **Multi-tenant** — multiple QBO companies per Laravel installation
- **Name-to-ID resolution** — agents pass human-readable names; ID lookups happen automatically
- **Full OAuth 2.0 flow** — any SaaS user can connect their own QBO company
- **Production-ready** from day one (sandbox also supported)
- **Laravel-native auth** — works with Passport or Sanctum, no assumptions made

---

## Requirements

| Requirement | Version |
|---|---|
| PHP | >= 8.2 |
| Laravel | >= 11.x |
| `laravel/mcp` | ^1.0 |
| `spinen/laravel-quickbooks-client` | ^4.0 |

Your host application must also have:
- A `users` table with an `id` column (standard Laravel)
- The `HasQuickBooksToken` trait from `spinen/laravel-quickbooks-client` on the `User` model
- At least one configured auth guard that resolves `auth()->user()` from a Bearer token (Passport or Sanctum)

---

## Installation

### 1. Install via Composer

```bash
composer require rajurayhan/laravel-quickbooks-mcp-server
```

### 2. Publish package assets

```bash
# Publish config
php artisan vendor:publish --tag=quickbooks-mcp-config

# Publish migrations
php artisan vendor:publish --tag=quickbooks-mcp-migrations

# Publish routes stub
php artisan vendor:publish --tag=quickbooks-mcp-routes

# Or publish everything at once
php artisan vendor:publish --provider="Raju\QuickBooksMcp\QuickBooksMcpServiceProvider"
```

### 3. Run migrations

```bash
php artisan migrate
```

This creates one table: `quickbooks_connections`.

### 4. Configure your `.env`

```dotenv
# Intuit app credentials (from developer.intuit.com)
QUICKBOOKS_CLIENT_ID=your_intuit_app_client_id
QUICKBOOKS_CLIENT_SECRET=your_intuit_app_client_secret
QUICKBOOKS_REDIRECT_URI=https://yourdomain.com/quickbooks/callback
QUICKBOOKS_SCOPE=com.intuit.quickbooks.accounting
QUICKBOOKS_DATA_SOURCE=production   # or: development (sandbox)

# MCP server settings
QBO_MCP_PATH=mcp/quickbooks
QBO_TOKEN_REFRESH_BUFFER=5
```

### 5. Register the published routes

Open `routes/quickbooks-mcp.php` (published to your app's `routes/` folder) and register it in your application. Choose one of:

**Option A — in `app/Providers/AppServiceProvider.php`:**

```php
public function boot(): void
{
    Route::middleware('web')
        ->group(base_path('routes/quickbooks-mcp.php'));
}
```

**Option B — at the bottom of `routes/web.php` or `routes/api.php`:**

```php
require base_path('routes/quickbooks-mcp.php');
```

### 6. Set your auth guard

Inside `routes/quickbooks-mcp.php`, change `auth:api` to match your application's Bearer token guard:

```php
// Change 'api' to 'sanctum' if your app uses Laravel Sanctum
Route::middleware([
    'api',
    'auth:api',   // ← change this line if needed
    ResolveQuickBooksRealm::class,
    RefreshQuickBooksToken::class,
])->group(function () {
    Mcp::server(QuickBooksServer::class)->at(config('quickbooks-mcp.path'));
});
```

### 7. Register your Intuit OAuth callback URI

In your [Intuit Developer app settings](https://developer.intuit.com), add this redirect URI:

```
https://yourdomain.com/quickbooks/callback
```

It must match the `/quickbooks/callback` route exactly.

### 8. Add `HasQuickBooksToken` to your User model

```php
use Spinen\QuickBooks\HasQuickBooksToken;

class User extends Authenticatable
{
    use HasQuickBooksToken;
    // ...
}
```

---

## OAuth Flow

Once installed, a SaaS user connects their QBO company through your application:

```
1. User visits GET /quickbooks/connect
   → Redirected to Intuit consent screen
   → Grants access to their QBO company
   → Redirected back to GET /quickbooks/callback

2. Callback handler:
   → Exchanges auth code for tokens (stored by spinen in quickbooks_tokens)
   → Package records connection in quickbooks_connections (realm_id + company_name)

3. User authenticates your app with their existing Bearer token (Passport or Sanctum)

4. AI client is configured with:
   MCP URL:  https://yourdomain.com/mcp/quickbooks
   Header:   Authorization: Bearer <bearer-token>

5. Every MCP tool call thereafter:
   → Guard authenticates the Bearer token
   → ResolveQuickBooksRealm finds the user's active QBO connection
   → RefreshQuickBooksToken silently refreshes QBO tokens near expiry
   → Tool runs — zero extra params needed from the AI agent
```

### Connection management routes

| Method | Route | Description |
|---|---|---|
| `GET` | `/quickbooks/connect` | Redirect to Intuit OAuth consent screen |
| `GET` | `/quickbooks/callback` | Handle OAuth callback and store tokens |
| `DELETE` | `/quickbooks/disconnect` | Revoke QBO tokens and remove connection |
| `GET` | `/quickbooks/connections` | List active QBO connections for the user |

---

## Configuration

`config/quickbooks-mcp.php`:

| Key | Default | Description |
|---|---|---|
| `path` | `mcp/quickbooks` | URL path where the MCP server is exposed |
| `multi_tenant` | `true` | Resolve realm from authenticated user |
| `search_limit` | `20` | Default result limit for search tools |
| `search_limit_max` | `100` | Maximum result limit for search tools |
| `environment` | `production` | QBO environment (`production` or `development`) |
| `redirect_uri` | `APP_URL/quickbooks/callback` | OAuth redirect URI |
| `token_refresh_buffer_minutes` | `5` | Minutes before expiry to proactively refresh |

---

## Available Tools

### Account (3 tools)

| Tool | Description |
|---|---|
| `create_account` | Create a new account in the chart of accounts |
| `search_accounts` | Search accounts by name or type |
| `update_account` | Update an existing account |

### Bill (5 tools)

| Tool | Description |
|---|---|
| `create_bill` | Create a new accounts payable bill |
| `get_bill` | Get a bill by ID |
| `search_bills` | Search bills by vendor, date range, or unpaid status |
| `update_bill` | Update an existing bill |
| `delete_bill` | Permanently delete a bill |

### Bill Payment (5 tools)

| Tool | Description |
|---|---|
| `create_bill_payment` | Pay one or more open bills |
| `get_bill_payment` | Get a bill payment by ID |
| `search_bill_payments` | Search bill payments by vendor or date range |
| `update_bill_payment` | Update an existing bill payment |
| `delete_bill_payment` | Permanently delete a bill payment |

### Customer (5 tools)

| Tool | Description |
|---|---|
| `create_customer` | Create a new customer |
| `get_customer` | Get a customer by ID |
| `search_customers` | Search customers by name, email, or company |
| `update_customer` | Update an existing customer |
| `delete_customer` | Deactivate a customer (QBO soft-delete) |

### Employee (4 tools)

| Tool | Description |
|---|---|
| `create_employee` | Create a new employee record |
| `get_employee` | Get an employee by ID |
| `search_employees` | Search employees by name or email |
| `update_employee` | Update an existing employee |

### Estimate (5 tools)

| Tool | Description |
|---|---|
| `create_estimate` | Create a new estimate / quote |
| `get_estimate` | Get an estimate by ID |
| `search_estimates` | Search estimates by customer, status, or date range |
| `update_estimate` | Update or change the status of an estimate |
| `delete_estimate` | Permanently delete an estimate |

### Invoice (4 tools)

| Tool | Description |
|---|---|
| `create_invoice` | Create a new invoice |
| `read_invoice` | Read a full invoice with all line items |
| `search_invoices` | Search invoices by customer, date range, or payment status |
| `update_invoice` | Update an existing invoice |

> Invoices cannot be permanently deleted in QBO.

### Item (4 tools)

| Tool | Description |
|---|---|
| `create_item` | Create a new product or service item |
| `read_item` | Read a full item record with pricing and account assignments |
| `search_items` | Search items by name or type |
| `update_item` | Update an existing item (set `active: false` to deactivate) |

> Items cannot be permanently deleted in QBO.

### Journal Entry (5 tools)

| Tool | Description |
|---|---|
| `create_journal_entry` | Create a journal entry (debits must equal credits) |
| `get_journal_entry` | Get a journal entry by ID |
| `search_journal_entries` | Search journal entries by date range or document number |
| `update_journal_entry` | Update an existing journal entry |
| `delete_journal_entry` | Permanently delete a journal entry |

### Purchase (5 tools)

| Tool | Description |
|---|---|
| `create_purchase` | Create a purchase / expense transaction |
| `get_purchase` | Get a purchase by ID |
| `search_purchases` | Search purchases by payment type or date range |
| `update_purchase` | Update an existing purchase |
| `delete_purchase` | Permanently delete a purchase |

### Vendor (5 tools)

| Tool | Description |
|---|---|
| `create_vendor` | Create a new vendor |
| `get_vendor` | Get a vendor by ID |
| `search_vendors` | Search vendors by name, email, or company |
| `update_vendor` | Update an existing vendor |
| `delete_vendor` | Deactivate a vendor (QBO soft-delete) |

---

## Name Resolution

Tools that reference related entities (vendor, customer, account, item) accept either a **name** or a **numeric ID**. The package resolves names to IDs automatically before calling the QBO API.

```
# These are equivalent when calling create_bill:
vendor: "Office Depot"
vendor: "42"
```

If a name cannot be found, the tool returns a descriptive error with a suggestion to use the corresponding search tool first.

---

## Delete Behaviour

QBO has two categories of delete:

| Behaviour | Entities |
|---|---|
| **Soft-delete** — sets `Active = false` | Customer, Vendor, Employee, Item, Account |
| **Hard-delete** — permanently removed | Bill, BillPayment, Estimate, JournalEntry, Purchase |
| **Cannot be deleted** | Invoice, Item (use `update_item` with `active: false`) |

---

## Multi-Tenant Architecture

Each authenticated user has exactly one active QBO connection tracked in the `quickbooks_connections` table. The `ResolveQuickBooksRealm` middleware automatically scopes every tool call to the correct QBO company — no `realm_id` parameter is ever needed from the AI agent.

---

## Package Architecture

```
src/
├── QuickBooksMcpServiceProvider.php   — registers service, publishes assets
├── Server/QuickBooksServer.php        — MCP server, registers all 50 tools
├── Services/QuickBooksService.php     — QBO API wrapper, name resolvers
├── Concerns/ResolvesEntityNames.php   — trait for name-to-ID resolution
├── Http/
│   ├── Controllers/QuickBooksOAuthController.php
│   └── Middleware/
│       ├── ResolveQuickBooksRealm.php — scopes QBO service to user's company
│       └── RefreshQuickBooksToken.php — proactive token refresh
├── Models/QuickBooksConnection.php    — tracks user ↔ QBO company links
├── Exceptions/
│   ├── QuickBooksAuthException.php
│   └── QuickBooksToolException.php
└── Tools/                             — 50 tool classes across 11 entities
    ├── Account/, Bill/, BillPayment/, Customer/, Employee/
    ├── Estimate/, Invoice/, Item/, JournalEntry/
    ├── Purchase/, Vendor/
```

---

## License

MIT — see [LICENSE](LICENSE) for details.

---

## Author

**Raju Rayhan** — [github.com/rajurayhan](https://github.com/rajurayhan)
