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
            if ($request->has('active'))       $customer->Active      = $request->get('active');

            if ($request->has('email')) {
                $customer->PrimaryEmailAddr          ??= new \stdClass();
                $customer->PrimaryEmailAddr->Address = $request->get('email');
            }
            if ($request->has('phone')) {
                $customer->PrimaryPhone                 ??= new \stdClass();
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
