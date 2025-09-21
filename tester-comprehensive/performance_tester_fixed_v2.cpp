#include <iostream>
#include <chrono>
#include <string>
#include <vector>
#include <unistd.h>
#include <sys/wait.h>

class PerformanceTester {
private:
    void runPhpCode(const std::string& code) {
        pid_t pid = fork();
        if (pid == 0) {
            // Child process - CHANGE TO PROJECT ROOT FIRST
            chdir("/var/www/html/inventory-ai");
            
            std::vector<char*> args;
            args.push_back((char*)"php");
            args.push_back((char*)"-r");
            args.push_back((char*)code.c_str());
            args.push_back(nullptr);
            
            execvp("php", args.data());
            perror("execvp failed");
            _exit(1);
        } else if (pid > 0) {
            // Parent process
            int status;
            waitpid(pid, &status, 0);
        } else {
            perror("fork failed");
        }
    }

    std::string getPhpCodeWithAbsolutePath(const std::string& phpCode) {
        // Replace require dengan absolute path
        std::string absoluteCode = phpCode;
        size_t pos = absoluteCode.find("require 'vendor/autoload.php';");
        if (pos != std::string::npos) {
            absoluteCode.replace(pos, 29, "require '/var/www/html/inventory-ai/vendor/autoload.php';");
        }
        return absoluteCode;
    }

public:
    void runPhpPerformanceTest() {
        std::cout << "Running PHP Performance Tests..." << std::endl;

        auto start = std::chrono::high_resolution_clock::now();

        // Test 1: MongoDB Connection
        std::cout << "1. Testing MongoDB Connection..." << std::endl;
        runPhpCode(getPhpCodeWithAbsolutePath(
            "require 'vendor/autoload.php'; "
            "use App\\Config\\MongoDBManager; "
            "MongoDBManager::initialize(); "
            "echo MongoDBManager::ping() ? '✅ Connected' : '❌ Failed'; "
            "echo PHP_EOL;"
        ));

        // Test 2: User Operations Performance
        std::cout << "2. Testing User Operations..." << std::endl;
        runPhpCode(getPhpCodeWithAbsolutePath(
            "require 'vendor/autoload.php'; "
            "use App\\Config\\MongoDBManager; "
            "use App\\Model\\User; "
            "use App\\Repository\\UserRepository; "
            "MongoDBManager::initialize(); "
            "$repo = new UserRepository(); "
            "$start = microtime(true); "
            "$user = new User('testuser', 'test@example.com', password_hash('test123', PASSWORD_BCRYPT), 'staff'); "
            "$repo->saveUser($user); "
            "$repo->deleteUser($user); "
            "$time = (microtime(true) - $start) * 1000; "
            "echo 'Single operation: ' . round($time, 2) . 'ms' . PHP_EOL;"
        ));

        auto end = std::chrono::high_resolution_clock::now();
        auto duration = std::chrono::duration_cast<std::chrono::milliseconds>(end - start);
        std::cout << "Total test duration: " << duration.count() << "ms" << std::endl;
    }

    void runLoadTest() {
        std::cout << "Running Load Test..." << std::endl;

        auto start = std::chrono::high_resolution_clock::now();

        runPhpCode(getPhpCodeWithAbsolutePath(
            "require 'vendor/autoload.php'; "
            "use App\\Config\\MongoDBManager; "
            "use App\\Model\\User; "
            "use App\\Repository\\UserRepository; "
            "MongoDBManager::initialize(); "
            "$repo = new UserRepository(); "
            "$start = microtime(true); "
            "for ($i = 0; $i < 50; $i++) { "
            "    $user = new User('loaduser'.$i, 'load'.$i.'@example.com', password_hash('test123', PASSWORD_BCRYPT), 'staff'); "
            "    $repo->saveUser($user); "
            "    $repo->deleteUser($user); "
            "} "
            "$time = (microtime(true) - $start) * 1000; "
            "echo 'Completed 50 iterations: ' . round($time, 2) . 'ms' . PHP_EOL;"
            "echo 'Average: ' . round($time / 50, 2) . 'ms per operation' . PHP_EOL;"
        ));

        auto end = std::chrono::high_resolution_clock::now();
        auto duration = std::chrono::duration_cast<std::chrono::milliseconds>(end - start);
        std::cout << "Load test duration: " << duration.count() << "ms" << std::endl;
    }

    void runInventoryTest() {
        std::cout << "Running Inventory Test..." << std::endl;

        runPhpCode(getPhpCodeWithAbsolutePath(
            "require 'vendor/autoload.php'; "
            "use App\\Config\\MongoDBManager; "
            "MongoDBManager::initialize(); "
            "$collection = MongoDBManager::getCollection('test_performance'); "
            "$start = microtime(true); "
            "for ($i = 0; $i < 20; $i++) { "
            "    $result = $collection->insertOne(['test' => 'data', 'index' => $i, 'timestamp' => new MongoDB\\BSON\\UTCDateTime()]); "
            "    $collection->deleteOne(['_id' => $result->getInsertedId()]); "
            "} "
            "$time = (microtime(true) - $start) * 1000; "
            "echo 'Inventory operations: ' . round($time, 2) . 'ms' . PHP_EOL;"
        ));
    }
};

int main() {
    PerformanceTester tester;
    
    std::cout << "=== C++ Performance Tester ===" << std::endl;
    std::cout << "Running from any directory..." << std::endl;
    std::cout << "==============================" << std::endl;
    
    tester.runPhpPerformanceTest();
    std::cout << std::endl;
    
    tester.runLoadTest();
    std::cout << std::endl;
    
    tester.runInventoryTest();
    
    std::cout << "==============================" << std::endl;
    std::cout << "All tests completed!" << std::endl;
    
    return 0;
}
