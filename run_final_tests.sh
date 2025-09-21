#!/bin/bash
# Final Test Runner - Simple and Robust

cd /var/www/html/inventory-ai

echo "=============================================="
echo "   INVENTORY AI - FINAL TEST SUITE"
echo "=============================================="

# Run the fixed universal launcher
php tester-comprehensive/run_universal_fixed.php

echo ""
echo "=============================================="
echo "   TESTS COMPLETED"
echo "=============================================="