<?php
declare(strict_types=1);

namespace App\Service;

/**
 * Interface untuk Inventory Service
 */
interface IInventoryService
{
    /**
     * Get inventory item by ID
     */
    public function getItem(string $id): ?array;

    /**
     * Get all inventory items dengan filter dan pagination
     */
    public function listItems(array $filter = [], array $options = []): array;

    /**
     * Create new inventory item
     */
    public function createItem(array $data): array;

    /**
     * Update inventory item
     */
    public function updateItem(string $id, array $data): array;

    /**
     * Delete inventory item
     */
    public function deleteItem(string $id): bool;

    /**
     * Get low stock items
     */
    public function getLowStockItems(int $threshold = 0): array;

    /**
     * Get out of stock items
     */
    public function getOutOfStockItems(): array;

    /**
     * Update item quantity (increase/decrease)
     */
    public function updateItemQuantity(string $id, int $quantityChange): array;

    /**
     * Get inventory statistics
     */
    public function getInventoryStats(): array;

    /**
     * Search inventory items
     */
    public function searchItems(string $query, array $options = []): array;

    /**
     * Validate inventory item data
     */
    public function validateItemData(array $data, bool $isCreate = true): array;

    /**
     * Save inventory entity (create or update)
    * public function saveInventory(Inventory $inventory): string;  
    */  
}