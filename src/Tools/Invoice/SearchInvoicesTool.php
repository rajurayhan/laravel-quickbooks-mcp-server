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

#[Description('Search QuickBooks invoices by customer name, date range, or payment status.')]
class SearchInvoicesTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'customer'    => ['type' => 'string',  'description' => 'Customer name to search for (optional)'],
                'date_from'   => ['type' => 'string',  'description' => 'Start date filter (YYYY-MM-DD, optional)'],
                'date_to'     => ['type' => 'string',  'description' => 'End date filter (YYYY-MM-DD, optional)'],
                'unpaid_only' => ['type' => 'boolean', 'description' => 'Only return invoices with outstanding balance (optional)'],
                'email_status'=> ['type' => 'string',  'description' => 'Filter by email status: NotSet, NeedToSend, EmailSent (optional)'],
                'limit'       => ['type' => 'integer', 'description' => 'Max results, 1–100 (default: 20)'],
            ],
            'required' => [],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $limit      = min((int) $request->get('limit', 20), 100);
            $conditions = [];

            if ($request->get('customer')) {
                $escaped      = str_replace("'", "\\'", $request->get('customer'));
                $conditions[] = "CustomerRef.name LIKE '%{$escaped}%'";
            }
            if ($request->get('date_from')) {
                $conditions[] = "TxnDate >= '{$request->get('date_from')}'";
            }
            if ($request->get('date_to')) {
                $conditions[] = "TxnDate <= '{$request->get('date_to')}'";
            }
            if ($request->get('unpaid_only')) {
                $conditions[] = "Balance > '0'";
            }
            if ($request->get('email_status')) {
                $status       = str_replace("'", "\\'", $request->get('email_status'));
                $conditions[] = "EmailStatus = '{$status}'";
            }

            $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
            $sql         = "SELECT * FROM Invoice {$whereClause} ORDER BY TxnDate DESC MAXRESULTS {$limit}";

            $results = $qb->query($sql);

            if (empty($results)) {
                return Response::text("No invoices found.");
            }

            $lines = collect($results)->map(fn($i) => implode(' | ', array_filter([
                "ID: {$i->Id}",
                $i->CustomerRef->name ?? null,
                "Total: {$i->TotalAmt}",
                "Balance: {$i->Balance}",
                "Due: {$i->DueDate}",
            ])))->join("\n");

            return Response::text("Found " . count($results) . " invoice(s):\n\n{$lines}");

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
