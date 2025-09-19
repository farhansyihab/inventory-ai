<?php
declare(strict_types=1);

namespace Tests\Integration\Database;

use PHPUnit\Framework\TestCase;
use App\Config\MongoDBManager;
use Psr\Log\NullLogger;

class MongoDBIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        MongoDBManager::initialize(new NullLogger());
        
        // Clean up test database (skip system collections)
        $collections = MongoDBManager::getDatabase()->listCollections();
        foreach ($collections as $collection) {
            $name = $collection->getName();
            if (!str_starts_with($name, 'system.')) {
                MongoDBManager::getDatabase()->dropCollection($name);
            }
        }
    }

    protected function tearDown(): void
    {
        MongoDBManager::reset();
    }

    public function testDatabaseConnectionAndOperations(): void
    {
        $collection = MongoDBManager::getCollection('test_integration');
        
        // Test insert
        $insertResult = $collection->insertOne([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'createdAt' => new \MongoDB\BSON\UTCDateTime(time() * 1000)
        ]);
        
        $this->assertTrue($insertResult->isAcknowledged());
        $this->assertNotEmpty($insertResult->getInsertedId());
        
        // Test find
        $document = $collection->findOne(['_id' => $insertResult->getInsertedId()]);
        $this->assertNotNull($document);
        $this->assertEquals('Test User', $document->name);
        
        // Test update
        $updateResult = $collection->updateOne(
            ['_id' => $insertResult->getInsertedId()],
            ['$set' => ['name' => 'Updated User']]
        );
        
        $this->assertEquals(1, $updateResult->getModifiedCount());
        
        // Test delete
        $deleteResult = $collection->deleteOne(['_id' => $insertResult->getInsertedId()]);
        $this->assertEquals(1, $deleteResult->getDeletedCount());
    }

    public function testIndexCreation(): void
    {
        $collection = MongoDBManager::getCollection('test_indexing');
        
        // Create indexes
        $indexes = [
            ['key' => ['email' => 1], 'unique' => true],
            ['key' => ['createdAt' => 1]]
        ];
        
        $result = MongoDBManager::createIndexes('test_indexing', $indexes);
        $this->assertTrue($result['success']);
        
        // Test index usage by inserting and querying
        $insertResult = $collection->insertOne([
            'email' => 'test1@example.com',
            'createdAt' => new \MongoDB\BSON\UTCDateTime(time() * 1000)
        ]);
        
        $this->assertTrue($insertResult->isAcknowledged());
    }

    public function testBulkOperations(): void
    {
        $collection = MongoDBManager::getCollection('test_bulk');
        
        // Prepare bulk operations using insertMany
        $documents = [];
        for ($i = 1; $i <= 5; $i++) {
            $documents[] = [
                'number' => $i,
                'email' => "user{$i}@example.com",
                'createdAt' => new \MongoDB\BSON\UTCDateTime(time() * 1000)
            ];
        }
        
        $result = $collection->insertMany($documents);
        $this->assertEquals(5, $result->getInsertedCount());
        
        // Verify documents were inserted
        $count = $collection->countDocuments();
        $this->assertEquals(5, $count);
    }

    public function testAggregationFramework(): void
    {
        $collection = MongoDBManager::getCollection('test_aggregation');
        
        // Insert test data
        $collection->insertMany([
            ['name' => 'John', 'age' => 25, 'department' => 'IT'],
            ['name' => 'Jane', 'age' => 30, 'department' => 'HR'],
            ['name' => 'Bob', 'age' => 25, 'department' => 'IT'],
            ['name' => 'Alice', 'age' => 35, 'department' => 'Finance']
        ]);
        
        // Test aggregation
        $pipeline = [
            ['$group' => [
                '_id' => '$department',
                'count' => ['$sum' => 1],
                'averageAge' => ['$avg' => '$age']
            ]],
            ['$sort' => ['_id' => 1]]
        ];
        
        $results = $collection->aggregate($pipeline)->toArray();
        
        $this->assertGreaterThanOrEqual(2, count($results));
        
        // Verify we have some results
        $this->assertIsArray($results);
    }
}