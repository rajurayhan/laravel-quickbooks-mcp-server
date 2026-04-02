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
use QuickBooksOnline\API\Facades\QBOFacade;
use Raju\QuickBooksMcp\Concerns\ResolvesEntityNames;
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description(
    'Create a new purchase (expense) transaction in QuickBooks. ' .
    'Payment type must be Cash, Check, or CreditCard. ' .
    'Pass account name for the payment source and line items with expense accounts.'
)]
class CreatePurchaseTool extends Tool
{
    use ResolvesEntityNames;

    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'payment_type'  => ['type' => 'string', 'description' => 'Payment method: Cash, Check, or CreditCard'],
                'account'       => ['type' => 'string', 'description' => 'Bank or credit card account name or ID'],
                'vendor'        => ['type' => 'string', 'description' => 'Vendor name or ID (optional)'],
                'txn_date'      => ['type' => 'string', 'description' => 'Transaction date (YYYY-MM-DD). Defaults to today.'],
                'line_items'    => [
                    'type'  => 'array',
                    'items' => [
                        'type'       => 'object',
                        'properties' => [
                            'account'     => ['type' => 'string', 'description' => 'Expense account name or ID'],
                            'amount'      => ['type' => 'number', 'description' => 'Line amount'],
                            'description' => ['type' => 'string', 'description' => 'Line description (optional)'],
                        ],
                        'required' => ['account', 'amount'],
                    ],
                ],
                'memo' => ['type' => 'string', 'description' => 'Private memo (optional)'],
            ],
            'required' => ['payment_type', 'account', 'line_items'],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $accountId = $this->resolveAccount($request->get('account'), $qb);

            $lines = [];
            foreach ($request->get('line_items') as $item) {
                $lineAccountId = $this->resolveAccount($item['account'], $qb);
                $line          = QBOFacade::create('Line');
                $line->Amount     = $item['amount'];
                $line->DetailType = 'AccountBasedExpenseLineDetail';

                $detail                    = QBOFacade::create('AccountBasedExpenseLineDetail');
                $detail->AccountRef        = QBOFacade::create('ReferenceType');
                $detail->AccountRef->value = $lineAccountId;
                $line->AccountBasedExpenseLineDetail = $detail;

                if (!empty($item['description'])) {
                    $line->Description = $item['description'];
                }
                $lines[] = $line;
            }

            $purchase              = QBOFacade::create('Purchase');
            $purchase->PaymentType = $request->get('payment_type');
            $purchase->AccountRef  = QBOFacade::create('ReferenceType');
            $purchase->AccountRef->value = $accountId;
            $purchase->Line    = $lines;
            $purchase->TxnDate = $request->get('txn_date', now()->toDateString());

            if ($request->get('vendor')) {
                $vendorId                     = $this->resolveVendor($request->get('vendor'), $qb);
                $purchase->EntityRef          = QBOFacade::create('ReferenceType');
                $purchase->EntityRef->value   = $vendorId;
                $purchase->EntityRef->type    = 'Vendor';
            }
            if ($request->get('memo')) $purchase->PrivateNote = $request->get('memo');

            $created = $qb->create($purchase);

            return Response::text(
                "Purchase created.\nID: {$created->Id} | Total: {$created->TotalAmt} | Type: {$created->PaymentType}"
            );

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
