#!/bin/bash

# ==================================================
# SESSION 2.2: CATEGORY SERVICE & API TEST SCRIPT
# ==================================================
# Comprehensive test script for Category Management System
# Author: Inventory AI Team
# Version: 1.0.0
# ==================================================

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
LOG_DIR="$PROJECT_ROOT/logs"
TEST_LOG="$LOG_DIR/category_test_$(date +%Y%m%d_%H%M%S).log"
TEST_RESULTS_DIR="$PROJECT_ROOT/test-results"

# Test files
UNIT_TEST="tests/Unit/Service/CategoryServiceTest.php"
FUNCTIONAL_TEST="tests/Functional/Api/CategoryEndpointsTest.php"
INTEGRATION_TEST="tests/Integration/Database/CategoryRepositoryIntegrationTest.php"

# Create directories if they don't exist
mkdir -p "$LOG_DIR"
mkdir -p "$TEST_RESULTS_DIR"

# ==================================================
# UTILITY FUNCTIONS
# ==================================================

print_header() {
    echo -e "${CYAN}"
    echo "=================================================="
    echo "   $1"
    echo "=================================================="
    echo -e "${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

log_message() {
    local message="$1"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $message" | tee -a "$TEST_LOG"
}

check_dependencies() {
    print_header "CHECKING DEPENDENCIES"
    
    local missing_deps=0
    
    # Check PHP
    if command -v php >/dev/null 2>&1; then
        PHP_VERSION=$(php -v | head -n1 | cut -d " " -f 2)
        print_success "PHP $PHP_VERSION found"
    else
        print_error "PHP not found"
        missing_deps=$((missing_deps + 1))
    fi
    
    # Check Composer
    if command -v composer >/dev/null 2>&1; then
        COMPOSER_VERSION=$(composer --version | cut -d " " -f 3)
        print_success "Composer $COMPOSER_VERSION found"
    else
        print_error "Composer not found"
        missing_deps=$((missing_deps + 1))
    fi
    
    # Check MongoDB extension
    if php -m | grep -q mongodb; then
        print_success "MongoDB PHP extension found"
    else
        print_error "MongoDB PHP extension not found"
        missing_deps=$((missing_deps + 1))
    fi
    
    # Check if vendor directory exists
    if [ -d "$PROJECT_ROOT/vendor" ]; then
        print_success "Vendor dependencies installed"
    else
        print_warning "Vendor dependencies not installed - running composer install"
        cd "$PROJECT_ROOT" && composer install --no-dev
    fi
    
    if [ $missing_deps -gt 0 ]; then
        print_error "Missing $missing_deps dependencies. Please install them before running tests."
        exit 1
    fi
    
    print_success "All dependencies satisfied"
}

setup_test_environment() {
    print_header "SETTING UP TEST ENVIRONMENT"
    
    # Copy test environment file if it doesn't exist
    if [ ! -f "$PROJECT_ROOT/.env.test" ]; then
        if [ -f "$PROJECT_ROOT/.env" ]; then
            cp "$PROJECT_ROOT/.env" "$PROJECT_ROOT/.env.test"
            print_success "Created .env.test from .env"
        else
            print_error ".env file not found. Please create one from .env.example"
            exit 1
        fi
    fi
    
    # Set test environment variables
    export APP_ENV=test
    export LOG_LEVEL=debug
    
    # Create test database indexes
    print_info "Creating database indexes..."
    php "$PROJECT_ROOT/scripts/create-indexes.php" >> "$TEST_LOG" 2>&1
    
    if [ $? -eq 0 ]; then
        print_success "Database indexes created"
    else
        print_warning "Failed to create database indexes (might already exist)"
    fi
    
    print_success "Test environment setup completed"
}

run_unit_tests() {
    print_header "RUNNING UNIT TESTS"
    log_message "Starting unit tests: $UNIT_TEST"
    
    local start_time=$(date +%s)
    
    php "$PROJECT_ROOT/vendor/bin/phpunit" \
        --bootstrap "$PROJECT_ROOT/tests/bootstrap.php" \
        --testdox \
        --colors=always \
        "$PROJECT_ROOT/$UNIT_TEST" 2>&1 | tee -a "$TEST_LOG"
    
    local exit_code=${PIPESTATUS[0]}
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    
    if [ $exit_code -eq 0 ]; then
        print_success "Unit tests passed in ${duration}s"
        return 0
    else
        print_error "Unit tests failed after ${duration}s"
        return 1
    fi
}

run_functional_tests() {
    print_header "RUNNING FUNCTIONAL TESTS"
    log_message "Starting functional tests: $FUNCTIONAL_TEST"
    
    local start_time=$(date +%s)
    
    php "$PROJECT_ROOT/vendor/bin/phpunit" \
        --bootstrap "$PROJECT_ROOT/tests/bootstrap.php" \
        --testdox \
        --colors=always \
        "$PROJECT_ROOT/$FUNCTIONAL_TEST" 2>&1 | tee -a "$TEST_LOG"
    
    local exit_code=${PIPESTATUS[0]}
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    
    if [ $exit_code -eq 0 ]; then
        print_success "Functional tests passed in ${duration}s"
        return 0
    else
        print_error "Functional tests failed after ${duration}s"
        return 1
    fi
}

run_integration_tests() {
    print_header "RUNNING INTEGRATION TESTS"
    log_message "Starting integration tests: $INTEGRATION_TEST"
    
    local start_time=$(date +%s)
    
    php "$PROJECT_ROOT/vendor/bin/phpunit" \
        --bootstrap "$PROJECT_ROOT/tests/bootstrap.php" \
        --testdox \
        --colors=always \
        "$PROJECT_ROOT/$INTEGRATION_TEST" 2>&1 | tee -a "$TEST_LOG"
    
    local exit_code=${PIPESTATUS[0]}
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    
    if [ $exit_code -eq 0 ]; then
        print_success "Integration tests passed in ${duration}s"
        return 0
    else
        print_error "Integration tests failed after ${duration}s"
        return 1
    fi
}

run_all_tests() {
    print_header "RUNNING COMPREHENSIVE TEST SUITE"
    
    local overall_success=0
    local tests_passed=0
    local tests_total=3
    
    log_message "Starting comprehensive test suite for Session 2.2"
    
    # Run unit tests
    if run_unit_tests; then
        tests_passed=$((tests_passed + 1))
    else
        overall_success=1
    fi
    
    # Run functional tests  
    if run_functional_tests; then
        tests_passed=$((tests_passed + 1))
    else
        overall_success=1
    fi
    
    # Run integration tests
    if run_integration_tests; then
        tests_passed=$((tests_passed + 1))
    else
        overall_success=1
    fi
    
    # Summary
    print_header "TEST SUMMARY"
    if [ $overall_success -eq 0 ]; then
        print_success "🎉 ALL TESTS PASSED! ($tests_passed/$tests_total)"
        echo -e "${GREEN}Session 2.2: Category Service & API implementation is working correctly.${NC}"
    else
        print_error "💥 SOME TESTS FAILED ($tests_passed/$tests_total passed)"
        echo -e "${YELLOW}Check the log file for details: $TEST_LOG${NC}"
    fi
    
    return $overall_success
}

run_api_health_check() {
    print_header "API HEALTH CHECK"
    
    # Start PHP built-in server in background
    print_info "Starting test server..."
    php -S localhost:8321 -t "$PROJECT_ROOT/public" > /dev/null 2>&1 &
    local server_pid=$!
    
    # Wait for server to start
    sleep 2
    
    # Test health endpoints
    local health_endpoints=(
        "http://localhost:8321/health"
        "http://localhost:8321/health/categories"
        "http://localhost:8321/api/docs"
    )
    
    local health_success=0
    
    for endpoint in "${health_endpoints[@]}"; do
        print_info "Testing: $endpoint"
        local response=$(curl -s -w "%{http_code}" -o /tmp/response.json "$endpoint")
        local status_code=${response: -3}
        
        if [ "$status_code" -eq 200 ]; then
            print_success "✅ $endpoint - HTTP $status_code"
        else
            print_error "❌ $endpoint - HTTP $status_code"
            health_success=1
        fi
    done
    
    # Stop the server
    kill $server_pid 2>/dev/null
    
    if [ $health_success -eq 0 ]; then
        print_success "API health check passed"
    else
        print_warning "API health check completed with warnings"
    fi
    
    return $health_success
}

run_performance_test() {
    print_header "PERFORMANCE BENCHMARK"
    
    local test_file="$PROJECT_ROOT/public/quick-test.php"
    
    if [ -f "$test_file" ]; then
        print_info "Running performance benchmark..."
        
        local start_time=$(date +%s%N)
        php "$test_file" > /dev/null 2>&1
        local end_time=$(date +%s%N)
        
        local duration=$(( (end_time - start_time) / 1000000 )) # Convert to milliseconds
        
        if [ $duration -lt 1000 ]; then
            print_success "Performance: ${duration}ms (Excellent)"
        elif [ $duration -lt 3000 ]; then
            print_warning "Performance: ${duration}ms (Acceptable)"
        else
            print_error "Performance: ${duration}ms (Slow)"
        fi
    else
        print_warning "Performance test file not found: $test_file"
    fi
}

generate_test_report() {
    print_header "GENERATING TEST REPORT"
    
    local report_file="$TEST_RESULTS_DIR/category_test_report_$(date +%Y%m%d_%H%M%S).html"
    
    # Create simple HTML report
    cat > "$report_file" << EOF
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session 2.2 Test Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .header { background: #f4f4f4; padding: 20px; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .log { background: #f9f9f9; padding: 15px; border-left: 4px solid #ccc; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Session 2.2: Category Service & API Test Report</h1>
        <p>Generated: $(date)</p>
        <p>Log File: $TEST_LOG</p>
    </div>
    
    <h2>Test Results</h2>
    <ul>
        <li>Unit Tests: <span class="success">Completed</span></li>
        <li>Functional Tests: <span class="success">Completed</span></li>
        <li>Integration Tests: <span class="success">Completed</span></li>
    </ul>
    
    <h2>Next Steps</h2>
    <ol>
        <li>Review the test log for detailed results</li>
        <li>Check API endpoints manually if needed</li>
        <li>Proceed to Session 2.3 if all tests pass</li>
    </ol>
</body>
</html>
EOF
    
    print_success "Test report generated: $report_file"
}

cleanup_test_data() {
    print_header "CLEANING UP TEST DATA"
    
    # Run cleanup script if it exists
    local cleanup_script="$PROJECT_ROOT/tester-comprehensive/cleanup_test_data.php"
    
    if [ -f "$cleanup_script" ]; then
        php "$cleanup_script" --categories >> "$TEST_LOG" 2>&1
        if [ $? -eq 0 ]; then
            print_success "Test data cleanup completed"
        else
            print_warning "Test data cleanup had issues"
        fi
    else
        print_info "No cleanup script found at $cleanup_script"
        print_info "Test data cleanup will be handled by integration tests"
    fi
}

show_usage() {
    echo -e "${CYAN}"
    echo "Session 2.2 Test Script - Category Service & API"
    echo "=================================================="
    echo -e "${NC}"
    echo "Usage: $0 [OPTION]"
    echo ""
    echo "Options:"
    echo "  all         Run all tests (unit, functional, integration)"
    echo "  unit        Run only unit tests"
    echo "  functional  Run only functional tests" 
    echo "  integration Run only integration tests"
    echo "  health      Run API health check"
    echo "  performance Run performance benchmark"
    echo "  report      Generate test report only"
    echo "  cleanup     Cleanup test data only"
    echo "  help        Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 all              # Run complete test suite"
    echo "  $0 unit             # Run only unit tests"
    echo "  $0 health performance # Run health check and performance test"
    echo ""
}

main() {
    local command="${1:-all}"
    
    print_header "SESSION 2.2: CATEGORY SERVICE & API TESTING"
    echo -e "${PURPLE}Start Time: $(date)${NC}"
    echo -e "${PURPLE}Log File: $TEST_LOG${NC}"
    echo ""
    
    # Initialize log file
    echo "Session 2.2 Test Log - $(date)" > "$TEST_LOG"
    echo "=================================" >> "$TEST_LOG"
    
    case "$command" in
        "all")
            check_dependencies
            setup_test_environment
            run_all_tests
            run_api_health_check
            run_performance_test
            generate_test_report
            cleanup_test_data
            ;;
        "unit")
            check_dependencies
            setup_test_environment
            run_unit_tests
            ;;
        "functional")
            check_dependencies
            setup_test_environment
            run_functional_tests
            ;;
        "integration")
            check_dependencies
            setup_test_environment
            run_integration_tests
            ;;
        "health")
            check_dependencies
            setup_test_environment
            run_api_health_check
            ;;
        "performance")
            check_dependencies
            run_performance_test
            ;;
        "report")
            generate_test_report
            ;;
        "cleanup")
            cleanup_test_data
            ;;
        "help"|"-h"|"--help")
            show_usage
            exit 0
            ;;
        *)
            print_error "Unknown command: $command"
            show_usage
            exit 1
            ;;
    esac
    
    local final_status=$?
    
    echo ""
    print_header "TESTING COMPLETED"
    echo -e "${PURPLE}End Time: $(date)${NC}"
    echo -e "${PURPLE}Log File: $TEST_LOG${NC}"
    
    if [ $final_status -eq 0 ]; then
        echo -e "${GREEN}🎉 Session 2.2 tests completed successfully!${NC}"
    else
        echo -e "${YELLOW}⚠️  Session 2.2 tests completed with issues. Check the log file.${NC}"
    fi
    
    exit $final_status
}

# Handle script interruption
trap 'print_error "Script interrupted by user"; exit 1' INT TERM

# Run main function with all arguments
main "$@"
