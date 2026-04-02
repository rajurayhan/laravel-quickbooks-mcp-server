<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2026 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Raju\QuickBooksMcp\Tools\Employee;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description('Search QuickBooks employees by name or email.')]
class SearchEmployeesTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'query'  => ['type' => 'string',  'description' => 'Name or email to search for'],
                'active' => ['type' => 'boolean', 'description' => 'Filter active only (default: true)'],
                'limit'  => ['type' => 'integer', 'description' => 'Max results, 1–100 (default: 20)'],
            ],
            'required' => ['query'],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $query        = $request->get('query');
            $active       = $request->get('active', true);
            $limit        = min((int) $request->get('limit', 20), 100);
            $escaped      = str_replace("'", "\\'", $query);
            $activeClause = $active ? "AND Active = true" : "";

            $sql = "SELECT * FROM Employee
                    WHERE (DisplayName LIKE '%{$escaped}%'
                        OR GivenName LIKE '%{$escaped}%'
                        OR FamilyName LIKE '%{$escaped}%')
                    {$activeClause}
                    MAXRESULTS {$limit}";

            $results = $qb->query($sql);

            if (empty($results)) {
                return Response::text("No employees found matching \"{$query}\".");
            }

            $lines = collect($results)->map(fn($e) => implode(' | ', array_filter([
                "ID: {$e->Id}",
                $e->DisplayName,
                $e->PrimaryEmailAddr->Address ?? null,
            ])))->join("\n");

            return Response::text("Found " . count($results) . " employee(s):\n\n{$lines}");

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
