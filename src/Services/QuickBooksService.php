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

use Spinen\QuickBooks\Client as QBClient;
use QuickBooksOnline\API\DataService\DataService;
use Raju\QuickBooksMcp\Exceptions\QuickBooksAuthException;
use Raju\QuickBooksMcp\Exceptions\QuickBooksToolException;

class QuickBooksService
{
    protected QBClient $client;
    protected ?string $realmId = null;

    public function __construct(QBClient $client)
    {
        $this->client = $client;
    }

    public function forRealm(string $realmId): static
    {
        $clone = clone $this;
        $clone->realmId = $realmId;
        return $clone;
    }

    public function dataService(): DataService
    {
        try {
            return $this->client->getDataService();
        } catch (\Exception $e) {
            throw new QuickBooksAuthException(
                'Failed to connect to QuickBooks: ' . $e->getMessage()
            );
        }
    }

    public function query(string $sql): array
    {
        $ds      = $this->dataService();
        $results = $ds->Query($sql);
        $this->throwIfError($ds);
        return $results ?? [];
    }

    public function findById(string $entityType, string $id): mixed
    {
        $ds     = $this->dataService();
        $result = $ds->FindById($entityType, $id);
        $this->throwIfError($ds);
        return $result;
    }

    public function create(mixed $entity): mixed
    {
        $ds     = $this->dataService();
        $result = $ds->Add($entity);
        $this->throwIfError($ds);
        return $result;
    }

    public function update(mixed $entity): mixed
    {
        $ds     = $this->dataService();
        $result = $ds->Update($entity);
        $this->throwIfError($ds);
        return $result;
    }

    public function delete(mixed $entity): mixed
    {
        $ds     = $this->dataService();
        $result = $ds->Delete($entity);
        $this->throwIfError($ds);
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
