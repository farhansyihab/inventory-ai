### 📁 Project Structure
## file dan folder yang sudah dibuat:
```
text
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
    ├── .phpunit.result.cache
    ├── Catatan.md
    ├── composer.json
    ├── composer.json.backup
    ├── composer.lock
    ├── final_test.php
    ├── pengajuan.md
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
            $document['_id'] = new ObjectId($this->id);
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
            $id = $document->_id instanceof ObjectId 
                ? (string) $document->_id 
                : $document->_id;
        }

        // Handle various date formats
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
        if ($dateValue instanceof UTCDateTime) {
            return $dateValue->toDateTime();
        }

        if ($dateValue instanceof DateTime) {
            return $dateValue;
        }

        if (is_numeric($dateValue)) {
            return new DateTime('@' . ($dateValue / 1000));
        }

        if (is_string($dateValue)) {
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
}
```

3. ***src/Repository/IRepository.php***
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

4. ***src/Repository/UserRepository.php***
```
php
<?php
declare(strict_types=1);

namespace App\Repository;

use App\Config\MongoDBManager;  // <- TAMBAH INI
use App\Model\User;
use MongoDB\Collection;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Driver\Exception\Exception as MongoException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use InvalidArgumentException;

/**
 * UserRepository dengan DI support dan domain-centric approach
 */
class UserRepository implements IRepository
{
    private Collection $collection;
    private LoggerInterface $logger;

/**
 *     public function __construct(Collection $collection, ?LoggerInterface $logger = null)
 *  {
 *      $this->collection = $collection;
 *      $this->logger = $logger ?? new NullLogger();
 *  }
 */

    public function __construct(?\MongoDB\Collection $collection = null)
    {
        $this->collection = $collection ?? MongoDBManager::getCollection('users');
    }    

    public function findById(string $id): ?array
    {
        try {
            $document = $this->collection->findOne(['_id' => new ObjectId($id)]);
            return $document ? $this->documentToArray($document) : null;
        } catch (MongoException $e) {
            $this->logger->error('UserRepository.findById failed', [
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
            // Ensure timestamps
            $data['createdAt'] = $data['createdAt'] ?? new UTCDateTime();
            $data['updatedAt'] = $data['updatedAt'] ?? new UTCDateTime();

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
            $data['updatedAt'] = new UTCDateTime();
            
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
        }
    }

    public function delete(string $id): bool
    {
        try {
            $result = $this->collection->deleteOne(['_id' => new ObjectId($id)]);
            $success = $result->getDeletedCount() > 0;
            
            if ($success) {
                $this->logger->info('User deleted', ['id' => $id]);
            }
            
            return $success;
        } catch (MongoException $e) {
            $this->logger->error('UserRepository.delete failed', [
                'id' => $id,
                'exception' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function count(array $filter = []): int
    {
        try {
            return $this->collection->countDocuments($filter);
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

    /**
     * Domain-centric methods
     */
    public function findUserById(string $id): ?User
    {
        $document = $this->findById($id);
        return $document ? User::fromDocument($document) : null;
    }

    public function findUserByUsername(string $username): ?User
    {
        try {
            $document = $this->collection->findOne(['username' => $username]);
            return $document ? User::fromDocument($document) : null;
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
            return $document ? User::fromDocument($document) : null;
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
                // Create new user
                return $this->create($document);
            } else {
                // Update existing user
                $success = $this->update($user->getId(), $document);
                return $success ? $user->getId() : '';
            }
        } catch (MongoException $e) {
            $this->logger->error('UserRepository.saveUser failed', [
                'user' => (string) $user,
                'exception' => $e->getMessage()
            ]);
            throw new InvalidArgumentException('Failed to save user: ' . $e->getMessage(), 0, $e);
        }
    }

    public function deleteUser(User $user): bool
    {
        if ($user->getId() === null) {
            return false;
        }
        
        return $this->delete($user->getId());
    }

    /**
     * Convert MongoDB document to array
     */
    private function documentToArray($document): array
    {
        $array = (array) $document;
        
        // Convert ObjectId to string
        if (isset($array['_id']) && $array['_id'] instanceof ObjectId) {
            $array['_id'] = (string) $array['_id'];
        }
        
        // Convert UTCDateTime to DateTime
        if (isset($array['createdAt']) && $array['createdAt'] instanceof UTCDateTime) {
            $array['createdAt'] = $array['createdAt']->toDateTime();
        }
        
        if (isset($array['updatedAt']) && $array['updatedAt'] instanceof UTCDateTime) {
            $array['updatedAt'] = $array['updatedAt']->toDateTime();
        }
        
        return $array;
    }

    /**
     * Create indexes for users collection
     */
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