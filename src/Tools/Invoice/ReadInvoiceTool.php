<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2026 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Raju\QuickBooksMcp\Tools\Invoice;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description(
    'Read a full invoice document including all line items, customer info, payment status, and amounts.'
)]
class ReadInvoiceTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id' => ['type' => 'string', 'description' => 'QuickBooks invoice ID'],
            ],
            'required' => ['id'],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $invoice = $qb->findById('Invoice', $request->get('id'));

            if (!$invoice) {
                return Response::text("Invoice ID {$request->get('id')} not found.");
            }

            $lines = collect($invoice->Line ?? [])->map(fn($line) => array_filter([
                'description' => $line->Description ?? null,
                'amount'      => $line->Amount ?? null,
                'qty'         => $line->SalesItemLineDetail?->Qty ?? null,
                'unit_price'  => $line->SalesItemLineDetail?->UnitPrice ?? null,
                'item_id'     => $line->SalesItemLineDetail?->ItemRef?->value ?? null,
                'item_name'   => $line->SalesItemLineDetail?->ItemRef?->name ?? null,
            ]))->values()->toArray();

            return Response::text(json_encode([
                'id'            => $invoice->Id,
                'doc_number'    => $invoice->DocNumber ?? null,
                'sync_token'    => $invoice->SyncToken,
                'customer_id'   => $invoice->CustomerRef->value ?? null,
                'customer_name' => $invoice->CustomerRef->name ?? null,
                'txn_date'      => $invoice->TxnDate ?? null,
                'due_date'      => $invoice->DueDate ?? null,
                'status'        => $invoice->EmailStatus ?? null,
                'balance'       => $invoice->Balance ?? 0,
                'total'         => $invoice->TotalAmt ?? 0,
                'line_items'    => $lines,
                'memo'          => $invoice->CustomerMemo->value ?? null,
                'email'         => $invoice->BillEmail->Address ?? null,
            ], JSON_PRETTY_PRINT));

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
