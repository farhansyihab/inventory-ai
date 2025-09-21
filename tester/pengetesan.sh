#!/bin/bash
echo "menuju root project"
cd ..
echo "posisi root project"
echo "=== Composer dump-autoload ==="
echo ""
composer dump-autoload -o
echo ""
echo ""

echo "===final_test.php ==="
echo ""
php final_test.php
echo ""
echo ""

echo "=== ./testing.sh ==="
echo ""
cd tester
./testing.sh