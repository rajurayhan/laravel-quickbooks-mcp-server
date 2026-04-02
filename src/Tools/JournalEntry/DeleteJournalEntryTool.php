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

#[Description(
    'Permanently delete a QuickBooks journal entry. ' .
    'This action is irreversible. Use with caution.'
)]
class DeleteJournalEntryTool extends Tool
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

            $qb->delete($entry);

            return Response::text(
                "Journal entry ID {$request->get('id')} has been permanently deleted."
            );

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
