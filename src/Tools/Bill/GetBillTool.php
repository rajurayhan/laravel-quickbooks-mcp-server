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

#[Description('Get a single QuickBooks bill by its ID.')]
class GetBillTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id' => ['type' => 'string', 'description' => 'QuickBooks bill ID'],
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

            $lines = collect($bill->Line ?? [])->map(fn($line) => array_filter([
                'amount'      => $line->Amount ?? null,
                'description' => $line->Description ?? null,
                'account_id'  => $line->AccountBasedExpenseLineDetail?->AccountRef?->value ?? null,
            ]))->values()->toArray();

            return Response::text(json_encode([
                'id'          => $bill->Id,
                'sync_token'  => $bill->SyncToken,
                'vendor_id'   => $bill->VendorRef->value ?? null,
                'vendor_name' => $bill->VendorRef->name ?? null,
                'txn_date'    => $bill->TxnDate ?? null,
                'due_date'    => $bill->DueDate ?? null,
                'total'       => $bill->TotalAmt ?? 0,
                'balance'     => $bill->Balance ?? 0,
                'memo'        => $bill->PrivateNote ?? null,
                'line_items'  => $lines,
            ], JSON_PRETTY_PRINT));

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
