#!/bin/bash
# File: run-ai-tests-fixed.sh

echo "🚀 Running Fixed AI Testing & Optimization Suite..."

echo "1. Running Unit Tests..."
./vendor/bin/phpunit tests/Unit/AI/ --colors=always --stop-on-failure

echo "2. Running Integration Tests..."
./vendor/bin/phpunit tests/Integration/AI/ --colors=always

echo "3. Running Performance Tests..."
./vendor/bin/phpunit tests/Performance/ --colors=always --stop-on-error

echo "4. Running Functional Tests..."
./vendor/bin/phpunit tests/Functional/Api/ --colors=always --stop-on-error

echo "5. Generating Test Report..."
./vendor/bin/phpunit --testdox-html tests/testdox-fixed.html

echo "✅ Fixed AI Testing Suite Completed!"