# Makefile for Category Tests
CXX = g++
CXXFLAGS = -std=c++11 -Wall -Wextra -O2
TARGETS = category_test repository_test validation_test performance_test file_test mongo_test

.PHONY: all clean test run-bash-tests

all: $(TARGETS)

category_test: category_model_test.cpp
	$(CXX) $(CXXFLAGS) -o $@ $<

repository_test: repository_interface_test.cpp
	$(CXX) $(CXXFLAGS) -o $@ $<

validation_test: validation_test.cpp
	$(CXX) $(CXXFLAGS) -o $@ $<

performance_test: performance_test.cpp
	$(CXX) $(CXXFLAGS) -o $@ $<

file_test: file_integration_test.cpp
	$(CXX) $(CXXFLAGS) -o $@ $<

mongo_test: mongo_simulation_test.cpp
	$(CXX) $(CXXFLAGS) -o $@ $<

test: all
	@echo "Running C++ tests..."
	@for test in $(TARGETS); do \
		if [ -f ./$$test ]; then \
			echo "Running $$test..."; \
			./$$test && echo "$$test: PASSED" || echo "$$test: FAILED"; \
		else \
			echo "$$test: NOT BUILT"; \
		fi; \
	done

clean:
	rm -f $(TARGETS) *.o

run-bash-tests:
	chmod +x run_category_tests.sh
	./run_category_tests.sh

help:
	@echo "Available targets:"
	@echo "  all      - Build all tests"
	@echo "  test     - Run all compiled tests"
	@echo "  clean    - Remove compiled files"
	@echo "  run-bash-tests - Run the bash test suite"