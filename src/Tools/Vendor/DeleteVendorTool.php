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

#[Description(
    'Deactivate a QuickBooks vendor. ' .
    'QBO does not permanently delete vendors — this sets Active = false.'
)]
class DeleteVendorTool extends Tool
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

            $vendor->Active = false;
            $qb->update($vendor);

            return Response::text(
                "Vendor {$vendor->DisplayName} (ID: {$vendor->Id}) has been deactivated."
            );

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
