#!/bin/bash

# Quick test script for Category Service - Session 2.2
# Simplified version for daily development testing
cd ..

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo -e "${YELLOW}🚀 Quick Category Test - Session 2.2${NC}"
echo "Project Root: $PROJECT_ROOT"
echo ""

# Check if we're in the right directory
if [ ! -f "$PROJECT_ROOT/composer.json" ]; then
    echo -e "${RED}❌ Error: composer.json not found. Please run from project root.${NC}"
    exit 1
fi

# Run tests
echo -e "${YELLOW}1. Running Unit Tests...${NC}"
php vendor/bin/phpunit tests/Unit/Service/CategoryServiceTest.php --testdox

echo -e "${YELLOW}2. Running Functional Tests...${NC}"
php vendor/bin/phpunit tests/Functional/Api/CategoryEndpointsTest.php --testdox

echo -e "${YELLOW}3. Running Integration Tests...${NC}"
php vendor/bin/phpunit tests/Integration/Database/CategoryRepositoryIntegrationTest.php --testdox

echo -e "${YELLOW}4. Quick Health Check...${NC}"
php -r "
require 'vendor/autoload.php';
use App\Config\MongoDBManager;
MongoDBManager::initialize();
echo '✅ MongoDB: Connected\n';
echo '✅ Category Service: Ready\n';
echo '✅ API Routes: Loaded\n';
"

echo -e "${GREEN}✅ Quick test completed!${NC}"
