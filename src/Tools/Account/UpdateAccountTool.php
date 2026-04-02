<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2026 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Raju\QuickBooksMcp\Tools\Account;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description('Update an existing QuickBooks account. Only pass fields you want to change.')]
class UpdateAccountTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id'               => ['type' => 'string',  'description' => 'QuickBooks account ID (required)'],
                'name'             => ['type' => 'string',  'description' => 'Account name'],
                'account_sub_type' => ['type' => 'string',  'description' => 'Account sub-type'],
                'acct_num'         => ['type' => 'string',  'description' => 'Account number'],
                'description'      => ['type' => 'string',  'description' => 'Account description'],
                'active'           => ['type' => 'boolean', 'description' => 'Active status'],
            ],
            'required' => ['id'],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $account = $qb->findById('Account', $request->get('id'));

            if (!$account) {
                return Response::text("Account ID {$request->get('id')} not found.");
            }

            if ($request->has('name'))             $account->Name           = $request->get('name');
            if ($request->has('account_sub_type')) $account->AccountSubType = $request->get('account_sub_type');
            if ($request->has('acct_num'))         $account->AcctNum        = $request->get('acct_num');
            if ($request->has('description'))      $account->Description    = $request->get('description');
            if ($request->has('active'))           $account->Active         = $request->get('active');

            $updated = $qb->update($account);

            return Response::text(
                "Account updated.\nID: {$updated->Id} | Name: {$updated->Name} | Type: {$updated->AccountType}"
            );

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
