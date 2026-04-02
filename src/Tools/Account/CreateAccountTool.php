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
use QuickBooksOnline\API\Facades\QBOFacade;
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description(
    'Create a new account in the QuickBooks chart of accounts. ' .
    'Specify the account type (e.g. Expense, Income, Asset, Liability) and sub-type.'
)]
class CreateAccountTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'name'         => ['type' => 'string', 'description' => 'Account name (must be unique)'],
                'account_type' => ['type' => 'string', 'description' => 'Account type: Expense, Income, Asset, Liability, Equity, OtherCurrentAsset, etc.'],
                'account_sub_type' => ['type' => 'string', 'description' => 'Account sub-type (optional, e.g. AdvertisingPromotional, Checking, Savings)'],
                'acct_num'     => ['type' => 'string', 'description' => 'Account number (optional)'],
                'description'  => ['type' => 'string', 'description' => 'Account description (optional)'],
            ],
            'required' => ['name', 'account_type'],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $account = QBOFacade::create('Account');
            $account->Name        = $request->get('name');
            $account->AccountType = $request->get('account_type');

            if ($request->get('account_sub_type')) {
                $account->AccountSubType = $request->get('account_sub_type');
            }
            if ($request->get('acct_num')) {
                $account->AcctNum = $request->get('acct_num');
            }
            if ($request->get('description')) {
                $account->Description = $request->get('description');
            }

            $created = $qb->create($account);

            return Response::text(
                "Account created.\nID: {$created->Id} | Name: {$created->Name} | Type: {$created->AccountType}"
            );

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
