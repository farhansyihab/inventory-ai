## 🧪 **COMPREHENSIVE TEST SUITE STRUCTURE**
```
text
tests/
├── Unit/
│   ├── Config/
│   │   └── MongoDBManagerTest.php
│   ├── Model/
│   │   └── UserTest.php
│   ├── Repository/
│   │   └── UserRepositoryTest.php
│   ├── Service/
│   │   └── UserServiceTest.php
│   ├── Utility/
│   │   ├── LoggerTest.php
│   │   └── RouterTest.php
│   └── Middleware/
│       └── ErrorHandlerTest.php
├── Integration/
│   ├── Database/
│   │   ├── MongoDBIntegrationTest.php
│   │   └── UserRepositoryIntegrationTest.php
│   └── Service/
│       └── UserServiceIntegrationTest.php
├── Functional/
│   ├── Controller/
│   │   ├── UserControllerTest.php
│   │   └── AuthControllerTest.php
│   └── Api/
│       └── HealthCheckTest.php
├── bootstrap.php
└── phpunit.xml
```

### 1\. **Test Bootstrap** (`tests/bootstrap.php`)
```
php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Load test environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../', '.env.test');
$dotenv->load();

// Set default timezone
date_default_timezone_set('Asia/Jakarta');

// Test configuration
define('TEST_DB_NAME', 'inventory_ai_test');
define('TEST_MONGODB_URI', $_ENV['MONGODB_URI'] ?? 'mongodb://localhost:27017');

// Ensure test database is used
$_ENV['MONGODB_DB'] = TEST_DB_NAME;
```

### 2\. **Enhanced PHPUnit Config** (`tests/phpunit.xml`)
```
xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/9.6/phpunit.xsd"
         bootstrap="bootstrap.php"
         colors="true"
         verbose="true"
         stopOnFailure="false"
         stopOnError="false"
         executionOrder="random"
         resolveDependencies="true">
    
    <testsuites>
        <testsuite name="Unit Tests">
            <directory>Unit</directory>
        </testsuite>
        <testsuite name="Integration Tests">
            <directory>Integration</directory>
        </testsuite>
        <testsuite name="Functional Tests">
            <directory>Functional</directory>
        </testsuite>
        <testsuite name="All Tests">
            <directory>Unit</directory>
            <directory>Integration</directory>
            <directory>Functional</directory>
        </testsuite>
    </testsuites>
    
    <coverage>
        <include>
            <directory>../src</directory>
        </include>
        <exclude>
            <directory>../src/Config</directory>
            <directory>../src/Migration</directory>
        </exclude>
        <report>
            <html outputDirectory="tests/coverage"/>
            <text outputFile="tests/coverage.txt"/>
        </report>
    </coverage>
    
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="MONGODB_URI" value="mongodb://localhost:27017"/>
        <env name="MONGODB_DB" value="inventory_ai_test"/>
        <env name="JWT_SECRET" value="test-jwt-secret-key-for-testing-only"/>
        <env name="LOG_LEVEL" value="DEBUG"/>
    </php>
    
    <logging>
        <testdoxHtml outputFile="tests/testdox.html"/>
        <junit outputFile="tests/junit.xml"/>
    </logging>
</phpunit>
```
### 3\. **Unit Test - MongoDBManager** (`tests/Unit/Config/MongoDBManagerTest.php`)
```
php
<?php
declare(strict_types=1);

namespace Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use App\Config\MongoDBManager;
use Psr\Log\NullLogger;

class MongoDBManagerTest extends TestCase
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
        $exists = MongoDBManager::collectionExists('test_users');
        // Collection might not exist yet, both true and false are valid
        $this->assertIsBool($exists);
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
}
```

### 4\. **Unit Test - User Model** (`tests/Unit/Model/UserTest.php`)
```
php
<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use App\Model\User;
use DateTime;

class UserTest extends TestCase
{
    public function testUserCreationWithValidData(): void
    {
        $user = new User(
            'testuser',
            'test@example.com',
            password_hash('password123', PASSWORD_BCRYPT),
            User::ROLE_STAFF
        );

        $this->assertEquals('testuser', $user->getUsername());
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals(User::ROLE_STAFF, $user->getRole());
        $this->assertInstanceOf(DateTime::class, $user->getCreatedAt());
        $this->assertInstanceOf(DateTime::class, $user->getUpdatedAt());
    }

    public function testUserValidationWithInvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new User(
            'testuser',
            'invalid-email',
            password_hash('password123', PASSWORD_BCRYPT)
        );
    }

    public function testUserValidationWithShortUsername(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new User(
            'ab', // Too short
            'test@example.com',
            password_hash('password123', PASSWORD_BCRYPT)
        );
    }

    public function testUserValidationWithInvalidRole(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new User(
            'testuser',
            'test@example.com',
            password_hash('password123', PASSWORD_BCRYPT),
            'invalid_role' // Invalid role
        );
    }

    public function testUserToDocumentConversion(): void
    {
        $user = new User(
            'testuser',
            'test@example.com',
            password_hash('password123', PASSWORD_BCRYPT),
            User::ROLE_ADMIN
        );

        $document = $user->toDocument();

        $this->assertIsArray($document);
        $this->assertEquals('testuser', $document['username']);
        $this->assertEquals('test@example.com', $document['email']);
        $this->assertEquals(User::ROLE_ADMIN, $document['role']);
        $this->assertArrayHasKey('createdAt', $document);
        $this->assertArrayHasKey('updatedAt', $document);
    }

    public function testUserFromDocumentCreation(): void
    {
        $document = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'passwordHash' => password_hash('password123', PASSWORD_BCRYPT),
            'role' => User::ROLE_MANAGER,
            'createdAt' => new \MongoDB\BSON\UTCDateTime(time() * 1000),
            'updatedAt' => new \MongoDB\BSON\UTCDateTime(time() * 1000)
        ];

        $user = User::fromDocument($document);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('testuser', $user->getUsername());
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals(User::ROLE_MANAGER, $user->getRole());
    }

    public function testUserRoleMethods(): void
    {
        $adminUser = new User('admin', 'admin@test.com', 'hash', User::ROLE_ADMIN);
        $managerUser = new User('manager', 'manager@test.com', 'hash', User::ROLE_MANAGER);
        $staffUser = new User('staff', 'staff@test.com', 'hash', User::ROLE_STAFF);

        $this->assertTrue($adminUser->isAdmin());
        $this->assertFalse($adminUser->isManager());
        $this->assertFalse($adminUser->isStaff());

        $this->assertFalse($managerUser->isAdmin());
        $this->assertTrue($managerUser->isManager());
        $this->assertFalse($managerUser->isStaff());

        $this->assertFalse($staffUser->isAdmin());
        $this->assertFalse($staffUser->isManager());
        $this->assertTrue($staffUser->isStaff());
    }

    public function testUserToArrayConversion(): void
    {
        $user = new User(
            'testuser',
            'test@example.com',
            password_hash('password123', PASSWORD_BCRYPT),
            User::ROLE_STAFF
        );

        $array = $user->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('testuser', $array['username']);
        $this->assertEquals('test@example.com', $array['email']);
        $this->assertEquals(User::ROLE_STAFF, $array['role']);
        $this->assertArrayHasKey('createdAt', $array);
        $this->assertArrayHasKey('updatedAt', $array);
    }
}
```

### 5\. **Integration Test - MongoDB** (`tests/Integration/Database/MongoDBIntegrationTest.php`)
```
php
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
        
        // Clean up test database
        $collections = MongoDBManager::getDatabase()->listCollections();
        foreach ($collections as $collection) {
            MongoDBManager::getDatabase()->dropCollection($collection->getName());
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
            'createdAt' => new \MongoDB\BSON\UTCDateTime()
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

    public function testIndexCreationAndQueryPerformance(): void
    {
        $collection = MongoDBManager::getCollection('test_indexing');
        
        // Create indexes
        $indexes = [
            ['key' => ['email' => 1], 'unique' => true],
            ['key' => ['createdAt' => 1]]
        ];
        
        $result = MongoDBManager::createIndexes('test_indexing', $indexes);
        $this->assertTrue($result['success']);
        
        // Test index usage with explain
        $collection->insertOne([
            'email' => 'test1@example.com',
            'createdAt' => new \MongoDB\BSON\UTCDateTime()
        ]);
        
        $explain = $collection->find(['email' => 'test1@example.com'])->explain();
        $this->assertNotEmpty($explain);
    }

    public function testTransactionSupportIfAvailable(): void
    {
        $session = MongoDBManager::startSession();
        
        if ($session === null) {
            $this->markTestSkipped('MongoDB transactions not available (not a replica set)');
            return;
        }
        
        $collection = MongoDBManager::getCollection('test_transactions');
        
        $session->startTransaction();
        
        try {
            $collection->insertOne(['test' => 'data'], ['session' => $session]);
            $session->commitTransaction();
            $this->assertTrue(true); // Transaction successful
        } catch (\Exception $e) {
            $session->abortTransaction();
            $this->fail('Transaction failed: ' . $e->getMessage());
        }
    }

    public function testBulkOperations(): void
    {
        $collection = MongoDBManager::getCollection('test_bulk');
        
        $bulk = new \MongoDB\BulkWrite();
        
        // Insert multiple documents
        for ($i = 1; $i <= 5; $i++) {
            $bulk->insert([
                'number' => $i,
                'email' => "user{$i}@example.com",
                'createdAt' => new \MongoDB\BSON\UTCDateTime()
            ]);
        }
        
        $result = $collection->bulkWrite([$bulk]);
        $this->assertEquals(5, $result->getInsertedCount());
        
        // Verify documents were inserted
        $count = $collection->countDocuments();
        $this->assertEquals(5, $count);
    }
}

```

## 🚀 **CARA MENJALANKAN TESTS:**
```
bash
#!/bin/bash
# Run semua tests
composer test

# Run specific test suite
./vendor/bin/phpunit tests/Unit/
./vendor/bin/phpunit tests/Integration/
./vendor/bin/phpunit tests/Functional/

# Run dengan coverage report
./vendor/bin/phpunit --coverage-html tests/coverage

# Run test specific file
./vendor/bin/phpunit tests/Unit/Model/UserTest.php
```