<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2026 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Raju\QuickBooksMcp\Tools\Employee;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description('Get a single QuickBooks employee by their ID.')]
class GetEmployeeTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id' => ['type' => 'string', 'description' => 'QuickBooks employee ID'],
            ],
            'required' => ['id'],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $employee = $qb->findById('Employee', $request->get('id'));

            if (!$employee) {
                return Response::text("Employee ID {$request->get('id')} not found.");
            }

            return Response::text(json_encode([
                'id'          => $employee->Id,
                'name'        => $employee->DisplayName,
                'first_name'  => $employee->GivenName ?? null,
                'last_name'   => $employee->FamilyName ?? null,
                'email'       => $employee->PrimaryEmailAddr->Address ?? null,
                'phone'       => $employee->PrimaryPhone->FreeFormNumber ?? null,
                'active'      => $employee->Active,
                'sync_token'  => $employee->SyncToken,
            ], JSON_PRETTY_PRINT));

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
