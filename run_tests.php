<?php
/**
 * Automated Test Runner for Akanyenyeri Magazine
 * Runs unit tests, integration tests, and generates reports
 */

// Set execution time limit for tests
set_time_limit(300); // 5 minutes

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define constants
define('TEST_ROOT', __DIR__ . '/tests');
define('PROJECT_ROOT', __DIR__);

// Colors for terminal output
class Colors {
    const RED = "\033[0;31m";
    const GREEN = "\033[0;32m";
    const YELLOW = "\033[1;33m";
    const BLUE = "\033[0;34m";
    const PURPLE = "\033[0;35m";
    const CYAN = "\033[0;36m";
    const WHITE = "\033[1;37m";
    const NC = "\033[0m"; // No Color
}

class TestRunner
{
    private $testResults = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $failedTests = 0;
    private $startTime;
    private $verbose = false;

    public function __construct($verbose = false)
    {
        $this->verbose = $verbose;
        $this->startTime = microtime(true);
    }

    /**
     * Run all tests
     */
    public function runAllTests()
    {
        $this->printHeader();

        // Check if PHPUnit is available
        if ($this->checkPHPUnit()) {
            $this->runPHPUnitTests();
        } else {
            $this->runCustomTests();
        }

        $this->runIntegrationTests();
        $this->runSecurityTests();
        $this->generateReport();
    }

    /**
     * Print test runner header
     */
    private function printHeader()
    {
        echo Colors::CYAN . str_repeat("=", 60) . Colors::NC . "\n";
        echo Colors::WHITE . "AKANYENYERI MAGAZINE - AUTOMATED TEST RUNNER" . Colors::NC . "\n";
        echo Colors::CYAN . str_repeat("=", 60) . Colors::NC . "\n";
        echo "Started: " . date('Y-m-d H:i:s') . "\n\n";
    }

    /**
     * Check if PHPUnit is available
     */
    private function checkPHPUnit()
    {
        $phpunit = shell_exec('phpunit --version 2>&1');
        return strpos($phpunit, 'PHPUnit') !== false;
    }

    /**
     * Run PHPUnit tests if available
     */
    private function runPHPUnitTests()
    {
        echo Colors::BLUE . "Running PHPUnit Tests..." . Colors::NC . "\n";
        echo str_repeat("-", 40) . "\n";

        $command = 'phpunit --configuration phpunit.xml --testdox';
        $output = [];
        $returnCode = 0;

        exec($command, $output, $returnCode);

        foreach ($output as $line) {
            echo $line . "\n";
        }

        // Parse PHPUnit results
        $this->parsePHPUnitResults($output, $returnCode);
        echo "\n";
    }

    /**
     * Parse PHPUnit results
     */
    private function parsePHPUnitResults($output, $returnCode)
    {
        $outputStr = implode("\n", $output);

        // Extract test counts
        if (preg_match('/(\d+) tests?, (\d+) assertions?/', $outputStr, $matches)) {
            $tests = (int)$matches[1];
            $this->totalTests += $tests;

            if ($returnCode === 0) {
                $this->passedTests += $tests;
                $this->testResults[] = [
                    'suite' => 'PHPUnit Tests',
                    'status' => 'PASSED',
                    'tests' => $tests,
                    'message' => 'All PHPUnit tests passed'
                ];
            } else {
                // Parse failures
                $failures = 0;
                if (preg_match('/(\d+) failures?/', $outputStr, $failMatches)) {
                    $failures = (int)$failMatches[1];
                }

                $this->failedTests += $failures;
                $this->passedTests += ($tests - $failures);

                $this->testResults[] = [
                    'suite' => 'PHPUnit Tests',
                    'status' => 'FAILED',
                    'tests' => $tests,
                    'failures' => $failures,
                    'message' => "$failures test(s) failed"
                ];
            }
        }
    }

    /**
     * Run custom tests when PHPUnit is not available
     */
    private function runCustomTests()
    {
        echo Colors::BLUE . "Running Custom Tests (PHPUnit not available)..." . Colors::NC . "\n";
        echo str_repeat("-", 40) . "\n";

        $this->runAuthenticationTests();
        $this->runDatabaseTests();
        $this->runFileTests();
        echo "\n";
    }

    /**
     * Run authentication tests
     */
    private function runAuthenticationTests()
    {
        echo "Testing Authentication System...\n";

        $tests = [
            'Database Connection' => $this->testDatabaseConnection(),
            'Auth Class Loading' => $this->testAuthClassLoading(),
            'Session Management' => $this->testSessionManagement(),
            'CSRF Protection' => $this->testCSRFProtection()
        ];

        $this->processTestResults('Authentication', $tests);
    }

    /**
     * Test database connection
     */
    private function testDatabaseConnection()
    {
        try {
            require_once PROJECT_ROOT . '/config/database.php';
            $pdo = getDB();
            $stmt = $pdo->query('SELECT 1');
            return $stmt ? ['passed' => true, 'message' => 'Database connection successful']
                        : ['passed' => false, 'message' => 'Database query failed'];
        } catch (Exception $e) {
            return ['passed' => false, 'message' => 'Database connection failed: ' . $e->getMessage()];
        }
    }

    /**
     * Test auth class loading
     */
    private function testAuthClassLoading()
    {
        try {
            require_once PROJECT_ROOT . '/admin/php/auth_check.php';
            return class_exists('AuthCheck')
                ? ['passed' => true, 'message' => 'AuthCheck class loaded successfully']
                : ['passed' => false, 'message' => 'AuthCheck class not found'];
        } catch (Exception $e) {
            return ['passed' => false, 'message' => 'Auth class loading failed: ' . $e->getMessage()];
        }
    }

    /**
     * Test session management
     */
    private function testSessionManagement()
    {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $_SESSION['test'] = 'value';
            $result = isset($_SESSION['test']) && $_SESSION['test'] === 'value';
            unset($_SESSION['test']);

            return $result
                ? ['passed' => true, 'message' => 'Session management working']
                : ['passed' => false, 'message' => 'Session management failed'];
        } catch (Exception $e) {
            return ['passed' => false, 'message' => 'Session test failed: ' . $e->getMessage()];
        }
    }

    /**
     * Test CSRF protection
     */
    private function testCSRFProtection()
    {
        try {
            require_once PROJECT_ROOT . '/admin/php/auth_check.php';
            $auth = new AuthCheck();
            $token = $auth->generateCSRF();
            $isValid = $auth->validateCSRF($token);

            return $isValid
                ? ['passed' => true, 'message' => 'CSRF protection working']
                : ['passed' => false, 'message' => 'CSRF validation failed'];
        } catch (Exception $e) {
            return ['passed' => false, 'message' => 'CSRF test failed: ' . $e->getMessage()];
        }
    }

    /**
     * Run database tests
     */
    private function runDatabaseTests()
    {
        echo "Testing Database Operations...\n";

        $tests = [
            'Table Existence' => $this->testTableExistence(),
            'User Table Structure' => $this->testUserTableStructure(),
            'Data Integrity' => $this->testDataIntegrity()
        ];

        $this->processTestResults('Database', $tests);
    }

    /**
     * Test table existence
     */
    private function testTableExistence()
    {
        try {
            require_once PROJECT_ROOT . '/config/database.php';
            $pdo = getDB();

            $requiredTables = ['users', 'posts', 'categories', 'tags', 'comments', 'media', 'site_settings'];
            $missingTables = [];

            foreach ($requiredTables as $table) {
                $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$table]);
                if (!$stmt->fetch()) {
                    $missingTables[] = $table;
                }
            }

            return empty($missingTables)
                ? ['passed' => true, 'message' => 'All required tables exist']
                : ['passed' => false, 'message' => 'Missing tables: ' . implode(', ', $missingTables)];
        } catch (Exception $e) {
            return ['passed' => false, 'message' => 'Table check failed: ' . $e->getMessage()];
        }
    }

    /**
     * Test user table structure
     */
    private function testUserTableStructure()
    {
        try {
            require_once PROJECT_ROOT . '/config/database.php';
            $pdo = getDB();

            $stmt = $pdo->query("DESCRIBE users");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $requiredColumns = ['id', 'username', 'email', 'password', 'role', 'status'];
            $missingColumns = array_diff($requiredColumns, $columns);

            return empty($missingColumns)
                ? ['passed' => true, 'message' => 'User table structure is correct']
                : ['passed' => false, 'message' => 'Missing columns: ' . implode(', ', $missingColumns)];
        } catch (Exception $e) {
            return ['passed' => false, 'message' => 'User table check failed: ' . $e->getMessage()];
        }
    }

    /**
     * Test data integrity
     */
    private function testDataIntegrity()
    {
        try {
            require_once PROJECT_ROOT . '/config/database.php';
            $pdo = getDB();

            // Check for admin user
            $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'");
            $adminCount = $stmt->fetchColumn();

            return $adminCount > 0
                ? ['passed' => true, 'message' => 'Active admin user exists']
                : ['passed' => false, 'message' => 'No active admin user found'];
        } catch (Exception $e) {
            return ['passed' => false, 'message' => 'Data integrity check failed: ' . $e->getMessage()];
        }
    }

    /**
     * Run file system tests
     */
    private function runFileTests()
    {
        echo "Testing File System...\n";

        $tests = [
            'Config Files' => $this->testConfigFiles(),
            'Upload Directory' => $this->testUploadDirectory(),
            'File Permissions' => $this->testFilePermissions()
        ];

        $this->processTestResults('File System', $tests);
    }

    /**
     * Test config files
     */
    private function testConfigFiles()
    {
        $configFile = PROJECT_ROOT . '/config/database.php';

        if (!file_exists($configFile)) {
            return ['passed' => false, 'message' => 'Database config file not found'];
        }

        if (!is_readable($configFile)) {
            return ['passed' => false, 'message' => 'Database config file not readable'];
        }

        return ['passed' => true, 'message' => 'Config files accessible'];
    }

    /**
     * Test upload directory
     */
    private function testUploadDirectory()
    {
        $uploadDir = PROJECT_ROOT . '/uploads';

        if (!is_dir($uploadDir)) {
            return ['passed' => false, 'message' => 'Upload directory does not exist'];
        }

        if (!is_writable($uploadDir)) {
            return ['passed' => false, 'message' => 'Upload directory not writable'];
        }

        return ['passed' => true, 'message' => 'Upload directory is writable'];
    }

    /**
     * Test file permissions
     */
    private function testFilePermissions()
    {
        $criticalFiles = [
            'config/database.php',
            'admin/php/auth_check.php',
            'admin/php/login.php'
        ];

        $issues = [];

        foreach ($criticalFiles as $file) {
            $fullPath = PROJECT_ROOT . '/' . $file;
            if (file_exists($fullPath)) {
                $perms = fileperms($fullPath);
                if ($perms & 0x0002) { // World writable
                    $issues[] = "$file is world-writable";
                }
            }
        }

        return empty($issues)
            ? ['passed' => true, 'message' => 'File permissions are secure']
            : ['passed' => false, 'message' => implode('; ', $issues)];
    }

    /**
     * Run integration tests
     */
    private function runIntegrationTests()
    {
        echo Colors::BLUE . "Running Integration Tests..." . Colors::NC . "\n";
        echo str_repeat("-", 40) . "\n";

        $tests = [
            'Admin Login Flow' => $this->testAdminLoginFlow(),
            'Rate Limiting' => $this->testRateLimiting(),
            'Security Logging' => $this->testSecurityLogging()
        ];

        $this->processTestResults('Integration', $tests);
        echo "\n";
    }

    /**
     * Test admin login flow
     */
    private function testAdminLoginFlow()
    {
        try {
            // This would need to be adapted for actual HTTP testing
            require_once PROJECT_ROOT . '/admin/php/login.php';
            return ['passed' => true, 'message' => 'Login script loads without errors'];
        } catch (Exception $e) {
            return ['passed' => false, 'message' => 'Login flow test failed: ' . $e->getMessage()];
        }
    }

    /**
     * Test rate limiting
     */
    private function testRateLimiting()
    {
        try {
            require_once PROJECT_ROOT . '/admin/php/rate_limiter.php';
            return class_exists('RateLimiter')
                ? ['passed' => true, 'message' => 'Rate limiter class available']
                : ['passed' => false, 'message' => 'Rate limiter class not found'];
        } catch (Exception $e) {
            return ['passed' => false, 'message' => 'Rate limiting test failed: ' . $e->getMessage()];
        }
    }

    /**
     * Test security logging
     */
    private function testSecurityLogging()
    {
        try {
            require_once PROJECT_ROOT . '/config/database.php';
            $pdo = getDB();

            // Check if security_logs table exists
            $stmt = $pdo->prepare("SHOW TABLES LIKE 'security_logs'");
            $stmt->execute();

            return $stmt->fetch()
                ? ['passed' => true, 'message' => 'Security logging table exists']
                : ['passed' => false, 'message' => 'Security logging table not found'];
        } catch (Exception $e) {
            return ['passed' => false, 'message' => 'Security logging test failed: ' . $e->getMessage()];
        }
    }

    /**
     * Run security tests
     */
    private function runSecurityTests()
    {
        echo Colors::BLUE . "Running Security Tests..." . Colors::NC . "\n";
        echo str_repeat("-", 40) . "\n";

        $tests = [
            'Password Hashing' => $this->testPasswordHashing(),
            'XSS Prevention' => $this->testXSSPrevention(),
            'File Upload Security' => $this->testFileUploadSecurity()
        ];

        $this->processTestResults('Security', $tests);
        echo "\n";
    }

    /**
     * Test password hashing
     */
    private function testPasswordHashing()
    {
        $password = 'test_password_123';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $verify = password_verify($password, $hash);

        return $verify
            ? ['passed' => true, 'message' => 'Password hashing/verification working']
            : ['passed' => false, 'message' => 'Password hashing failed'];
    }

    /**
     * Test XSS prevention
     */
    private function testXSSPrevention()
    {
        $maliciousInput = '<script>alert("xss")</script>';
        $sanitized = htmlspecialchars($maliciousInput, ENT_QUOTES, 'UTF-8');

        return strpos($sanitized, '<script>') === false
            ? ['passed' => true, 'message' => 'XSS prevention working']
            : ['passed' => false, 'message' => 'XSS prevention failed'];
    }

    /**
     * Test file upload security
     */
    private function testFileUploadSecurity()
    {
        $uploadDir = PROJECT_ROOT . '/uploads';

        if (!is_dir($uploadDir)) {
            return ['passed' => false, 'message' => 'Upload directory does not exist'];
        }

        // Check for .htaccess protection
        $htaccess = $uploadDir . '/.htaccess';
        if (file_exists($htaccess)) {
            $content = file_get_contents($htaccess);
            if (strpos($content, 'php') !== false) {
                return ['passed' => true, 'message' => 'Upload directory has PHP execution protection'];
            }
        }

        return ['passed' => false, 'message' => 'Upload directory lacks proper security'];
    }

    /**
     * Process test results for a suite
     */
    private function processTestResults($suiteName, $tests)
    {
        $passed = 0;
        $total = count($tests);

        foreach ($tests as $testName => $result) {
            $this->totalTests++;

            if ($result['passed']) {
                $this->passedTests++;
                $passed++;
                $status = Colors::GREEN . "✓" . Colors::NC;
            } else {
                $this->failedTests++;
                $status = Colors::RED . "✗" . Colors::NC;
            }

            echo "  $status $testName";
            if ($this->verbose || !$result['passed']) {
                echo " - " . $result['message'];
            }
            echo "\n";
        }

        $this->testResults[] = [
            'suite' => $suiteName,
            'status' => $passed === $total ? 'PASSED' : 'FAILED',
            'passed' => $passed,
            'total' => $total,
            'details' => $tests
        ];
    }

    /**
     * Generate final test report
     */
    private function generateReport()
    {
        $endTime = microtime(true);
        $executionTime = round($endTime - $this->startTime, 2);

        echo Colors::CYAN . str_repeat("=", 60) . Colors::NC . "\n";
        echo Colors::WHITE . "TEST RESULTS SUMMARY" . Colors::NC . "\n";
        echo Colors::CYAN . str_repeat("=", 60) . Colors::NC . "\n";

        // Overall statistics
        echo "Total Tests: " . Colors::WHITE . $this->totalTests . Colors::NC . "\n";
        echo "Passed: " . Colors::GREEN . $this->passedTests . Colors::NC . "\n";
        echo "Failed: " . Colors::RED . $this->failedTests . Colors::NC . "\n";
        echo "Success Rate: " . $this->getSuccessRate() . "\n";
        echo "Execution Time: " . Colors::YELLOW . $executionTime . "s" . Colors::NC . "\n\n";

        // Suite breakdown
        foreach ($this->testResults as $suite) {
            $statusColor = $suite['status'] === 'PASSED' ? Colors::GREEN : Colors::RED;
            echo $statusColor . $suite['status'] . Colors::NC . " - " . $suite['suite'];

            if (isset($suite['passed']) && isset($suite['total'])) {
                echo " ({$suite['passed']}/{$suite['total']})";
            } elseif (isset($suite['tests'])) {
                echo " ({$suite['tests']} tests)";
            }
            echo "\n";
        }

        echo "\n";

        // Final result
        if ($this->failedTests === 0) {
            echo Colors::GREEN . "🎉 ALL TESTS PASSED!" . Colors::NC . "\n";
            exit(0);
        } else {
            echo Colors::RED . "❌ SOME TESTS FAILED!" . Colors::NC . "\n";
            exit(1);
        }
    }

    /**
     * Get success rate as colored string
     */
    private function getSuccessRate()
    {
        if ($this->totalTests === 0) {
            return Colors::YELLOW . "0%" . Colors::NC;
        }

        $rate = round(($this->passedTests / $this->totalTests) * 100, 1);

        if ($rate >= 90) {
            return Colors::GREEN . $rate . "%" . Colors::NC;
        } elseif ($rate >= 70) {
            return Colors::YELLOW . $rate . "%" . Colors::NC;
        } else {
            return Colors::RED . $rate . "%" . Colors::NC;
        }
    }
}

// Parse command line arguments
$verbose = in_array('--verbose', $argv) || in_array('-v', $argv);
$help = in_array('--help', $argv) || in_array('-h', $argv);

if ($help) {
    echo "Akanyenyeri Test Runner\n";
    echo "Usage: php run_tests.php [options]\n\n";
    echo "Options:\n";
    echo "  -v, --verbose    Show detailed test output\n";
    echo "  -h, --help       Show this help message\n\n";
    exit(0);
}

// Run tests
$runner = new TestRunner($verbose);
$runner->runAllTests();
