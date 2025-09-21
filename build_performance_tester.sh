#!/bin/bash

echo "Building C++ Performance Tester..."
echo "=================================="

# Check compiler
if ! command -v g++ &> /dev/null; then
    echo "❌ g++ compiler not found. Installing build-essential..."
    sudo apt install build-essential -y
fi

# Compile with C++11 standard
g++ -std=c++11 -o performance_tester_fixed performance_tester_fixed_v2.cpp

if [ $? -eq 0 ]; then
    echo "✅ Compilation successful!"
    echo "Run with: ./performance_tester_fixed"
else
    echo "❌ Compilation failed!"
    exit 1
fi

# Optional: Build compatibility checker
g++ -o check_compatibility check_cpp_compatibility.cpp
