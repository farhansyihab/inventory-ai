#!/bin/bash
echo "menuju root project"
cd ..
echo "posisi root project"
echo "=== Inventory AI Comprehensive Test Suite ==="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to run tests with error handling
run_test() {
    local test_name=$1
    local test_command=$2
    
    echo -e "${YELLOW}Running $test_name...${NC}"
    if eval $test_command; then
        echo -e "${GREEN}✅ $test_name PASSED${NC}"
        return 0
    else
        echo -e "${RED}❌ $test_name FAILED${NC}"
        return 1
    fi
}

# Run tests sequentially
overall_result=0

echo -e "${YELLOW}=== Unit Tests ===${NC}"
run_test "Unit Tests" "./vendor/bin/phpunit tests/Unit/" || overall_result=1

echo -e "${YELLOW}=== Integration Tests ===${NC}"
run_test "Integration Tests" "./vendor/bin/phpunit tests/Integration/" || overall_result=1

echo -e "${YELLOW}=== Functional Tests ===${NC}"
if [ -d "tests/Functional" ] && [ -n "$(ls tests/Functional)" ]; then
    run_test "Functional Tests" "./vendor/bin/phpunit tests/Functional/" || overall_result=1
else
    echo -e "${YELLOW}⚠️  No functional tests found, skipping...${NC}"
fi

echo -e "${YELLOW}=== All Tests Summary ===${NC}"
if [ $overall_result -eq 0 ]; then
    echo -e "${GREEN}🎉 ALL TEST SUITES PASSED!${NC}"
else
    echo -e "${RED}💥 SOME TESTS FAILED!${NC}"
fi

echo ""
echo -e "${YELLOW}=== Coverage Report ===${NC}"
if [ -f "tests/coverage/index.html" ]; then
    echo -e "${GREEN}Coverage report available at: tests/coverage/index.html${NC}"
else
    echo -e "${YELLOW}Run with coverage: ./vendor/bin/phpunit --coverage-html tests/coverage${NC}"
fi

exit $overall_result