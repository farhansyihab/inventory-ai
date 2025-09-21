<?php
declare(strict_types=1);

// Fix path issue - require from project root
require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\MongoDBManager;
use App\Model\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use App\Utility\Logger;
use MongoDB\BSON\ObjectId;
use MongoDB\Driver\Exception\Exception as MongoDBException;

class DeepTester
{
    private Logger $logger;
    private UserRepository $userRepository;
    private UserService $userService;
    private array $testResults = [];
    private int $passed = 0;
    private int $failed = 0;

    public function __construct()
    {
        $this->logger = new Logger(__DIR__ . '/logs/deep_test.log');
        MongoDBManager::initialize($this->logger);
        
        $this->userRepository = new UserRepository();
        $this->userService = new UserService($this->userRepository, $this->logger);
        
        // Create test database
        $this->setupTestEnvironment();
    }

    private function setupTestEnvironment(): void
    {
        try {
            // Clean test collections
            $collections = MongoDBManager::getDatabase()->listCollections();
            foreach ($collections as $collection) {
                $name = $collection->getName();
                if (!str_starts_with($name, 'system.')) {
                    MongoDBManager::getDatabase()->dropCollection($name);
                }
            }

            // Create indexes
            $this->userRepository->createIndexes();
            
            $this->logSuccess("Test environment setup completed");
        } catch (MongoDBException $e) {
            $this->logError("Failed to setup test environment: " . $e->getMessage());
            exit(1);
        }
    }

    public function runAllTests(): void
    {
        $this->logInfo("Starting comprehensive tests...");
        
        $tests = [
            'testMongoDBConnection' => 'MongoDB Connection Test',
            'testUserModelValidation' => 'User Model Validation',
            'testUserRepositoryCRUD' => 'User Repository CRUD Operations',
            'testUserServiceIntegration' => 'User Service Integration',
            'testErrorHandling' => 'Error Handling',
            'testEdgeCases' => 'Edge Cases'
        ];

        foreach ($tests as $method => $description) {
            $this->runTest($method, $description);
        }

        $this->printSummary();
    }

    private function runTest(string $method, string $description): void
    {
        $this->logInfo("Running: $description");
        
        try {
            $startTime = microtime(true);
            $result = $this->$method();
            $endTime = microtime(true);
            
            $executionTime = round(($endTime - $startTime) * 1000, 2);
            
            if ($result) {
                $this->passed++;
                $this->testResults[] = [
                    'test' => $description,
                    'status' => 'PASS',
                    'time' => $executionTime . 'ms'
                ];
                $this->logSuccess("✓ $description ($executionTime ms)");
            } else {
                $this->failed++;
                $this->testResults[] = [
                    'test' => $description,
                    'status' => 'FAIL',
                    'time' => $executionTime . 'ms'
                ];
                $this->logError("✗ $description ($executionTime ms)");
            }
        } catch (\Exception $e) {
            $this->failed++;
            $this->testResults[] = [
                'test' => $description,
                'status' => 'ERROR',
                'error' => $e->getMessage()
            ];
            $this->logError("✗ $description - ERROR: " . $e->getMessage());
        }
    }

    private function testMongoDBConnection(): bool
    {
        // Test basic connection
        if (!MongoDBManager::ping()) {
            throw new \Exception("MongoDB connection failed");
        }

        // Test collection operations
        $collection = MongoDBManager::getCollection('test_connection');
        $result = $collection->insertOne(['test' => 'connection', 'timestamp' => new \DateTime()]);
        
        if (!$result->getInsertedId()) {
            return false;
        }

        // Test read
        $document = $collection->findOne(['_id' => $result->getInsertedId()]);
        if (!$document) {
            return false;
        }

        // Test delete
        $deleteResult = $collection->deleteOne(['_id' => $result->getInsertedId()]);

        return $deleteResult->getDeletedCount() > 0;
    }

    private function testUserModelValidation(): bool
    {
        // Test valid user creation
        $validUser = new User(
            'testuser',
            'test@example.com',
            password_hash('Password123!', PASSWORD_BCRYPT),
            User::ROLE_ADMIN
        );

        // Test invalid email
        try {
            new User('testuser', 'invalid-email', 'hash', User::ROLE_STAFF);
            return false;
        } catch (\InvalidArgumentException $e) {
            // Expected
        }

        // Test short username
        try {
            new User('ab', 'test@example.com', 'hash', User::ROLE_STAFF);
            return false;
        } catch (\InvalidArgumentException $e) {
            // Expected
        }

        // Test invalid role
        try {
            new User('testuser', 'test@example.com', 'hash', 'invalid_role');
            return false;
        } catch (\InvalidArgumentException $e) {
            // Expected
        }

        return true;
    }

    private function testUserRepositoryCRUD(): bool
    {
        // Create test user
        $user = new User(
            'cruduser',
            'crud@example.com',
            password_hash('Password123!', PASSWORD_BCRYPT),
            User::ROLE_MANAGER
        );

        // Test create
        $userId = $this->userRepository->saveUser($user);
        if (!$userId) {
            return false;
        }

        // Test read
        $retrievedUser = $this->userRepository->findUserById($userId);
        if (!$retrievedUser || $retrievedUser->getUsername() !== 'cruduser') {
            return false;
        }

        // Test delete
        $deleteSuccess = $this->userRepository->deleteUser($retrievedUser);
        if (!$deleteSuccess) {
            return false;
        }

        // Verify delete
        $deletedUser = $this->userRepository->findUserById($userId);
        return $deletedUser === null;
    }

    private function testUserServiceIntegration(): bool
    {
        // Test user creation through service
        $userData = [
            'username' => 'serviceuser',
            'email' => 'service@example.com',
            'password' => 'SecurePassword123!',
            'role' => User::ROLE_STAFF
        ];

        $createdUser = $this->userService->create($userData);
        if (!isset($createdUser['id'])) {
            return false;
        }

        // Test find by ID
        $foundUser = $this->userService->findById($createdUser['id']);
        if (!$foundUser || $foundUser['username'] !== 'serviceuser') {
            return false;
        }

        // Test delete
        $deleteResult = $this->userService->delete($createdUser['id']);
        return $deleteResult;
    }

    private function testErrorHandling(): bool
    {
        // Test invalid MongoDB operations
        try {
            $collection = MongoDBManager::getCollection('nonexistent');
            $collection->findOne(['_id' => new ObjectId('invalid_id')]);
        } catch (\Exception $e) {
            // Expected to throw exception
        }

        // Test repository with invalid data
        try {
            $invalidUser = new User('', 'invalid', 'hash', 'invalid_role');
            $this->userRepository->saveUser($invalidUser);
            return false;
        } catch (\InvalidArgumentException $e) {
            // Expected
        }

        return true;
    }

    private function testEdgeCases(): bool
    {
        // Test very long strings
        $longString = str_repeat('a', 100);
        try {
            $user = new User(
                $longString,
                'long@example.com',
                password_hash('Password123!', PASSWORD_BCRYPT),
                User::ROLE_STAFF
            );
            // Should work for reasonable length
            if (strlen($longString) > 255) {
                return false;
            }
        } catch (\InvalidArgumentException $e) {
            // Expected for very long strings
        }

        // Test special characters
        $specialUser = new User(
            'user_with_underscore',
            'special.chars+test@example.com',
            password_hash('Password123!', PASSWORD_BCRYPT),
            User::ROLE_STAFF
        );

        $userId = $this->userRepository->saveUser($specialUser);
        $retrieved = $this->userRepository->findUserById($userId);

        if (!$retrieved || $retrieved->getEmail() !== 'special.chars+test@example.com') {
            return false;
        }

        return true;
    }

    private function printSummary(): void
    {
        $this->logInfo("\n" . str_repeat("=", 60));
        $this->logInfo("TEST SUMMARY");
        $this->logInfo(str_repeat("=", 60));
        
        foreach ($this->testResults as $result) {
            $status = $result['status'] === 'PASS' ? "✓ PASS" : 
                     ($result['status'] === 'FAIL' ? "✗ FAIL" : "⚠ ERROR");
            
            $time = isset($result['time']) ? "({$result['time']})" : "";
            $error = isset($result['error']) ? "- {$result['error']}" : "";
            
            $this->logInfo(sprintf("%-40s %-10s %-10s %s", 
                substr($result['test'], 0, 40), 
                $status, 
                $time,
                $error
            ));
        }
        
        $this->logInfo(str_repeat("=", 60));
        $this->logInfo("TOTAL: {$this->passed} PASSED, {$this->failed} FAILED");
        $this->logInfo(str_repeat("=", 60));
        
        if ($this->failed === 0) {
            $this->logSuccess("🎉 ALL TESTS PASSED!");
        } else {
            $this->logError("❌ SOME TESTS FAILED!");
            exit(1);
        }
    }

    private function logInfo(string $message): void
    {
        echo $message . "\n";
        $this->logger->info($message);
    }

    private function logSuccess(string $message): void
    {
        echo "\033[32m" . $message . "\033[0m\n";
        $this->logger->info($message);
    }

    private function logError(string $message): void
    {
        echo "\033[31m" . $message . "\033[0m\n";
        $this->logger->error($message);
    }
}

// Run the tests
$tester = new DeepTester();
$tester->runAllTests();