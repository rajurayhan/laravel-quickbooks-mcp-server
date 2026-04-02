<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2026 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Raju\QuickBooksMcp\Tools\Account;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description('Search QuickBooks chart of accounts by name or account type.')]
class SearchAccountsTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'query'        => ['type' => 'string',  'description' => 'Account name to search for'],
                'account_type' => ['type' => 'string',  'description' => 'Filter by type: Expense, Income, Asset, Liability, Equity, etc. (optional)'],
                'active'       => ['type' => 'boolean', 'description' => 'Filter active only (default: true)'],
                'limit'        => ['type' => 'integer', 'description' => 'Max results, 1–100 (default: 20)'],
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
            $typeClause   = '';

            if ($request->get('account_type')) {
                $type       = str_replace("'", "\\'", $request->get('account_type'));
                $typeClause = "AND AccountType = '{$type}'";
            }

            $sql = "SELECT * FROM Account
                    WHERE Name LIKE '%{$escaped}%'
                    {$typeClause}
                    {$activeClause}
                    MAXRESULTS {$limit}";

            $results = $qb->query($sql);

            if (empty($results)) {
                return Response::text("No accounts found matching \"{$query}\".");
            }

            $lines = collect($results)->map(fn($a) => implode(' | ', array_filter([
                "ID: {$a->Id}",
                $a->Name,
                $a->AccountType,
                $a->AccountSubType ?? null,
                isset($a->AcctNum) ? "Acct#: {$a->AcctNum}" : null,
            ])))->join("\n");

            return Response::text("Found " . count($results) . " account(s):\n\n{$lines}");

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
