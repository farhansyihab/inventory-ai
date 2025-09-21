#!/bin/bash
# Comprehensive Test Runner v3 - With AI Tests

cd /var/www/html/inventory-ai

echo "=============================================="
echo "   INVENTORY AI COMPREHENSIVE TEST SUITE"
echo "=============================================="

# Run the universal launcher with AI tests
php tester-comprehensive/run_universal_fixed_v3.php

# Run C++ performance tests if available
if command -v g++ &> /dev/null && [ -f "tester-comprehensive/performance_tester.cpp" ]; then
    echo ""
    echo "=============================================="
    echo "   C++ PERFORMANCE TESTS"
    echo "=============================================="
    
    g++ -std=c++11 -o tester-comprehensive/performance_tester tester-comprehensive/performance_tester.cpp
    ./tester-comprehensive/performance_tester
fi

echo ""
echo "=============================================="
echo "   ALL TESTS COMPLETED"
echo "=============================================="