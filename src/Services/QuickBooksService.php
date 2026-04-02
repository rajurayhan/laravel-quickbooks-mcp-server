<?php
/*
 * This file is part of the Laravel QuickBooks MCP package.
 *
 * Copyright (c) 2026 Raju Rayhan
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Raju\QuickBooksMcp\Services;

use QuickBooksOnline\API\DataService\DataService;
use Raju\QuickBooksMcp\Exceptions\QuickBooksAuthException;
use Raju\QuickBooksMcp\Exceptions\QuickBooksToolException;

class QuickBooksService
{
    public function __construct(protected DataService $dataService) {}

    public function dataService(): DataService
    {
        return $this->dataService;
    }

    public function query(string $sql): array
    {
        $results = $this->dataService->Query($sql);
        $this->throwIfError($this->dataService);
        return $results ?? [];
    }

    public function findById(string $entityType, string $id): mixed
    {
        $result = $this->dataService->FindById($entityType, $id);
        $this->throwIfError($this->dataService);
        return $result;
    }

    public function create(mixed $entity): mixed
    {
        $result = $this->dataService->Add($entity);
        $this->throwIfError($this->dataService);
        return $result;
    }

    public function update(mixed $entity): mixed
    {
        $result = $this->dataService->Update($entity);
        $this->throwIfError($this->dataService);
        return $result;
    }

    public function delete(mixed $entity): mixed
    {
        $result = $this->dataService->Delete($entity);
        $this->throwIfError($this->dataService);
        return $result;
    }

    public function resolveCustomerId(string $name): ?string
    {
        $results = $this->query(
            "SELECT Id FROM Customer WHERE DisplayName = '{$this->escape($name)}' MAXRESULTS 1"
        );
        return $results[0]->Id ?? null;
    }

    public function resolveVendorId(string $name): ?string
    {
        $results = $this->query(
            "SELECT Id FROM Vendor WHERE DisplayName = '{$this->escape($name)}' MAXRESULTS 1"
        );
        return $results[0]->Id ?? null;
    }

    public function resolveAccountId(string $name): ?string
    {
        $results = $this->query(
            "SELECT Id FROM Account WHERE Name = '{$this->escape($name)}' MAXRESULTS 1"
        );
        return $results[0]->Id ?? null;
    }

    public function resolveItemId(string $name): ?string
    {
        $results = $this->query(
            "SELECT Id FROM Item WHERE Name = '{$this->escape($name)}' MAXRESULTS 1"
        );
        return $results[0]->Id ?? null;
    }

    protected function throwIfError(DataService $ds): void
    {
        $error = $ds->getLastError();
        if ($error) {
            throw new QuickBooksToolException(
                $error->getIntuitErrorMessage() ?? 'Unknown QuickBooks error',
                (int) ($error->getIntuitErrorCode() ?? 0)
            );
        }
    }

    protected function escape(string $value): string
    {
        return str_replace("'", "\\'", $value);
    }
}
