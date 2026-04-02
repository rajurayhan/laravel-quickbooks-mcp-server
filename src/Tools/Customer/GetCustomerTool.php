<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2026 Raju Rayhan
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
