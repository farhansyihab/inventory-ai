### 📁 Project Structure
## file dan folder yang sudah dibuat:
```
└── 📁inventory-ai
    └── 📁app
    └── 📁config
    └── 📁public
        ├── index.php
        ├── quick-test.php
        ├── test_connection.php
        ├── test_db.php
        ├── test_mongo_manager.php
    └── 📁scripts
        ├── create-indexes.php
    └── 📁src
        └── 📁Config
            ├── MongoDBManager.php
        └── 📁Controller
        └── 📁Middleware
        └── 📁Model
            ├── User.php
        └── 📁Repository
            ├── IRepository.php
            ├── UserRepository.php
        └── 📁Service
        └── 📁Utility
    └── 📁tests
        └── 📁Integration
        └── 📁Unit
        ├── bootstrap.php
    └── 📁Unit
        ├── ExampleTest.php
    ├── .env
    ├── .env.test
    ├── .gitignore
    ├── .phpunit.result.cache
    ├── Catatan.md
    ├── composer.json
    ├── composer.json.backup
    ├── composer.lock
    ├── final_test.php
    ├── phpunit.xml
    ├── README.md
    ├── RencanaPengembangan.md
    ├── testing.sh
    └── testmongodbconn.sh
```
## File-file setting :
1. ***.env***
```
APP_ENV=development
MONGODB_URI=mongodb://localhost:27017
MONGODB_DB=inventory_ai
JWT_SECRET=your-super-secret-jwt-key-change-this-in-production
```

2. ***composer.json***
```
json
{
    "name": "farhan/inventory-ai",
    "description": "Inventory Management System with AI Integration",
    "type": "project",
    "require": {
        "php": "^8.3|^8.4",
        "mongodb/mongodb": "^2.1",
        "firebase/php-jwt": "^6.8",
        "vlucas/phpdotenv": "^5.5"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.6",
        "squizlabs/php_codesniffer": "^3.7"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        },
        "files": [
            "src/Config/MongoDBManager.php",
            "src/Repository/UserRepository.php"
        ]
    },
    "scripts": {
        "test": "echo 'Tests coming soon...'",
        "lint": "echo 'Linting coming soon...'",
        "check": ["@lint", "@test"],
        "create-indexes": "php scripts/create-indexes.php",
        "dump-autoload": "composer dump-autoload -o"
    },
    "config": {
        "allow-plugins": {
            "dealerdirect/phpcodesniffer-composer-installer": true
        }
    }
}


```

3. ***testing.sh***
```
bash
#!/bin/bash
echo "=== Inventory AI Test Script ==="
echo ""

# Check PHP version
echo "1. PHP Version:"
php --version
echo ""

# Check MongoDB extension
echo "2. MongoDB Extension:"
if php -m | grep -q mongodb; then
    echo "✅ MongoDB extension loaded"
else
    echo "❌ MongoDB extension NOT loaded"
fi
echo ""

# Test basic autoload
echo "3. Basic Autoload:"
php -r "
if (@require 'vendor/autoload.php') {
    echo '✅ Vendor autoload working';
} else {
    echo '❌ Vendor autoload failed';
}
"
echo ""
echo ""

# Test our classes
echo "4. Our Classes:"
php -r "
if (!@require 'vendor/autoload.php') {
    echo '❌ Autoload failed';
    exit;
}

\$items = [
    'App\Config\MongoDBManager' => 'class',
    'App\Repository\UserRepository' => 'class',
    'App\Model\User' => 'class', 
    'App\Repository\IRepository' => 'interface',
    'MongoDB\Client' => 'class'
];

\$allGood = true;
foreach (\$items as \$name => \$type) {
    if (\$type === 'class') {
        \$exists = class_exists(\$name);
    } else {
        \$exists = interface_exists(\$name);
    }
    
    echo \$name . ': ' . (\$exists ? '✅' : '❌') . \"\n\";
    if (!\$exists) \$allGood = false;
}

echo \$allGood ? '✅ All classes/interfaces found' : '❌ Some items missing';
"
echo ""

echo "=== Test completed ==="

#Catatan : "Testing passes, berhasil semua"
```

4. ***final_test.php***

```
php
<?php
require 'vendor/autoload.php';

echo "=== Final Test ===\n";

// Test semua classes dan interfaces
$items = [
    'App\Config\MongoDBManager' => 'class',
    'App\Repository\UserRepository' => 'class', 
    'App\Model\User' => 'class',
    'App\Repository\IRepository' => 'interface',
    'MongoDB\Client' => 'class'
];

foreach ($items as $name => $type) {
    if ($type === 'class') {
        $exists = class_exists($name);
    } else {
        $exists = interface_exists($name);
    }
    echo $name . ': ' . ($exists ? '✅' : '❌') . "\n";
}

// Test MongoDB connection
echo "\n=== MongoDB Test ===\n";
if (class_exists('MongoDB\Client')) {
    try {
        $client = new MongoDB\Client('mongodb://localhost:27017');
        $result = $client->selectDatabase('admin')->command(['ping' => 1]);
        echo 'Direct Connection: ✅ Successful' . "\n";
    } catch (Exception $e) {
        echo 'Direct Connection: ❌ Failed - ' . $e->getMessage() . "\n";
    }
}

// Test MongoDBManager
echo "\n=== MongoDBManager Test ===\n";
if (class_exists('App\Config\MongoDBManager')) {
    try {
        App\Config\MongoDBManager::initialize();
        $connected = App\Config\MongoDBManager::ping();
        echo 'MongoDBManager Ping: ' . ($connected ? '✅ Success' : '❌ Failed') . "\n";
        
        // Test get collection
        $collection = App\Config\MongoDBManager::getCollection('test');
        echo 'Get Collection: ✅ Success' . "\n";
        
    } catch (Exception $e) {
        echo 'MongoDBManager Error: ' . $e->getMessage() . "\n";
    }
}

// Test UserRepository
echo "\n=== UserRepository Test ===\n";
if (class_exists('App\Repository\UserRepository')) {
    try {
        $userRepo = new App\Repository\UserRepository();
        echo 'UserRepository Instantiation: ✅ Success' . "\n";
        
        // Test find method
        $users = $userRepo->find();
        echo 'UserRepository Find: ✅ Success (' . count($users) . ' users)' . "\n";
        
    } catch (Exception $e) {
        echo 'UserRepository Error: ' . $e->getMessage() . "\n";
    }
}

echo "\n=== Test Complete ===\n";
?>
```
catatan ketika eksekusi : 
```
bash
php final_test.php
```
hasilnya bagus semua

5. ***phpunit.xml***
```
xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/9.6/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         verbose="true">
    <testsuites>
        <testsuite name="Unit Tests">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration Tests">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    
    <coverage>
        <include>
            <directory>src/</directory>
        </include>
        <exclude>
            <directory>src/Config/</directory>
            <directory>src/Migration/</directory>
        </exclude>
    </coverage>
    
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="MONGODB_URI" value="mongodb://localhost:27017"/>
        <env name="MONGODB_DB" value="inventory_ai_test"/>
        <env name="JWT_SECRET" value="test-secret-key-for-testing-only"/>
    </php>
</phpunit>
```

6. ***scripts/create-indexes.php***
```
php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\MongoDBManager;

echo "Creating MongoDB indexes...\n";

try {
    // Create indexes for users collection
    $indexes = [
        ['key' => ['username' => 1], 'unique' => true],
        ['key' => ['email' => 1], 'unique' => true],
        ['key' => ['role' => 1]],
        ['key' => ['createdAt' => 1]]
    ];
    
    $collection = MongoDBManager::getCollection('users');
    $result = $collection->createIndexes($indexes);
    
    echo "✅ User indexes created successfully\n";
    echo "Indexes: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    
    echo "✅ All indexes created successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error creating indexes: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
```

7. ***tests/bootstrap.php***
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
```

8. ***Unit/ExampleTest.php***
```
php
<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function testBasicExample(): void
    {
        $this->assertTrue(true);
    }
    
    public function testEnvironment(): void
    {
        $this->assertEquals('testing', $_ENV['APP_ENV']);
    }
    
    public function testAddition(): void
    {
        $result = 2 + 2;
        $this->assertEquals(4, $result);
    }
}
```

## File-file di folder public:
1. ***public/index.php*** (updated)
```
php
<?php
require_once '../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Initialize error handling
$errorHandler = new App\Middleware\ErrorHandler(null, $_ENV['APP_ENV'] === 'development');
$errorHandler->register();

// Initialize MongoDB Manager with logger
$logger = new App\Utility\Logger();
App\Config\MongoDBManager::initialize($logger);

// Initialize Router
$router = new App\Utility\Router();

// Define routes
$router->get('/', function () {
    return [
        'status' => 'success',
        'message' => 'Inventory AI API is running!',
        'timestamp' => time(),
        'php_version' => PHP_VERSION,
        'environment' => $_ENV['APP_ENV'] ?? 'not set',
        'mongodb_connected' => App\Config\MongoDBManager::ping()
    ];
});

$router->get('/health', function () {
    return [
        'status' => 'healthy',
        'timestamp' => time(),
        'services' => [
            'mongodb' => App\Config\MongoDBManager::ping() ? 'connected' : 'disconnected',
            'php' => 'running'
        ]
    ];
});

// API Routes group
$router->group('/api', function ($router) {
    // Authentication routes (to be implemented)
    $router->post('/auth/register', 'App\Controller\AuthController@register');
    $router->post('/auth/login', 'App\Controller\AuthController@login');
    
    // User routes (to be implemented)
    $router->get('/users', 'App\Controller\UserController@listUsers');
    $router->get('/users/{id}', 'App\Controller\UserController@getUser');
    $router->post('/users', 'App\Controller\UserController@createUser');
    
    // Inventory routes (to be implemented)
    $router->get('/inventory', 'App\Controller\InventoryController@listItems');
});

// Set 404 handler
$router->setNotFoundHandler(function () {
    http_response_code(404);
    return [
        'status' => 'error',
        'message' => 'Endpoint not found',
        'timestamp' => time(),
        'documentation' => '/api/docs' // TODO: Add API documentation
    ];
});

// Dispatch the request
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

// Remove base path if using subdirectory
$basePath = '/inventory-ai';
if (strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}

$response = $router->dispatch($method, $path);

// Send response
header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
```

2. ***public/quick-test.php***
```
php
<?php
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "MongoDB Extension: " . (extension_loaded('mongodb') ? '✅ Loaded' : '❌ Not loaded') . "<br>";

// Test MongoDB connection
try {
    $client = new MongoDB\Client('mongodb://localhost:27017');
    $database = $client->selectDatabase('admin');
    $result = $database->command(['ping' => 1]);
    echo "MongoDB Connection: ✅ Successful<br>";
    echo "Ping Response: " . json_encode($result->toArray()[0]) . "<br>";
} catch (Exception $e) {
    echo "MongoDB Connection: ❌ Failed - " . $e->getMessage() . "<br>";
}

// Test MongoDBManager class
if (class_exists('App\Config\MongoDBManager')) {
    echo "MongoDBManager Class: ✅ Found<br>";
    try {
        $connected = App\Config\MongoDBManager::ping();
        echo "MongoDBManager Ping: " . ($connected ? '✅ OK' : '❌ Failed') . "<br>";
    } catch (Exception $e) {
        echo "MongoDBManager Error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "MongoDBManager Class: ❌ Not found<br>";
}
?>
```

3. ***public/test_connection.php***
```
php
<?php
require_once 'vendor/autoload.php';

use MongoDB\Client;

try {
    $client = new Client($_ENV['MONGODB_URI']);
    $database = $client->selectDatabase($_ENV['MONGODB_DB']);
    $collection = $database->selectCollection('test');
    
    // Test insert
    $result = $collection->insertOne(['test' => 'connection', 'timestamp' => new DateTime()]);
    echo "✅ MongoDB Connection Successful! Inserted ID: " . $result->getInsertedId();
    
} catch (Exception $e) {
    echo "❌ MongoDB Connection Failed: " . $e->getMessage();
}
```
4. ***public/test_db.php***
```
php
<?php
require_once '../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

use MongoDB\Client;

header('Content-Type: text/plain');

try {
    $client = new Client($_ENV['MONGODB_URI']);
    $database = $client->selectDatabase($_ENV['MONGODB_DB']);
    $collection = $database->selectCollection('test');
    
    // Test insert
    $result = $collection->insertOne([
        'test' => 'connection', 
        'timestamp' => new DateTime(),
        'php_version' => PHP_VERSION
    ]);
    
    echo "✅ MongoDB Connection Successful!\n";
    echo "Inserted ID: " . $result->getInsertedId() . "\n";
    
    // Test read
    $document = $collection->findOne(['_id' => $result->getInsertedId()]);
    echo "Document: " . json_encode($document, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo "❌ MongoDB Connection Failed: " . $e->getMessage() . "\n";
    echo "MONGODB_URI: " . $_ENV['MONGODB_URI'] . "\n";
    echo "MONGODB_DB: " . $_ENV['MONGODB_DB'] . "\n";
}
?>
```

5. ***public/test_mongo_manager.php***
```
php
<?php
require_once '../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

use App\Config\MongoDBManager;

header('Content-Type: text/plain');

try {
    echo "=== MongoDB Manager Test ===\n\n";
    
    // Test connection
    $isConnected = MongoDBManager::ping();
    echo "Connection Test: " . ($isConnected ? "✅ SUCCESS" : "❌ FAILED") . "\n";
    
    if ($isConnected) {
        // Test get collection and insert
        $collection = MongoDBManager::getCollection('test_manager');
        $result = $collection->insertOne([
            'test' => 'MongoDBManager Integration', 
            'timestamp' => new DateTime(),
            'status' => 'working',
            'php_version' => PHP_VERSION
        ]);
        
        echo "Insert Test: ✅ SUCCESS\n";
        echo "Inserted ID: " . $result->getInsertedId() . "\n";
        
        // Test find
        $document = $collection->findOne(['_id' => $result->getInsertedId()]);
        echo "Find Test: " . ($document ? "✅ SUCCESS" : "❌ FAILED") . "\n";
        
        // Test connection info
        $info = MongoDBManager::getConnectionInfo();
        echo "\n=== Connection Info ===\n";
        print_r($info);
        
        echo "\n✅ All MongoDB Manager tests passed!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
?>
```

## File-file PHP dan Source Code lainnya:
1. ***src/Config/MongoDBManager.php***
```
php
<?php
declare(strict_types=1);

namespace App\Config;

use MongoDB\Client;
use MongoDB\Database;
use MongoDB\Collection;
use MongoDB\Driver\Session;
use MongoDB\Driver\Exception\Exception as MongoDBException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * MongoDBManager - Singleton pattern untuk MongoDB connection
 */
class MongoDBManager
{
    private static ?Client $client = null;
    private static ?Database $database = null;
    private static ?LoggerInterface $logger = null;

    public static function initialize(?LoggerInterface $logger = null): void
    {
        self::$logger = $logger ?? new NullLogger();
    }

    public static function getClient(): Client
    {
        if (self::$client === null) {
            $connectionString = $_ENV['MONGODB_URI'] ?? 'mongodb://localhost:27017';
            
            $options = [
                'connectTimeoutMS' => 30000,
                'socketTimeoutMS' => 30000,
                'serverSelectionTimeoutMS' => 5000,
            ];

            if (!empty($_ENV['MONGODB_USERNAME']) && !empty($_ENV['MONGODB_PASSWORD'])) {
                $options['username'] = $_ENV['MONGODB_USERNAME'];
                $options['password'] = $_ENV['MONGODB_PASSWORD'];
                $options['authSource'] = $_ENV['MONGODB_AUTH_SOURCE'] ?? 'admin';
            }

            self::$client = new Client($connectionString, $options);
            
            if (self::$logger) {
                self::$logger->info('MongoDB client initialized');
            }
        }

        return self::$client;
    }

    public static function getDatabase(): Database
    {
        if (self::$database === null) {
            $databaseName = $_ENV['MONGODB_DB'] ?? 'inventory_ai';
            self::$database = self::getClient()->selectDatabase($databaseName);
            
            if (self::$logger) {
                self::$logger->info('MongoDB database selected', ['database' => $databaseName]);
            }
        }

        return self::$database;
    }

    public static function getCollection(string $name): Collection
    {
        return self::getDatabase()->selectCollection($name);
    }

    public static function ping(): bool
    {
        try {
            self::getDatabase()->command(['ping' => 1]);
            return true;
        } catch (MongoDBException $e) {
            if (self::$logger) {
                self::$logger->error('MongoDB ping failed', ['exception' => $e->getMessage()]);
            }
            return false;
        }
    }

    public static function startSession(): ?Session
    {
        try {
            return self::getClient()->startSession();
        } catch (MongoDBException $e) {
            if (self::$logger) {
                self::$logger->warning('MongoDB session not available', ['exception' => $e->getMessage()]);
            }
            return null;
        }
    }

    public static function getConnectionInfo(): array
    {
        return [
            'connected' => self::ping(),
            'database' => $_ENV['MONGODB_DB'] ?? 'inventory_ai',
            'uri' => $_ENV['MONGODB_URI'] ?? 'mongodb://localhost:27017',
            'client_status' => self::$client ? 'initialized' : 'not_initialized',
            'database_status' => self::$database ? 'initialized' : 'not_initialized'
        ];
    }
}
```
catatan untuk file "src/Config/MongoDBManager.php" :
- nama file disesuaikan karena pernah ada konflik nama
- file sudah diusahakan disesuaikan dengan kaidah design pattern lalu berubah beberapa kali karena terjadi error
- update terakhir tanggal 19 September adalah versi paing stabil
2. ***src/Model/User.php***
```
php
<?php
declare(strict_types=1);

namespace App\Model;

use DateTime;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;
use InvalidArgumentException;

/**
 * User entity dengan robust mapping dan validation
 */
class User
{
    private ?string $id;
    private string $username;
    private string $email;
    private string $passwordHash;
    private string $role;
    private DateTime $createdAt;
    private DateTime $updatedAt;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_STAFF = 'staff';

    public const VALID_ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_MANAGER,
        self::ROLE_STAFF
    ];

    public function __construct(
        string $username,
        string $email,
        string $passwordHash,
        string $role = self::ROLE_STAFF,
        ?string $id = null,
        ?DateTime $createdAt = null,
        ?DateTime $updatedAt = null
    ) {
        $this->id = $id;
        $this->setUsername($username);
        $this->setEmail($email);
        $this->setPasswordHash($passwordHash);
        $this->setRole($role);
        $this->createdAt = $createdAt ?? new DateTime();
        $this->updatedAt = $updatedAt ?? new DateTime();
    }

    // Getters
    public function getId(): ?string { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function getEmail(): string { return $this->email; }
    public function getPasswordHash(): string { return $this->passwordHash; }
    public function getRole(): string { return $this->role; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): DateTime { return $this->updatedAt; }

    // Setters with validation
    public function setUsername(string $username): void 
    {
        $username = trim($username);
        if (empty($username)) {
            throw new InvalidArgumentException('Username cannot be empty');
        }
        if (strlen($username) < 3) {
            throw new InvalidArgumentException('Username must be at least 3 characters');
        }
        $this->username = $username;
    }

    public function setEmail(string $email): void 
    {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }
        $this->email = $email;
    }

    public function setPasswordHash(string $hash): void 
    {
        if (empty($hash)) {
            throw new InvalidArgumentException('Password hash cannot be empty');
        }
        $this->passwordHash = $hash;
    }

    public function setRole(string $role): void 
    {
        if (!in_array($role, self::VALID_ROLES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid role. Must be one of: %s',
                implode(', ', self::VALID_ROLES)
            ));
        }
        $this->role = $role;
    }

    public function setUpdatedAt(DateTime $updatedAt): void 
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Convert to document format untuk MongoDB
     */
   public function toDocument(): array
    {
        $document = [
            'username' => $this->username,
            'email' => $this->email,
            'passwordHash' => $this->passwordHash,
            'role' => $this->role,
            'createdAt' => new UTCDateTime($this->createdAt->getTimestamp() * 1000),
            'updatedAt' => new UTCDateTime($this->updatedAt->getTimestamp() * 1000),
        ];

        if ($this->id !== null) {
            // try-catch in case id is not valid hex
            try {
                $document['_id'] = new ObjectId($this->id);
            } catch (\Throwable $e) {
                // ignore: let repository decide how to handle a bad id format
            }
        }

        return $document;
    }

    /**
     * Create User dari MongoDB document
     */
    public static function fromDocument($document): self
    {
        if (is_array($document)) {
            $document = (object) $document;
        }

        $id = null;
        if (isset($document->_id)) {
            $id = $document->_id instanceof ObjectId ? (string) $document->_id : (string) $document->_id;
        }

        $createdAt = self::parseDate($document->createdAt ?? null);
        $updatedAt = self::parseDate($document->updatedAt ?? null);

        return new self(
            $document->username ?? '',
            $document->email ?? '',
            $document->passwordHash ?? '',
            $document->role ?? self::ROLE_STAFF,
            $id,
            $createdAt,
            $updatedAt
        );
    }

    /**
     * Parse various date formats to DateTime
     */
    private static function parseDate($dateValue): DateTime
    {
        // UTCDateTime -> DateTime
        if ($dateValue instanceof UTCDateTime) {
            return $dateValue->toDateTime();
        }

        // already DateTime
        if ($dateValue instanceof DateTime) {
            return $dateValue;
        }

        // numeric timestamp - detect ms vs s
        if (is_numeric($dateValue)) {
            $num = (int)$dateValue;
            // heuristics: > 1e12 likely milliseconds (year ~ 2001+ in ms), >1e9 seconds
            if ($num > 1000000000000) { // ms
                $seconds = intdiv($num, 1000);
            } elseif ($num > 1000000000) { // probably seconds
                $seconds = $num;
            } else {
                // fallback: treat as seconds
                $seconds = $num;
            }
            $dt = new DateTime();
            $dt->setTimestamp($seconds);
            return $dt;
        }

        // string parse
        if (is_string($dateValue) && $dateValue !== '') {
            return new DateTime($dateValue);
        }

        return new DateTime();
    }

    /**
     * Validate user data integrity
     */
    public function validate(): void
    {
        $this->setUsername($this->username);
        $this->setEmail($this->email);
        $this->setPasswordHash($this->passwordHash);
        $this->setRole($this->role);
    }

    /**
     * Check if user has admin role
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Check if user has manager role
     */
    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    /**
     * Check if user has staff role
     */
    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    public function __toString(): string
    {
        return sprintf(
            'User[id=%s, username=%s, email=%s, role=%s]',
            $this->id ?? 'null',
            $this->username,
            $this->email,
            $this->role
        );
    }

   /**
     * Clean, serializable array useful for APIs / logging
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->role,
            'createdAt' => $this->createdAt->format(DATE_ATOM),
            'updatedAt' => $this->updatedAt->format(DATE_ATOM),
        ];
    }    
}
```

4. ***src/Repository/UserRepository.php***
```
php
<?php
declare(strict_types=1);

namespace App\Repository;

use App\Config\MongoDBManager;
use App\Model\User;
use MongoDB\Collection;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Driver\Exception\Exception as MongoException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use InvalidArgumentException;
use Throwable;

/**
 * UserRepository - DI-friendly, domain-centric, safe update/create
 */
class UserRepository implements IRepository
{
    private Collection $collection;
    private LoggerInterface $logger;

    public function __construct(?Collection $collection = null, ?LoggerInterface $logger = null)
    {
        $this->collection = $collection ?? MongoDBManager::getCollection('users');
        $this->logger = $logger ?? new NullLogger();
    }

    public function findById(string $id): ?array
    {
        try {
            $objectId = new ObjectId($id);
            $document = $this->collection->findOne(['_id' => $objectId]);
            return $document ? $this->documentToArray($document) : null;
        } catch (MongoException $e) {
            $this->logger->error('UserRepository.findById failed', [
                'id' => $id,
                'exception' => $e->getMessage()
            ]);
            return null;
        } catch (Throwable $e) {
            // e.g. invalid ObjectId string
            $this->logger->warning('UserRepository.findById invalid id or unexpected', [
                'id' => $id,
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function find(array $filter = [], array $options = []): array
    {
        try {
            $cursor = $this->collection->find($filter, $options);
            $results = [];
            foreach ($cursor as $document) {
                $results[] = $this->documentToArray($document);
            }
            return $results;
        } catch (MongoException $e) {
            $this->logger->error('UserRepository.find failed', [
                'filter' => $filter,
                'exception' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function create(array $data): string
    {
        try {
            // Normalize timestamps -> ensure UTCDateTime
            $data['createdAt'] = $this->normalizeToUTCDateTime($data['createdAt'] ?? null);
            $data['updatedAt'] = $this->normalizeToUTCDateTime($data['updatedAt'] ?? null);

            $result = $this->collection->insertOne($data);
            $insertedId = (string) $result->getInsertedId();

            $this->logger->info('User created', [
                'id' => $insertedId,
                'username' => $data['username'] ?? 'unknown'
            ]);

            return $insertedId;
        } catch (MongoException $e) {
            $this->logger->error('UserRepository.create failed', [
                'data' => $data,
                'exception' => $e->getMessage()
            ]);
            throw new InvalidArgumentException('Failed to create user: ' . $e->getMessage(), 0, $e);
        }
    }

    public function update(string $id, array $data): bool
    {
        try {
            // remove _id to avoid immutable id update error
            if (isset($data['_id'])) {
                unset($data['_id']);
            }

            // set/update updatedAt and normalize any DateTime fields to UTCDateTime
            $data['updatedAt'] = new UTCDateTime();
            foreach ($data as $k => $v) {
                if ($v instanceof \DateTime) {
                    $data[$k] = new UTCDateTime($v->getTimestamp() * 1000);
                }
            }

            $result = $this->collection->updateOne(
                ['_id' => new ObjectId($id)],
                ['$set' => $data]
            );

            $success = $result->getMatchedCount() > 0;
            if ($success) {
                $this->logger->info('User updated', ['id' => $id]);
            } else {
                $this->logger->warning('User update not found', ['id' => $id]);
            }

            return $success;
        } catch (MongoException $e) {
            $this->logger->error('UserRepository.update failed', [
                'id' => $id,
                'exception' => $e->getMessage()
            ]);
            return false;
        } catch (Throwable $e) {
            $this->logger->error('UserRepository.update unexpected error', [
                'id' => $id,
                'exception' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function delete(string $id): bool
    {
        try {
            $result = $this->collection->deleteOne(['_id' => new ObjectId($id)]);
            $success = $result->getDeletedCount() > 0;
            if ($success) $this->logger->info('User deleted', ['id' => $id]);
            return $success;
        } catch (MongoException $e) {
            $this->logger->error('UserRepository.delete failed', [
                'id' => $id,
                'exception' => $e->getMessage()
            ]);
            return false;
        } catch (Throwable $e) {
            $this->logger->error('UserRepository.delete unexpected', [
                'id' => $id,
                'exception' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function count(array $filter = []): int
    {
        try {
            return (int) $this->collection->countDocuments($filter);
        } catch (MongoException $e) {
            $this->logger->error('UserRepository.count failed', [
                'filter' => $filter,
                'exception' => $e->getMessage()
            ]);
            return 0;
        }
    }

    public function findOne(array $filter = []): ?array
    {
        try {
            $document = $this->collection->findOne($filter);
            return $document ? $this->documentToArray($document) : null;
        } catch (MongoException $e) {
            $this->logger->error('UserRepository.findOne failed', [
                'filter' => $filter,
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }

    /* ---------------- Domain-centric helpers ---------------- */

    public function findUserById(string $id): ?User
    {
        $document = $this->findById($id);
        return $document ? User::fromDocument($document) : null;
    }

    public function findUserByUsername(string $username): ?User
    {
        try {
            $document = $this->collection->findOne(['username' => $username]);
            return $document ? User::fromDocument((array)$document) : null;
        } catch (MongoException $e) {
            $this->logger->error('UserRepository.findUserByUsername failed', [
                'username' => $username,
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function findUserByEmail(string $email): ?User
    {
        try {
            $document = $this->collection->findOne(['email' => $email]);
            return $document ? User::fromDocument((array)$document) : null;
        } catch (MongoException $e) {
            $this->logger->error('UserRepository.findUserByEmail failed', [
                'email' => $email,
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function saveUser(User $user): string
    {
        try {
            $document = $user->toDocument();

            if ($user->getId() === null) {
                return $this->create($document);
            } else {
                // remove _id before update to prevent MongoDB error
                if (isset($document['_id'])) unset($document['_id']);
                $success = $this->update($user->getId(), $document);
                return $success ? $user->getId() : '';
            }
        } catch (MongoException $e) {
            $this->logger->error('UserRepository.saveUser failed', [
                'user' => (string)$user,
                'exception' => $e->getMessage()
            ]);
            throw new InvalidArgumentException('Failed to save user: ' . $e->getMessage(), 0, $e);
        }
    }

    public function deleteUser(User $user): bool
    {
        if ($user->getId() === null) return false;
        return $this->delete($user->getId());
    }

    /* ---------------- internal helpers ---------------- */

    private function documentToArray($document): array
    {
        $array = (array) $document;

        if (isset($array['_id']) && $array['_id'] instanceof ObjectId) {
            $array['_id'] = (string) $array['_id'];
        }

        if (isset($array['createdAt']) && $array['createdAt'] instanceof UTCDateTime) {
            $array['createdAt'] = $array['createdAt']->toDateTime();
        }

        if (isset($array['updatedAt']) && $array['updatedAt'] instanceof UTCDateTime) {
            $array['updatedAt'] = $array['updatedAt']->toDateTime();
        }

        return $array;
    }

    private function normalizeToUTCDateTime($value): UTCDateTime
    {
        if ($value instanceof UTCDateTime) return $value;
        if ($value instanceof \DateTime) return new UTCDateTime($value->getTimestamp() * 1000);
        return new UTCDateTime();
    }

    public function createIndexes(): array
    {
        $indexes = [
            ['key' => ['username' => 1], 'unique' => true],
            ['key' => ['email' => 1], 'unique' => true],
            ['key' => ['role' => 1]],
            ['key' => ['createdAt' => 1]]
        ];

        try {
            $result = $this->collection->createIndexes($indexes);
            $this->logger->info('User indexes created');
            return ['success' => true, 'result' => $result];
        } catch (MongoException $e) {
            $this->logger->error('UserRepository.createIndexes failed', [
                'exception' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

```

5. ***src/Repository/IRepository.php***
```
php
<?php
declare(strict_types=1);

namespace App\Repository;

/**
 * Interface untuk repository pattern
 * Menyediakan contract dasar untuk CRUD operations
 * 
 * @template T Entity type
 */
interface IRepository
{
    /**
     * Find document by ID
     * 
     * @param string $id Document ID
     * @return array|null Document data atau null jika tidak ditemukan
     */
    public function findById(string $id): ?array;

    /**
     * Find documents berdasarkan filter
     * 
     * @param array $filter Query filter
     * @param array $options Find options
     * @return array Array of documents
     */
    public function find(array $filter = [], array $options = []): array;

    /**
     * Create new document
     * 
     * @param array $data Document data
     * @return string ID dari document yang dibuat
     */
    public function create(array $data): string;

    /**
     * Update document by ID
     * 
     * @param string $id Document ID
     * @param array $data Update data
     * @return bool True jika update berhasil
     */
    public function update(string $id, array $data): bool;

    /**
     * Delete document by ID
     * 
     * @param string $id Document ID
     * @return bool True jika delete berhasil
     */
    public function delete(string $id): bool;

    /**
     * Count documents berdasarkan filter
     * 
     * @param array $filter Query filter
     * @return int Jumlah documents
     */
    public function count(array $filter = []): int;

    /**
     * Find one document berdasarkan filter
     * 
     * @param array $filter Query filter
     * @return array|null Document data atau null
     */
    public function findOne(array $filter = []): ?array;
}
```

## Khusus untuk konfigurasi Ngix:
File ***/etc/nginx/sites-available/default***
```
ngix
##
# You should look at the following URL's in order to grasp a solid understanding
# of Nginx configuration files in order to fully unleash the power of Nginx.
# https://www.nginx.com/resources/wiki/start/
# https://www.nginx.com/resources/wiki/start/topics/tutorials/config_pitfalls/
# https://wiki.debian.org/Nginx/DirectoryStructure
#
# In most cases, administrators will remove this file from sites-enabled/ and
# leave it as reference inside of sites-available where it will continue to be
# updated by the nginx packaging team.
#
# This file will automatically load configuration files provided by other
# applications, such as Drupal or Wordpress. These applications will be made
# available underneath a path with that package name, such as /drupal8.
#
# Please see /usr/share/doc/nginx-doc/examples/ for more detailed examples.
##

# Default server configuration
#
server {
    listen 80 default_server;
    listen [::]:80 default_server;

    root /var/www/html;
    index index.php index.html index.htm index.nginx-debian.html;

    server_name _;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP 8.3 Configuration
    # location ~ \.php$ {
    #    include snippets/fastcgi-php.conf;
    #    
        # Gunakan socket PHP 8.3 FPM
    #    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        
    #    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    #    include fastcgi_params;
    #}
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        # Ganti ini:
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;  # Update ke 8.4
    }    

    location ~ /\.ht {
        deny all;
    }
    location /inventory-ai/ {
        alias /var/www/html/inventory-ai/public/;
        try_files $uri $uri/ /inventory-ai/public/index.php?$args;
        
        location ~ \.php$ {
            include snippets/fastcgi-php.conf;
            fastcgi_pass unix:/run/php/php8.4-fpm.sock;  # Update ke 8.4
            fastcgi_param SCRIPT_FILENAME $request_filename;
        }
    }    
}
```

## ***3 file paling critical*** untuk melengkapi Phase 1:
### 1\. **Logger Utility** (`src/Utility/Logger.php`)
```
php
<?php
declare(strict_types=1);

namespace App\Utility;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * Simple file logger implementing PSR-3 LoggerInterface
 */
class Logger extends AbstractLogger
{
    private string $logFile;
    private string $defaultLevel;

    public function __construct(?string $logFile = null, string $defaultLevel = LogLevel::INFO)
    {
        $this->logFile = $logFile ?? __DIR__ . '/../../logs/app.log';
        $this->defaultLevel = $defaultLevel;
        
        // Ensure log directory exists
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $level = strtoupper((string) $level);
        $contextStr = !empty($context) ? json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
        
        $logMessage = sprintf(
            "[%s] %s: %s %s\n",
            $timestamp,
            $level,
            (string) $message,
            $contextStr
        );

        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * Quick debug log
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * Quick info log
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * Quick error log
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Quick warning log
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * Get log file path
     */
    public function getLogFile(): string
    {
        return $this->logFile;
    }
}
```

### 2\. **Service Interface** (`src/Service/IService.php`)
```
php
<?php
declare(strict_types=1);

namespace App\Service;

/**
 * Base Service Interface
 * Provides common contract for all service classes
 */
interface IService
{
    /**
     * Find entity by ID
     * 
     * @param string $id Entity ID
     * @return array|null Entity data or null if not found
     */
    public function findById(string $id): ?array;

    /**
     * Find entities by criteria
     * 
     * @param array $filter Query filter
     * @param array $options Find options
     * @return array Array of entities
     */
    public function find(array $filter = [], array $options = []): array;

    /**
     * Create new entity
     * 
     * @param array $data Entity data
     * @return array Created entity data with ID
     * @throws \InvalidArgumentException
     */
    public function create(array $data): array;

    /**
     * Update entity by ID
     * 
     * @param string $id Entity ID
     * @param array $data Update data
     * @return bool True if update successful
     */
    public function update(string $id, array $data): bool;

    /**
     * Delete entity by ID
     * 
     * @param string $id Entity ID
     * @return bool True if delete successful
     */
    public function delete(string $id): bool;

    /**
     * Count entities by criteria
     * 
     * @param array $filter Query filter
     * @return int Number of matching entities
     */
    public function count(array $filter = []): int;

    /**
     * Validate entity data
     * 
     * @param array $data Entity data to validate
     * @return bool True if valid
     * @throws \InvalidArgumentException
     */
    public function validate(array $data): bool;
}
```

### 3\. **Error Handling Middleware** (`src/Middleware/ErrorHandler.php`)
```
php
<?php
declare(strict_types=1);

namespace App\Middleware;

use Psr\Log\LoggerInterface;
use App\Utility\Logger as AppLogger;

/**
 * Global Error Handler Middleware
 */
class ErrorHandler
{
    private LoggerInterface $logger;
    private bool $displayErrors;

    public function __construct(?LoggerInterface $logger = null, bool $displayErrors = false)
    {
        $this->logger = $logger ?? new AppLogger();
        $this->displayErrors = $displayErrors;
    }

    /**
     * Register error handlers
     */
    public function register(): void
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * Handle PHP errors
     */
    public function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        $errorType = $this->getErrorType($errno);
        
        $this->logger->error("PHP {$errorType}: {$errstr} in {$errfile}:{$errline}", [
            'errno' => $errno,
            'errfile' => $errfile,
            'errline' => $errline
        ]);

        // Don't execute PHP internal error handler
        return true;
    }

    /**
     * Handle uncaught exceptions
     */
    public function handleException(\Throwable $exception): void
    {
        $this->logger->error("Uncaught Exception: " . $exception->getMessage(), [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);

        $this->sendErrorResponse($exception);
    }

    /**
     * Handle shutdown errors (fatal errors)
     */
    public function handleShutdown(): void
    {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->logger->error("Shutdown Error: {$error['message']} in {$error['file']}:{$error['line']}", [
                'type' => $error['type'],
                'file' => $error['file'],
                'line' => $error['line']
            ]);

            $exception = new \ErrorException(
                $error['message'], 0, $error['type'], $error['file'], $error['line']
            );
            
            $this->sendErrorResponse($exception);
        }
    }

    /**
     * Send appropriate error response
     */
    private function sendErrorResponse(\Throwable $exception): void
    {
        if (headers_sent()) {
            return;
        }

        http_response_code(500);
        header('Content-Type: application/json');

        $response = [
            'status' => 'error',
            'message' => 'Internal Server Error',
            'timestamp' => time()
        ];

        if ($this->displayErrors) {
            $response['error'] = [
                'message' => $exception->getMessage(),
                'type' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ];
        }

        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Convert error number to error type name
     */
    private function getErrorType(int $errno): string
    {
        $errorTypes = [
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED'
        ];

        return $errorTypes[$errno] ?? "E_UNKNOWN ($errno)";
    }

    /**
     * Set whether to display errors in response
     */
    public function setDisplayErrors(bool $displayErrors): void
    {
        $this->displayErrors = $displayErrors;
    }
}
```

## 🚀 File phase 1: Router dan BaseController

### 1\. **Router Utility** (`src/Utility/Router.php`)
```
php
<?php
declare(strict_types=1);

namespace App\Utility;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Simple Router implementation for HTTP request routing
 */
class Router
{
    private array $routes = [];
    private array $routeGroups = [];
    private $notFoundHandler;
    private $currentGroupPrefix = '';

    // HTTP Methods
    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';
    public const METHOD_PUT = 'PUT';
    public const METHOD_DELETE = 'DELETE';
    public const METHOD_PATCH = 'PATCH';
    public const METHOD_OPTIONS = 'OPTIONS';

    public function __construct()
    {
        // Initialize with common HTTP methods
        $this->routes = [
            self::METHOD_GET => [],
            self::METHOD_POST => [],
            self::METHOD_PUT => [],
            self::METHOD_DELETE => [],
            self::METHOD_PATCH => [],
            self::METHOD_OPTIONS => [],
        ];
    }

    /**
     * Add a route for GET method
     */
    public function get(string $path, $handler): self
    {
        return $this->addRoute(self::METHOD_GET, $path, $handler);
    }

    /**
     * Add a route for POST method
     */
    public function post(string $path, $handler): self
    {
        return $this->addRoute(self::METHOD_POST, $path, $handler);
    }

    /**
     * Add a route for PUT method
     */
    public function put(string $path, $handler): self
    {
        return $this->addRoute(self::METHOD_PUT, $path, $handler);
    }

    /**
     * Add a route for DELETE method
     */
    public function delete(string $path, $handler): self
    {
        return $this->addRoute(self::METHOD_DELETE, $path, $handler);
    }

    /**
     * Add a route for PATCH method
     */
    public function patch(string $path, $handler): self
    {
        return $this->addRoute(self::METHOD_PATCH, $path, $handler);
    }

    /**
     * Add a route for OPTIONS method
     */
    public function options(string $path, $handler): self
    {
        return $this->addRoute(self::METHOD_OPTIONS, $path, $handler);
    }

    /**
     * Add a route for any HTTP method
     */
    public function any(string $path, $handler): self
    {
        foreach ($this->routes as $method => $_) {
            $this->addRoute($method, $path, $handler);
        }
        return $this;
    }

    /**
     * Add a route with custom HTTP method
     */
    public function addRoute(string $method, string $path, $handler): self
    {
        $method = strtoupper($method);
        $path = $this->currentGroupPrefix . $this->normalizePath($path);

        if (!isset($this->routes[$method])) {
            $this->routes[$method] = [];
        }

        $this->routes[$method][$path] = $handler;
        return $this;
    }

    /**
     * Group routes with a common prefix
     */
    public function group(string $prefix, callable $callback): self
    {
        $previousGroupPrefix = $this->currentGroupPrefix;
        $this->currentGroupPrefix .= $this->normalizePath($prefix);
        
        $callback($this);
        
        $this->currentGroupPrefix = $previousGroupPrefix;
        return $this;
    }

    /**
     * Set 404 Not Found handler
     */
    public function setNotFoundHandler(callable $handler): self
    {
        $this->notFoundHandler = $handler;
        return $this;
    }

    /**
     * Dispatch the request to appropriate handler
     */
    public function dispatch(string $method, string $path)
    {
        $method = strtoupper($method);
        $path = $this->normalizePath($path);

        // Check if method exists
        if (!isset($this->routes[$method])) {
            return $this->handleNotFound();
        }

        // Exact match
        if (isset($this->routes[$method][$path])) {
            return $this->executeHandler($this->routes[$method][$path]);
        }

        // Pattern matching with parameters
        foreach ($this->routes[$method] as $routePath => $handler) {
            if ($this->matchRoute($routePath, $path, $params)) {
                return $this->executeHandler($handler, $params);
            }
        }

        return $this->handleNotFound();
    }

    /**
     * Execute the route handler
     */
    private function executeHandler($handler, array $params = [])
    {
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }

        if (is_string($handler) && strpos($handler, '@') !== false) {
            [$controller, $method] = explode('@', $handler, 2);
            $controllerInstance = new $controller();
            
            if (method_exists($controllerInstance, $method)) {
                return call_user_func_array([$controllerInstance, $method], $params);
            }
        }

        throw new \RuntimeException("Invalid route handler");
    }

    /**
     * Check if route path matches request path
     */
    private function matchRoute(string $routePath, string $requestPath, ?array &$params): bool
    {
        $params = [];
        
        // Convert route pattern to regex
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $routePath);
        $pattern = "#^$pattern$#";
        
        if (preg_match($pattern, $requestPath, $matches)) {
            // Extract named parameters
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return true;
        }
        
        return false;
    }

    /**
     * Handle 404 Not Found
     */
    private function handleNotFound()
    {
        if ($this->notFoundHandler) {
            return $this->executeHandler($this->notFoundHandler);
        }

        http_response_code(404);
        return [
            'status' => 'error',
            'message' => 'Not Found',
            'timestamp' => time()
        ];
    }

    /**
     * Normalize path by ensuring it starts with slash and doesn't end with slash
     */
    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? $path : rtrim($path, '/');
    }

    /**
     * Get all registered routes
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Clear all routes
     */
    public function clearRoutes(): void
    {
        $this->routes = [
            self::METHOD_GET => [],
            self::METHOD_POST => [],
            self::METHOD_PUT => [],
            self::METHOD_DELETE => [],
            self::METHOD_PATCH => [],
            self::METHOD_OPTIONS => [],
        ];
        $this->currentGroupPrefix = '';
    }
}
```

### 2\. **Base Controller** (`src/Controller/BaseController.php`)
```
php
<?php
declare(strict_types=1);

namespace App\Controller;

use App\Utility\Logger;
use Psr\Http\Message\ResponseInterface;

/**
 * Base Controller with common functionality for all controllers
 */
abstract class BaseController
{
    protected Logger $logger;
    protected array $requestData = [];

    public function __construct(?Logger $logger = null)
    {
        $this->logger = $logger ?? new Logger();
        $this->parseRequestData();
    }

    /**
     * Parse request data from JSON input or form data
     */
    protected function parseRequestData(): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        // Parse JSON data
        if (strpos($contentType, 'application/json') !== false) {
            $jsonInput = file_get_contents('php://input');
            $data = json_decode($jsonInput, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->requestData = $data;
            }
        } 
        // Parse form data
        else {
            $this->requestData = $_POST;
        }

        // Merge with query parameters
        $this->requestData = array_merge($this->requestData, $_GET);
    }

    /**
     * Get request data by key with optional default value
     */
    protected function getRequestValue(string $key, $default = null)
    {
        return $this->requestData[$key] ?? $default;
    }

    /**
     * Send JSON response
     */
    protected function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send success response
     */
    protected function successResponse(array $data = [], string $message = 'Success', int $statusCode = 200): void
    {
        $response = [
            'status' => 'success',
            'message' => $message,
            'timestamp' => time(),
            'data' => $data
        ];

        $this->jsonResponse($response, $statusCode);
    }

    /**
     * Send error response
     */
    protected function errorResponse(string $message, array $errors = [], int $statusCode = 400): void
    {
        $response = [
            'status' => 'error',
            'message' => $message,
            'timestamp' => time(),
            'errors' => $errors
        ];

        $this->jsonResponse($response, $statusCode);
    }

    /**
     * Send not found response
     */
    protected function notFoundResponse(string $message = 'Resource not found'): void
    {
        $this->errorResponse($message, [], 404);
    }

    /**
     * Send unauthorized response
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized'): void
    {
        $this->errorResponse($message, [], 401);
    }

    /**
     * Send validation error response
     */
    protected function validationErrorResponse(array $errors, string $message = 'Validation failed'): void
    {
        $this->errorResponse($message, $errors, 422);
    }

    /**
     * Get authenticated user ID (to be implemented with JWT)
     */
    protected function getAuthUserId(): ?string
    {
        // TODO: Implement JWT authentication
        return null;
    }

    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated(): bool
    {
        // TODO: Implement authentication check
        return $this->getAuthUserId() !== null;
    }

    /**
     * Validate required fields in request data
     */
    protected function validateRequiredFields(array $fields): array
    {
        $errors = [];

        foreach ($fields as $field) {
            if (!isset($this->requestData[$field]) || empty($this->requestData[$field])) {
                $errors[$field] = "The {$field} field is required";
            }
        }

        return $errors;
    }

    /**
     * Log controller action
     */
    protected function logAction(string $action, array $context = []): void
    {
        $this->logger->info("Controller Action: {$action}", array_merge([
            'controller' => static::class,
            'action' => $action,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ], $context));
    }

    /**
     * Get pagination parameters from request
     */
    protected function getPaginationParams(): array
    {
        $page = max(1, (int) $this->getRequestValue('page', 1));
        $limit = max(1, min(100, (int) $this->getRequestValue('limit', 20)));
        $offset = ($page - 1) * $limit;

        return [
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    /**
     * Get sorting parameters from request
     */
    protected function getSortingParams(): array
    {
        $sortBy = $this->getRequestValue('sort_by', 'createdAt');
        $sortOrder = strtolower($this->getRequestValue('sort_order', 'desc'));
        
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        return [
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder
        ];
    }
}
```

