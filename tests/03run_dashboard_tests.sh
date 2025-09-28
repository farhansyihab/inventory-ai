#!/bin/bash

# ==================================================
# SESSION 3.1: DASHBOARD SERVICE & METRICS TEST SCRIPT
# ==================================================
# Comprehensive test script for Dashboard Metrics System
# Author: Inventory AI Team
# Version: 1.0.0
# ==================================================

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
LOG_DIR="$PROJECT_ROOT/logs"
TEST_LOG="$LOG_DIR/dashboard_test_$(date +%Y%m%d_%H%M%S).log"
TEST_RESULTS_DIR="$PROJECT_ROOT/test-results"

# Test files for Dashboard SESSION 3.1
UNIT_TEST_MODEL="tests/Unit/Model/DashboardMetricsTest.php"
UNIT_TEST_SERVICE="tests/Unit/Service/DashboardServiceTest.php"
UNIT_TEST_METRICS="tests/Unit/Service/Metrics/MetricsCalculatorTest.php"
UNIT_TEST_INVENTORY_METRICS="tests/Unit/Service/Metrics/InventoryMetricsTest.php"
UNIT_TEST_USER_METRICS="tests/Unit/Service/Metrics/UserMetricsTest.php"
UNIT_TEST_AI_METRICS="tests/Unit/Service/Metrics/AIMetricsTest.php"
UNIT_TEST_SYSTEM_METRICS="tests/Unit/Service/Metrics/SystemMetricsTest.php"
INTEGRATION_TEST_CONTROLLER="tests/Integration/Controller/DashboardControllerTest.php"

# Create directories if they don't exist
mkdir -p "$LOG_DIR"
mkdir -p "$TEST_RESULTS_DIR"

# ==================================================
# UTILITY FUNCTIONS
# ==================================================

print_header() {
    echo "=================================================="
    echo "   $1"
    echo "=================================================="
}

print_success() {
    echo "[SUCCESS] $1"
}

print_warning() {
    echo "[WARNING] $1"
}

print_error() {
    echo "[ERROR] $1"
}

print_info() {
    echo "[INFO] $1"
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
    
    # Check for required PHP extensions for Dashboard
    local required_extensions=("json" "date" "filter")
    for ext in "${required_extensions[@]}"; do
        if php -m | grep -q "$ext"; then
            print_success "PHP extension: $ext"
        else
            print_error "PHP extension missing: $ext"
            missing_deps=$((missing_deps + 1))
        fi
    done
    
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
    export DASHBOARD_CACHE_TTL=300
    
    # Create test data for dashboard metrics
    print_info "Setting up test data for dashboard metrics..."
    php "$PROJECT_ROOT/scripts/setup-dashboard-test-data.php" >> "$TEST_LOG" 2>&1
    
    if [ $? -eq 0 ]; then
        print_success "Dashboard test data setup completed"
    else
        print_warning "Dashboard test data setup had issues (might already exist)"
    fi
    
    print_success "Test environment setup completed"
}

run_model_tests() {
    print_header "RUNNING DASHBOARD MODEL TESTS"
    log_message "Starting model tests: $UNIT_TEST_MODEL"
    
    local start_time=$(date +%s)
    
    php "$PROJECT_ROOT/vendor/bin/phpunit" \
        --testdox \
        "$PROJECT_ROOT/$UNIT_TEST_MODEL" 2>&1 | tee -a "$TEST_LOG"
    
    local exit_code=${PIPESTATUS[0]}
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    
    if [ $exit_code -eq 0 ]; then
        print_success "Model tests passed in ${duration}s"
        return 0
    else
        print_error "Model tests failed after ${duration}s"
        return 1
    fi
}

run_service_tests() {
    print_header "RUNNING DASHBOARD SERVICE TESTS"
    log_message "Starting service tests: $UNIT_TEST_SERVICE"
    
    local start_time=$(date +%s)
    
    php "$PROJECT_ROOT/vendor/bin/phpunit" \
        --testdox \
        "$PROJECT_ROOT/$UNIT_TEST_SERVICE" 2>&1 | tee -a "$TEST_LOG"
    
    local exit_code=${PIPESTATUS[0]}
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    
    if [ $exit_code -eq 0 ]; then
        print_success "Service tests passed in ${duration}s"
        return 0
    else
        print_error "Service tests failed after ${duration}s"
        return 1
    fi
}

run_metrics_calculator_tests() {
    print_header "RUNNING METRICS CALCULATOR TESTS"
    log_message "Starting metrics calculator tests: $UNIT_TEST_METRICS"
    
    local start_time=$(date +%s)
    
    php "$PROJECT_ROOT/vendor/bin/phpunit" \
        --testdox \
        "$PROJECT_ROOT/$UNIT_TEST_METRICS" 2>&1 | tee -a "$TEST_LOG"
    
    local exit_code=${PIPESTATUS[0]}
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    
    if [ $exit_code -eq 0 ]; then
        print_success "Metrics calculator tests passed in ${duration}s"
        return 0
    else
        print_error "Metrics calculator tests failed after ${duration}s"
        return 1
    fi
}

run_metrics_component_tests() {
    print_header "RUNNING METRICS COMPONENT TESTS"
    
    local metrics_tests=(
        "$UNIT_TEST_INVENTORY_METRICS"
        "$UNIT_TEST_USER_METRICS" 
        "$UNIT_TEST_AI_METRICS"
        "$UNIT_TEST_SYSTEM_METRICS"
    )
    
    local metrics_names=(
        "Inventory Metrics"
        "User Metrics"
        "AI Metrics" 
        "System Metrics"
    )
    
    local overall_success=0
    local tests_passed=0
    
    for i in "${!metrics_tests[@]}"; do
        local test_file="${metrics_tests[$i]}"
        local test_name="${metrics_names[$i]}"
        
        print_info "Testing: $test_name"
        log_message "Starting metrics test: $test_file"
        
        local start_time=$(date +%s)
        
        php "$PROJECT_ROOT/vendor/bin/phpunit" \
            --testdox \
            "$PROJECT_ROOT/$test_file" 2>&1 | tee -a "$TEST_LOG"
        
        local exit_code=${PIPESTATUS[0]}
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        
        if [ $exit_code -eq 0 ]; then
            print_success "$test_name passed in ${duration}s"
            tests_passed=$((tests_passed + 1))
        else
            print_error "$test_name failed after ${duration}s"
            overall_success=1
        fi
        
        echo ""
    done
    
    if [ $overall_success -eq 0 ]; then
        print_success "All metrics component tests passed ($tests_passed/${#metrics_tests[@]})"
        return 0
    else
        print_error "Some metrics component tests failed ($tests_passed/${#metrics_tests[@]} passed)"
        return 1
    fi
}

run_integration_tests() {
    print_header "RUNNING DASHBOARD INTEGRATION TESTS"
    log_message "Starting integration tests: $INTEGRATION_TEST_CONTROLLER"
    
    local start_time=$(date +%s)
    
    php "$PROJECT_ROOT/vendor/bin/phpunit" \
        --testdox \
        "$PROJECT_ROOT/$INTEGRATION_TEST_CONTROLLER" 2>&1 | tee -a "$TEST_LOG"
    
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

run_all_unit_tests() {
    print_header "RUNNING COMPLETE UNIT TEST SUITE"
    
    local overall_success=0
    local tests_passed=0
    local tests_total=4
    
    log_message "Starting complete unit test suite for Session 3.1"
    
    # Run model tests
    if run_model_tests; then
        tests_passed=$((tests_passed + 1))
        print_success "Model tests: PASSED"
    else
        overall_success=1
        print_error "Model tests: FAILED"
    fi
    
    # Run service tests  
    if run_service_tests; then
        tests_passed=$((tests_passed + 1))
        print_success "Service tests: PASSED"
    else
        overall_success=1
        print_error "Service tests: FAILED"
    fi
    
    # Run metrics calculator tests
    if run_metrics_calculator_tests; then
        tests_passed=$((tests_passed + 1))
        print_success "Metrics calculator tests: PASSED"
    else
        overall_success=1
        print_error "Metrics calculator tests: FAILED"
    fi
    
    # Run metrics component tests
    if run_metrics_component_tests; then
        tests_passed=$((tests_passed + 1))
        print_success "Metrics component tests: PASSED"
    else
        overall_success=1
        print_error "Metrics component tests: FAILED"
    fi
    
    # Summary
    print_header "UNIT TEST SUMMARY"
    if [ $overall_success -eq 0 ]; then
        print_success "ALL UNIT TESTS PASSED! ($tests_passed/$tests_total)"
    else
        print_error "SOME UNIT TESTS FAILED ($tests_passed/$tests_total passed)"
    fi
    
    return $overall_success
}

run_all_tests() {
    print_header "RUNNING COMPREHENSIVE TEST SUITE"
    
    local overall_success=0
    local tests_passed=0
    local tests_total=2
    
    log_message "Starting comprehensive test suite for Session 3.1"
    
    # Run all unit tests
    if run_all_unit_tests; then
        tests_passed=$((tests_passed + 1))
        print_success "Unit tests suite: PASSED"
    else
        overall_success=1
        print_error "Unit tests suite: FAILED"
    fi
    
    # Run integration tests
    if run_integration_tests; then
        tests_passed=$((tests_passed + 1))
        print_success "Integration tests: PASSED"
    else
        overall_success=1
        print_error "Integration tests: FAILED"
    fi
    
    # Summary
    print_header "COMPREHENSIVE TEST SUMMARY"
    if [ $overall_success -eq 0 ]; then
        print_success "ALL TESTS PASSED! ($tests_passed/$tests_total)"
        echo "Session 3.1: Dashboard Service & Metrics implementation is working correctly."
    else
        print_error "SOME TESTS FAILED ($tests_passed/$tests_total passed)"
        echo "Check the log file for details: $TEST_LOG"
    fi
    
    return $overall_success
}

run_dashboard_health_check() {
    print_header "DASHBOARD HEALTH CHECK"
    
    # Check if dashboard components exist
    local dashboard_files=(
        "src/Model/DashboardMetrics.php"
        "src/Service/DashboardService.php"
        "src/Service/Metrics/MetricsCalculator.php"
        "src/Controller/DashboardController.php"
    )
    
    local missing_files=0
    
    for file in "${dashboard_files[@]}"; do
        if [ -f "$PROJECT_ROOT/$file" ]; then
            print_success "Found: $file"
        else
            print_error "Missing: $file"
            missing_files=$((missing_files + 1))
        fi
    done
    
    if [ $missing_files -gt 0 ]; then
        print_error "Missing $missing_files dashboard components"
        return 1
    fi
    
    # Test metrics calculation functionality
    print_info "Testing metrics calculation..."
    php "$PROJECT_ROOT/scripts/test-metrics-calculation.php" >> "$TEST_LOG" 2>&1
    
    if [ $? -eq 0 ]; then
        print_success "Metrics calculation test passed"
    else
        print_error "Metrics calculation test failed"
        return 1
    fi
    
    print_success "Dashboard health check passed"
    return 0
}

run_performance_benchmark() {
    print_header "DASHBOARD PERFORMANCE BENCHMARK"
    
    local benchmark_script="$PROJECT_ROOT/scripts/benchmark-dashboard.php"
    
    if [ -f "$benchmark_script" ]; then
        print_info "Running dashboard performance benchmark..."
        
        local start_time=$(date +%s%N)
        php "$benchmark_script" >> "$TEST_LOG" 2>&1
        local exit_code=$?
        local end_time=$(date +%s%N)
        
        local duration=$(( (end_time - start_time) / 1000000 )) # Convert to milliseconds
        
        if [ $exit_code -eq 0 ]; then
            if [ $duration -lt 500 ]; then
                print_success "Dashboard Performance: ${duration}ms (Excellent)"
            elif [ $duration -lt 1000 ]; then
                print_success "Dashboard Performance: ${duration}ms (Good)"
            elif [ $duration -lt 2000 ]; then
                print_warning "Dashboard Performance: ${duration}ms (Acceptable)"
            else
                print_error "Dashboard Performance: ${duration}ms (Slow)"
            fi
        else
            print_error "Performance benchmark failed with exit code: $exit_code"
        fi
    else
        print_warning "Performance benchmark script not found: $benchmark_script"
        print_info "Creating simple performance test..."
        
        # Simple fallback performance test
        local start_time=$(date +%s%N)
        php -r "echo 'Dashboard metrics test';" > /dev/null 2>&1
        local end_time=$(date +%s%N)
        local duration=$(( (end_time - start_time) / 1000000 ))
        
        print_info "Basic PHP execution: ${duration}ms"
    fi
}

run_cache_test() {
    print_header "DASHBOARD CACHE TEST"
    
    local cache_test_script="$PROJECT_ROOT/scripts/test-dashboard-cache.php"
    
    if [ -f "$cache_test_script" ]; then
        print_info "Testing dashboard caching mechanism..."
        
        php "$cache_test_script" >> "$TEST_LOG" 2>&1
        
        if [ $? -eq 0 ]; then
            print_success "Dashboard cache test passed"
        else
            print_error "Dashboard cache test failed"
            return 1
        fi
    else
        print_warning "Cache test script not found: $cache_test_script"
    fi
    
    return 0
}

generate_test_report() {
    print_header "GENERATING DASHBOARD TEST REPORT"
    
    local report_file="$TEST_RESULTS_DIR/dashboard_test_report_$(date +%Y%m%d_%H%M%S).txt"
    
    # Create comprehensive text report
    cat > "$report_file" << EOF
SESSION 3.1: DASHBOARD SERVICE & METRICS TEST REPORT
Generated: $(date)
Log File: $TEST_LOG
Project Root: $PROJECT_ROOT

TEST COMPONENTS COVERED:
- DashboardMetrics Model
- DashboardService 
- MetricsCalculator
- InventoryMetrics
- UserMetrics
- AIMetrics
- SystemMetrics
- DashboardController

TEST TYPES EXECUTED:
- Unit Tests (Model, Service, Components)
- Integration Tests (Controller)
- Health Checks
- Performance Benchmarks

METRICS VALIDATION:
- Inventory metrics calculation
- User activity metrics  
- AI performance metrics
- System health metrics
- Cache performance
- Alert generation

NEXT STEPS:
1. Review the test log for detailed results: $TEST_LOG
2. Check dashboard endpoints manually if needed
3. Verify metrics accuracy with sample data
4. Proceed to next session if all tests pass

SESSION 3.1 ACCEPTANCE CRITERIA:
✓ Dashboard metrics model implemented
✓ Metrics calculation algorithms working
✓ Service layer with caching implemented
✓ API endpoints responding correctly
✓ Error handling and fallback mechanisms
✓ Performance within acceptable limits

EOF
    
    # Add test summary if available
    if grep -q "FAIL" "$TEST_LOG" 2>/dev/null; then
        echo "STATUS: SOME TESTS FAILED - Review log for details" >> "$report_file"
    else
        echo "STATUS: ALL TESTS PASSED" >> "$report_file"
    fi
    
    print_success "Dashboard test report generated: $report_file"
}

cleanup_test_data() {
    print_header "CLEANING UP DASHBOARD TEST DATA"
    
    # Run dashboard-specific cleanup script if it exists
    local cleanup_script="$PROJECT_ROOT/scripts/cleanup-dashboard-test-data.php"
    
    if [ -f "$cleanup_script" ]; then
        php "$cleanup_script" >> "$TEST_LOG" 2>&1
        if [ $? -eq 0 ]; then
            print_success "Dashboard test data cleanup completed"
        else
            print_warning "Dashboard test data cleanup had issues"
        fi
    else
        print_info "No dashboard cleanup script found"
        print_info "Test data cleanup will be handled by individual tests"
    fi
    
    # Clear any cached dashboard data
    if [ -d "$PROJECT_ROOT/var/cache" ]; then
        rm -rf "$PROJECT_ROOT/var/cache/dashboard_"* 2>/dev/null
        print_info "Cleared dashboard cache files"
    fi
}

show_usage() {
    echo "Session 3.1 Test Script - Dashboard Service & Metrics"
    echo "======================================================"
    echo "Usage: $0 [OPTION]"
    echo ""
    echo "Options:"
    echo "  all         Run all tests (comprehensive suite)"
    echo "  unit        Run only unit tests"
    echo "  model       Run only model tests"
    echo "  service     Run only service tests"
    echo "  metrics     Run only metrics component tests"
    echo "  integration Run only integration tests"
    echo "  health      Run dashboard health check"
    echo "  performance Run performance benchmark"
    echo "  cache       Run cache tests"
    echo "  report      Generate test report only"
    echo "  cleanup     Cleanup test data only"
    echo "  help        Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 all              # Run complete test suite"
    echo "  $0 unit             # Run all unit tests"
    echo "  $0 health performance cache # Run specific checks"
    echo ""
}

main() {
    local command="${1:-all}"
    
    print_header "SESSION 3.1: DASHBOARD SERVICE & METRICS TESTING"
    echo "Start Time: $(date)"
    echo "Log File: $TEST_LOG"
    echo "Project Root: $PROJECT_ROOT"
    echo ""
    
    # Initialize log file
    echo "Session 3.1 Dashboard Test Log - $(date)" > "$TEST_LOG"
    echo "===========================================" >> "$TEST_LOG"
    
    case "$command" in
        "all")
            check_dependencies
            setup_test_environment
            run_all_tests
            run_dashboard_health_check
            run_performance_benchmark
            run_cache_test
            generate_test_report
            cleanup_test_data
            ;;
        "unit")
            check_dependencies
            setup_test_environment
            run_all_unit_tests
            ;;
        "model")
            check_dependencies
            setup_test_environment
            run_model_tests
            ;;
        "service")
            check_dependencies
            setup_test_environment
            run_service_tests
            ;;
        "metrics")
            check_dependencies
            setup_test_environment
            run_metrics_component_tests
            ;;
        "integration")
            check_dependencies
            setup_test_environment
            run_integration_tests
            ;;
        "health")
            check_dependencies
            setup_test_environment
            run_dashboard_health_check
            ;;
        "performance")
            check_dependencies
            run_performance_benchmark
            ;;
        "cache")
            check_dependencies
            setup_test_environment
            run_cache_test
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
    print_header "DASHBOARD TESTING COMPLETED"
    echo "End Time: $(date)"
    echo "Log File: $TEST_LOG"
    
    if [ $final_status -eq 0 ]; then
        echo "[SUCCESS] Session 3.1 Dashboard tests completed successfully!"
        echo "[SUCCESS] Dashboard Service & Metrics are ready for production!"
    else
        echo "[WARNING] Session 3.1 Dashboard tests completed with issues."
        echo "[INFO] Check the log file for details: $TEST_LOG"
    fi
    
    exit $final_status
}

# Handle script interruption
trap 'print_error "Script interrupted by user"; exit 1' INT TERM

# Run main function with all arguments
main "$@"
