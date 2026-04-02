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
use QuickBooksOnline\API\Facades\QBOFacade;
use Raju\QuickBooksMcp\Services\QuickBooksService;

#[Description('Create a new employee record in QuickBooks.')]
class CreateEmployeeTool extends Tool
{
    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'first_name'   => ['type' => 'string', 'description' => 'Employee first name'],
                'last_name'    => ['type' => 'string', 'description' => 'Employee last name'],
                'display_name' => ['type' => 'string', 'description' => 'Display name (optional, defaults to first + last)'],
                'email'        => ['type' => 'string', 'description' => 'Primary email address (optional)'],
                'phone'        => ['type' => 'string', 'description' => 'Primary phone number (optional)'],
            ],
            'required' => ['first_name', 'last_name'],
        ];
    }

    public function handle(Request $request, QuickBooksService $qb): Response
    {
        try {
            $employee = QBOFacade::create('Employee');
            $employee->GivenName  = $request->get('first_name');
            $employee->FamilyName = $request->get('last_name');
            $employee->DisplayName = $request->get(
                'display_name',
                trim($request->get('first_name') . ' ' . $request->get('last_name'))
            );

            if ($request->get('email')) {
                $employee->PrimaryEmailAddr          = new \stdClass();
                $employee->PrimaryEmailAddr->Address = $request->get('email');
            }
            if ($request->get('phone')) {
                $employee->PrimaryPhone                 = new \stdClass();
                $employee->PrimaryPhone->FreeFormNumber = $request->get('phone');
            }

            $created = $qb->create($employee);

            return Response::text(
                "Employee created.\nID: {$created->Id} | Name: {$created->DisplayName}"
            );

        } catch (\Exception $e) {
            return Response::text("Error: " . $e->getMessage());
        }
    }
}
