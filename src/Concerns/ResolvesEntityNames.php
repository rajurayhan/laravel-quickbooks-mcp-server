<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2026 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Raju\QuickBooksMcp\Concerns;

use Raju\QuickBooksMcp\Services\QuickBooksService;
use Raju\QuickBooksMcp\Exceptions\QuickBooksToolException;

trait ResolvesEntityNames
{
    protected function resolveCustomer(string $nameOrId, QuickBooksService $qb): string
    {
        if (is_numeric($nameOrId)) return $nameOrId;
        $id = $qb->resolveCustomerId($nameOrId);
        if (!$id) {
            throw new QuickBooksToolException(
                "Customer \"{$nameOrId}\" not found. Use search_customers to find the correct name."
            );
        }
        return $id;
    }

    protected function resolveVendor(string $nameOrId, QuickBooksService $qb): string
    {
        if (is_numeric($nameOrId)) return $nameOrId;
        $id = $qb->resolveVendorId($nameOrId);
        if (!$id) {
            throw new QuickBooksToolException(
                "Vendor \"{$nameOrId}\" not found. Use search_vendors to find the correct name."
            );
        }
        return $id;
    }

    protected function resolveAccount(string $nameOrId, QuickBooksService $qb): string
    {
        if (is_numeric($nameOrId)) return $nameOrId;
        $id = $qb->resolveAccountId($nameOrId);
        if (!$id) {
            throw new QuickBooksToolException(
                "Account \"{$nameOrId}\" not found. Use search_accounts to find the correct name."
            );
        }
        return $id;
    }

    protected function resolveItem(string $nameOrId, QuickBooksService $qb): string
    {
        if (is_numeric($nameOrId)) return $nameOrId;
        $id = $qb->resolveItemId($nameOrId);
        if (!$id) {
            throw new QuickBooksToolException(
                "Item \"{$nameOrId}\" not found. Use search_items to find the correct name."
            );
        }
        return $id;
    }
}
