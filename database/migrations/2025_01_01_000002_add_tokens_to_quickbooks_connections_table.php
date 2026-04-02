<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quickbooks_connections', function (Blueprint $table) {
            $table->text('access_token')->nullable()->after('last_used_at');
            $table->text('refresh_token')->nullable()->after('access_token');
            $table->timestamp('access_token_expires_at')->nullable()->after('refresh_token');
            $table->timestamp('refresh_token_expires_at')->nullable()->after('access_token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('quickbooks_connections', function (Blueprint $table) {
            $table->dropColumn([
                'access_token',
                'refresh_token',
                'access_token_expires_at',
                'refresh_token_expires_at',
            ]);
        });
    }
};
