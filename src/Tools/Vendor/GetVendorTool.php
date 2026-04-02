<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2026 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Raju\QuickBooksMcp\Tools\Vendor;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description('Get a single QuickBooks vendor by their ID.')]
class GetVendorTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id' => ['type' => 'string', 'description' => 'QuickBooks vendor ID'],
            ],
            'required' => ['id'],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $vendor = $qb->findById('Vendor', $request->get('id'));

            if (!$vendor) {
                return Response::text("Vendor ID {$request->get('id')} not found.");
            }

            return Response::text(json_encode([
                'id'             => $vendor->Id,
                'name'           => $vendor->DisplayName,
                'company'        => $vendor->CompanyName ?? null,
                'email'          => $vendor->PrimaryEmailAddr->Address ?? null,
                'phone'          => $vendor->PrimaryPhone->FreeFormNumber ?? null,
                'acct_num'       => $vendor->AcctNum ?? null,
                'tax_identifier' => $vendor->TaxIdentifier ?? null,
                'balance'        => $vendor->Balance ?? 0,
                'active'         => $vendor->Active,
                'sync_token'     => $vendor->SyncToken,
            ], JSON_PRETTY_PRINT));

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
