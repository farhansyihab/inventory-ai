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
echo "./vendor/bin/phpunit"
./vendor/bin/phpunit
echo ""
echo "###############################################"
echo ""
# Run hanya unit tests
echo "./vendor/bin/phpunit --testsuite Unit"
./vendor/bin/phpunit --testsuite Unit
echo ""
echo "###############################################"
echo ""
# Run tests untuk Category Model saja
echo "./vendor/bin/phpunit tests/Unit/Model/CategoryTest.php"
./vendor/bin/phpunit tests/Unit/Model/CategoryTest.php
echo ""
echo "###############################################"
echo ""
# Run tests dengan coverage report
echo "./vendor/bin/phpunit --coverage-html tests/coverage"
./vendor/bin/phpunit --coverage-html tests/coverage
echo ""
echo "###############################################"
echo ""
# Run tests dengan verbose output
echo "./vendor/bin/phpunit --verbose"
./vendor/bin/phpunit --verbose
