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

#[Description('Update an existing QuickBooks employee. Only pass fields you want to change.')]
class UpdateEmployeeTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id'           => ['type' => 'string',  'description' => 'QuickBooks employee ID (required)'],
                'display_name' => ['type' => 'string',  'description' => 'Display name'],
                'first_name'   => ['type' => 'string',  'description' => 'First name'],
                'last_name'    => ['type' => 'string',  'description' => 'Last name'],
                'email'        => ['type' => 'string',  'description' => 'Primary email address'],
                'phone'        => ['type' => 'string',  'description' => 'Primary phone number'],
                'active'       => ['type' => 'boolean', 'description' => 'Active status'],
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

            if ($request->has('display_name')) $employee->DisplayName = $request->get('display_name');
            if ($request->has('first_name'))   $employee->GivenName   = $request->get('first_name');
            if ($request->has('last_name'))    $employee->FamilyName  = $request->get('last_name');
            if ($request->has('active'))       $employee->Active      = $request->get('active');

            if ($request->has('email')) {
                $employee->PrimaryEmailAddr          ??= new \stdClass();
                $employee->PrimaryEmailAddr->Address = $request->get('email');
            }
            if ($request->has('phone')) {
                $employee->PrimaryPhone                 ??= new \stdClass();
                $employee->PrimaryPhone->FreeFormNumber = $request->get('phone');
            }

            $updated = $qb->update($employee);

            return Response::text(
                "Employee updated.\nID: {$updated->Id} | Name: {$updated->DisplayName}"
            );

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
