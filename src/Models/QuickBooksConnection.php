<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2026 Raju Rayhan
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
        'user_id', 'realm_id', 'company_name', 'active',
        'connected_at', 'last_used_at',
        'access_token', 'refresh_token',
        'access_token_expires_at', 'refresh_token_expires_at',
    ];

    protected $casts = [
        'active'                   => 'boolean',
        'connected_at'             => 'datetime',
        'last_used_at'             => 'datetime',
        'access_token_expires_at'  => 'datetime',
        'refresh_token_expires_at' => 'datetime',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function isAccessTokenExpiringSoon(int $bufferMinutes = 5): bool
    {
        return now()->addMinutes($bufferMinutes)->isAfter(
            $this->access_token_expires_at ?? now()
        );
    }
}
