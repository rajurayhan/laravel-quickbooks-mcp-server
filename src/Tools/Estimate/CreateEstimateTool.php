<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2026 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Raju\QuickBooksMcp\Tools\Estimate;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use QuickBooksOnline\API\Facades\QBOFacade;
use Raju\QuickBooksMcp\Concerns\ResolvesEntityNames;
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description(
    'Create a new estimate (quote) in QuickBooks. ' .
    'Pass customer name or ID and line items with item name/ID, quantity, and unit price.'
)]
class CreateEstimateTool extends Tool
{
    use ResolvesEntityNames;

    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'customer'        => ['type' => 'string', 'description' => 'Customer name or ID'],
                'txn_date'        => ['type' => 'string', 'description' => 'Estimate date (YYYY-MM-DD). Defaults to today.'],
                'expiration_date' => ['type' => 'string', 'description' => 'Expiration date (YYYY-MM-DD, optional)'],
                'line_items'      => [
                    'type'  => 'array',
                    'items' => [
                        'type'       => 'object',
                        'properties' => [
                            'item'        => ['type' => 'string', 'description' => 'Item name or ID'],
                            'qty'         => ['type' => 'number', 'description' => 'Quantity'],
                            'unit_price'  => ['type' => 'number', 'description' => 'Unit price'],
                            'description' => ['type' => 'string', 'description' => 'Line description (optional)'],
                        ],
                        'required' => ['item', 'qty', 'unit_price'],
                    ],
                ],
                'memo' => ['type' => 'string', 'description' => 'Customer-facing memo (optional)'],
            ],
            'required' => ['customer', 'line_items'],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $customerId = $this->resolveCustomer($request->get('customer'), $qb);

            $lines = [];
            foreach ($request->get('line_items') as $item) {
                $itemId = $this->resolveItem($item['item'], $qb);
                $line   = QBOFacade::create('Line');
                $line->Amount     = round($item['qty'] * $item['unit_price'], 2);
                $line->DetailType = 'SalesItemLineDetail';

                $detail                       = QBOFacade::create('SalesItemLineDetail');
                $detail->ItemRef              = QBOFacade::create('ReferenceType');
                $detail->ItemRef->value       = $itemId;
                $detail->Qty                  = $item['qty'];
                $detail->UnitPrice            = $item['unit_price'];
                $line->SalesItemLineDetail    = $detail;

                if (!empty($item['description'])) {
                    $line->Description = $item['description'];
                }
                $lines[] = $line;
            }

            $estimate                    = QBOFacade::create('Estimate');
            $estimate->CustomerRef       = QBOFacade::create('ReferenceType');
            $estimate->CustomerRef->value = $customerId;
            $estimate->Line     = $lines;
            $estimate->TxnDate  = $request->get('txn_date', now()->toDateString());
            if ($request->get('expiration_date')) {
                $estimate->ExpirationDate = $request->get('expiration_date');
            }
            if ($request->get('memo')) {
                $estimate->CustomerMemo        = new \stdClass();
                $estimate->CustomerMemo->value = $request->get('memo');
            }

            $created = $qb->create($estimate);

            return Response::text(
                "Estimate created.\nID: {$created->Id} | Total: {$created->TotalAmt} | Status: {$created->TxnStatus}"
            );

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
