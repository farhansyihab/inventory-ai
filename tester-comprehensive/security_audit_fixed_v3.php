<?php
declare(strict_types=1);

// Gunakan path absolut yang benar
require_once '/var/www/html/inventory-ai/vendor/autoload.php';

use App\Config\MongoDBManager;
use App\Utility\Logger;

class SecurityAudit
{
    private Logger $logger;
    private array $issues = [];
    private array $warnings = [];

    public function __construct()
    {
        $this->logger = new Logger('/var/www/html/inventory-ai/tester-comprehensive/logs/security_audit.log');
    }

    public function runAudit(): void
    {
        $this->log("Starting security audit...");
        
        $checks = [
            'checkEnvFile' => 'Environment File Security',
            'checkMongoDBConnection' => 'MongoDB Connection Security',
            'checkErrorHandling' => 'Error Handling Security',
            'checkInputValidation' => 'Input Validation',
            'checkPasswordSecurity' => 'Password Security',
            'checkDependencies' => 'Dependency Security',
            'checkFilePermissions' => 'File Permissions'
        ];

        foreach ($checks as $method => $description) {
            $this->$method();
        }

        $this->generateReport();
    }

    private function checkEnvFile(): void
    {
        $envFile = '/var/www/html/inventory-ai/.env'; // PATH ABSOLUT YANG BENAR
        
        if (!file_exists($envFile)) {
            $this->addIssue('High', '.env file does not exist at: ' . $envFile);
            return;
        }

        $this->log(".env file found at: " . $envFile);

        // Check if .env is readable by others
        $perms = fileperms($envFile);
        if (($perms & 0x0004) || ($perms & 0x0002)) {
            $this->addIssue('High', '.env file is readable by others (permissions: ' . decoct($perms & 0777) . ')');
        }

        // Check for sensitive data in .env
        $content = file_get_contents($envFile);
        $sensitivePatterns = [
            '/password/i',
            '/secret/i',
            '/key/i',
            '/token/i',
            '/auth/i'
        ];

        foreach ($sensitivePatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $this->addWarning('Medium', 'Sensitive data pattern found in .env file: ' . $pattern);
                break;
            }
        }

        // Check if .env contains actual values or just placeholders
        if (preg_match('/\b(your|example|placeholder|change|this)\b/i', $content)) {
            $this->addWarning('Low', '.env file contains placeholder values that need to be updated');
        }
    }

    private function checkMongoDBConnection(): void
    {
        try {
            MongoDBManager::initialize();
            
            // Check if connection uses TLS/SSL
            $connectionString = $_ENV['MONGODB_URI'] ?? '';
            if (strpos($connectionString, 'tls=true') === false && 
                strpos($connectionString, 'ssl=true') === false) {
                $this->addWarning('Medium', 'MongoDB connection may not be using encryption');
            } else {
                $this->log("MongoDB connection uses encryption: " . $connectionString);
            }
            
        } catch (Exception $e) {
            $this->addIssue('High', 'MongoDB connection test failed: ' . $e->getMessage());
        }
    }

    private function checkErrorHandling(): void
    {
        // Check if display_errors is enabled
        if (ini_get('display_errors')) {
            $this->addIssue('High', 'display_errors is enabled in PHP configuration');
        }

        // Check error reporting level
        $errorLevel = error_reporting();
        if ($errorLevel & E_ALL) {
            $this->addWarning('Low', 'Error reporting set to show all errors');
        }
    }

    private function checkInputValidation(): void
    {
        try {
            // Test User model validation
            $user = new App\Model\User(
                "<script>alert('xss')</script>",
                "invalid-email",
                "short",
                "invalid_role"
            );
            $this->addIssue('High', 'User model accepted invalid input');
        } catch (InvalidArgumentException $e) {
            // Expected behavior - validation working
            $this->log("Input validation working correctly: " . $e->getMessage());
        }
    }

    private function checkPasswordSecurity(): void
    {
        // Test password hashing
        $weakPassword = 'password123';
        $hash = password_hash($weakPassword, PASSWORD_BCRYPT);
        
        if (password_verify($weakPassword, $hash)) {
            if (strlen($weakPassword) < 8) {
                $this->addWarning('Medium', 'Weak passwords are accepted by the system');
            }
        }
    }

    private function checkDependencies(): void
    {
        // Check composer.lock for known vulnerabilities
        $composerLock = '/var/www/html/inventory-ai/composer.lock'; // PATH ABSOLUT
        
        if (!file_exists($composerLock)) {
            $this->addWarning('Medium', 'composer.lock file not found at: ' . $composerLock);
            return;
        }

        $this->log("composer.lock file found at: " . $composerLock);

        // Simple check for outdated dependencies
        $output = shell_exec('cd /var/www/html/inventory-ai && composer outdated');
        if ($output && strpos($output, 'up to date') === false) {
            $this->addWarning('Medium', 'Outdated dependencies found');
        }
    }

    private function checkFilePermissions(): void
    {
        $directories = [
            '/var/www/html/inventory-ai/logs',
            '/var/www/html/inventory-ai/vendor',
            '/var/www/html/inventory-ai/tester-comprehensive/logs'
        ];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $perms = fileperms($dir);
                if ($perms & 0x0002) { // World writable
                    $this->addIssue('High', "Directory $dir is world writable (permissions: " . decoct($perms & 0777) . ")");
                } else {
                    $this->log("Directory $dir has secure permissions: " . decoct($perms & 0777));
                }
            } else {
                $this->log("Directory not found: $dir");
            }
        }
    }

    private function addIssue(string $severity, string $message): void
    {
        $this->issues[] = ['severity' => $severity, 'message' => $message];
        $this->log("ISSUE [$severity]: $message");
    }

    private function addWarning(string $severity, string $message): void
    {
        $this->warnings[] = ['severity' => $severity, 'message' => $message];
        $this->log("WARNING [$severity]: $message");
    }

    private function generateReport(): void
    {
        $this->log("\n" . str_repeat("=", 60));
        $this->log("SECURITY AUDIT REPORT");
        $this->log(str_repeat("=", 60));
        
        if (empty($this->issues) && empty($this->warnings)) {
            $this->log("No security issues found. ✅");
            return;
        }

        if (!empty($this->issues)) {
            $this->log("\nCRITICAL ISSUES:");
            foreach ($this->issues as $issue) {
                $this->log("[{$issue['severity']}] {$issue['message']}");
            }
        }

        if (!empty($this->warnings)) {
            $this->log("\nWARNINGS:");
            foreach ($this->warnings as $warning) {
                $this->log("[{$warning['severity']}] {$warning['message']}");
            }
        }

        $this->log(str_repeat("=", 60));
        $this->log("Total issues: " . count($this->issues));
        $this->log("Total warnings: " . count($this->warnings));
        
        if (count($this->issues) > 0) {
            $this->log("❌ Security audit failed!");
            exit(1);
        } else {
            $this->log("⚠️  Security audit completed with warnings");
        }
    }

    private function log(string $message): void
    {
        echo $message . "\n";
        $this->logger->info($message);
    }
}

// Run the audit
$audit = new SecurityAudit();
$audit->runAudit();