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
use QuickBooksOnline\API\Facades\QBOFacade;
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description('Create a new customer in QuickBooks.')]
class CreateCustomerTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'display_name' => ['type' => 'string', 'description' => 'Display name (must be unique)'],
                'company_name' => ['type' => 'string', 'description' => 'Company name (optional)'],
                'first_name'   => ['type' => 'string', 'description' => 'First name (optional)'],
                'last_name'    => ['type' => 'string', 'description' => 'Last name (optional)'],
                'email'        => ['type' => 'string', 'description' => 'Primary email address (optional)'],
                'phone'        => ['type' => 'string', 'description' => 'Primary phone number (optional)'],
            ],
            'required' => ['display_name'],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $customer = QBOFacade::create('Customer');
            $customer->DisplayName = $request->get('display_name');

            if ($request->get('company_name')) $customer->CompanyName = $request->get('company_name');
            if ($request->get('first_name'))   $customer->GivenName   = $request->get('first_name');
            if ($request->get('last_name'))    $customer->FamilyName  = $request->get('last_name');

            if ($request->get('email')) {
                $customer->PrimaryEmailAddr          = new \stdClass();
                $customer->PrimaryEmailAddr->Address = $request->get('email');
            }
            if ($request->get('phone')) {
                $customer->PrimaryPhone                    = new \stdClass();
                $customer->PrimaryPhone->FreeFormNumber    = $request->get('phone');
            }

            $created = $qb->create($customer);

            return Response::text(
                "Customer created.\nID: {$created->Id} | Name: {$created->DisplayName}"
            );

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
