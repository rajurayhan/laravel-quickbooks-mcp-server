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

#[Description('Update an existing QuickBooks vendor. Only pass fields you want to change.')]
class UpdateVendorTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id'             => ['type' => 'string',  'description' => 'QuickBooks vendor ID (required)'],
                'display_name'   => ['type' => 'string',  'description' => 'Display name'],
                'company_name'   => ['type' => 'string',  'description' => 'Company name'],
                'email'          => ['type' => 'string',  'description' => 'Primary email address'],
                'phone'          => ['type' => 'string',  'description' => 'Primary phone number'],
                'acct_num'       => ['type' => 'string',  'description' => 'Account number'],
                'tax_identifier' => ['type' => 'string',  'description' => 'Tax identifier / EIN'],
                'active'         => ['type' => 'boolean', 'description' => 'Active status'],
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

            if ($request->has('display_name'))   $vendor->DisplayName   = $request->get('display_name');
            if ($request->has('company_name'))   $vendor->CompanyName   = $request->get('company_name');
            if ($request->has('acct_num'))        $vendor->AcctNum       = $request->get('acct_num');
            if ($request->has('tax_identifier')) $vendor->TaxIdentifier = $request->get('tax_identifier');
            if ($request->has('active'))         $vendor->Active        = $request->get('active');

            if ($request->has('email')) {
                $vendor->PrimaryEmailAddr          ??= new \stdClass();
                $vendor->PrimaryEmailAddr->Address = $request->get('email');
            }
            if ($request->has('phone')) {
                $vendor->PrimaryPhone                 ??= new \stdClass();
                $vendor->PrimaryPhone->FreeFormNumber = $request->get('phone');
            }

            $updated = $qb->update($vendor);

            return Response::text(
                "Vendor updated.\nID: {$updated->Id} | Name: {$updated->DisplayName}"
            );

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
