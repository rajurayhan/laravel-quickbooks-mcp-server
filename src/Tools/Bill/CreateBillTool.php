<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2026 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Raju\QuickBooksMcp\Tools\Bill;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use QuickBooksOnline\API\Facades\QBOFacade;
use Raju\QuickBooksMcp\Concerns\ResolvesEntityNames;
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description(
    'Create a new bill (accounts payable) in QuickBooks. ' .
    'Pass vendor name or ID. Line items need account name or ID and amount.'
)]
class CreateBillTool extends Tool
{
    use ResolvesEntityNames;

    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'vendor'     => ['type' => 'string', 'description' => 'Vendor name or ID'],
                'txn_date'   => ['type' => 'string', 'description' => 'Transaction date (YYYY-MM-DD). Defaults to today.'],
                'due_date'   => ['type' => 'string', 'description' => 'Due date (YYYY-MM-DD)'],
                'line_items' => [
                    'type'  => 'array',
                    'items' => [
                        'type'       => 'object',
                        'properties' => [
                            'account'     => ['type' => 'string', 'description' => 'Account name or ID'],
                            'amount'      => ['type' => 'number', 'description' => 'Line amount'],
                            'description' => ['type' => 'string', 'description' => 'Line description (optional)'],
                        ],
                        'required' => ['account', 'amount'],
                    ],
                ],
                'memo' => ['type' => 'string', 'description' => 'Private memo (optional)'],
            ],
            'required' => ['vendor', 'line_items'],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $vendorId = $this->resolveVendor($request->get('vendor'), $qb);

            $lines = [];
            foreach ($request->get('line_items') as $item) {
                $accountId = $this->resolveAccount($item['account'], $qb);
                $line      = QBOFacade::create('Line');
                $line->Amount     = $item['amount'];
                $line->DetailType = 'AccountBasedExpenseLineDetail';
                $detail           = QBOFacade::create('AccountBasedExpenseLineDetail');
                $detail->AccountRef        = QBOFacade::create('ReferenceType');
                $detail->AccountRef->value = $accountId;
                if (!empty($item['description'])) {
                    $line->Description = $item['description'];
                }
                $line->AccountBasedExpenseLineDetail = $detail;
                $lines[] = $line;
            }

            $bill             = QBOFacade::create('Bill');
            $bill->VendorRef        = QBOFacade::create('ReferenceType');
            $bill->VendorRef->value = $vendorId;
            $bill->Line    = $lines;
            $bill->TxnDate = $request->get('txn_date', now()->toDateString());
            if ($request->get('due_date')) $bill->DueDate     = $request->get('due_date');
            if ($request->get('memo'))     $bill->PrivateNote = $request->get('memo');

            $created = $qb->create($bill);

            return Response::text(
                "Bill created.\nID: {$created->Id} | Total: {$created->TotalAmt} | Due: {$created->DueDate}"
            );

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
