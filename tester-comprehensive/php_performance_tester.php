<?php
require_once '/var/www/html/inventory-ai/vendor/autoload.php';

use App\Config\MongoDBManager;
use App\Model\User;
use App\Repository\UserRepository;
use App\Utility\Logger;

class PhpPerformanceTester {
    public function runAllTests() {
        echo "=== PHP Performance Tester ===\n";
        
        $tests = [
            'testMongoDBConnection' => 'MongoDB Connection',
            'testUserOperations' => 'User Operations',
            'testBulkOperations' => 'Bulk Operations'
        ];
        
        foreach ($tests as $method => $name) {
            $start = microtime(true);
            $result = $this->$method();
            $time = round((microtime(true) - $start) * 1000, 2);
            
            echo "✅ $name: {$time}ms" . ($result ? " ($result)" : "") . "\n";
        }
    }
    
    private function testMongoDBConnection() {
        MongoDBManager::initialize();
        return MongoDBManager::ping() ? 'Connected' : 'Failed';
    }
    
    private function testUserOperations() {
        MongoDBManager::initialize();
        $repo = new UserRepository();
        
        $user = new User('perftest', 'perf@test.com', password_hash('test123', PASSWORD_BCRYPT), 'staff');
        $id = $repo->saveUser($user);
        $repo->deleteUser($user);
        
        return "OK";
    }
    
    private function testBulkOperations() {
        MongoDBManager::initialize();
        $repo = new UserRepository();
        $count = 0;
        
        for ($i = 0; $i < 5; $i++) {
            $user = new User("bulkuser$i", "bulk$i@test.com", password_hash('test123', PASSWORD_BCRYPT), 'staff');
            $repo->saveUser($user);
            $count++;
        }
        
        // Cleanup
        $users = $repo->find(['username' => ['$regex' => 'bulkuser']]);
        foreach ($users as $userData) {
            $repo->delete($userData['_id']);
        }
        
        return "$count operations";
    }
}

$tester = new PhpPerformanceTester();
$tester->runAllTests();
