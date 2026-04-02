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
use QuickBooksOnline\API\Facades\QBOFacade;
use Raju\QuickBooksMcp\Concerns\ResolvesEntityNames;
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description(
    'Create a new product or service item in QuickBooks. ' .
    'Specify type as Inventory, Service, or NonInventory.'
)]
class CreateItemTool extends Tool
{
    use ResolvesEntityNames;

    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'name'             => ['type' => 'string', 'description' => 'Item name (must be unique)'],
                'type'             => ['type' => 'string', 'description' => 'Item type: Inventory, Service, or NonInventory'],
                'unit_price'       => ['type' => 'number', 'description' => 'Sales price / unit price'],
                'income_account'   => ['type' => 'string', 'description' => 'Income account name or ID'],
                'expense_account'  => ['type' => 'string', 'description' => 'Expense account name or ID (optional, required for Inventory)'],
                'description'      => ['type' => 'string', 'description' => 'Item description (optional)'],
            ],
            'required' => ['name', 'type', 'income_account'],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $incomeAccountId = $this->resolveAccount($request->get('income_account'), $qb);

            $item = QBOFacade::create('Item');
            $item->Name       = $request->get('name');
            $item->Type       = $request->get('type');
            $item->UnitPrice  = $request->get('unit_price', 0);

            $item->IncomeAccountRef        = QBOFacade::create('ReferenceType');
            $item->IncomeAccountRef->value = $incomeAccountId;

            if ($request->get('expense_account')) {
                $expenseAccountId                   = $this->resolveAccount($request->get('expense_account'), $qb);
                $item->ExpenseAccountRef             = QBOFacade::create('ReferenceType');
                $item->ExpenseAccountRef->value      = $expenseAccountId;
            }
            if ($request->get('description')) {
                $item->Description = $request->get('description');
            }

            $created = $qb->create($item);

            return Response::text(
                "Item created.\nID: {$created->Id} | Name: {$created->Name} | Type: {$created->Type} | Price: {$created->UnitPrice}"
            );

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
