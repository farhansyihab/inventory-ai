<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use App\Model\Inventory;
use DateTime;

class InventoryTest extends TestCase
{
    public function testInventoryCreationWithValidData(): void
    {
        $inventory = new Inventory(
            'Laptop Dell XPS 13',
            'High-performance laptop with Intel i7 processor',
            10,
            1299.99,
            'cat123',
            'sup456',
            5
        );

        $this->assertEquals('Laptop Dell XPS 13', $inventory->getName());
        $this->assertEquals('High-performance laptop with Intel i7 processor', $inventory->getDescription());
        $this->assertEquals(10, $inventory->getQuantity());
        $this->assertEquals(1299.99, $inventory->getPrice());
        $this->assertEquals('cat123', $inventory->getCategoryId());
        $this->assertEquals('sup456', $inventory->getSupplierId());
        $this->assertEquals(5, $inventory->getMinStockLevel());
        $this->assertInstanceOf(DateTime::class, $inventory->getCreatedAt());
        $this->assertInstanceOf(DateTime::class, $inventory->getUpdatedAt());
    }

    public function testInventoryValidationWithInvalidName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new Inventory(
            '', // Empty name
            'Test description',
            10,
            100.0
        );
    }

    public function testInventoryValidationWithNegativeQuantity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new Inventory(
            'Test Item',
            'Test description',
            -5, // Negative quantity
            100.0
        );
    }

    public function testInventoryValidationWithNegativePrice(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new Inventory(
            'Test Item',
            'Test description',
            10,
            -50.0 // Negative price
        );
    }

    public function testIsLowStock(): void
    {
        $inventory = new Inventory('Test Item', 'Test description', 3, 100.0, null, null, 5);
        $this->assertTrue($inventory->isLowStock());

        $inventory2 = new Inventory('Test Item', 'Test description', 6, 100.0, null, null, 5);
        $this->assertFalse($inventory2->isLowStock());
    }

    public function testIsOutOfStock(): void
    {
        $inventory = new Inventory('Test Item', 'Test description', 0, 100.0);
        $this->assertTrue($inventory->isOutOfStock());

        $inventory2 = new Inventory('Test Item', 'Test description', 5, 100.0);
        $this->assertFalse($inventory2->isOutOfStock());
    }

    public function testGetTotalValue(): void
    {
        $inventory = new Inventory('Test Item', 'Test description', 10, 25.5);
        $this->assertEquals(255.0, $inventory->getTotalValue());
    }

    public function testToArrayConversion(): void
    {
        $inventory = new Inventory(
            'Test Item',
            'Test description',
            10,
            99.99,
            'cat1',
            'sup1',
            2
        );

        $array = $inventory->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('Test Item', $array['name']);
        $this->assertEquals('Test description', $array['description']);
        $this->assertEquals(10, $array['quantity']);
        $this->assertEquals(99.99, $array['price']);
        $this->assertEquals('cat1', $array['categoryId']);
        $this->assertEquals('sup1', $array['supplierId']);
        $this->assertEquals(2, $array['minStockLevel']);
        $this->assertArrayHasKey('isLowStock', $array);
        $this->assertArrayHasKey('isOutOfStock', $array);
        $this->assertArrayHasKey('totalValue', $array);
        $this->assertArrayHasKey('createdAt', $array);
        $this->assertArrayHasKey('updatedAt', $array);
    }

    public function testToDocumentConversion(): void
    {
        $inventory = new Inventory(
            'Test Item',
            'Test description',
            15,
            49.99,
            'cat2',
            'sup2',
            3
        );

        $document = $inventory->toDocument();

        $this->assertIsArray($document);
        $this->assertEquals('Test Item', $document['name']);
        $this->assertEquals('Test description', $document['description']);
        $this->assertEquals(15, $document['quantity']);
        $this->assertEquals(49.99, $document['price']);
        $this->assertEquals('cat2', $document['categoryId']);
        $this->assertEquals('sup2', $document['supplierId']);
        $this->assertEquals(3, $document['minStockLevel']);
        $this->assertArrayHasKey('createdAt', $document);
        $this->assertArrayHasKey('updatedAt', $document);
    }

    public function testFromDocumentCreation(): void
    {
        $document = [
            'name' => 'Test Item',
            'description' => 'Test description',
            'quantity' => 20,
            'price' => 79.99,
            'categoryId' => 'cat3',
            'supplierId' => 'sup3',
            'minStockLevel' => 4,
            'createdAt' => new \MongoDB\BSON\UTCDateTime(time() * 1000),
            'updatedAt' => new \MongoDB\BSON\UTCDateTime(time() * 1000)
        ];

        $inventory = Inventory::fromDocument($document);

        $this->assertInstanceOf(Inventory::class, $inventory);
        $this->assertEquals('Test Item', $inventory->getName());
        $this->assertEquals('Test description', $inventory->getDescription());
        $this->assertEquals(20, $inventory->getQuantity());
        $this->assertEquals(79.99, $inventory->getPrice());
        $this->assertEquals('cat3', $inventory->getCategoryId());
        $this->assertEquals('sup3', $inventory->getSupplierId());
        $this->assertEquals(4, $inventory->getMinStockLevel());
    }

    public function testSetMethodsValidation(): void
    {
        $inventory = new Inventory('Test', 'Desc', 10, 100.0);

        // Test valid updates
        $inventory->setName('Updated Name');
        $inventory->setQuantity(15);
        $inventory->setPrice(150.0);
        $inventory->setMinStockLevel(3);

        $this->assertEquals('Updated Name', $inventory->getName());
        $this->assertEquals(15, $inventory->getQuantity());
        $this->assertEquals(150.0, $inventory->getPrice());
        $this->assertEquals(3, $inventory->getMinStockLevel());

        // Test invalid updates should throw exceptions
        $this->expectException(\InvalidArgumentException::class);
        $inventory->setQuantity(-1);
    }
}