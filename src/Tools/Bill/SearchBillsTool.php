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

#[Description('Search QuickBooks bills by vendor name, date range, or outstanding balance.')]
class SearchBillsTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'vendor'     => ['type' => 'string', 'description' => 'Vendor name to search for (optional)'],
                'date_from'  => ['type' => 'string', 'description' => 'Start date filter (YYYY-MM-DD, optional)'],
                'date_to'    => ['type' => 'string', 'description' => 'End date filter (YYYY-MM-DD, optional)'],
                'unpaid_only'=> ['type' => 'boolean', 'description' => 'Only return bills with outstanding balance (optional)'],
                'limit'      => ['type' => 'integer', 'description' => 'Max results, 1–100 (default: 20)'],
            ],
            'required' => [],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $limit      = min((int) $request->get('limit', 20), 100);
            $conditions = [];

            if ($request->get('vendor')) {
                $escaped      = str_replace("'", "\\'", $request->get('vendor'));
                $conditions[] = "VendorRef.name LIKE '%{$escaped}%'";
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

            $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
            $sql         = "SELECT * FROM Bill {$whereClause} MAXRESULTS {$limit}";

            $results = $qb->query($sql);

            if (empty($results)) {
                return Response::text("No bills found.");
            }

            $lines = collect($results)->map(fn($b) => implode(' | ', array_filter([
                "ID: {$b->Id}",
                $b->VendorRef->name ?? null,
                "Total: {$b->TotalAmt}",
                "Balance: {$b->Balance}",
                "Due: {$b->DueDate}",
            ])))->join("\n");

            return Response::text("Found " . count($results) . " bill(s):\n\n{$lines}");

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
