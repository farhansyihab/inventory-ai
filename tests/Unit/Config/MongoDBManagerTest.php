<?php
declare(strict_types=1);

namespace Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use App\Config\MongoDBManager;
use Psr\Log\NullLogger;

class MongoDBManagerTest extends TestCase // ✅ PERBAIKI NAMA CLASS
{
    protected function setUp(): void
    {
        MongoDBManager::reset();
        MongoDBManager::initialize(new NullLogger());
    }

    protected function tearDown(): void
    {
        MongoDBManager::reset();
    }

    public function testGetClientReturnsClientInstance(): void
    {
        $client = MongoDBManager::getClient();
        $this->assertInstanceOf(\MongoDB\Client::class, $client);
    }

    public function testGetDatabaseReturnsDatabaseInstance(): void
    {
        $database = MongoDBManager::getDatabase();
        $this->assertInstanceOf(\MongoDB\Database::class, $database);
        $this->assertEquals('inventory_ai_test', $database->getDatabaseName());
    }

    public function testGetCollectionReturnsCollectionInstance(): void
    {
        $collection = MongoDBManager::getCollection('test_users');
        $this->assertInstanceOf(\MongoDB\Collection::class, $collection);
        $this->assertEquals('test_users', $collection->getCollectionName());
    }

    public function testPingReturnsTrueWhenConnected(): void
    {
        $this->assertTrue(MongoDBManager::ping());
    }

    public function testStartSessionReturnsSessionOrNull(): void
    {
        $session = MongoDBManager::startSession();
        // Session might be null if not in replica set, both are acceptable
        $this->assertTrue($session === null || $session instanceof \MongoDB\Driver\Session);
    }

    public function testGetConnectionInfoReturnsValidArray(): void
    {
        $info = MongoDBManager::getConnectionInfo();
        
        $this->assertIsArray($info);
        $this->assertArrayHasKey('connected', $info);
        $this->assertArrayHasKey('database', $info);
        $this->assertArrayHasKey('uri', $info);
        $this->assertTrue($info['connected']);
    }

    public function testCreateIndexesReturnsSuccess(): void
    {
        $indexes = [
            ['key' => ['test_field' => 1]],
            ['key' => ['created_at' => 1]]
        ];
        
        $result = MongoDBManager::createIndexes('test_indexes', $indexes);
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('indexes', $result);
    }

    public function testCollectionExistsReturnsBoolean(): void
    {
        $exists = MongoDBManager::collectionExists('nonexistent_collection');
        // Collection doesn't exist, should return false
        $this->assertFalse($exists);
    }

    public function testGetServerInfoReturnsValidData(): void
    {
        $result = MongoDBManager::getServerInfo();
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('server_info', $result);
    }

    public function testGetServerVersionReturnsValidData(): void
    {
        $result = MongoDBManager::getServerVersion();
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('version', $result);
    }

    public function testResetMethodWorks(): void
    {
        $clientBefore = MongoDBManager::getClient();
        MongoDBManager::reset();
        $clientAfter = MongoDBManager::getClient();
        
        // Should be different instances after reset
        $this->assertNotSame($clientBefore, $clientAfter);
    }
}