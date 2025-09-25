<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\IInventoryRepository;
use App\Model\Inventory;
use App\Utility\Logger;
use RuntimeException;
use InvalidArgumentException;

class InventoryService implements IInventoryService
{
    private IInventoryRepository $inventoryRepo;
    private Logger $logger;

    public function __construct(IInventoryRepository $inventoryRepo, Logger $logger)
    {
        $this->inventoryRepo = $inventoryRepo;
        $this->logger = $logger;
    }

    public function getItem(string $id): ?array
    {
        try {
            $item = $this->inventoryRepo->findById($id);
            
            if (!$item) {
                $this->logger->warning('Inventory item not found', ['id' => $id]);
                return null;
            }

            return $item;
        } catch (\Exception $e) {
            $this->logger->error('InventoryService::getItem failed: ' . $e->getMessage());
            throw new RuntimeException('Failed to get inventory item: ' . $e->getMessage());
        }
    }

    public function listItems(array $filter = [], array $options = []): array
    {
        try {
            // Default options
            $defaultOptions = [
                'limit' => 50,
                'skip' => 0,
                'sort' => ['name' => 1]
            ];

            $finalOptions = array_merge($defaultOptions, $options);

            $items = $this->inventoryRepo->find($filter, $finalOptions);
            $total = $this->inventoryRepo->count($filter);

            return [
                'items' => $items,
                'total' => $total,
                'limit' => $finalOptions['limit'],
                'skip' => $finalOptions['skip']
            ];
        } catch (\Exception $e) {
            $this->logger->error('InventoryService::listItems failed: ' . $e->getMessage());
            throw new RuntimeException('Failed to list inventory items: ' . $e->getMessage());
        }
    }

    public function createItem(array $data): array
    {
        try {
            // Validate data
            $errors = $this->validateItemData($data, true);
            if (!empty($errors)) {
                throw new InvalidArgumentException('Validation failed: ' . json_encode($errors));
            }

            // Create Inventory entity untuk validasi tambahan
            $inventory = new Inventory(
                $data['name'],
                $data['description'],
                $data['quantity'],
                $data['price'],
                $data['categoryId'] ?? null,
                $data['supplierId'] ?? null,
                $data['minStockLevel'] ?? 0
            );

            $inventory->validate();

            // Convert to document and use create() method from interface
            $document = $inventory->toDocument();
            $id = $this->inventoryRepo->create($document);
            
            $this->logger->info('Inventory item created successfully', [
                'id' => $id,
                'name' => $data['name']
            ]);

            return $this->getItem($id);

        } catch (InvalidArgumentException $e) {
            $this->logger->warning('InventoryService::createItem validation failed: ' . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('InventoryService::createItem failed: ' . $e->getMessage());
            throw new RuntimeException('Failed to create inventory item: ' . $e->getMessage());
        }
    }

    public function updateItem(string $id, array $data): array
    {
        try {
            // Check if item exists
            $existingItem = $this->getItem($id);
            if (!$existingItem) {
                throw new InvalidArgumentException('Inventory item not found');
            }

            // Validate data
            $errors = $this->validateItemData($data, false);
            if (!empty($errors)) {
                throw new InvalidArgumentException('Validation failed: ' . json_encode($errors));
            }

            // Update item
            $success = $this->inventoryRepo->update($id, $data);
            
            if (!$success) {
                throw new RuntimeException('Failed to update inventory item');
            }

            $this->logger->info('Inventory item updated successfully', ['id' => $id]);

            return $this->getItem($id);

        } catch (InvalidArgumentException $e) {
            $this->logger->warning('InventoryService::updateItem validation failed: ' . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('InventoryService::updateItem failed: ' . $e->getMessage());
            throw new RuntimeException('Failed to update inventory item: ' . $e->getMessage());
        }
    }

    public function deleteItem(string $id): bool
    {
        try {
            // Check if item exists
            $existingItem = $this->getItem($id);
            if (!$existingItem) {
                throw new InvalidArgumentException('Inventory item not found');
            }

            $success = $this->inventoryRepo->delete($id);
            
            if ($success) {
                $this->logger->info('Inventory item deleted successfully', ['id' => $id]);
            }

            return $success;

        } catch (\Exception $e) {
            $this->logger->error('InventoryService::deleteItem failed: ' . $e->getMessage());
            throw new RuntimeException('Failed to delete inventory item: ' . $e->getMessage());
        }
    }

    public function getLowStockItems(int $threshold = 0): array
    {
        try {
            return $this->inventoryRepo->findLowStock($threshold);
        } catch (\Exception $e) {
            $this->logger->error('InventoryService::getLowStockItems failed: ' . $e->getMessage());
            throw new RuntimeException('Failed to get low stock items: ' . $e->getMessage());
        }
    }

    public function getOutOfStockItems(): array
    {
        try {
            return $this->inventoryRepo->findOutOfStock();
        } catch (\Exception $e) {
            $this->logger->error('InventoryService::getOutOfStockItems failed: ' . $e->getMessage());
            throw new RuntimeException('Failed to get out of stock items: ' . $e->getMessage());
        }
    }

    public function updateItemQuantity(string $id, int $quantityChange): array
    {
        try {
            // Check if item exists
            $existingItem = $this->getItem($id);
            if (!$existingItem) {
                throw new InvalidArgumentException('Inventory item not found');
            }

            $success = $this->inventoryRepo->updateQuantity($id, $quantityChange);
            
            if (!$success) {
                throw new RuntimeException('Failed to update item quantity');
            }

            $this->logger->info('Inventory quantity updated', [
                'id' => $id,
                'quantityChange' => $quantityChange
            ]);

            return $this->getItem($id);

        } catch (\Exception $e) {
            $this->logger->error('InventoryService::updateItemQuantity failed: ' . $e->getMessage());
            throw new RuntimeException('Failed to update item quantity: ' . $e->getMessage());
        }
    }

    public function getInventoryStats(): array
    {
        try {
            $stats = $this->inventoryRepo->getStats();
            
            // Add additional calculated stats
            $stats['healthStatus'] = $this->calculateInventoryHealth($stats);
            
            return $stats;
        } catch (\Exception $e) {
            $this->logger->error('InventoryService::getInventoryStats failed: ' . $e->getMessage());
            throw new RuntimeException('Failed to get inventory stats: ' . $e->getMessage());
        }
    }

    public function searchItems(string $query, array $options = []): array
    {
        try {
            $filter = [
                '$or' => [
                    ['name' => ['$regex' => $query, '$options' => 'i']],
                    ['description' => ['$regex' => $query, '$options' => 'i']]
                ]
            ];

            return $this->listItems($filter, $options);
        } catch (\Exception $e) {
            $this->logger->error('InventoryService::searchItems failed: ' . $e->getMessage());
            throw new RuntimeException('Failed to search inventory items: ' . $e->getMessage());
        }
    }

    public function validateItemData(array $data, bool $isCreate = true): array
    {
        $errors = [];

        if ($isCreate) {
            // Validation for create
            if (empty($data['name'])) {
                $errors['name'] = 'Name is required';
            } elseif (strlen($data['name']) < 2) {
                $errors['name'] = 'Name must be at least 2 characters';
            }

            if (empty($data['description'])) {
                $errors['description'] = 'Description is required';
            }

            if (!isset($data['quantity']) || $data['quantity'] === '') {
                $errors['quantity'] = 'Quantity is required';
            } elseif (!is_numeric($data['quantity']) || $data['quantity'] < 0) {
                $errors['quantity'] = 'Quantity must be a non-negative number';
            }

            if (!isset($data['price']) || $data['price'] === '') {
                $errors['price'] = 'Price is required';
            } elseif (!is_numeric($data['price']) || $data['price'] < 0) {
                $errors['price'] = 'Price must be a non-negative number';
            }
        } else {
            // Validation for update
            if (isset($data['name']) && empty(trim($data['name']))) {
                $errors['name'] = 'Name cannot be empty';
            }

            if (isset($data['description']) && empty(trim($data['description']))) {
                $errors['description'] = 'Description cannot be empty';
            }

            if (isset($data['quantity']) && (!is_numeric($data['quantity']) || $data['quantity'] < 0)) {
                $errors['quantity'] = 'Quantity must be a non-negative number';
            }

            if (isset($data['price']) && (!is_numeric($data['price']) || $data['price'] < 0)) {
                $errors['price'] = 'Price must be a non-negative number';
            }
        }

        if (isset($data['minStockLevel']) && (!is_numeric($data['minStockLevel']) || $data['minStockLevel'] < 0)) {
            $errors['minStockLevel'] = 'Minimum stock level must be a non-negative number';
        }

        return $errors;
    }

    /**
     * Calculate inventory health status based on stats
     */
    private function calculateInventoryHealth(array $stats): string
    {
        if (!isset($stats['outOfStockCount']) || !isset($stats['totalItems'])) {
            return 'unknown';
        }

        $outOfStockPercentage = $stats['totalItems'] > 0 
            ? ($stats['outOfStockCount'] / $stats['totalItems']) * 100 
            : 0;

        if ($outOfStockPercentage > 20) {
            return 'critical';
        } elseif ($outOfStockPercentage > 10) {
            return 'warning';
        } elseif ($outOfStockPercentage > 5) {
            return 'fair';
        } else {
            return 'healthy';
        }
    }

    /**
     * Get items by category
     */
    public function getItemsByCategory(string $categoryId, array $options = []): array
    {
        try {
            $filter = ['categoryId' => $categoryId];
            return $this->listItems($filter, $options);
        } catch (\Exception $e) {
            $this->logger->error('InventoryService::getItemsByCategory failed: ' . $e->getMessage());
            throw new RuntimeException('Failed to get items by category: ' . $e->getMessage());
        }
    }

    /**
     * Get items by supplier
     */
    public function getItemsBySupplier(string $supplierId, array $options = []): array
    {
        try {
            $filter = ['supplierId' => $supplierId];
            return $this->listItems($filter, $options);
        } catch (\Exception $e) {
            $this->logger->error('InventoryService::getItemsBySupplier failed: ' . $e->getMessage());
            throw new RuntimeException('Failed to get items by supplier: ' . $e->getMessage());
        }
    }
}