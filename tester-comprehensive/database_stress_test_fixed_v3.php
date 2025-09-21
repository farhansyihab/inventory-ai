<?php
declare(strict_types=1);

// FIXED: Gunakan path absolut yang benar
require_once '/var/www/html/inventory-ai/vendor/autoload.php';

use App\Config\MongoDBManager;
use App\Model\User;
use App\Repository\UserRepository;
use App\Utility\Logger;

class DatabaseStressTest
{
    private Logger $logger;
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->logger = new Logger('/var/www/html/inventory-ai/tester-comprehensive/logs/stress_test.log');
        MongoDBManager::initialize($this->logger);
        $this->userRepository = new UserRepository();
    }

    public function runStressTest(): void
    {
        $this->log("Starting database stress test...");
        
        $tests = [
            'testBasicOperations' => 'Basic CRUD Operations',
            'testMultipleUsers' => 'Multiple User Creation'
        ];

        foreach ($tests as $method => $description) {
            $this->runTest($method, $description);
        }

        $this->generateReport();
    }

    private function runTest(string $method, string $description): void
    {
        $this->log("Running: $description");
        
        try {
            $startTime = microtime(true);
            $this->$method();
            $endTime = microtime(true);
            
            $executionTime = round(($endTime - $startTime) * 1000, 2);
            
            $this->log("✓ $description completed in {$executionTime}ms");
            
        } catch (Exception $e) {
            $this->log("✗ $description failed: " . $e->getMessage());
            throw $e;
        }
    }

    private function testBasicOperations(): void
    {
        // Create user
        $user = new User(
            "stress_user",
            "stress@example.com",
            password_hash("Password123!", PASSWORD_BCRYPT),
            User::ROLE_STAFF
        );
        
        $userId = $this->userRepository->saveUser($user);
        if (!$userId) {
            throw new Exception("Failed to create user");
        }

        // Read user
        $retrieved = $this->userRepository->findUserById($userId);
        if (!$retrieved) {
            throw new Exception("Failed to read user");
        }

        // Delete user
        $deleted = $this->userRepository->deleteUser($retrieved);
        if (!$deleted) {
            throw new Exception("Failed to delete user");
        }
    }

    private function testMultipleUsers(): void
    {
        $userIds = [];
        
        // Create multiple users
        for ($i = 0; $i < 5; $i++) {
            $user = new User(
                "multi_user_$i",
                "multi$i@example.com",
                password_hash("Password$i!", PASSWORD_BCRYPT),
                User::ROLE_STAFF
            );
            
            $userId = $this->userRepository->saveUser($user);
            if ($userId) {
                $userIds[] = $userId;
            } else {
                throw new Exception("Failed to create user $i");
            }
        }

        // Verify all were created
        if (count($userIds) !== 5) {
            throw new Exception("Failed to create all users");
        }

        // Cleanup
        foreach ($userIds as $userId) {
            $user = $this->userRepository->findUserById($userId);
            if ($user) {
                $success = $this->userRepository->deleteUser($user);
                if (!$success) {
                    $this->log("Warning: Failed to delete user $userId");
                }
            }
        }
    }

    private function generateReport(): void
    {
        $this->log("\n" . str_repeat("=", 60));
        $this->log("STRESS TEST COMPLETED SUCCESSFULLY");
        $this->log(str_repeat("=", 60));
    }

    private function log(string $message): void
    {
        echo $message . "\n";
        $this->logger->info($message);
    }
}

// Run the test
$test = new DatabaseStressTest();
$test->runStressTest();