<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2026 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Raju\QuickBooksMcp\Tools\JournalEntry;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description('Search QuickBooks journal entries by date range or document number.')]
class SearchJournalEntriesTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'date_from'  => ['type' => 'string',  'description' => 'Start date filter (YYYY-MM-DD, optional)'],
                'date_to'    => ['type' => 'string',  'description' => 'End date filter (YYYY-MM-DD, optional)'],
                'doc_number' => ['type' => 'string',  'description' => 'Document number (optional)'],
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

            if ($request->get('date_from')) {
                $conditions[] = "TxnDate >= '{$request->get('date_from')}'";
            }
            if ($request->get('date_to')) {
                $conditions[] = "TxnDate <= '{$request->get('date_to')}'";
            }
            if ($request->get('doc_number')) {
                $docNum       = str_replace("'", "\\'", $request->get('doc_number'));
                $conditions[] = "DocNumber = '{$docNum}'";
            }

            $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
            $sql         = "SELECT * FROM JournalEntry {$whereClause} ORDER BY TxnDate DESC MAXRESULTS {$limit}";

            $results = $qb->query($sql);

            if (empty($results)) {
                return Response::text("No journal entries found.");
            }

            $lines = collect($results)->map(fn($e) => implode(' | ', array_filter([
                "ID: {$e->Id}",
                "Date: {$e->TxnDate}",
                isset($e->DocNumber) ? "Doc#: {$e->DocNumber}" : null,
            ])))->join("\n");

            return Response::text("Found " . count($results) . " journal entr(ies):\n\n{$lines}");

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
