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

#[Description('Update an existing QuickBooks invoice. Only pass fields you want to change.')]
class UpdateInvoiceTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id'           => ['type' => 'string', 'description' => 'QuickBooks invoice ID (required)'],
                'txn_date'     => ['type' => 'string', 'description' => 'Invoice date (YYYY-MM-DD)'],
                'due_date'     => ['type' => 'string', 'description' => 'Due date (YYYY-MM-DD)'],
                'email'        => ['type' => 'string', 'description' => 'Billing email address'],
                'memo'         => ['type' => 'string', 'description' => 'Customer-facing memo'],
                'email_status' => ['type' => 'string', 'description' => 'Email status: NotSet, NeedToSend, EmailSent'],
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

            if ($request->has('txn_date'))     $invoice->TxnDate     = $request->get('txn_date');
            if ($request->has('due_date'))     $invoice->DueDate     = $request->get('due_date');
            if ($request->has('email_status')) $invoice->EmailStatus = $request->get('email_status');

            if ($request->has('email')) {
                $invoice->BillEmail          ??= new \stdClass();
                $invoice->BillEmail->Address = $request->get('email');
            }
            if ($request->has('memo')) {
                $invoice->CustomerMemo        ??= new \stdClass();
                $invoice->CustomerMemo->value = $request->get('memo');
            }

            $updated = $qb->update($invoice);

            return Response::text(
                "Invoice updated.\nID: {$updated->Id} | Total: {$updated->TotalAmt} | Balance: {$updated->Balance}"
            );

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
