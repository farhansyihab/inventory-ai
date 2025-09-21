#!/bin/bash

# Deep Tester for Inventory AI Phase 1
set -e

echo "=============================================="
echo "   INVENTORY AI PHASE 1 - DEEP TESTER"
echo "=============================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
TEST_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$TEST_DIR")"
LOG_DIR="$TEST_DIR/logs"

# Create log directory
mkdir -p "$LOG_DIR"

# Function to print status
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if we're in the right directory
if [ ! -f "$PROJECT_ROOT/composer.json" ]; then
    print_error "Please run this script from the inventory-ai project root"
    exit 1
fi

# Check dependencies
print_status "Checking dependencies..."

if ! command -v php &> /dev/null; then
    print_error "PHP is not installed"
    exit 1
fi

if ! php -m | grep -q mongodb; then
    print_error "MongoDB PHP extension is not installed"
    exit 1
fi

# Install composer dependencies if needed
if [ ! -d "$PROJECT_ROOT/vendor" ]; then
    print_status "Installing composer dependencies..."
    composer install
fi

# Run tests
FAILED_TESTS=0

print_status "Starting test suite..."

# 1. PHP Unit Tests
print_status "Running PHPUnit tests..."
if php vendor/bin/phpunit --testdox; then
    print_success "PHPUnit tests passed"
else
    print_error "PHPUnit tests failed"
    ((FAILED_TESTS++))
fi

echo ""

# 2. Deep Tester
print_status "Running Deep Tester..."
if php "$TEST_DIR/deep_tester_fixed.php"; then
    print_success "Deep Tester passed"
else
    print_error "Deep Tester failed"
    ((FAILED_TESTS++))
fi

# Summary
echo ""
echo "=============================================="
if [ $FAILED_TESTS -eq 0 ]; then
    print_success "All tests passed! 🎉"
else
    print_error "$FAILED_TESTS test suites failed! ❌"
    exit $FAILED_TESTS
fi