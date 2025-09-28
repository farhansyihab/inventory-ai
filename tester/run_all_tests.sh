<?php
/**
 * Universal Tester Launcher - Solves all path issues
 * Run from ANY directory: php run_universal.php
 */

// Detect project root
$projectRoot = detectProjectRoot();

if (!$projectRoot) {
    die("ERROR: Could not find project root. Please run from inventory-ai directory.\n");
}

// Change to project root directory
chdir($projectRoot);

// Now run all tests from the correct directory
runAllTests();

function detectProjectRoot() {
    $possibleRoots = [
        getcwd(),
        dirname(getcwd()),
        dirname(dirname(getcwd())),
        '/var/www/html/inventory-ai'
    ];
    
    foreach ($possibleRoots as $path) {
        if (file_exists($path . '/composer.json') && file_exists($path . '/vendor/autoload.php')) {
            return $path;
        }
    }
    
    return null;
}

function runAllTests() {
    echo "==============================================\n";
    echo "   INVENTORY AI UNIVERSAL TEST LAUNCHER\n";
    echo "==============================================\n";
    
    // 1. Run PHPUnit
    echo "[1/4] Running PHPUnit Tests...\n";
    system('php vendor/bin/phpunit --testdox', $phpunitResult);
    
    // 2. Run Deep Tester
    echo "\n[2/4] Running Deep Integration Tests...\n";
    system('php tester/deep_tester_fixed.php', $deepTesterResult);
    
    // 3. Run Security Audit
    echo "\n[3/4] Running Security Audit...\n";
    system('php tester-comprehensive/security_audit_fixed_v2.php', $securityResult);
    
    // 4. Run Stress Test
    echo "\n[4/4] Running Database Stress Test...\n";
    system('php tester-comprehensive/database_stress_test_fixed.php', $stressResult);
    
    // Summary
    echo "\n==============================================\n";
    echo "TEST SUMMARY:\n";
    echo "==============================================\n";
    echo "PHPUnit Tests:          " . ($phpunitResult === 0 ? "✅ PASSED" : "❌ FAILED") . "\n";
    echo "Deep Integration Tests: " . ($deepTesterResult === 0 ? "✅ PASSED" : "❌ FAILED") . "\n";
    echo "Security Audit:         " . ($securityResult === 0 ? "✅ PASSED" : "❌ FAILED") . "\n";
    echo "Stress Test:            " . ($stressResult === 0 ? "✅ PASSED" : "❌ FAILED") . "\n";
    
    $totalFailed = ($phpunitResult !== 0 ? 1 : 0) + 
                   ($deepTesterResult !== 0 ? 1 : 0) + 
                   ($securityResult !== 0 ? 1 : 0) + 
                   ($stressResult !== 0 ? 1 : 0);
    
    echo "==============================================\n";
    echo "TOTAL FAILED: $totalFailed\n";
    
    if ($totalFailed === 0) {
        echo "🎉 ALL TESTS PASSED!\n";
    } else {
        echo "❌ SOME TESTS FAILED!\n";
        exit(1);
    }
}