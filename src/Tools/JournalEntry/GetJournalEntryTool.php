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

#[Description('Get a single QuickBooks journal entry by its ID.')]
class GetJournalEntryTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id' => ['type' => 'string', 'description' => 'QuickBooks journal entry ID'],
            ],
            'required' => ['id'],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $entry = $qb->findById('JournalEntry', $request->get('id'));

            if (!$entry) {
                return Response::text("Journal entry ID {$request->get('id')} not found.");
            }

            $lines = collect($entry->Line ?? [])->map(fn($line) => array_filter([
                'posting_type' => $line->JournalEntryLineDetail?->PostingType ?? null,
                'account_id'   => $line->JournalEntryLineDetail?->AccountRef?->value ?? null,
                'account_name' => $line->JournalEntryLineDetail?->AccountRef?->name ?? null,
                'amount'       => $line->Amount ?? null,
                'description'  => $line->Description ?? null,
            ]))->values()->toArray();

            return Response::text(json_encode([
                'id'         => $entry->Id,
                'sync_token' => $entry->SyncToken,
                'txn_date'   => $entry->TxnDate ?? null,
                'memo'       => $entry->PrivateNote ?? null,
                'line_items' => $lines,
            ], JSON_PRETTY_PRINT));

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
