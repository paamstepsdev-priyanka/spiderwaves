<?php

namespace App\Models;

use CodeIgniter\Model;

abstract class UccBaseModel extends Model
{
    protected bool $syncMode = false;

    /**
     * Retrieve all active live records (deleted_at IS NULL).
     */
    public function getActiveRecords(): array
    {
        return $this->where('deleted_at IS NULL')->findAll();
    }

    /**
     * Find active live record by primary key (deleted_at IS NULL).
     */
    public function findActive($id)
    {
        return $this->where('deleted_at IS NULL')->find($id);
    }

    /**
     * Find active live record by private_key (deleted_at IS NULL).
     */
    public function findByPrivateKey(string $privateKey)
    {
        return $this->where('deleted_at IS NULL')
            ->where('private_key', $privateKey)
            ->first();
    }

    /**
     * Find any record by private_key (including soft-deleted).
     */
    public function findAnyByPrivateKey(string $privateKey)
    {
        return $this->where('private_key', $privateKey)->first();
    }

    /**
     * Find any record by ID (including soft-deleted).
     */
    public function findAnyById($id)
    {
        return $this->find($id);
    }

    /**
     * Safe execution context granting write permission strictly to synchronization routines.
     */
    public function executeSyncWrite(callable $callback)
    {
        $previous = $this->syncMode;
        $this->syncMode = true;
        try {
            return $callback($this);
        } finally {
            $this->syncMode = $previous;
        }
    }

    /**
     * Enforce read-only constraint: direct insert blocked unless in syncMode.
     */
    public function insert($data = null, bool $returnID = true)
    {
        if (!$this->syncMode) {
            throw new \RuntimeException(static::class . " is a read-only UCC master table. Direct insertions are prohibited.");
        }
        return parent::insert($data, $returnID);
    }

    /**
     * Enforce read-only constraint: direct update blocked unless in syncMode.
     */
    public function update($id = null, $data = null): bool
    {
        if (!$this->syncMode) {
            throw new \RuntimeException(static::class . " is a read-only UCC master table. Direct updates are prohibited.");
        }
        return parent::update($id, $data);
    }

    /**
     * Enforce read-only constraint: direct delete blocked unless in syncMode.
     */
    public function delete($id = null, bool $purge = false)
    {
        if (!$this->syncMode) {
            throw new \RuntimeException(static::class . " is a read-only UCC master table. Direct deletions are prohibited.");
        }
        return parent::delete($id, $purge);
    }
}

