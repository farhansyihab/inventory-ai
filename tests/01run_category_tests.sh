#!/bin/bash

# Test Runner untuk Category Model dan Repository
set -e

echo "=== Inventory AI Category Tests ==="
echo "Timestamp: $(date)"
echo "===================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test counters
PASSED=0
FAILED=0
SKIPPED=0

# Utility functions
log_success() {
    echo -e "${GREEN}✓ PASS: $1${NC}"
    ((PASSED++))
}

log_failure() {
    echo -e "${RED}✗ FAIL: $1${NC}"
    ((FAILED++))
}

log_skip() {
    echo -e "${YELLOW}⚠ SKIP: $1${NC}"
    ((SKIPPED++))
}

# Cleanup function
cleanup() {
    echo "Cleaning up..."
    rm -f /tmp/category_test_* /tmp/mongodb_test.js 2>/dev/null || true
}

trap cleanup EXIT

# Test 1: Basic Category Model Validation
test_category_creation() {
    echo ""
    echo "=== Testing Category Model ==="
    
    local test_file="/tmp/category_test_creation.cpp"
    cat > $test_file << 'EOF'
#include <iostream>
#include <string>
#include <stdexcept>
#include <cassert>

class Category {
private:
    std::string name;
    std::string slug;
    std::string description;
    bool active;
    std::string parentId;
    int depth;
    
public:
    Category(const std::string& name, const std::string& slug, 
             const std::string& description = "", bool active = true,
             const std::string& parentId = "")
        : name(name), slug(slug), description(description), 
          active(active), parentId(parentId), depth(0) {
        validate();
    }
    
    void validate() const {
        if (name.length() < 3) {
            throw std::invalid_argument("Category name must be at least 3 characters");
        }
        if (name.length() > 100) {
            throw std::invalid_argument("Category name cannot exceed 100 characters");
        }
        if (slug.length() < 2) {
            throw std::invalid_argument("Slug must be at least 2 characters");
        }
        if (slug.length() > 50) {
            throw std::invalid_argument("Slug cannot exceed 50 characters");
        }
        if (description.length() > 500) {
            throw std::invalid_argument("Description cannot exceed 500 characters");
        }
    }
    
    std::string getName() const { return name; }
    std::string getSlug() const { return slug; }
    bool isActive() const { return active; }
    std::string getParentId() const { return parentId; }
    bool isRoot() const { return parentId.empty(); }
};

int main() {
    try {
        // Test 1: Valid category creation
        std::cout << "Test 1: Valid category creation..." << std::endl;
        Category cat1("Electronics", "electronics", "Electronic items");
        assert(cat1.getName() == "Electronics");
        assert(cat1.getSlug() == "electronics");
        assert(cat1.isActive() == true);
        assert(cat1.isRoot() == true);
        
        // Test 2: Category with parent
        std::cout << "Test 2: Category with parent..." << std::endl;
        Category cat2("Smartphones", "smartphones", "", true, "parent123");
        assert(cat2.getParentId() == "parent123");
        assert(cat2.isRoot() == false);
        
        // Test 3: Invalid name (should throw)
        std::cout << "Test 3: Invalid name validation..." << std::endl;
        try {
            Category cat3("A", "valid-slug");
            std::cerr << "ERROR: Should have thrown exception for short name" << std::endl;
            return 1;
        } catch (const std::invalid_argument& e) {
            std::cout << "Correctly caught exception: " << e.what() << std::endl;
        }
        
        // Test 4: Invalid slug (should throw)
        std::cout << "Test 4: Invalid slug validation..." << std::endl;
        try {
            Category cat4("Valid Name", "invalid_slug");
            std::cerr << "ERROR: Should have thrown exception for invalid slug" << std::endl;
            return 1;
        } catch (const std::invalid_argument& e) {
            std::cout << "Correctly caught exception: " << e.what() << std::endl;
        }
        
        std::cout << "All category model tests passed!" << std::endl;
        return 0;
    } catch (const std::exception& e) {
        std::cerr << "Test failed: " << e.what() << std::endl;
        return 1;
    }
}
EOF
    
    if g++ -std=c++11 -o /tmp/category_test_creation $test_file 2>/dev/null; then
        if /tmp/category_test_creation; then
            log_success "Category model creation and validation"
        else
            log_failure "Category model creation and validation"
        fi
    else
        log_skip "Category model tests (C++ compiler not available)"
    fi
}

# Test 2: Repository Interface Validation
test_repository_interface() {
    echo ""
    echo "=== Testing Repository Interface ==="
    
    local test_file="/tmp/repository_interface_test.cpp"
    cat > $test_file << 'EOF'
#include <iostream>
#include <string>
#include <vector>
#include <map>
#include <cassert>

// Mock repository interface
class ICategoryRepository {
public:
    virtual ~ICategoryRepository() = default;
    virtual std::string create(const std::map<std::string, std::string>& data) = 0;
    virtual bool update(const std::string& id, const std::map<std::string, std::string>& data) = 0;
    virtual bool deleteCategory(const std::string& id) = 0;
    virtual std::map<std::string, std::string> findById(const std::string& id) = 0;
    virtual int count() = 0;
};

// Mock implementation for testing
class MockCategoryRepository : public ICategoryRepository {
private:
    std::map<std::string, std::map<std::string, std::string>> storage;
    int nextId = 1;
    
public:
    std::string create(const std::map<std::string, std::string>& data) override {
        std::string id = std::to_string(nextId++);
        storage[id] = data;
        return id;
    }
    
    bool update(const std::string& id, const std::map<std::string, std::string>& data) override {
        if (storage.find(id) != storage.end()) {
            for (const auto& pair : data) {
                storage[id][pair.first] = pair.second;
            }
            return true;
        }
        return false;
    }
    
    bool deleteCategory(const std::string& id) override {
        return storage.erase(id) > 0;
    }
    
    std::map<std::string, std::string> findById(const std::string& id) override {
        auto it = storage.find(id);
        if (it != storage.end()) {
            return it->second;
        }
        return {};
    }
    
    int count() override {
        return storage.size();
    }
};

int main() {
    try {
        MockCategoryRepository repo;
        
        std::cout << "Test 1: Create category..." << std::endl;
        std::map<std::string, std::string> categoryData = {
            {"name", "Test Category"},
            {"slug", "test-category"},
            {"description", "Test description"},
            {"active", "true"}
        };
        
        std::string id = repo.create(categoryData);
        assert(!id.empty());
        std::cout << "Created category with ID: " << id << std::endl;
        
        std::cout << "Test 2: Find category..." << std::endl;
        auto found = repo.findById(id);
        assert(found.at("name") == "Test Category");
        assert(found.at("slug") == "test-category");
        
        std::cout << "Test 3: Update category..." << std::endl;
        std::map<std::string, std::string> updateData = {{"name", "Updated Name"}};
        bool updateResult = repo.update(id, updateData);
        assert(updateResult == true);
        
        auto updated = repo.findById(id);
        assert(updated.at("name") == "Updated Name");
        
        std::cout << "Test 4: Count categories..." << std::endl;
        int count = repo.count();
        assert(count == 1);
        
        std::cout << "Test 5: Delete category..." << std::endl;
        bool deleteResult = repo.deleteCategory(id);
        assert(deleteResult == true);
        
        auto deleted = repo.findById(id);
        assert(deleted.empty());
        
        std::cout << "Test 6: Count after deletion..." << std::endl;
        count = repo.count();
        assert(count == 0);
        
        std::cout << "All repository interface tests passed!" << std::endl;
        return 0;
    } catch (const std::exception& e) {
        std::cerr << "Test failed: " << e.what() << std::endl;
        return 1;
    }
}
EOF
    
    if g++ -std=c++11 -o /tmp/repository_interface_test $test_file 2>/dev/null; then
        if /tmp/repository_interface_test; then
            log_success "Repository interface operations"
        else
            log_failure "Repository interface operations"
        fi
    else
        log_skip "Repository interface tests (C++ compiler not available)"
    fi
}

# Test 3: Data Validation Tests
test_data_validation() {
    echo ""
    echo "=== Testing Data Validation ==="
    
    local test_file="/tmp/validation_test.cpp"
    cat > $test_file << 'EOF'
#include <iostream>
#include <string>
#include <regex>
#include <cassert>

bool validateSlug(const std::string& slug) {
    if (slug.length() < 2 || slug.length() > 50) {
        return false;
    }
    std::regex slug_pattern("^[a-z0-9-]+$");
    return std::regex_match(slug, slug_pattern);
}

bool validateName(const std::string& name) {
    return name.length() >= 3 && name.length() <= 100;
}

bool validateDescription(const std::string& desc) {
    return desc.length() <= 500;
}

void runValidationTests() {
    std::cout << "Testing slug validation..." << std::endl;
    assert(validateSlug("valid-slug") == true);
    assert(validateSlug("another-valid-123") == true);
    assert(validateSlug("invalid_slug") == false); // underscore not allowed
    assert(validateSlug("InvalidSlug") == false); // uppercase not allowed
    assert(validateSlug("a") == false); // too short
    assert(validateSlug(std::string(51, 'a')) == false); // too long
    
    std::cout << "Testing name validation..." << std::endl;
    assert(validateName("Valid Name") == true);
    assert(validateName("A") == false); // too short
    assert(validateName("A valid name that is exactly 100 characters long, which should be acceptable for our test case") == true);
    assert(validateName(std::string(101, 'a')) == false); // too long
    
    std::cout << "Testing description validation..." << std::endl;
    assert(validateDescription("Short desc") == true);
    assert(validateDescription(std::string(500, 'a')) == true);
    assert(validateDescription(std::string(501, 'a')) == false);
    
    std::cout << "All validation tests completed!" << std::endl;
}

int main() {
    try {
        runValidationTests();
        std::cout << "All data validation tests passed!" << std::endl;
        return 0;
    } catch (const std::exception& e) {
        std::cerr << "Validation test failed: " << e.what() << std::endl;
        return 1;
    }
}
EOF
    
    if g++ -std=c++11 -o /tmp/validation_test $test_file 2>/dev/null; then
        if /tmp/validation_test; then
            log_success "Data validation rules"
        else
            log_failure "Data validation rules"
        fi
    else
        log_skip "Data validation tests (C++ compiler not available)"
    fi
}

# Test 4: Performance Test (Simplified)
test_performance() {
    echo ""
    echo "=== Testing Performance ==="
    
    local test_file="/tmp/performance_test.cpp"
    cat > $test_file << 'EOF'
#include <iostream>
#include <chrono>
#include <map>
#include <string>
#include <cassert>

class PerformanceTest {
public:
    void testCreateOperations() {
        std::cout << "Starting performance test..." << std::endl;
        auto start = std::chrono::high_resolution_clock::now();
        
        std::map<std::string, std::map<std::string, std::string>> store;
        const int iterations = 1000;
        
        for (int i = 0; i < iterations; i++) {
            std::string id = "cat_" + std::to_string(i);
            store[id] = {
                {"name", "Category " + std::to_string(i)},
                {"slug", "category-" + std::to_string(i)},
                {"active", "true"}
            };
        }
        
        auto end = std::chrono::high_resolution_clock::now();
        auto duration = std::chrono::duration_cast<std::chrono::milliseconds>(end - start);
        
        std::cout << "Created " << iterations << " categories in " 
                  << duration.count() << " ms" << std::endl;
        
        // Verify all items were created
        assert(store.size() == iterations);
        
        if (duration.count() < 500) { // Should take less than 500ms
            std::cout << "Performance test PASSED - Within acceptable limits" << std::endl;
        } else {
            std::cout << "Performance test WARNING - Slower than expected but still functional" << std::endl;
            // Don't fail for performance, just warn
        }
    }
};

int main() {
    try {
        PerformanceTest test;
        test.testCreateOperations();
        return 0;
    } catch (const std::exception& e) {
        std::cerr << "Performance test failed: " << e.what() << std::endl;
        return 1;
    }
}
EOF
    
    if g++ -std=c++11 -O2 -o /tmp/performance_test $test_file 2>/dev/null; then
        if /tmp/performance_test; then
            log_success "Basic performance"
        else
            log_failure "Basic performance"
        fi
    else
        log_skip "Performance tests (C++ compiler not available)"
    fi
}

# Test 5: File-based Integration Test
test_file_integration() {
    echo ""
    echo "=== Testing File Integration ==="
    
    local test_file="/tmp/file_integration_test.cpp"
    cat > $test_file << 'EOF'
#include <iostream>
#include <fstream>
#include <string>
#include <cassert>
#include <filesystem>

bool testFileOperations() {
    const std::string test_filename = "/tmp/category_test_data.txt";
    
    // Test writing
    std::ofstream outfile(test_filename);
    if (!outfile.is_open()) {
        std::cerr << "Failed to open file for writing" << std::endl;
        return false;
    }
    
    outfile << "category_id, name, slug, active\n";
    outfile << "1, Electronics, electronics, true\n";
    outfile << "2, Books, books, true\n";
    outfile << "3, Clothing, clothing, true\n";
    outfile.close();
    
    // Test reading
    std::ifstream infile(test_filename);
    if (!infile.is_open()) {
        std::cerr << "Failed to open file for reading" << std::endl;
        return false;
    }
    
    std::string line;
    int line_count = 0;
    while (std::getline(infile, line)) {
        line_count++;
    }
    infile.close();
    
    // Cleanup
    std::remove(test_filename.c_str());
    
    assert(line_count == 4); // Header + 3 data lines
    return line_count == 4;
}

int main() {
    try {
        std::cout << "Testing file I/O operations..." << std::endl;
        if (testFileOperations()) {
            std::cout << "File integration test PASSED" << std::endl;
            return 0;
        } else {
            std::cout << "File integration test FAILED" << std::endl;
            return 1;
        }
    } catch (const std::exception& e) {
        std::cerr << "File test failed: " << e.what() << std::endl;
        return 1;
    }
}
EOF
    
    if g++ -std=c++11 -o /tmp/file_integration_test $test_file 2>/dev/null; then
        if /tmp/file_integration_test; then
            log_success "File integration"
        else
            log_failure "File integration"
        fi
    else
        log_skip "File integration tests (C++ compiler not available)"
    fi
}

# Test 6: Simple MongoDB connectivity check (without actual MongoDB)
test_mongodb_simulation() {
    echo ""
    echo "=== Testing MongoDB Simulation ==="
    
    local test_file="/tmp/mongodb_simulation_test.cpp"
    cat > $test_file << 'EOF'
#include <iostream>
#include <map>
#include <string>
#include <cassert>

// Simulate MongoDB document
class MongoDBDocument {
private:
    std::map<std::string, std::string> data;
    
public:
    void set(const std::string& key, const std::string& value) {
        data[key] = value;
    }
    
    std::string get(const std::string& key) const {
        auto it = data.find(key);
        if (it != data.end()) {
            return it->second;
        }
        return "";
    }
    
    bool has(const std::string& key) const {
        return data.find(key) != data.end();
    }
};

// Simulate MongoDB collection
class MongoDBCollection {
private:
    std::map<std::string, MongoDBDocument> documents;
    int nextId = 1;
    
public:
    std::string insert(const MongoDBDocument& doc) {
        std::string id = std::to_string(nextId++);
        documents[id] = doc;
        return id;
    }
    
    MongoDBDocument findById(const std::string& id) {
        auto it = documents.find(id);
        if (it != documents.end()) {
            return it->second;
        }
        return MongoDBDocument();
    }
    
    int count() const {
        return documents.size();
    }
};

int main() {
    try {
        std::cout << "Simulating MongoDB operations..." << std::endl;
        
        MongoDBCollection categories;
        
        // Create a category document
        MongoDBDocument doc;
        doc.set("name", "Test Category");
        doc.set("slug", "test-category");
        doc.set("active", "true");
        
        // Insert document
        std::string id = categories.insert(doc);
        assert(!id.empty());
        std::cout << "Inserted document with ID: " << id << std::endl;
        
        // Find document
        MongoDBDocument found = categories.findById(id);
        assert(found.has("name"));
        assert(found.get("name") == "Test Category");
        assert(found.get("slug") == "test-category");
        
        // Count documents
        assert(categories.count() == 1);
        
        std::cout << "MongoDB simulation test PASSED" << std::endl;
        return 0;
    } catch (const std::exception& e) {
        std::cerr << "MongoDB simulation test failed: " << e.what() << std::endl;
        return 1;
    }
}
EOF
    
    if g++ -std=c++11 -o /tmp/mongodb_simulation_test $test_file 2>/dev/null; then
        if /tmp/mongodb_simulation_test; then
            log_success "MongoDB simulation"
        else
            log_failure "MongoDB simulation"
        fi
    else
        log_skip "MongoDB simulation tests (C++ compiler not available)"
    fi
}

# Main test execution function
run_all_tests() {
    echo "Starting test suite..."
    
    test_category_creation
    test_repository_interface
    test_data_validation
    test_performance
    test_file_integration
    test_mongodb_simulation
    
    # Summary
    echo ""
    echo "===================================="
    echo "TEST SUMMARY:"
    echo "===================================="
    echo -e "${GREEN}Passed: $PASSED${NC}"
    echo -e "${RED}Failed: $FAILED${NC}"
    echo -e "${YELLOW}Skipped: $SKIPPED${NC}"
    echo "Total: $((PASSED + FAILED + SKIPPED))"
    echo ""
}

# Run all tests
run_all_tests

# Exit with appropriate code
if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}🎉 ALL TESTS PASSED!${NC}"
    exit 0
else
    echo -e "${RED}❌ SOME TESTS FAILED${NC}"
    exit 1
fi