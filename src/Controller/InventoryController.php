<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\InventoryService;
use App\Utility\Logger;
use InvalidArgumentException;
use RuntimeException;

class InventoryController extends BaseController
{
    private InventoryService $inventoryService;

    public function __construct(?InventoryService $inventoryService = null, ?Logger $logger = null)
    {
        parent::__construct($logger);
        $this->inventoryService = $inventoryService ?? new InventoryService(
            new \App\Repository\InventoryRepository(),
            new Logger()
        );
    }

    /**
     * Get all inventory items
     */
    public function listItems(): void
    {
        try {
            $pagination = $this->getPaginationParams();
            $filter = $this->getRequestData();

            // Remove pagination parameters from filter
            unset($filter['page'], $filter['limit']);

            $result = $this->inventoryService->listItems($filter, [
                'limit' => $pagination['limit'],
                'skip' => $pagination['offset'],
                'sort' => ['name' => 1]
            ]);

            $this->logAction('inventory_list', [
                'total_items' => $result['total'],
                'page' => $pagination['page'],
                'limit' => $pagination['limit']
            ]);

            $this->successResponse([
                'items' => $result['items'],
                'pagination' => [
                    'page' => $pagination['page'],
                    'limit' => $pagination['limit'],
                    'total' => $result['total'],
                    'pages' => ceil($result['total'] / $pagination['limit'])
                ]
            ], 'Inventory items retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->error("InventoryController::listItems unexpected error: " . $e->getMessage());
            $this->errorResponse('Failed to retrieve inventory items', [], 500);
        }
    }

    /**
     * Get inventory item by ID
     */
    public function getItem(string $id): void
    {
        try {
            $item = $this->inventoryService->getItem($id);
            
            if (!$item) {
                $this->notFoundResponse('Inventory item not found');
                return;
            }

            $this->logAction('inventory_view', ['item_id' => $id]);
            $this->successResponse(['item' => $item], 'Inventory item retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->error("InventoryController::getItem unexpected error: " . $e->getMessage());
            $this->errorResponse('Failed to retrieve inventory item', [], 500);
        }
    }

    /**
     * Create new inventory item
     */
    public function createItem(): void
    {
        try {
            $requestData = $this->getRequestData();

            $item = $this->inventoryService->createItem($requestData);

            $this->logAction('inventory_create', [
                'item_id' => $item['id'],
                'item_name' => $item['name']
            ]);

            $this->successResponse(['item' => $item], 'Inventory item created successfully', 201);

        } catch (InvalidArgumentException $e) {
            $this->validationErrorResponse(['general' => $e->getMessage()]);
        } catch (RuntimeException $e) {
            $this->errorResponse($e->getMessage(), [], 400);
        } catch (\Exception $e) {
            $this->logger->error("InventoryController::createItem unexpected error: " . $e->getMessage());
            $this->errorResponse('Failed to create inventory item', [], 500);
        }
    }

    /**
     * Update inventory item
     */
    public function updateItem(string $id): void
    {
        try {
            $requestData = $this->getRequestData();

            $item = $this->inventoryService->updateItem($id, $requestData);

            $this->logAction('inventory_update', ['item_id' => $id]);
            $this->successResponse(['item' => $item], 'Inventory item updated successfully');

        } catch (InvalidArgumentException $e) {
            $this->validationErrorResponse(['general' => $e->getMessage()]);
        } catch (RuntimeException $e) {
            $this->errorResponse($e->getMessage(), [], 400);
        } catch (\Exception $e) {
            $this->logger->error("InventoryController::updateItem unexpected error: " . $e->getMessage());
            $this->errorResponse('Failed to update inventory item', [], 500);
        }
    }

    /**
     * Delete inventory item
     */
    public function deleteItem(string $id): void
    {
        try {
            $success = $this->inventoryService->deleteItem($id);

            if ($success) {
                $this->logAction('inventory_delete', ['item_id' => $id]);
                $this->successResponse([], 'Inventory item deleted successfully');
            } else {
                $this->errorResponse('Failed to delete inventory item', [], 400);
            }

        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), [], 404);
        } catch (RuntimeException $e) {
            $this->errorResponse($e->getMessage(), [], 400);
        } catch (\Exception $e) {
            $this->logger->error("InventoryController::deleteItem unexpected error: " . $e->getMessage());
            $this->errorResponse('Failed to delete inventory item', [], 500);
        }
    }

    /**
     * Get low stock items
     */
    public function getLowStock(): void
    {
        try {
            $threshold = (int) $this->getRequestValue('threshold', 0);
            $items = $this->inventoryService->getLowStockItems($threshold);

            $this->logAction('inventory_low_stock', ['threshold' => $threshold]);
            $this->successResponse(['items' => $items], 'Low stock items retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->error("InventoryController::getLowStock unexpected error: " . $e->getMessage());
            $this->errorResponse('Failed to retrieve low stock items', [], 500);
        }
    }

    /**
     * Get out of stock items
     */
    public function getOutOfStock(): void
    {
        try {
            $items = $this->inventoryService->getOutOfStockItems();

            $this->logAction('inventory_out_of_stock');
            $this->successResponse(['items' => $items], 'Out of stock items retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->error("InventoryController::getOutOfStock unexpected error: " . $e->getMessage());
            $this->errorResponse('Failed to retrieve out of stock items', [], 500);
        }
    }

    /**
     * Get inventory statistics
     */
    public function getStats(): void
    {
        try {
            $stats = $this->inventoryService->getInventoryStats();

            $this->logAction('inventory_stats');
            $this->successResponse(['stats' => $stats], 'Inventory statistics retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->error("InventoryController::getStats unexpected error: " . $e->getMessage());
            $this->errorResponse('Failed to retrieve inventory statistics', [], 500);
        }
    }

    /**
     * Search inventory items
     */
    public function searchItems(): void
    {
        try {
            $query = $this->getRequestValue('q', '');
            $pagination = $this->getPaginationParams();

            if (empty($query)) {
                $this->validationErrorResponse(['q' => 'Search query is required']);
                return;
            }

            $result = $this->inventoryService->searchItems($query, [
                'limit' => $pagination['limit'],
                'skip' => $pagination['offset']
            ]);

            $this->logAction('inventory_search', ['query' => $query]);
            $this->successResponse([
                'items' => $result['items'],
                'pagination' => [
                    'page' => $pagination['page'],
                    'limit' => $pagination['limit'],
                    'total' => $result['total'],
                    'pages' => ceil($result['total'] / $pagination['limit'])
                ]
            ], 'Search results retrieved successfully');

        } catch (\Exception $e) {
            $this->logger->error("InventoryController::searchItems unexpected error: " . $e->getMessage());
            $this->errorResponse('Failed to search inventory items', [], 500);
        }
    }

    /**
     * Update item quantity
     */
    public function updateQuantity(string $id): void
    {
        try {
            $requestData = $this->getRequestData();

            if (!isset($requestData['quantityChange'])) {
                $this->validationErrorResponse(['quantityChange' => 'Quantity change is required']);
                return;
            }

            $quantityChange = (int) $requestData['quantityChange'];
            $item = $this->inventoryService->updateItemQuantity($id, $quantityChange);

            $this->logAction('inventory_quantity_update', [
                'item_id' => $id,
                'quantity_change' => $quantityChange
            ]);

            $this->successResponse(['item' => $item], 'Item quantity updated successfully');

        } catch (InvalidArgumentException $e) {
            $this->validationErrorResponse(['general' => $e->getMessage()]);
        } catch (RuntimeException $e) {
            $this->errorResponse($e->getMessage(), [], 400);
        } catch (\Exception $e) {
            $this->logger->error("InventoryController::updateQuantity unexpected error: " . $e->getMessage());
            $this->errorResponse('Failed to update item quantity', [], 500);
        }
    }
}