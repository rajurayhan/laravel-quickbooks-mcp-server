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
use QuickBooksOnline\API\Facades\QBOFacade;
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description('Create a new vendor in QuickBooks.')]
class CreateVendorTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'display_name'   => ['type' => 'string', 'description' => 'Vendor display name (must be unique)'],
                'company_name'   => ['type' => 'string', 'description' => 'Company name (optional)'],
                'email'          => ['type' => 'string', 'description' => 'Primary email address (optional)'],
                'phone'          => ['type' => 'string', 'description' => 'Primary phone number (optional)'],
                'acct_num'       => ['type' => 'string', 'description' => 'Account number (optional)'],
                'tax_identifier' => ['type' => 'string', 'description' => 'Tax identifier / EIN (optional)'],
            ],
            'required' => ['display_name'],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $vendor = QBOFacade::create('Vendor');
            $vendor->DisplayName = $request->get('display_name');

            if ($request->get('company_name'))   $vendor->CompanyName    = $request->get('company_name');
            if ($request->get('acct_num'))        $vendor->AcctNum        = $request->get('acct_num');
            if ($request->get('tax_identifier')) $vendor->TaxIdentifier  = $request->get('tax_identifier');

            if ($request->get('email')) {
                $vendor->PrimaryEmailAddr          = new \stdClass();
                $vendor->PrimaryEmailAddr->Address = $request->get('email');
            }
            if ($request->get('phone')) {
                $vendor->PrimaryPhone                 = new \stdClass();
                $vendor->PrimaryPhone->FreeFormNumber = $request->get('phone');
            }

            $created = $qb->create($vendor);

            return Response::text(
                "Vendor created.\nID: {$created->Id} | Name: {$created->DisplayName}"
            );

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
