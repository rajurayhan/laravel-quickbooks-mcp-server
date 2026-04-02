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
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description('Get a single QuickBooks bill payment by its ID.')]
class GetBillPaymentTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id' => ['type' => 'string', 'description' => 'QuickBooks bill payment ID'],
            ],
            'required' => ['id'],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $payment = $qb->findById('BillPayment', $request->get('id'));

            if (!$payment) {
                return Response::text("Bill payment ID {$request->get('id')} not found.");
            }

            return Response::text(json_encode([
                'id'          => $payment->Id,
                'sync_token'  => $payment->SyncToken,
                'vendor_id'   => $payment->VendorRef->value ?? null,
                'vendor_name' => $payment->VendorRef->name ?? null,
                'pay_type'    => $payment->PayType ?? null,
                'total'       => $payment->TotalAmt ?? 0,
                'txn_date'    => $payment->TxnDate ?? null,
            ], JSON_PRETTY_PRINT));

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
