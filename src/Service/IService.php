<?php
declare(strict_types=1);

namespace App\Service;

/**
 * Base Service Interface
 * Provides common contract for all service classes
 * 
 * @template T Entity type
 */
interface IService
{
    /**
     * Find entity by ID
     * 
     * @param string $id Entity ID
     * @return array|null Entity data or null if not found
     * @throws \InvalidArgumentException If ID format is invalid
     */
    public function findById(string $id): ?array;

    /**
     * Find entities by criteria
     * 
     * @param array $filter Query filter
     * @param array $options Find options (sort, limit, skip, etc.)
     * @return array Array of entities
     * @throws \RuntimeException If database operation fails
     */
    public function find(array $filter = [], array $options = []): array;

    /**
     * Create new entity
     * 
     * @param array $data Entity data
     * @return array Created entity data with ID
     * @throws \InvalidArgumentException If validation fails
     * @throws \RuntimeException If creation fails
     */
    public function create(array $data): array;

    /**
     * Update entity by ID
     * 
     * @param string $id Entity ID
     * @param array $data Update data
     * @return bool True if update successful
     * @throws \InvalidArgumentException If ID format is invalid or validation fails
     * @throws \RuntimeException If update operation fails
     */
    public function update(string $id, array $data): bool;

    /**
     * Delete entity by ID
     * 
     * @param string $id Entity ID
     * @return bool True if delete successful
     * @throws \InvalidArgumentException If ID format is invalid
     * @throws \RuntimeException If delete operation fails
     */
    public function delete(string $id): bool;

    /**
     * Count entities by criteria
     * 
     * @param array $filter Query filter
     * @return int Number of matching entities
     * @throws \RuntimeException If count operation fails
     */
    public function count(array $filter = []): int;

    /**
     * Validate entity data
     * 
     * @param array $data Entity data to validate
     * @return bool True if valid
     * @throws \InvalidArgumentException If validation fails with detailed errors
     */
    public function validate(array $data): bool;

    /**
     * Find one entity by criteria
     * 
     * @param array $filter Query filter
     * @return array|null Entity data or null if not found
     * @throws \RuntimeException If database operation fails
     */
    public function findOne(array $filter = []): ?array;
}