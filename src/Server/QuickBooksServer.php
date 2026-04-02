<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2026 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Raju\QuickBooksMcp\Server;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Raju\QuickBooksMcp\Tools\Account\CreateAccountTool;
use Raju\QuickBooksMcp\Tools\Account\SearchAccountsTool;
use Raju\QuickBooksMcp\Tools\Account\UpdateAccountTool;
use Raju\QuickBooksMcp\Tools\Bill\CreateBillTool;
use Raju\QuickBooksMcp\Tools\Bill\DeleteBillTool;
use Raju\QuickBooksMcp\Tools\Bill\GetBillTool;
use Raju\QuickBooksMcp\Tools\Bill\SearchBillsTool;
use Raju\QuickBooksMcp\Tools\Bill\UpdateBillTool;
use Raju\QuickBooksMcp\Tools\BillPayment\CreateBillPaymentTool;
use Raju\QuickBooksMcp\Tools\BillPayment\DeleteBillPaymentTool;
use Raju\QuickBooksMcp\Tools\BillPayment\GetBillPaymentTool;
use Raju\QuickBooksMcp\Tools\BillPayment\SearchBillPaymentsTool;
use Raju\QuickBooksMcp\Tools\BillPayment\UpdateBillPaymentTool;
use Raju\QuickBooksMcp\Tools\Customer\CreateCustomerTool;
use Raju\QuickBooksMcp\Tools\Customer\DeleteCustomerTool;
use Raju\QuickBooksMcp\Tools\Customer\GetCustomerTool;
use Raju\QuickBooksMcp\Tools\Customer\SearchCustomersTool;
use Raju\QuickBooksMcp\Tools\Customer\UpdateCustomerTool;
use Raju\QuickBooksMcp\Tools\Employee\CreateEmployeeTool;
use Raju\QuickBooksMcp\Tools\Employee\GetEmployeeTool;
use Raju\QuickBooksMcp\Tools\Employee\SearchEmployeesTool;
use Raju\QuickBooksMcp\Tools\Employee\UpdateEmployeeTool;
use Raju\QuickBooksMcp\Tools\Estimate\CreateEstimateTool;
use Raju\QuickBooksMcp\Tools\Estimate\DeleteEstimateTool;
use Raju\QuickBooksMcp\Tools\Estimate\GetEstimateTool;
use Raju\QuickBooksMcp\Tools\Estimate\SearchEstimatesTool;
use Raju\QuickBooksMcp\Tools\Estimate\UpdateEstimateTool;
use Raju\QuickBooksMcp\Tools\Invoice\CreateInvoiceTool;
use Raju\QuickBooksMcp\Tools\Invoice\ReadInvoiceTool;
use Raju\QuickBooksMcp\Tools\Invoice\SearchInvoicesTool;
use Raju\QuickBooksMcp\Tools\Invoice\UpdateInvoiceTool;
use Raju\QuickBooksMcp\Tools\Item\CreateItemTool;
use Raju\QuickBooksMcp\Tools\Item\ReadItemTool;
use Raju\QuickBooksMcp\Tools\Item\SearchItemsTool;
use Raju\QuickBooksMcp\Tools\Item\UpdateItemTool;
use Raju\QuickBooksMcp\Tools\JournalEntry\CreateJournalEntryTool;
use Raju\QuickBooksMcp\Tools\JournalEntry\DeleteJournalEntryTool;
use Raju\QuickBooksMcp\Tools\JournalEntry\GetJournalEntryTool;
use Raju\QuickBooksMcp\Tools\JournalEntry\SearchJournalEntriesTool;
use Raju\QuickBooksMcp\Tools\JournalEntry\UpdateJournalEntryTool;
use Raju\QuickBooksMcp\Tools\Purchase\CreatePurchaseTool;
use Raju\QuickBooksMcp\Tools\Purchase\DeletePurchaseTool;
use Raju\QuickBooksMcp\Tools\Purchase\GetPurchaseTool;
use Raju\QuickBooksMcp\Tools\Purchase\SearchPurchasesTool;
use Raju\QuickBooksMcp\Tools\Purchase\UpdatePurchaseTool;
use Raju\QuickBooksMcp\Tools\Vendor\CreateVendorTool;
use Raju\QuickBooksMcp\Tools\Vendor\DeleteVendorTool;
use Raju\QuickBooksMcp\Tools\Vendor\GetVendorTool;
use Raju\QuickBooksMcp\Tools\Vendor\SearchVendorsTool;
use Raju\QuickBooksMcp\Tools\Vendor\UpdateVendorTool;

#[Name('QuickBooks Online')]
#[Version('1.0.0')]
#[Instructions(
    'This MCP server provides full access to QuickBooks Online. ' .
    'You can manage customers, vendors, invoices, bills, estimates, purchases, ' .
    'employees, items, accounts, journal entries, and payments. ' .
    'For search tools, pass human-readable names — ID resolution is handled automatically. ' .
    'All write operations require confirmation of key fields before executing.'
)]
class QuickBooksServer extends Server
{
    protected array $tools = [
        CreateAccountTool::class,
        SearchAccountsTool::class,
        UpdateAccountTool::class,
        CreateBillTool::class,
        DeleteBillTool::class,
        GetBillTool::class,
        SearchBillsTool::class,
        UpdateBillTool::class,
        CreateBillPaymentTool::class,
        DeleteBillPaymentTool::class,
        GetBillPaymentTool::class,
        SearchBillPaymentsTool::class,
        UpdateBillPaymentTool::class,
        CreateCustomerTool::class,
        DeleteCustomerTool::class,
        GetCustomerTool::class,
        SearchCustomersTool::class,
        UpdateCustomerTool::class,
        CreateEmployeeTool::class,
        GetEmployeeTool::class,
        SearchEmployeesTool::class,
        UpdateEmployeeTool::class,
        CreateEstimateTool::class,
        DeleteEstimateTool::class,
        GetEstimateTool::class,
        SearchEstimatesTool::class,
        UpdateEstimateTool::class,
        CreateInvoiceTool::class,
        ReadInvoiceTool::class,
        SearchInvoicesTool::class,
        UpdateInvoiceTool::class,
        CreateItemTool::class,
        ReadItemTool::class,
        SearchItemsTool::class,
        UpdateItemTool::class,
        CreateJournalEntryTool::class,
        DeleteJournalEntryTool::class,
        GetJournalEntryTool::class,
        SearchJournalEntriesTool::class,
        UpdateJournalEntryTool::class,
        CreatePurchaseTool::class,
        DeletePurchaseTool::class,
        GetPurchaseTool::class,
        SearchPurchasesTool::class,
        UpdatePurchaseTool::class,
        CreateVendorTool::class,
        DeleteVendorTool::class,
        GetVendorTool::class,
        SearchVendorsTool::class,
        UpdateVendorTool::class,
    ];
}
