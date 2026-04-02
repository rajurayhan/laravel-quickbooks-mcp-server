<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2026 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Raju\QuickBooksMcp\Tools\Item;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description('Search QuickBooks items (products and services) by name or type.')]
class SearchItemsTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'query'  => ['type' => 'string',  'description' => 'Item name to search for'],
                'type'   => ['type' => 'string',  'description' => 'Filter by type: Inventory, Service, NonInventory (optional)'],
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
            $typeClause   = '';

            if ($request->get('type')) {
                $type       = str_replace("'", "\\'", $request->get('type'));
                $typeClause = "AND Type = '{$type}'";
            }

            $sql = "SELECT * FROM Item
                    WHERE Name LIKE '%{$escaped}%'
                    {$typeClause}
                    {$activeClause}
                    MAXRESULTS {$limit}";

            $results = $qb->query($sql);

            if (empty($results)) {
                return Response::text("No items found matching \"{$query}\".");
            }

            $lines = collect($results)->map(fn($i) => implode(' | ', array_filter([
                "ID: {$i->Id}",
                $i->Name,
                $i->Type ?? null,
                isset($i->UnitPrice) ? "Price: {$i->UnitPrice}" : null,
            ])))->join("\n");

            return Response::text("Found " . count($results) . " item(s):\n\n{$lines}");

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
