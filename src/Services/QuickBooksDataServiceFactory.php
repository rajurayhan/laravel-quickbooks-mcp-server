<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2026 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Raju\QuickBooksMcp\Services;

use Carbon\Carbon;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2AccessToken;
use QuickBooksOnline\API\DataService\DataService;
use Raju\QuickBooksMcp\Models\QuickBooksConnection;

class QuickBooksDataServiceFactory
{
    /**
     * Base OAuth2 config shared by all DataService instances.
     */
    protected function baseConfig(): array
    {
        return [
            'auth_mode'    => 'oauth2',
            'ClientID'     => config('quickbooks-mcp.client_id'),
            'ClientSecret' => config('quickbooks-mcp.client_secret'),
            'RedirectURI'  => config('quickbooks-mcp.redirect_uri'),
            'scope'        => 'com.intuit.quickbooks.accounting',
            'baseUrl'      => config('quickbooks-mcp.environment') === 'production'
                ? 'Production'
                : 'Development',
        ];
    }

    /**
     * DataService instance for the initial OAuth flow (no realm or tokens yet).
     */
    public function makeForAuth(): DataService
    {
        return DataService::Configure($this->baseConfig());
    }

    /**
     * DataService instance pre-loaded with a connection's stored tokens.
     */
    public function makeFromConnection(QuickBooksConnection $connection): DataService
    {
        return DataService::Configure(array_merge($this->baseConfig(), [
            'QBORealmID'      => $connection->realm_id,
            'accessTokenKey'  => $connection->access_token,
            'refreshTokenKey' => $connection->refresh_token,
        ]));
    }

    /**
     * Persist a fresh OAuth2 token set back to the connection record.
     */
    public function persistToken(QuickBooksConnection $connection, OAuth2AccessToken $token): void
    {
        $connection->update([
            'access_token'              => $token->getAccessToken(),
            'refresh_token'             => $token->getRefreshToken(),
            'access_token_expires_at'   => $this->parseExpiry($token->getAccessTokenExpiresAt(), 3600),
            'refresh_token_expires_at'  => $this->parseExpiry($token->getRefreshTokenExpiresAt(), 8726400),
        ]);
    }

    /**
     * Parse an expiry value from the SDK, falling back to $fallbackSeconds from now.
     * The SDK may return a Unix timestamp int, a datetime string, or null.
     */
    protected function parseExpiry(mixed $value, int $fallbackSeconds): Carbon
    {
        if (!$value) {
            return now()->addSeconds($fallbackSeconds);
        }

        try {
            return is_numeric($value)
                ? Carbon::createFromTimestamp((int) $value)
                : Carbon::parse($value);
        } catch (\Exception) {
            return now()->addSeconds($fallbackSeconds);
        }
    }
}
