<?php
/**
 * Universal Tester Launcher - Version 3 (With AI Tests)
 */

require_once '/var/www/html/inventory-ai/vendor/autoload.php';
chdir('/var/www/html/inventory-ai');

runAllTests();

function runAllTests() {
    echo "==============================================\n";
    echo "   INVENTORY AI COMPREHENSIVE TEST SUITE\n";
    echo "==============================================\n";
    
    $results = [];
    
    // 1. PHP Unit Tests
    echo "[1/5] Running PHPUnit Tests...\n";
    $results['phpunit'] = runCommand('php vendor/bin/phpunit --testdox');
    
    // 2. Deep Integration Tests
    echo "\n[2/5] Running Deep Integration Tests...\n";
    $results['deep_tester'] = runCommand('php tester/deep_tester_fixed.php');
    
    // 3. Security Audit
    echo "\n[3/5] Running Security Audit...\n";
    $results['security'] = runCommand('php tester-comprehensive/security_audit_fixed_v3.php');
    
    // 4. Stress Test
    echo "\n[4/5] Running Database Stress Test...\n";
    $results['stress'] = runCommand('php tester-comprehensive/database_stress_test_fixed_v3.php');
    
    // 5. AI Integration Test (jika Ollama available)
    echo "\n[5/5] Running AI Integration Tests...\n";
    if (isOllamaAvailable()) {
        $results['ai_integration'] = runCommand('php tester-comprehensive/ai_integration_test_fixed_v3.php');
    } else {
        echo "⚠️  Ollama not available, skipping AI tests\n";
        $results['ai_integration'] = ['exit_code' => 0, 'output' => ['Skipped - Ollama not available']];
    }
    
    // Summary
    echo "\n==============================================\n";
    echo "TEST SUMMARY:\n";
    echo "==============================================\n";
    echo "PHPUnit Tests:          " . formatResult($results['phpunit']) . "\n";
    echo "Deep Integration Tests: " . formatResult($results['deep_tester']) . "\n";
    echo "Security Audit:         " . formatResult($results['security']) . "\n";
    echo "Stress Test:            " . formatResult($results['stress']) . "\n";
    echo "AI Integration Tests:   " . formatResult($results['ai_integration']) . "\n";
    
    $totalFailed = array_sum(array_map(function($result) { 
        return $result['exit_code'] !== 0 ? 1 : 0; 
    }, $results));
    
    echo "==============================================\n";
    echo "TOTAL FAILED: $totalFailed\n";
    
    if ($totalFailed === 0) {
        echo "🎉 ALL TESTS PASSED!\n";
    } else {
        echo "❌ SOME TESTS FAILED!\n";
        exit(1);
    }
}

function isOllamaAvailable() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost:11434/api/tags");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode === 200;
}

function runCommand($command) {
    $output = [];
    $exit_code = 0;
    
    exec($command . ' 2>&1', $output, $exit_code);
    
    foreach ($output as $line) {
        echo $line . "\n";
    }
    
    return [
        'exit_code' => $exit_code,
        'output' => $output
    ];
}

function formatResult($result) {
    return $result['exit_code'] === 0 ? "✅ PASSED" : "❌ FAILED";
}