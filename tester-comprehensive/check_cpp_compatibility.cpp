#include <iostream>

int main() {
    std::cout << "C++ Compiler Compatibility Check" << std::endl;
    std::cout << "=================================" << std::endl;
    
    // Check C++ version
    #if __cplusplus == 201103L
    std::cout << "C++ Version: C++11" << std::endl;
    #elif __cplusplus == 201402L
    std::cout << "C++ Version: C++14" << std::endl;
    #elif __cplusplus == 201703L
    std::cout << "C++ Version: C++17" << std::endl;
    #elif __cplusplus == 202002L
    std::cout << "C++ Version: C++20" << std::endl;
    #else
    std::cout << "C++ Version: " << __cplusplus << std::endl;
    #endif
    
    std::cout << "Compiler: " << __VERSION__ << std::endl;
    std::cout << "System: Linux Lubuntu" << std::endl;
    
    return 0;
}
