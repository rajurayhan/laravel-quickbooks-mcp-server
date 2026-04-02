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

#[Description('Update an existing QuickBooks journal entry. Only pass fields you want to change.')]
class UpdateJournalEntryTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id'       => ['type' => 'string', 'description' => 'QuickBooks journal entry ID (required)'],
                'txn_date' => ['type' => 'string', 'description' => 'Transaction date (YYYY-MM-DD)'],
                'memo'     => ['type' => 'string', 'description' => 'Private memo'],
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

            if ($request->has('txn_date')) $entry->TxnDate     = $request->get('txn_date');
            if ($request->has('memo'))     $entry->PrivateNote = $request->get('memo');

            $updated = $qb->update($entry);

            return Response::text(
                "Journal entry updated.\nID: {$updated->Id} | Date: {$updated->TxnDate}"
            );

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
