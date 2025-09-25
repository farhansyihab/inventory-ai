<?php
declare(strict_types=1);

namespace App\Repository;

/**
 * Interface untuk Inventory Repository
 */
interface IInventoryRepository
{
    /**
     * Find inventory items dengan filter dan options
     */
    public function find(array $filter = [], array $options = []): array;

    /**
     * Find inventory item by ID
     */
    public function findById(string $id): ?array;

    /**
     * Find one inventory item dengan filter
     */
    public function findOne(array $filter = []): ?array;

    /**
     * Create new inventory item
     */
    public function create(array $data): string;

    /**
     * Update inventory item by ID
     */
    public function update(string $id, array $data): bool;

    /**
     * Delete inventory item by ID
     */
    public function delete(string $id): bool;

    /**
     * Count inventory items dengan filter
     */
    public function count(array $filter = []): int;

    /**
     * Find low stock items
     */
    public function findLowStock(int $threshold = 0): array;

    /**
     * Find out of stock items
     */
    public function findOutOfStock(): array;

    /**
     * Update inventory quantity
     */
    public function updateQuantity(string $id, int $quantityChange): bool;

    /**
     * Get inventory statistics
     */
    public function getStats(): array;

    /**
     * Aggregate inventory data
     */
    public function aggregate(array $pipeline): array;
}