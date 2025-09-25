#!/bin/bash
echo "menuju root project"
cd ..
echo "posisi root project"
echo "=== Composer dump-autoload ==="
echo ""
composer dump-autoload -o
echo ""
echo ""
# Run semua tests
./vendor/bin/phpunit

# Run hanya unit tests
./vendor/bin/phpunit --testsuite Unit

# Run tests untuk Category Model saja
./vendor/bin/phpunit tests/Unit/Model/CategoryTest.php

# Run tests dengan coverage report
./vendor/bin/phpunit --coverage-html tests/coverage

# Run tests dengan verbose output
./vendor/bin/phpunit --verbose
