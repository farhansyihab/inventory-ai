// tester-comprehensive/performance_tester.cpp
#include <iostream>
#include <chrono>
#include <cstdlib>
#include <string>
#include <vector>
#include <cstdio>

class PerformanceTester {
public:
    void runPhpPerformanceTest() {
        std::cout << "Running PHP Performance Tests..." << std::endl;
        
        auto start = std::chrono::high_resolution_clock::now();
        
        // Test MongoDB connection performance
        system("cd /var/www/html/inventory-ai && php -r \"require 'vendor/autoload.php'; use App\\Config\\MongoDBManager; MongoDBManager::initialize(); echo 'MongoDB Ping: ' . (MongoDBManager::ping() ? 'OK' : 'FAIL') . '\\n';\"");
        
        // Test user creation performance
        system("cd /var/www/html/inventory-ai && php -r \"require 'vendor/autoload.php'; use App\\Model\\User; use App\\Repository\\UserRepository; use App\\Utility\\Logger; \$logger = new Logger(); App\\Config\\MongoDBManager::initialize(\$logger); \$repo = new UserRepository(); \$start = microtime(true); for (\$i = 0; \$i < 20; \$i++) { \$user = new User('testuser' . \$i, 'test' . \$i . '@example.com', 'hash', 'staff'); \$repo->saveUser(\$user); \$repo->deleteUser(\$user); } \$time = (microtime(true) - \$start) * 1000; echo '20 operations: ' . round(\$time, 2) . 'ms\\n'; echo 'Average: ' . round(\$time / 20, 2) . 'ms per operation\\n';\"");
        
        auto end = std::chrono::high_resolution_clock::now();
        auto duration = std::chrono::duration_cast<std::chrono::milliseconds>(end - start);
        
        std::cout << "Total test duration: " << duration.count() << "ms" << std::endl;
    }
    
    void runLoadTest() {
        std::cout << "Running basic load test..." << std::endl;
        
        auto start = std::chrono::high_resolution_clock::now();
        
        // Simple load test with 5 concurrent processes
        for (int i = 0; i < 3; i++) {
            std::string cmd = "cd /var/www/html/inventory-ai && php -r \"require 'vendor/autoload.php'; use App\\Config\\MongoDBManager; use App\\Repository\\UserRepository; use App\\Utility\\Logger; \$logger = new Logger(); MongoDBManager::initialize(\$logger); \$repo = new UserRepository(); for (\$j = 0; \$j < 10; \$j++) { \$user = new User('loaduser' . \$i . '_' . \$j, 'load' . \$i . '.' . \$j . '@example.com', 'hash', 'staff'); \$repo->saveUser(\$user); } \" > /dev/null 2>&1 &";
            
            system(cmd.c_str());
        }
        
        // Wait for processes to complete
        system("wait");
        
        auto end = std::chrono::high_resolution_clock::now();
        auto duration = std::chrono::duration_cast<std::chrono::milliseconds>(end - start);
        
        std::cout << "Load test completed in: " << duration.count() << "ms" << std::endl;
        
        // Cleanup
        system("cd /var/www/html/inventory-ai && php -r \"require 'vendor/autoload.php'; use App\\Config\\MongoDBManager; use App\\Repository\\UserRepository; MongoDBManager::initialize(); \$repo = new UserRepository(); \$users = \$repo->find(['username' => ['\$regex' => 'loaduser']]); foreach (\$users as \$user) { \$repo->delete(\$user['_id']); } echo 'Cleanup completed\\n';\"");
    }
};

int main() {
    PerformanceTester tester;
    
    std::cout << "=== Inventory AI C++ Performance Tester ===" << std::endl;
    
    tester.runPhpPerformanceTest();
    std::cout << std::endl;
    
    tester.runLoadTest();
    
    return 0;
}