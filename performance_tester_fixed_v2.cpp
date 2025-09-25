
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
            // Child process
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

public:
    void runPhpPerformanceTest() {
        std::cout << "Running PHP Performance Tests..." << std::endl;

        auto start = std::chrono::high_resolution_clock::now();

        // Test 1: MongoDB Connection
        std::cout << "1. Testing MongoDB Connection..." << std::endl;
        runPhpCode(
            "require 'vendor/autoload.php'; "
            "use App\\Config\\MongoDBManager; "
            "MongoDBManager::initialize(); "
            "echo MongoDBManager::ping() ? '✅ Connected' : '❌ Failed'; "
            "echo PHP_EOL;"
        );

        // Test 2: User Operations Performance
        std::cout << "2. Testing User Operations..." << std::endl;
        runPhpCode(
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
        );

        auto end = std::chrono::high_resolution_clock::now();
        auto duration = std::chrono::duration_cast<std::chrono::milliseconds>(end - start);
        std::cout << "Total test duration: " << duration.count() << "ms" << std::endl;
    }

    void runLoadTest() {
        std::cout << "Running Load Test..." << std::endl;

        auto start = std::chrono::high_resolution_clock::now();

        runPhpCode(
            "require 'vendor/autoload.php'; "
            "use App\\Config\\MongoDBManager; "
            "use App\\Model\\Inventory; "
            "use App\\Repository\\InventoryRepository; "
            "MongoDBManager::initialize(); "
            "$repo = new InventoryRepository(); "
            "for ($i = 0; $i < 100; $i++) { "
            "    $item = new Inventory('Item'.$i, 'Desc'.$i, 10+$i, 100.0+$i); "
            "    $repo->saveInventory($item); "
            "    $repo->deleteInventory($item); "
            "} "
            "echo 'Completed 100 iterations.' . PHP_EOL;"
        );

        auto end = std::chrono::high_resolution_clock::now();
        auto duration = std::chrono::duration_cast<std::chrono::milliseconds>(end - start);
        std::cout << "Load test duration: " << duration.count() << "ms" << std::endl;
    }
};

int main() {
    PerformanceTester tester;
    tester.runPhpPerformanceTest();
    tester.runLoadTest();
    return 0;
}
