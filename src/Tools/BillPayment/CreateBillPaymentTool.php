<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2026 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Raju\QuickBooksMcp\Tools\BillPayment;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use QuickBooksOnline\API\Facades\QBOFacade;
use Raju\QuickBooksMcp\Concerns\ResolvesEntityNames;
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description(
    'Create a bill payment to pay one or more open bills from a vendor. ' .
    'Pass vendor name or ID, payment type (Check or CreditCard), and the bill IDs to pay.'
)]
class CreateBillPaymentTool extends Tool
{
    use ResolvesEntityNames;

    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'vendor'       => ['type' => 'string', 'description' => 'Vendor name or ID'],
                'pay_type'     => ['type' => 'string', 'description' => 'Payment type: Check or CreditCard'],
                'account'      => ['type' => 'string', 'description' => 'Bank or credit card account name or ID used for payment'],
                'txn_date'     => ['type' => 'string', 'description' => 'Payment date (YYYY-MM-DD). Defaults to today.'],
                'total_amount' => ['type' => 'number', 'description' => 'Total payment amount'],
                'bill_ids'     => [
                    'type'        => 'array',
                    'items'       => ['type' => 'string'],
                    'description' => 'List of bill IDs to mark as paid',
                ],
            ],
            'required' => ['vendor', 'pay_type', 'account', 'total_amount', 'bill_ids'],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $vendorId  = $this->resolveVendor($request->get('vendor'), $qb);
            $accountId = $this->resolveAccount($request->get('account'), $qb);

            $lines = [];
            foreach ($request->get('bill_ids') as $billId) {
                $line                    = QBOFacade::create('BillPaymentLine');
                $line->Amount            = $request->get('total_amount');
                $linkedTxn               = QBOFacade::create('LinkedTxn');
                $linkedTxn->TxnId        = $billId;
                $linkedTxn->TxnType      = 'Bill';
                $line->LinkedTxn         = [$linkedTxn];
                $lines[]                 = $line;
            }

            $payment                   = QBOFacade::create('BillPayment');
            $payment->VendorRef        = QBOFacade::create('ReferenceType');
            $payment->VendorRef->value = $vendorId;
            $payment->PayType          = $request->get('pay_type');
            $payment->TotalAmt         = $request->get('total_amount');
            $payment->TxnDate          = $request->get('txn_date', now()->toDateString());
            $payment->Line             = $lines;

            $checkPayment                   = QBOFacade::create('BillPaymentCheck');
            $checkPayment->BankAccountRef        = QBOFacade::create('ReferenceType');
            $checkPayment->BankAccountRef->value = $accountId;
            $payment->CheckPayment = $checkPayment;

            $created = $qb->create($payment);

            return Response::text(
                "Bill payment created.\nID: {$created->Id} | Amount: {$created->TotalAmt} | Date: {$created->TxnDate}"
            );

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
