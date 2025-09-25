<?php
declare(strict_types=1);

namespace Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use App\Service\InventoryService;
use App\Repository\IInventoryRepository;
use App\Utility\Logger;
use RuntimeException;
use InvalidArgumentException;

class InventoryServiceTest extends TestCase
{
    private InventoryService $inventoryService;
    private $inventoryRepoMock;
    private $loggerMock;

    protected function setUp(): void
    {
        $this->inventoryRepoMock = $this->createMock(IInventoryRepository::class);
        $this->loggerMock = $this->createMock(Logger::class);
        
        $this->inventoryService = new InventoryService(
            $this->inventoryRepoMock,
            $this->loggerMock
        );
    }

    public function testGetItemReturnsItemWhenExists(): void
    {
        $itemData = [
            'id' => '507f1f77bcf86cd799439011',
            'name' => 'Test Product',
            'description' => 'Test Description',
            'quantity' => 10,
            'price' => 99.99,
            'categoryId' => 'cat123',
            'supplierId' => 'sup456',
            'minStockLevel' => 5
        ];

        $this->inventoryRepoMock
            ->method('findById')
            ->with('507f1f77bcf86cd799439011')
            ->willReturn($itemData);

        $result = $this->inventoryService->getItem('507f1f77bcf86cd799439011');

        $this->assertNotNull($result);
        $this->assertEquals('Test Product', $result['name']);
        $this->assertEquals(10, $result['quantity']);
        $this->assertEquals(99.99, $result['price']);
    }

    public function testGetItemReturnsNullWhenNotFound(): void
    {
        $this->inventoryRepoMock
            ->method('findById')
            ->with('nonexistentid')
            ->willReturn(null);

        $result = $this->inventoryService->getItem('nonexistentid');

        $this->assertNull($result);
    }

    public function testCreateItemValidatesRequiredFields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation failed');

        $invalidData = [
            'name' => '', // Empty name
            'description' => '', // Empty description
            'quantity' => -5, // Negative quantity
            'price' => -10.0 // Negative price
        ];

        $this->inventoryService->createItem($invalidData);
    }

    public function testCreateItemSuccessfully(): void
    {
        $itemData = [
            'name' => 'Valid Product',
            'description' => 'Valid Description',
            'quantity' => 15,
            'price' => 49.99,
            'categoryId' => 'cat1',
            'supplierId' => 'sup1',
            'minStockLevel' => 3
        ];

        $savedItem = [
            'id' => '507f1f77bcf86cd799439012',
            'name' => 'Valid Product',
            'description' => 'Valid Description',
            'quantity' => 15,
            'price' => 49.99,
            'categoryId' => 'cat1',
            'supplierId' => 'sup1',
            'minStockLevel' => 3
        ];

        // Mock repository create method (bukan saveInventory)
        $this->inventoryRepoMock
            ->method('create')
            ->willReturn('507f1f77bcf86cd799439012');

        // Mock getItem to return the saved item
        $this->inventoryRepoMock
            ->method('findById')
            ->with('507f1f77bcf86cd799439012')
            ->willReturn($savedItem);

        // Expect logger to be called
        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with('Inventory item created successfully', $this->arrayHasKey('id'));

        $result = $this->inventoryService->createItem($itemData);

        $this->assertIsArray($result);
        $this->assertEquals('Valid Product', $result['name']);
        $this->assertEquals(15, $result['quantity']);
        $this->assertEquals(49.99, $result['price']);
    }

    public function testUpdateItemValidatesData(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $invalidData = [
            'quantity' => -10 // Negative quantity
        ];

        // Mock existing item
        $this->inventoryRepoMock
            ->method('findById')
            ->with('existingid')
            ->willReturn(['id' => 'existingid', 'name' => 'Test', 'quantity' => 5, 'price' => 10.0]);

        $this->inventoryService->updateItem('existingid', $invalidData);
    }

    public function testUpdateItemThrowsExceptionWhenItemNotFound(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Inventory item not found');

        $this->inventoryRepoMock
            ->method('findById')
            ->with('nonexistentid')
            ->willReturn(null);

        $this->inventoryService->updateItem('nonexistentid', ['name' => 'New Name']);
    }

    public function testDeleteItemReturnsTrueWhenSuccessful(): void
    {
        // Mock existing item
        $this->inventoryRepoMock
            ->method('findById')
            ->with('existingid')
            ->willReturn(['id' => 'existingid', 'name' => 'Test']);

        $this->inventoryRepoMock
            ->method('delete')
            ->with('existingid')
            ->willReturn(true);

        // Expect logger to be called
        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with('Inventory item deleted successfully', ['id' => 'existingid']);

        $result = $this->inventoryService->deleteItem('existingid');

        $this->assertTrue($result);
    }

    public function testGetLowStockItems(): void
    {
        $lowStockItems = [
            ['id' => '1', 'name' => 'Low Stock Item 1', 'quantity' => 2, 'minStockLevel' => 5],
            ['id' => '2', 'name' => 'Low Stock Item 2', 'quantity' => 3, 'minStockLevel' => 5]
        ];

        $this->inventoryRepoMock
            ->method('findLowStock')
            ->with(0)
            ->willReturn($lowStockItems);

        $result = $this->inventoryService->getLowStockItems();

        $this->assertCount(2, $result);
        $this->assertEquals('Low Stock Item 1', $result[0]['name']);
    }

    public function testGetOutOfStockItems(): void
    {
        $outOfStockItems = [
            ['id' => '1', 'name' => 'Out of Stock Item 1', 'quantity' => 0],
            ['id' => '2', 'name' => 'Out of Stock Item 2', 'quantity' => 0]
        ];

        $this->inventoryRepoMock
            ->method('findOutOfStock')
            ->willReturn($outOfStockItems);

        $result = $this->inventoryService->getOutOfStockItems();

        $this->assertCount(2, $result);
        $this->assertEquals('Out of Stock Item 1', $result[0]['name']);
    }

    public function testUpdateItemQuantitySuccessfully(): void
    {
        $existingItem = [
            'id' => 'item123',
            'name' => 'Test Item',
            'quantity' => 10,
            'price' => 15.99
        ];

        $updatedItem = [
            'id' => 'item123',
            'name' => 'Test Item',
            'quantity' => 15, // 10 + 5
            'price' => 15.99
        ];

        // Setup mock sequencing
        $this->inventoryRepoMock
            ->expects($this->exactly(2))
            ->method('findById')
            ->with('item123')
            ->willReturnOnConsecutiveCalls($existingItem, $updatedItem);

        $this->inventoryRepoMock
            ->method('updateQuantity')
            ->with('item123', 5)
            ->willReturn(true);

        $result = $this->inventoryService->updateItemQuantity('item123', 5);

        $this->assertEquals(15, $result['quantity']);
    }

    public function testValidateItemDataWithValidData(): void
    {
        $validData = [
            'name' => 'Valid Product',
            'description' => 'Valid Description',
            'quantity' => 10,
            'price' => 29.99,
            'minStockLevel' => 2
        ];

        $errors = $this->inventoryService->validateItemData($validData, true);

        $this->assertEmpty($errors);
    }

    public function testValidateItemDataWithInvalidData(): void
    {
        $invalidData = [
            'name' => '',
            'description' => '',
            'quantity' => -5,
            'price' => -10.0,
            'minStockLevel' => -1
        ];

        $errors = $this->inventoryService->validateItemData($invalidData, true);

        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('description', $errors);
        $this->assertArrayHasKey('quantity', $errors);
        $this->assertArrayHasKey('price', $errors);
        $this->assertArrayHasKey('minStockLevel', $errors);
    }

    public function testSearchItems(): void
    {
        $searchResults = [
            'items' => [
                ['id' => '1', 'name' => 'Test Product', 'description' => 'Test description'],
                ['id' => '2', 'name' => 'Another Test', 'description' => 'Another description']
            ],
            'total' => 2,
            'limit' => 10,
            'skip' => 0
        ];

        $this->inventoryRepoMock
            ->method('find')
            ->willReturn($searchResults['items']);

        $this->inventoryRepoMock
            ->method('count')
            ->willReturn(2);

        $result = $this->inventoryService->searchItems('test');

        $this->assertCount(2, $result['items']);
        $this->assertEquals(2, $result['total']);
    }

    public function testGetInventoryStats(): void
    {
        $stats = [
            'totalItems' => 10,
            'totalQuantity' => 100,
            'totalValue' => 2500.50,
            'avgPrice' => 250.05,
            'lowStockCount' => 2,
            'outOfStockCount' => 1
        ];

        $this->inventoryRepoMock
            ->method('getStats')
            ->willReturn($stats);

        $result = $this->inventoryService->getInventoryStats();

        $this->assertIsArray($result);
        $this->assertEquals(10, $result['totalItems']);
        $this->assertEquals(100, $result['totalQuantity']);
        $this->assertArrayHasKey('healthStatus', $result);
    }
}