<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2026 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Raju\QuickBooksMcp\Tools\Item;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description(
    'Update an existing QuickBooks item (product or service). ' .
    'QBO does not permanently delete items — use active = false to deactivate. ' .
    'Only pass fields you want to change.'
)]
class UpdateItemTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id'          => ['type' => 'string',  'description' => 'QuickBooks item ID (required)'],
                'name'        => ['type' => 'string',  'description' => 'Item name'],
                'description' => ['type' => 'string',  'description' => 'Item description'],
                'unit_price'  => ['type' => 'number',  'description' => 'Sales price / unit price'],
                'active'      => ['type' => 'boolean', 'description' => 'Active status'],
            ],
            'required' => ['id'],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $item = $qb->findById('Item', $request->get('id'));

            if (!$item) {
                return Response::text("Item ID {$request->get('id')} not found.");
            }

            if ($request->has('name'))        $item->Name        = $request->get('name');
            if ($request->has('description')) $item->Description = $request->get('description');
            if ($request->has('unit_price'))  $item->UnitPrice   = $request->get('unit_price');
            if ($request->has('active'))      $item->Active      = $request->get('active');

            $updated = $qb->update($item);

            return Response::text(
                "Item updated.\nID: {$updated->Id} | Name: {$updated->Name} | Price: {$updated->UnitPrice}"
            );

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
