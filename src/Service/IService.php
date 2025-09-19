<?php
declare(strict_types=1);

namespace App\Service;

/**
 * Base Service Interface
 * Provides common contract for all service classes
 */
interface IService
{
    /**
     * Find entity by ID
     * 
     * @param string $id Entity ID
     * @return array|null Entity data or null if not found
     */
    public function findById(string $id): ?array;

    /**
     * Find entities by criteria
     * 
     * @param array $filter Query filter
     * @param array $options Find options
     * @return array Array of entities
     */
    public function find(array $filter = [], array $options = []): array;

    /**
     * Create new entity
     * 
     * @param array $data Entity data
     * @return array Created entity data with ID
     * @throws \InvalidArgumentException
     */
    public function create(array $data): array;

    /**
     * Update entity by ID
     * 
     * @param string $id Entity ID
     * @param array $data Update data
     * @return bool True if update successful
     */
    public function update(string $id, array $data): bool;

    /**
     * Delete entity by ID
     * 
     * @param string $id Entity ID
     * @return bool True if delete successful
     */
    public function delete(string $id): bool;

    /**
     * Count entities by criteria
     * 
     * @param array $filter Query filter
     * @return int Number of matching entities
     */
    public function count(array $filter = []): int;

    /**
     * Validate entity data
     * 
     * @param array $data Entity data to validate
     * @return bool True if valid
     * @throws \InvalidArgumentException
     */
    public function validate(array $data): bool;
}