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
