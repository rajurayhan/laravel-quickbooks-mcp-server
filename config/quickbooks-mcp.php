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
