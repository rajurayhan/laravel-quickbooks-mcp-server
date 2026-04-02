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

#[Description('Search QuickBooks purchase (expense) transactions by payment type or date range.')]
class SearchPurchasesTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'payment_type' => ['type' => 'string',  'description' => 'Filter by payment type: Cash, Check, CreditCard (optional)'],
                'date_from'    => ['type' => 'string',  'description' => 'Start date filter (YYYY-MM-DD, optional)'],
                'date_to'      => ['type' => 'string',  'description' => 'End date filter (YYYY-MM-DD, optional)'],
                'limit'        => ['type' => 'integer', 'description' => 'Max results, 1–100 (default: 20)'],
            ],
            'required' => [],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $limit      = min((int) $request->get('limit', 20), 100);
            $conditions = [];

            if ($request->get('payment_type')) {
                $ptype        = str_replace("'", "\\'", $request->get('payment_type'));
                $conditions[] = "PaymentType = '{$ptype}'";
            }
            if ($request->get('date_from')) {
                $conditions[] = "TxnDate >= '{$request->get('date_from')}'";
            }
            if ($request->get('date_to')) {
                $conditions[] = "TxnDate <= '{$request->get('date_to')}'";
            }

            $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
            $sql         = "SELECT * FROM Purchase {$whereClause} ORDER BY TxnDate DESC MAXRESULTS {$limit}";

            $results = $qb->query($sql);

            if (empty($results)) {
                return Response::text("No purchases found.");
            }

            $lines = collect($results)->map(fn($p) => implode(' | ', array_filter([
                "ID: {$p->Id}",
                $p->PaymentType ?? null,
                "Total: {$p->TotalAmt}",
                "Date: {$p->TxnDate}",
            ])))->join("\n");

            return Response::text("Found " . count($results) . " purchase(s):\n\n{$lines}");

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
