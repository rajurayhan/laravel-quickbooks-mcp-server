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
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description(
    'Update an existing QuickBooks estimate. ' .
    'Only pass fields you want to change. Use to accept, close, or reject an estimate.'
)]
class UpdateEstimateTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id'              => ['type' => 'string', 'description' => 'QuickBooks estimate ID (required)'],
                'txn_date'        => ['type' => 'string', 'description' => 'Estimate date (YYYY-MM-DD)'],
                'expiration_date' => ['type' => 'string', 'description' => 'Expiration date (YYYY-MM-DD)'],
                'status'          => ['type' => 'string', 'description' => 'Status: Pending, Accepted, Closed, Rejected'],
                'memo'            => ['type' => 'string', 'description' => 'Customer-facing memo'],
            ],
            'required' => ['id'],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $estimate = $qb->findById('Estimate', $request->get('id'));

            if (!$estimate) {
                return Response::text("Estimate ID {$request->get('id')} not found.");
            }

            if ($request->has('txn_date'))        $estimate->TxnDate        = $request->get('txn_date');
            if ($request->has('expiration_date')) $estimate->ExpirationDate = $request->get('expiration_date');
            if ($request->has('status'))          $estimate->TxnStatus      = $request->get('status');
            if ($request->has('memo')) {
                $estimate->CustomerMemo        ??= new \stdClass();
                $estimate->CustomerMemo->value = $request->get('memo');
            }

            $updated = $qb->update($estimate);

            return Response::text(
                "Estimate updated.\nID: {$updated->Id} | Total: {$updated->TotalAmt} | Status: {$updated->TxnStatus}"
            );

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
