<?php
// tests/Unit/Repository/InventoryRepositoryTest.php
declare(strict_types=1);

namespace Tests\Unit\Repository;

use PHPUnit\Framework\TestCase;
use App\Repository\InventoryRepository;
use App\Config\MongoDBManager;
use App\Model\Inventory;
use MongoDB\BSON\ObjectId;
use Psr\Log\NullLogger;

class InventoryRepositoryTest extends TestCase
{
    private InventoryRepository $inventoryRepo;
    private \MongoDB\Collection $collection;

    protected function setUp(): void
    {
        // Gunakan test database
        $this->inventoryRepo = new InventoryRepository(new NullLogger());
        $this->collection = MongoDBManager::getCollection('inventory');
        
        // Bersihkan collection sebelum setiap test
        $this->collection->deleteMany([]);
    }

    protected function tearDown(): void
    {
        // Bersihkan collection setelah setiap test
        $this->collection->deleteMany([]);
    }

    public function testCreateInventoryItem(): void
    {
        $inventoryData = [
            'name' => 'Test Product',
            'description' => 'Test Description',
            'quantity' => 10,
            'price' => 99.99,
            'categoryId' => 'cat1',
            'supplierId' => 'sup1',
            'minStockLevel' => 5,
            'createdAt' => new \MongoDB\BSON\UTCDateTime(),
            'updatedAt' => new \MongoDB\BSON\UTCDateTime()
        ];

        $id = $this->inventoryRepo->create($inventoryData);
        
        $this->assertNotEmpty($id);
        // Ganti ObjectId::isValid() dengan regex check untuk MongoDB ObjectId
        $this->assertMatchesRegularExpression('/^[a-f\d]{24}$/i', $id);
    }

    public function testFindInventoryItemById(): void
    {
        // First create an item
        $inventoryData = [
            'name' => 'Find Test Product',
            'description' => 'Test Description',
            'quantity' => 15,
            'price' => 49.99,
            'createdAt' => new \MongoDB\BSON\UTCDateTime(),
            'updatedAt' => new \MongoDB\BSON\UTCDateTime()
        ];

        $id = $this->inventoryRepo->create($inventoryData);
        
        // Then find it
        $item = $this->inventoryRepo->findById($id);
        
        $this->assertNotNull($item);
        $this->assertEquals('Find Test Product', $item['name']);
        $this->assertEquals(15, $item['quantity']);
        $this->assertEquals(49.99, $item['price']);
    }

    public function testUpdateInventoryItem(): void
    {
        // Create item first
        $inventoryData = [
            'name' => 'Original Name',
            'description' => 'Original Description',
            'quantity' => 10,
            'price' => 29.99,
            'createdAt' => new \MongoDB\BSON\UTCDateTime(),
            'updatedAt' => new \MongoDB\BSON\UTCDateTime()
        ];

        $id = $this->inventoryRepo->create($inventoryData);
        
        // Update the item
        $updateData = [
            'name' => 'Updated Name',
            'quantity' => 20,
            'price' => 39.99
        ];

        $success = $this->inventoryRepo->update($id, $updateData);
        
        $this->assertTrue($success);
        
        // Verify the update
        $updatedItem = $this->inventoryRepo->findById($id);
        $this->assertEquals('Updated Name', $updatedItem['name']);
        $this->assertEquals(20, $updatedItem['quantity']);
        $this->assertEquals(39.99, $updatedItem['price']);
    }

    public function testDeleteInventoryItem(): void
    {
        // Create item first
        $inventoryData = [
            'name' => 'Item to Delete',
            'description' => 'Will be deleted',
            'quantity' => 5,
            'price' => 19.99,
            'createdAt' => new \MongoDB\BSON\UTCDateTime(),
            'updatedAt' => new \MongoDB\BSON\UTCDateTime()
        ];

        $id = $this->inventoryRepo->create($inventoryData);
        
        // Verify it exists
        $item = $this->inventoryRepo->findById($id);
        $this->assertNotNull($item);
        
        // Delete it
        $success = $this->inventoryRepo->delete($id);
        $this->assertTrue($success);
        
        // Verify it's gone
        $deletedItem = $this->inventoryRepo->findById($id);
        $this->assertNull($deletedItem);
    }

    public function testFindLowStockItems(): void
    {
        // Create items with different stock levels
        $lowStockItem = [
            'name' => 'Low Stock Item',
            'description' => 'Low stock',
            'quantity' => 3,
            'price' => 10.99,
            'minStockLevel' => 5,
            'createdAt' => new \MongoDB\BSON\UTCDateTime(),
            'updatedAt' => new \MongoDB\BSON\UTCDateTime()
        ];

        $goodStockItem = [
            'name' => 'Good Stock Item',
            'description' => 'Good stock',
            'quantity' => 20,
            'price' => 15.99,
            'minStockLevel' => 5,
            'createdAt' => new \MongoDB\BSON\UTCDateTime(),
            'updatedAt' => new \MongoDB\BSON\UTCDateTime()
        ];

        $this->inventoryRepo->create($lowStockItem);
        $this->inventoryRepo->create($goodStockItem);
        
        // Test findLowStock with threshold
        $lowStockItems = $this->inventoryRepo->findLowStock(5);
        
        $this->assertCount(1, $lowStockItems);
        $this->assertEquals('Low Stock Item', $lowStockItems[0]['name']);
    }

    public function testFindOutOfStockItems(): void
    {
        // Create items with different stock levels
        $outOfStockItem = [
            'name' => 'Out of Stock Item',
            'description' => 'No stock',
            'quantity' => 0,
            'price' => 25.99,
            'createdAt' => new \MongoDB\BSON\UTCDateTime(),
            'updatedAt' => new \MongoDB\BSON\UTCDateTime()
        ];

        $inStockItem = [
            'name' => 'In Stock Item',
            'description' => 'Has stock',
            'quantity' => 10,
            'price' => 35.99,
            'createdAt' => new \MongoDB\BSON\UTCDateTime(),
            'updatedAt' => new \MongoDB\BSON\UTCDateTime()
        ];

        $this->inventoryRepo->create($outOfStockItem);
        $this->inventoryRepo->create($inStockItem);
        
        $outOfStockItems = $this->inventoryRepo->findOutOfStock();
        
        $this->assertCount(1, $outOfStockItems);
        $this->assertEquals('Out of Stock Item', $outOfStockItems[0]['name']);
    }

    public function testUpdateQuantity(): void
    {
        // Create item first
        $inventoryData = [
            'name' => 'Quantity Test Item',
            'description' => 'For quantity testing',
            'quantity' => 10,
            'price' => 9.99,
            'createdAt' => new \MongoDB\BSON\UTCDateTime(),
            'updatedAt' => new \MongoDB\BSON\UTCDateTime()
        ];

        $id = $this->inventoryRepo->create($inventoryData);
        
        // Increase quantity
        $success = $this->inventoryRepo->updateQuantity($id, 5);
        $this->assertTrue($success);
        
        $item = $this->inventoryRepo->findById($id);
        $this->assertEquals(15, $item['quantity']);
        
        // Decrease quantity
        $success = $this->inventoryRepo->updateQuantity($id, -3);
        $this->assertTrue($success);
        
        $item = $this->inventoryRepo->findById($id);
        $this->assertEquals(12, $item['quantity']);
    }

public function testCountInventoryItems(): void
{
    // Create some items dengan quantity yang berbeda
    $items = [
        ['quantity' => 10, 'price' => 5.99],
        ['quantity' => 20, 'price' => 10.99],
        ['quantity' => 30, 'price' => 15.99]
    ];
    
    foreach ($items as $itemData) {
        $this->inventoryRepo->create([
            'name' => "Item with quantity {$itemData['quantity']}",
            'description' => "Description",
            'quantity' => $itemData['quantity'],
            'price' => $itemData['price'],
            'createdAt' => new \MongoDB\BSON\UTCDateTime(),
            'updatedAt' => new \MongoDB\BSON\UTCDateTime()
        ]);
    }
    
    $count = $this->inventoryRepo->count();
    $this->assertEquals(3, $count);
    
    // Test dengan filter yang lebih spesifik
    $filteredCount = $this->inventoryRepo->count(['quantity' => ['$gt' => 25]]);
    $this->assertEquals(1, $filteredCount); // Hanya item dengan quantity 30
    
    // Test filter lainnya
    $filteredCount2 = $this->inventoryRepo->count(['quantity' => ['$gte' => 20]]);
    $this->assertEquals(2, $filteredCount2); // Item dengan quantity 20 dan 30
}
}