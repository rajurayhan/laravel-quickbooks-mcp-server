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
    'Read a full item (product or service) record from QuickBooks including pricing and account assignments.'
)]
class ReadItemTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id' => ['type' => 'string', 'description' => 'QuickBooks item ID'],
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

            return Response::text(json_encode([
                'id'              => $item->Id,
                'name'            => $item->Name,
                'type'            => $item->Type ?? null,
                'description'     => $item->Description ?? null,
                'unit_price'      => $item->UnitPrice ?? 0,
                'income_account'  => [
                    'id'   => $item->IncomeAccountRef->value ?? null,
                    'name' => $item->IncomeAccountRef->name ?? null,
                ],
                'expense_account' => [
                    'id'   => $item->ExpenseAccountRef->value ?? null,
                    'name' => $item->ExpenseAccountRef->name ?? null,
                ],
                'active'          => $item->Active ?? true,
                'sync_token'      => $item->SyncToken,
            ], JSON_PRETTY_PRINT));

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
