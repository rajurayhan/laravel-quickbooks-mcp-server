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
use QuickBooksOnline\API\Facades\QBOFacade;
use Raju\QuickBooksMcp\Concerns\ResolvesEntityNames;
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description(
    'Create a new journal entry in QuickBooks. ' .
    'Each line must specify an account name or ID, posting type (Debit or Credit), and amount. ' .
    'Debits must equal credits.'
)]
class CreateJournalEntryTool extends Tool
{
    use ResolvesEntityNames;

    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'txn_date'   => ['type' => 'string', 'description' => 'Transaction date (YYYY-MM-DD). Defaults to today.'],
                'line_items' => [
                    'type'  => 'array',
                    'items' => [
                        'type'       => 'object',
                        'properties' => [
                            'account'      => ['type' => 'string', 'description' => 'Account name or ID'],
                            'posting_type' => ['type' => 'string', 'description' => 'Debit or Credit'],
                            'amount'       => ['type' => 'number', 'description' => 'Line amount'],
                            'description'  => ['type' => 'string', 'description' => 'Line description (optional)'],
                        ],
                        'required' => ['account', 'posting_type', 'amount'],
                    ],
                ],
                'memo' => ['type' => 'string', 'description' => 'Private memo (optional)'],
            ],
            'required' => ['line_items'],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $lines = [];
            foreach ($request->get('line_items') as $item) {
                $accountId = $this->resolveAccount($item['account'], $qb);
                $line      = QBOFacade::create('Line');
                $line->Amount     = $item['amount'];
                $line->DetailType = 'JournalEntryLineDetail';

                $detail                      = QBOFacade::create('JournalEntryLineDetail');
                $detail->PostingType         = $item['posting_type'];
                $detail->AccountRef          = QBOFacade::create('ReferenceType');
                $detail->AccountRef->value   = $accountId;
                $line->JournalEntryLineDetail = $detail;

                if (!empty($item['description'])) {
                    $line->Description = $item['description'];
                }
                $lines[] = $line;
            }

            $entry         = QBOFacade::create('JournalEntry');
            $entry->Line   = $lines;
            $entry->TxnDate = $request->get('txn_date', now()->toDateString());
            if ($request->get('memo')) $entry->PrivateNote = $request->get('memo');

            $created = $qb->create($entry);

            return Response::text(
                "Journal entry created.\nID: {$created->Id} | Date: {$created->TxnDate}"
            );

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
