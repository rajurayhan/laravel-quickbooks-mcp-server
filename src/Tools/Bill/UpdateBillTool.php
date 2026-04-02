<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2026 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Raju\QuickBooksMcp\Tools\Bill;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description('Update an existing QuickBooks bill. Only pass fields you want to change.')]
class UpdateBillTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id'       => ['type' => 'string', 'description' => 'QuickBooks bill ID (required)'],
                'txn_date' => ['type' => 'string', 'description' => 'Transaction date (YYYY-MM-DD)'],
                'due_date' => ['type' => 'string', 'description' => 'Due date (YYYY-MM-DD)'],
                'memo'     => ['type' => 'string', 'description' => 'Private memo'],
            ],
            'required' => ['id'],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $bill = $qb->findById('Bill', $request->get('id'));

            if (!$bill) {
                return Response::text("Bill ID {$request->get('id')} not found.");
            }

            if ($request->has('txn_date')) $bill->TxnDate     = $request->get('txn_date');
            if ($request->has('due_date')) $bill->DueDate     = $request->get('due_date');
            if ($request->has('memo'))     $bill->PrivateNote = $request->get('memo');

            $updated = $qb->update($bill);

            return Response::text(
                "Bill updated.\nID: {$updated->Id} | Total: {$updated->TotalAmt} | Due: {$updated->DueDate}"
            );

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
