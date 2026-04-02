<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2026 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Raju\QuickBooksMcp\Tools\Purchase;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description('Update an existing QuickBooks purchase transaction. Only pass fields you want to change.')]
class UpdatePurchaseTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id'       => ['type' => 'string', 'description' => 'QuickBooks purchase ID (required)'],
                'txn_date' => ['type' => 'string', 'description' => 'Transaction date (YYYY-MM-DD)'],
                'memo'     => ['type' => 'string', 'description' => 'Private memo'],
            ],
            'required' => ['id'],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $purchase = $qb->findById('Purchase', $request->get('id'));

            if (!$purchase) {
                return Response::text("Purchase ID {$request->get('id')} not found.");
            }

            if ($request->has('txn_date')) $purchase->TxnDate     = $request->get('txn_date');
            if ($request->has('memo'))     $purchase->PrivateNote = $request->get('memo');

            $updated = $qb->update($purchase);

            return Response::text(
                "Purchase updated.\nID: {$updated->Id} | Total: {$updated->TotalAmt} | Date: {$updated->TxnDate}"
            );

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
