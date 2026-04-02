<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2026 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Raju\QuickBooksMcp\Tools\Estimate;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description('Search QuickBooks estimates by customer name, status, or date range.')]
class SearchEstimatesTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'customer'  => ['type' => 'string',  'description' => 'Customer name to search for (optional)'],
                'status'    => ['type' => 'string',  'description' => 'Filter by status: Pending, Accepted, Closed, Rejected (optional)'],
                'date_from' => ['type' => 'string',  'description' => 'Start date filter (YYYY-MM-DD, optional)'],
                'date_to'   => ['type' => 'string',  'description' => 'End date filter (YYYY-MM-DD, optional)'],
                'limit'     => ['type' => 'integer', 'description' => 'Max results, 1–100 (default: 20)'],
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
            if ($request->get('status')) {
                $status       = str_replace("'", "\\'", $request->get('status'));
                $conditions[] = "TxnStatus = '{$status}'";
            }
            if ($request->get('date_from')) {
                $conditions[] = "TxnDate >= '{$request->get('date_from')}'";
            }
            if ($request->get('date_to')) {
                $conditions[] = "TxnDate <= '{$request->get('date_to')}'";
            }

            $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
            $sql         = "SELECT * FROM Estimate {$whereClause} MAXRESULTS {$limit}";

            $results = $qb->query($sql);

            if (empty($results)) {
                return Response::text("No estimates found.");
            }

            $lines = collect($results)->map(fn($e) => implode(' | ', array_filter([
                "ID: {$e->Id}",
                $e->CustomerRef->name ?? null,
                "Total: {$e->TotalAmt}",
                "Status: {$e->TxnStatus}",
                "Date: {$e->TxnDate}",
            ])))->join("\n");

            return Response::text("Found " . count($results) . " estimate(s):\n\n{$lines}");

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
