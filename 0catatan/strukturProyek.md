# 📁 Project Structure
## Generated on: Sep 20 2025 10:09:40
## Target: /var/www/html/inventory-ai
## Max lines per file: no limit
## Ignore patterns: 51 patterns loaded

## Files and folders processed:

📁 inventory-ai
    📄 `inventory-ai/composer.json`
    ```json
    {
        "name": "farhan/inventory-ai",
        "description": "Inventory AI backend with PHP and MongoDB",
        "type": "project",
        "require": {
            "php": "^8.2",
            "mongodb/mongodb": "^1.19",
            "vlucas/phpdotenv": "^5.6",
            "firebase/php-jwt": "^6.10",
            "psr/log": "^3.0"
        },
        "autoload": {
            "psr-4": {
                "App\\": "src/"
            }
        },
        "autoload-dev": {
            "psr-4": {
                "Tests\\": "tests/"
            }
        },
        "require-dev": {
            "phpunit/phpunit": "^10.5",
            "squizlabs/php_codesniffer": "^3.10"
        },
        "scripts": {
            "test": "phpunit --colors=always --bootstrap vendor/autoload.php tests",
            "lint": "phpcs --standard=PSR12 src tests",
            "fix": "phpcbf --standard=PSR12 src tests"
        },
        "minimum-stability": "stable",
        "prefer-stable": true
    }
    ```

    📄 `inventory-ai/final_test.php`
    ```php
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

    📄 `inventory-ai/test_mongodb_manager.php`
    ```php
    <?php
    require_once 'vendor/autoload.php';
    
    use App\Config\MongoDBManager;
    
    echo "=== Testing Enhanced MongoDBManager ===\n";
    
    MongoDBManager::initialize();
    
    // Test basic connectivity
    echo 'Ping: ' . (MongoDBManager::ping() ? '✅' : '❌') . PHP_EOL;
    
    // Test collection existence
    echo 'Collection exists (users): ' . (MongoDBManager::collectionExists('users') ? '✅' : '❌') . PHP_EOL;
    
    // Test stats
    $stats = MongoDBManager::getStats();
    echo 'DB Stats: ' . ($stats['success'] ? '✅' : '❌') . PHP_EOL;
    
    // Test server version
    $version = MongoDBManager::getServerVersion();
    echo 'Server Version: ' . ($version['success'] ? '✅' : '❌') . PHP_EOL;
    
    // Test connection info
    $info = MongoDBManager::getConnectionInfo();
    echo 'Connection Info: ✅' . PHP_EOL;
    print_r($info);
    
    echo "=== Test Complete ===\n";
    ?>
    ```

    └── 📁 Unit
        📄 `inventory-ai/Unit/ExampleTest.php`
        ```php
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

    └── 📁 app
    └── 📁 config
    └── 📁 public
        📄 `inventory-ai/public/index.php`
        ```php
        <?php
        declare(strict_types=1);
        
        require_once __DIR__ . '/../vendor/autoload.php';
        
        // Load environment variables early
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->safeLoad(); // safeLoad to avoid exception in CI if no .env
        
        // Initialize error handling (display errors only in development)
        $errorHandler = new App\Middleware\ErrorHandler(null, ($_ENV['APP_ENV'] ?? 'production') === 'development');
        $errorHandler->register();
        
        // Initialize Logger and Mongo
        $logger = new App\Utility\Logger();
        App\Config\MongoDBManager::initialize($logger);
        
        // Initialize Router
        $router = new App\Utility\Router();
        
        // Define routes (same as before)
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
        
        // API group...
        $router->group('/api', function ($router) {
            $router->post('/auth/register', 'App\Controller\AuthController@register');
            $router->post('/auth/login', 'App\Controller\AuthController@login');
        
            $router->get('/users', 'App\Controller\UserController@listUsers');
            $router->get('/users/{id}', 'App\Controller\UserController@getUser');
            $router->post('/users', 'App\Controller\UserController@createUser');
        
            $router->get('/inventory', 'App\Controller\InventoryController@listItems');
        });
        
        // 404 handler
        $router->setNotFoundHandler(function () {
            http_response_code(404);
            return [
                'status' => 'error',
                'message' => 'Endpoint not found',
                'timestamp' => time(),
                'documentation' => '/api/docs'
            ];
        });
        
        // Dispatch
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // normalize path and remove query
        $parsed = parse_url($uri);
        $path = $parsed['path'] ?? '/';
        
        // Base path support (robust): strip base if present with exact prefix
        $basePath = '/inventory-ai';
        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = '/' . ltrim(substr($path, strlen($basePath)), '/');
        }
        
        // Security headers & CORS (adjust for prod allowed origins)
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: no-referrer-when-downgrade');
        header('Access-Control-Allow-Origin: *'); // change in prod
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        // short-circuit OPTIONS
        if ($method === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
        
        // dispatch and handle result
        $response = $router->dispatch($method, $path);
        
        // If route returned array that contains 'statusCode', use it
        if (is_array($response) && isset($response['statusCode']) && is_int($response['statusCode'])) {
            http_response_code($response['statusCode']);
            unset($response['statusCode']);
        }
        
        // final output
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        ```

        📄 `inventory-ai/public/quick-test.php`
        ```php
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

        📄 `inventory-ai/public/test_connection.php`
        ```php
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

        📄 `inventory-ai/public/test_db.php`
        ```php
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

        📄 `inventory-ai/public/test_mongo_manager.php`
        ```php
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

    └── 📁 scripts
        📄 `inventory-ai/scripts/create-indexes.php`
        ```php
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
            
            $result = MongoDBManager::createIndexes('users', $indexes);
            
            if ($result['success']) {
                echo "✅ User indexes created successfully\n";
                echo "Indexes: " . json_encode($result['indexes'], JSON_PRETTY_PRINT) . "\n";
            } else {
                echo "❌ Error creating indexes: " . $result['error'] . "\n";
                exit(1);
            }
            
            echo "✅ All indexes created successfully!\n";
            
        } catch (Exception $e) {
            echo "❌ Error creating indexes: " . $e->getMessage() . "\n";
            echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
            exit(1);
        }
        ```

    └── 📁 src
        └── 📁 Config
            📄 `inventory-ai/src/Config/MongoDBManager.php`
            ```php
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
             * Versi improved dengan maintain compatibility dan tambahan fitur
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
            
                /**
                 * Create indexes untuk collection tertentu
                 * Improved method dengan better error handling dan logging
                 */
                public static function createIndexes(string $collectionName, array $indexes): array
                {
                    try {
                        $collection = self::getCollection($collectionName);
                        $result = $collection->createIndexes($indexes);
                        
                        if (self::$logger) {
                            self::$logger->info('MongoDB indexes created', [
                                'collection' => $collectionName,
                                'indexes_count' => count($indexes),
                                'result' => $result
                            ]);
                        }
                        
                        return [
                            'success' => true,
                            'result' => $result,
                            'indexes' => array_map(fn($index) => $index['key'] ?? $index, $indexes)
                        ];
                        
                    } catch (MongoDBException $e) {
                        if (self::$logger) {
                            self::$logger->error('MongoDB createIndexes failed', [
                                'collection' => $collectionName,
                                'exception' => $e->getMessage(),
                                'indexes' => $indexes
                            ]);
                        }
                        
                        return [
                            'success' => false,
                            'error' => $e->getMessage(),
                            'collection' => $collectionName
                        ];
                    }
                }
            
                /**
                 * Get database statistics
                 */
                public static function getStats(): array
                {
                    try {
                        $stats = self::getDatabase()->command(['dbStats' => 1])->toArray()[0];
                        return [
                            'success' => true,
                            'stats' => $stats
                        ];
                    } catch (MongoDBException $e) {
                        if (self::$logger) {
                            self::$logger->error('MongoDB getStats failed', ['exception' => $e->getMessage()]);
                        }
                        return [
                            'success' => false,
                            'error' => $e->getMessage()
                        ];
                    }
                }
            
                /**
                 * Get collection statistics
                 */
                public static function getCollectionStats(string $collectionName): array
                {
                    try {
                        $collection = self::getCollection($collectionName);
                        $stats = $collection->aggregate([['$collStats' => ['storageStats' => []]]])->toArray();
                        return [
                            'success' => true,
                            'stats' => $stats[0] ?? []
                        ];
                    } catch (MongoDBException $e) {
                        if (self::$logger) {
                            self::$logger->error('MongoDB getCollectionStats failed', [
                                'collection' => $collectionName,
                                'exception' => $e->getMessage()
                            ]);
                        }
                        return [
                            'success' => false,
                            'error' => $e->getMessage()
                        ];
                    }
                }
            
                /**
                 * Check if collection exists
                 */
                public static function collectionExists(string $collectionName): bool
                {
                    try {
                        $collections = self::getDatabase()->listCollections([
                            'filter' => ['name' => $collectionName]
                        ]);
                        return count(iterator_to_array($collections)) > 0;
                    } catch (MongoDBException $e) {
                        if (self::$logger) {
                            self::$logger->error('MongoDB collectionExists check failed', [
                                'collection' => $collectionName,
                                'exception' => $e->getMessage()
                            ]);
                        }
                        return false;
                    }
                }
            
                /**
                 * Drop collection safely
                 */
                public static function dropCollection(string $collectionName): array
                {
                    try {
                        $collection = self::getCollection($collectionName);
                        $result = $collection->drop();
                        
                        if (self::$logger) {
                            self::$logger->info('MongoDB collection dropped', [
                                'collection' => $collectionName,
                                'result' => $result
                            ]);
                        }
                        
                        return [
                            'success' => true,
                            'collection' => $collectionName
                        ];
                        
                    } catch (MongoDBException $e) {
                        if (self::$logger) {
                            self::$logger->error('MongoDB dropCollection failed', [
                                'collection' => $collectionName,
                                'exception' => $e->getMessage()
                            ]);
                        }
                        return [
                            'success' => false,
                            'error' => $e->getMessage()
                        ];
                    }
                }
            
                /**
                 * Get server information
                 */
                public static function getServerInfo(): array
                {
                    try {
                        $info = self::getClient()->getManager()->getServers()[0]->getInfo();
                        return [
                            'success' => true,
                            'server_info' => $info
                        ];
                    } catch (MongoDBException $e) {
                        if (self::$logger) {
                            self::$logger->error('MongoDB getServerInfo failed', ['exception' => $e->getMessage()]);
                        }
                        return [
                            'success' => false,
                            'error' => $e->getMessage()
                        ];
                    }
                }
            
                /**
                 * Get MongoDB server version
                 */
                public static function getServerVersion(): array
                {
                    try {
                        $buildInfo = self::getDatabase()->command(['buildInfo' => 1])->toArray()[0];
                        return [
                            'success' => true,
                            'version' => $buildInfo['version'] ?? 'unknown',
                            'version_array' => $buildInfo['versionArray'] ?? []
                        ];
                    } catch (MongoDBException $e) {
                        if (self::$logger) {
                            self::$logger->error('MongoDB getServerVersion failed', ['exception' => $e->getMessage()]);
                        }
                        return [
                            'success' => false,
                            'error' => $e->getMessage()
                        ];
                    }
                }
            
                /**
                 * Flush semua koneksi dan reset state (utama untuk testing)
                 */
                public static function reset(): void
                {
                    self::$client = null;
                    self::$database = null;
                    
                    if (self::$logger) {
                        self::$logger->info('MongoDBManager reset');
                    }
                }
            
                /**
                 * Get logger instance
                 */
                public static function getLogger(): LoggerInterface
                {
                    if (self::$logger === null) {
                        self::initialize();
                    }
                    return self::$logger;
                }
            
                /**
                 * Set custom logger
                 */
                public static function setLogger(LoggerInterface $logger): void
                {
                    self::$logger = $logger;
                }
            }
            ```

        └── 📁 Controller
            📄 `inventory-ai/src/Controller/BaseController.php`
            ```php
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

        └── 📁 Middleware
            📄 `inventory-ai/src/Middleware/ErrorHandler.php`
            ```php
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

        └── 📁 Model
            📄 `inventory-ai/src/Model/User.php`
            ```php
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

        └── 📁 Repository
            📄 `inventory-ai/src/Repository/IRepository.php`
            ```php
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

            📄 `inventory-ai/src/Repository/UserRepository.php`
            ```php
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

        └── 📁 Service
            📄 `inventory-ai/src/Service/IService.php`
            ```php
            <?php
            declare(strict_types=1);
            
            namespace App\Service;
            
            use InvalidArgumentException;
            use RuntimeException;
            
            /**
             * Base Service Interface
             * Provides common contract for all service classes
             * 
             * @template T Entity type
             */
            interface IService
            {
                /**
                 * Find entity by ID
                 * 
                 * @param string $id Entity ID
                 * @return array|null Entity data or null if not found
                 * @throws InvalidArgumentException If ID format is invalid
                 */
                public function findById(string $id): ?array;
            
                /**
                 * Find entities by criteria
                 * 
                 * @param array $filter Query filter
                 * @param array $options Find options (sort, limit, skip, etc.)
                 * @return array Array of entities
                 * @throws RuntimeException If database operation fails
                 */
                public function find(array $filter = [], array $options = []): array;
            
                /**
                 * Create new entity
                 * 
                 * @param array $data Entity data
                 * @return array Created entity data with ID
                 * @throws InvalidArgumentException If validation fails
                 * @throws RuntimeException If creation fails
                 */
                public function create(array $data): array;
            
                /**
                 * Update entity by ID
                 * 
                 * @param string $id Entity ID
                 * @param array $data Update data
                 * @return bool True if update successful
                 * @throws InvalidArgumentException If ID format is invalid or validation fails
                 * @throws RuntimeException If update operation fails
                 */
                public function update(string $id, array $data): bool;
            
                /**
                 * Delete entity by ID
                 * 
                 * @param string $id Entity ID
                 * @return bool True if delete successful
                 * @throws InvalidArgumentException If ID format is invalid
                 * @throws RuntimeException If delete operation fails
                 */
                public function delete(string $id): bool;
            
                /**
                 * Count entities by criteria
                 * 
                 * @param array $filter Query filter
                 * @return int Number of matching entities
                 * @throws RuntimeException If count operation fails
                 */
                public function count(array $filter = []): int;
            
                /**
                 * Validate entity data
                 * 
                 * @param array $data Entity data to validate
                 * @return bool True if valid
                 * @throws InvalidArgumentException If validation fails with detailed errors
                 */
                public function validate(array $data): bool;
            
                /**
                 * Find one entity by criteria
                 * 
                 * @param array $filter Query filter
                 * @return array|null Entity data or null if not found
                 * @throws RuntimeException If database operation fails
                 */
                public function findOne(array $filter = []): ?array;
            }
            ```

            📄 `inventory-ai/src/Service/UserService.php`
            ```php
            <?php
            declare(strict_types=1);
            
            namespace App\Service;
            
            use App\Model\User;
            use App\Repository\UserRepository;
            use App\Utility\Logger;
            use InvalidArgumentException;
            use RuntimeException;
            
            class UserService implements IService
            {
                private UserRepository $userRepository;
                private Logger $logger;
            
                public function __construct(UserRepository $userRepository, Logger $logger)
                {
                    $this->userRepository = $userRepository;
                    $this->logger = $logger;
                }
            
                public function findById(string $id): ?array
                {
                    try {
                        $user = $this->userRepository->findUserById($id);
                        return $user ? $user->toArray() : null;
                    } catch (\Exception $e) {
                        $this->logger->error("UserService::findById failed: " . $e->getMessage());
                        throw new RuntimeException("Failed to find user: " . $e->getMessage());
                    }
                }
            
                public function find(array $filter = [], array $options = []): array
                {
                    try {
                        $users = $this->userRepository->find($filter, $options);
                        return array_map(fn($userData) => $this->convertToArray($userData), $users);
                    } catch (\Exception $e) {
                        $this->logger->error("UserService::find failed: " . $e->getMessage());
                        throw new RuntimeException("Failed to find users: " . $e->getMessage());
                    }
                }
            
                public function create(array $data): array
                {
                    $this->validate($data);
            
                    try {
                        // Hash password jika ada
                        if (isset($data['password'])) {
                            $data['passwordHash'] = password_hash($data['password'], PASSWORD_BCRYPT);
                            unset($data['password']);
                        }
            
                        $user = new User(
                            $data['username'],
                            $data['email'],
                            $data['passwordHash'],
                            $data['role'] ?? User::ROLE_STAFF
                        );
            
                        $userId = $this->userRepository->saveUser($user);
                        
                        $this->logger->info("User created successfully", ['userId' => $userId]);
                        
                        return $this->findById($userId);
                    } catch (\Exception $e) {
                        $this->logger->error("UserService::create failed: " . $e->getMessage());
                        throw new RuntimeException("Failed to create user: " . $e->getMessage());
                    }
                }
            
                public function update(string $id, array $data): bool
                {
                    $this->validate($data, false); // Validation for update (might skip required fields)
            
                    try {
                        $existingUser = $this->userRepository->findUserById($id);
                        if (!$existingUser) {
                            throw new InvalidArgumentException("User not found with ID: " . $id);
                        }
            
                        // Update fields
                        if (isset($data['username'])) {
                            $existingUser->setUsername($data['username']);
                        }
                        if (isset($data['email'])) {
                            $existingUser->setEmail($data['email']);
                        }
                        if (isset($data['role'])) {
                            $existingUser->setRole($data['role']);
                        }
                        if (isset($data['password'])) {
                            $existingUser->setPasswordHash(password_hash($data['password'], PASSWORD_BCRYPT));
                        }
            
                        $success = $this->userRepository->saveUser($existingUser);
                        
                        if ($success) {
                            $this->logger->info("User updated successfully", ['userId' => $id]);
                        }
                        
                        return $success;
                    } catch (\Exception $e) {
                        $this->logger->error("UserService::update failed: " . $e->getMessage());
                        throw new RuntimeException("Failed to update user: " . $e->getMessage());
                    }
                }
            
                public function delete(string $id): bool
                {
                    try {
                        $success = $this->userRepository->delete($id);
                        
                        if ($success) {
                            $this->logger->info("User deleted successfully", ['userId' => $id]);
                        }
                        
                        return $success;
                    } catch (\Exception $e) {
                        $this->logger->error("UserService::delete failed: " . $e->getMessage());
                        throw new RuntimeException("Failed to delete user: " . $e->getMessage());
                    }
                }
            
                public function count(array $filter = []): int
                {
                    try {
                        return $this->userRepository->count($filter);
                    } catch (\Exception $e) {
                        $this->logger->error("UserService::count failed: " . $e->getMessage());
                        throw new RuntimeException("Failed to count users: " . $e->getMessage());
                    }
                }
            
                public function validate(array $data, bool $isCreate = true): bool
                {
                    $errors = [];
            
                    if ($isCreate) {
                        // Validation for create
                        if (empty($data['username'])) {
                            $errors['username'] = 'Username is required';
                        } elseif (strlen($data['username']) < 3) {
                            $errors['username'] = 'Username must be at least 3 characters';
                        }
            
                        if (empty($data['email'])) {
                            $errors['email'] = 'Email is required';
                        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                            $errors['email'] = 'Invalid email format';
                        }
            
                        if (empty($data['password'])) {
                            $errors['password'] = 'Password is required';
                        } elseif (strlen($data['password']) < 6) {
                            $errors['password'] = 'Password must be at least 6 characters';
                        }
                    } else {
                        // Validation for update
                        if (isset($data['username']) && empty($data['username'])) {
                            $errors['username'] = 'Username cannot be empty';
                        }
            
                        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                            $errors['email'] = 'Invalid email format';
                        }
            
                        if (isset($data['password']) && strlen($data['password']) < 6) {
                            $errors['password'] = 'Password must be at least 6 characters';
                        }
                    }
            
                    if (isset($data['role']) && !in_array($data['role'], User::VALID_ROLES)) {
                        $errors['role'] = 'Invalid role. Must be one of: ' . implode(', ', User::VALID_ROLES);
                    }
            
                    if (!empty($errors)) {
                        throw new InvalidArgumentException("Validation failed: " . json_encode($errors));
                    }
            
                    return true;
                }
            
                public function findOne(array $filter = []): ?array
                {
                    try {
                        $userData = $this->userRepository->findOne($filter);
                        return $userData ? $this->convertToArray($userData) : null;
                    } catch (\Exception $e) {
                        $this->logger->error("UserService::findOne failed: " . $e->getMessage());
                        throw new RuntimeException("Failed to find user: " . $e->getMessage());
                    }
                }
            
                private function convertToArray(array $userData): array
                {
                    // Remove sensitive data
                    unset($userData['passwordHash']);
                    return $userData;
                }
            
                // Additional domain-specific methods
                public function findByUsername(string $username): ?array
                {
                    try {
                        $user = $this->userRepository->findUserByUsername($username);
                        return $user ? $this->convertToArray($user->toArray()) : null;
                    } catch (\Exception $e) {
                        $this->logger->error("UserService::findByUsername failed: " . $e->getMessage());
                        throw new RuntimeException("Failed to find user by username: " . $e->getMessage());
                    }
                }
            
                public function findByEmail(string $email): ?array
                {
                    try {
                        $user = $this->userRepository->findUserByEmail($email);
                        return $user ? $this->convertToArray($user->toArray()) : null;
                    } catch (\Exception $e) {
                        $this->logger->error("UserService::findByEmail failed: " . $e->getMessage());
                        throw new RuntimeException("Failed to find user by email: " . $e->getMessage());
                    }
                }
            }
            ```

        └── 📁 Utility
            📄 `inventory-ai/src/Utility/Logger.php`
            ```php
            <?php
            declare(strict_types=1);
            
            namespace App\Utility;
            
            use Psr\Log\AbstractLogger;
            use Psr\Log\LogLevel;
            use Stringable;
            
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
                 * @param string|Stringable $message
                 * @param array $context
                 * @return void
                 */
                public function log($level, string|Stringable $message, array $context = []): void
                {
                    $timestamp = date('Y-m-d H:i:s');
                    $level = strtoupper((string) $level);
                    $message = (string) $message;
                    $contextStr = !empty($context) ? json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
                    
                    $logMessage = sprintf(
                        "[%s] %s: %s %s\n",
                        $timestamp,
                        $level,
                        $message,
                        $contextStr
                    );
            
                    file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
                }
            
                /**
                 * Quick debug log
                 */
                public function debug(string|Stringable $message, array $context = []): void
                {
                    $this->log(LogLevel::DEBUG, $message, $context);
                }
            
                /**
                 * Quick info log
                 */
                public function info(string|Stringable $message, array $context = []): void
                {
                    $this->log(LogLevel::INFO, $message, $context);
                }
            
                /**
                 * Quick error log
                 */
                public function error(string|Stringable $message, array $context = []): void
                {
                    $this->log(LogLevel::ERROR, $message, $context);
                }
            
                /**
                 * Quick warning log
                 */
                public function warning(string|Stringable $message, array $context = []): void
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

            📄 `inventory-ai/src/Utility/Router.php`
            ```php
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

    └── 📁 tests
        📄 `inventory-ai/tests/bootstrap.php`
        ```php
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

        └── 📁 Functional
            └── 📁 Api
                📄 `inventory-ai/tests/Functional/Api/HealthCheckTest.php`
                ```php
                <?php
                declare(strict_types=1);
                
                namespace Tests\Functional\Api;
                
                use PHPUnit\Framework\TestCase;
                
                class HealthCheckTest extends TestCase
                {
                    public function testApiHealthEndpoint(): void
                    {
                        // Simulate a request to the health endpoint
                        $url = 'http://localhost/inventory-ai/health';
                        
                        // Use curl or file_get_contents to test actual endpoint
                        $context = stream_context_create([
                            'http' => [
                                'method' => 'GET',
                                'header' => 'Accept: application/json'
                            ]
                        ]);
                        
                        try {
                            $response = @file_get_contents($url, false, $context);
                            
                            if ($response === false) {
                                $this->markTestSkipped('API server not running');
                                return;
                            }
                            
                            $data = json_decode($response, true);
                            
                            $this->assertIsArray($data);
                            $this->assertArrayHasKey('status', $data);
                            $this->assertEquals('healthy', $data['status']);
                            
                        } catch (\Exception $e) {
                            $this->markTestSkipped('API test skipped: ' . $e->getMessage());
                        }
                    }
                
                    public function testApiRootEndpoint(): void
                    {
                        $url = 'http://localhost/inventory-ai/';
                        
                        try {
                            $response = @file_get_contents($url);
                            
                            if ($response === false) {
                                $this->markTestSkipped('API server not running');
                                return;
                            }
                            
                            $data = json_decode($response, true);
                            
                            $this->assertIsArray($data);
                            $this->assertArrayHasKey('status', $data);
                            $this->assertEquals('success', $data['status']);
                            
                        } catch (\Exception $e) {
                            $this->markTestSkipped('API test skipped: ' . $e->getMessage());
                        }
                    }
                }
                ```

        └── 📁 Integration
            └── 📁 Database
                📄 `inventory-ai/tests/Integration/Database/MongoDBIntegrationTest.php`
                ```php
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
                ```

        └── 📁 Unit
            └── 📁 Config
                📄 `inventory-ai/tests/Unit/Config/MongoDBManagerTest.php`
                ```php
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
                ```

            └── 📁 Model
                📄 `inventory-ai/tests/Unit/Model/UserTest.php`
                ```php
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

            └── 📁 Service
                📄 `inventory-ai/tests/Unit/Service/UserServiceTest.php`
                ```php
                <?php
                declare(strict_types=1);
                
                namespace Tests\Unit\Service;
                
                use PHPUnit\Framework\TestCase;
                use App\Service\UserService;
                use App\Repository\UserRepository;
                use App\Utility\Logger;
                use App\Model\User;
                use InvalidArgumentException;
                use RuntimeException;
                
                class UserServiceTest extends TestCase
                {
                    private UserService $userService;
                    private $userRepositoryMock;
                    private $loggerMock;
                
                    protected function setUp(): void
                    {
                        $this->userRepositoryMock = $this->createMock(UserRepository::class);
                        $this->loggerMock = $this->createMock(Logger::class);
                        
                        $this->userService = new UserService(
                            $this->userRepositoryMock,
                            $this->loggerMock
                        );
                    }
                
                    public function testFindByIdReturnsUserWhenExists(): void
                    {
                        $userData = [
                            'id' => '507f1f77bcf86cd799439011',
                            'username' => 'testuser',
                            'email' => 'test@example.com',
                            'role' => User::ROLE_STAFF,
                            'createdAt' => '2023-01-01T00:00:00+00:00',
                            'updatedAt' => '2023-01-01T00:00:00+00:00'
                        ];
                
                        $userMock = $this->createMock(User::class);
                        $userMock->method('toArray')->willReturn($userData);
                
                        $this->userRepositoryMock
                            ->method('findUserById')
                            ->with('507f1f77bcf86cd799439011')
                            ->willReturn($userMock);
                
                        $result = $this->userService->findById('507f1f77bcf86cd799439011');
                
                        $this->assertNotNull($result);
                        $this->assertEquals('testuser', $result['username']);
                        $this->assertEquals('test@example.com', $result['email']);
                        $this->assertArrayNotHasKey('passwordHash', $result); // Sensitive data removed
                    }
                
                    public function testFindByIdReturnsNullWhenUserNotFound(): void
                    {
                        $this->userRepositoryMock
                            ->method('findUserById')
                            ->with('nonexistentid')
                            ->willReturn(null);
                
                        $result = $this->userService->findById('nonexistentid');
                
                        $this->assertNull($result);
                    }
                
                    public function testFindByIdThrowsExceptionOnRepositoryError(): void
                    {
                        $this->expectException(RuntimeException::class);
                        $this->expectExceptionMessage('Failed to find user');
                
                        $this->userRepositoryMock
                            ->method('findUserById')
                            ->willThrowException(new \Exception('Database error'));
                
                        $this->userService->findById('someid');
                    }
                
                    public function testCreateUserValidatesRequiredFields(): void
                    {
                        $this->expectException(InvalidArgumentException::class);
                        $this->expectExceptionMessage('Validation failed');
                
                        $invalidData = [
                            'username' => 'ab', // Too short
                            'email' => 'invalid-email',
                            'password' => 'short'
                        ];
                
                        $this->userService->create($invalidData);
                    }
                
                    public function testCreateUserSuccessfully(): void
                    {
                        $userData = [
                            'username' => 'validuser',
                            'email' => 'valid@example.com',
                            'password' => 'validpassword123',
                            'role' => User::ROLE_STAFF
                        ];
                
                        $savedUser = $this->createMock(User::class);
                        $savedUser->method('toArray')->willReturn([
                            'id' => '507f1f77bcf86cd799439011',
                            'username' => 'validuser',
                            'email' => 'valid@example.com',
                            'role' => User::ROLE_STAFF,
                            'createdAt' => '2023-01-01T00:00:00+00:00',
                            'updatedAt' => '2023-01-01T00:00:00+00:00'
                        ]);
                
                        $this->userRepositoryMock
                            ->method('saveUser')
                            ->willReturn('507f1f77bcf86cd799439011');
                
                        $this->userRepositoryMock
                            ->method('findUserById')
                            ->with('507f1f77bcf86cd799439011')
                            ->willReturn($savedUser);
                
                        // Expect logger to be called
                        $this->loggerMock
                            ->expects($this->once())
                            ->method('info')
                            ->with('User created successfully', $this->arrayHasKey('userId'));
                
                        $result = $this->userService->create($userData);
                
                        $this->assertIsArray($result);
                        $this->assertEquals('validuser', $result['username']);
                        $this->assertEquals('valid@example.com', $result['email']);
                        $this->assertArrayNotHasKey('password', $result);
                        $this->assertArrayNotHasKey('passwordHash', $result);
                    }
                
                    public function testValidateAcceptsValidDataForCreate(): void
                    {
                        $validData = [
                            'username' => 'validuser',
                            'email' => 'valid@example.com',
                            'password' => 'validpassword123',
                            'role' => User::ROLE_STAFF
                        ];
                
                        $result = $this->userService->validate($validData, true);
                        $this->assertTrue($result);
                    }
                
                    public function testValidateRejectsInvalidDataForCreate(): void
                    {
                        $this->expectException(InvalidArgumentException::class);
                
                        $invalidData = [
                            'username' => 'ab', // Too short
                            'email' => 'invalid-email',
                            'password' => 'short'
                        ];
                
                        $this->userService->validate($invalidData, true);
                    }
                
                    public function testValidateAcceptsPartialDataForUpdate(): void
                    {
                        $partialData = [
                            'email' => 'new@example.com'
                        ];
                
                        $result = $this->userService->validate($partialData, false);
                        $this->assertTrue($result);
                    }
                
                    public function testFindByUsernameReturnsUserWhenExists(): void
                    {
                        $userData = [
                            'id' => '507f1f77bcf86cd799439011',
                            'username' => 'testuser',
                            'email' => 'test@example.com',
                            'role' => User::ROLE_STAFF
                        ];
                
                        $userMock = $this->createMock(User::class);
                        $userMock->method('toArray')->willReturn($userData);
                
                        $this->userRepositoryMock
                            ->method('findUserByUsername')
                            ->with('testuser')
                            ->willReturn($userMock);
                
                        $result = $this->userService->findByUsername('testuser');
                
                        $this->assertNotNull($result);
                        $this->assertEquals('testuser', $result['username']);
                    }
                
                    public function testFindByEmailReturnsUserWhenExists(): void
                    {
                        $userData = [
                            'id' => '507f1f77bcf86cd799439011',
                            'username' => 'testuser',
                            'email' => 'test@example.com',
                            'role' => User::ROLE_STAFF
                        ];
                
                        $userMock = $this->createMock(User::class);
                        $userMock->method('toArray')->willReturn($userData);
                
                        $this->userRepositoryMock
                            ->method('findUserByEmail')
                            ->with('test@example.com')
                            ->willReturn($userMock);
                
                        $result = $this->userService->findByEmail('test@example.com');
                
                        $this->assertNotNull($result);
                        $this->assertEquals('test@example.com', $result['email']);
                    }
                
                    public function testCountReturnsCorrectNumber(): void
                    {
                        $this->userRepositoryMock
                            ->method('count')
                            ->with(['role' => User::ROLE_STAFF])
                            ->willReturn(5);
                
                        $result = $this->userService->count(['role' => User::ROLE_STAFF]);
                
                        $this->assertEquals(5, $result);
                    }
                
                    public function testDeleteReturnsTrueWhenSuccessful(): void
                    {
                        $this->userRepositoryMock
                            ->method('delete')
                            ->with('507f1f77bcf86cd799439011')
                            ->willReturn(true);
                
                        // Expect logger to be called
                        $this->loggerMock
                            ->expects($this->once())
                            ->method('info')
                            ->with('User deleted successfully', $this->arrayHasKey('userId'));
                
                        $result = $this->userService->delete('507f1f77bcf86cd799439011');
                
                        $this->assertTrue($result);
                    }
                
                    public function testUpdateValidatesData(): void
                    {
                        $this->expectException(InvalidArgumentException::class);
                
                        $invalidData = [
                            'email' => 'invalid-email'
                        ];
                
                        $this->userService->update('someid', $invalidData);
                    }
                
                    public function testUpdateThrowsExceptionWhenUserNotFound(): void
                    {
                        // Ubah ke RuntimeException karena InvalidArgumentException di-wrap
                        $this->expectException(RuntimeException::class);
                        $this->expectExceptionMessage('Failed to update user: User not found with ID: nonexistentid');
                
                        $this->userRepositoryMock
                            ->method('findUserById')
                            ->with('nonexistentid')
                            ->willReturn(null);
                
                        $this->userService->update('nonexistentid', ['username' => 'newusername']);
                    }
                }
                ```


## Summary
- 📁 Folders processed: 23
- 📄 Files processed: 26
- ⏰ Generated on: Sep 20 2025 10:09:40
- Max lines per file: no limit
