<?php
/**
 * Automated Deployment Script for Akanyenyeri Magazine
 * Handles deployment, testing, and rollback operations
 */

// Prevent direct web access
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

// Set execution time limit
set_time_limit(0);

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Colors for CLI output
class DeployColors {
    const RED = "\033[0;31m";
    const GREEN = "\033[0;32m";
    const YELLOW = "\033[1;33m";
    const BLUE = "\033[0;34m";
    const PURPLE = "\033[0;35m";
    const CYAN = "\033[0;36m";
    const WHITE = "\033[1;37m";
    const NC = "\033[0m"; // No Color
}

class AkanyenyeriDeployer
{
    private $config;
    private $currentVersion;
    private $deploymentPath;
    private $backupPath;
    private $logFile;
    private $verbose;

    public function __construct($configFile = 'deploy.json', $verbose = false)
    {
        $this->verbose = $verbose;
        $this->loadConfiguration($configFile);
        $this->setupLogging();
        $this->currentVersion = $this->getCurrentVersion();
    }

    /**
     * Load deployment configuration
     */
    private function loadConfiguration($configFile)
    {
        if (!file_exists($configFile)) {
            $this->createDefaultConfig($configFile);
        }

        $config = json_decode(file_get_contents($configFile), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid configuration file: ' . json_last_error_msg());
        }

        $this->config = $config;
        $this->deploymentPath = $this->config['deployment_path'] ?? __DIR__;
        $this->backupPath = $this->config['backup_path'] ?? __DIR__ . '/backups';
    }

    /**
     * Create default configuration file
     */
    private function createDefaultConfig($configFile)
    {
        $defaultConfig = [
            'deployment_path' => __DIR__,
            'backup_path' => __DIR__ . '/backups',
            'database' => [
                'host' => 'localhost',
                'name' => 'akanyenyeri_db',
                'user' => 'root',
                'password' => ''
            ],
            'pre_deployment' => [
                'run_tests' => true,
                'backup_database' => true,
                'backup_files' => true,
                'maintenance_mode' => true
            ],
            'post_deployment' => [
                'clear_cache' => true,
                'update_permissions' => true,
                'send_notification' => false
            ],
            'rollback' => [
                'keep_backups' => 5,
                'auto_rollback_on_failure' => true
            ],
            'notifications' => [
                'email' => [
                    'enabled' => false,
                    'to' => 'admin@akanyenyeri.com',
                    'from' => 'deploy@akanyenyeri.com'
                ]
            ]
        ];

        file_put_contents($configFile, json_encode($defaultConfig, JSON_PRETTY_PRINT));
        $this->log('Created default configuration file: ' . $configFile, 'INFO');
    }

    /**
     * Setup logging
     */
    private function setupLogging()
    {
        $logDir = $this->config['log_path'] ?? __DIR__ . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $this->logFile = $logDir . '/deployment_' . date('Y-m-d') . '.log';
    }

    /**
     * Main deployment process
     */
    public function deploy($version = null)
    {
        $this->printHeader('STARTING DEPLOYMENT');

        try {
            $version = $version ?: $this->generateVersion();
            $this->log("Starting deployment of version: $version", 'INFO');

            // Pre-deployment steps
            if ($this->config['pre_deployment']['maintenance_mode']) {
                $this->enableMaintenanceMode();
            }

            if ($this->config['pre_deployment']['run_tests']) {
                $this->runTests();
            }

            if ($this->config['pre_deployment']['backup_database']) {
                $this->backupDatabase($version);
            }

            if ($this->config['pre_deployment']['backup_files']) {
                $this->backupFiles($version);
            }

            // Deployment steps
            $this->deployCode($version);
            $this->updateDatabase();
            $this->updateConfiguration();

            // Post-deployment steps
            if ($this->config['post_deployment']['clear_cache']) {
                $this->clearCache();
            }

            if ($this->config['post_deployment']['update_permissions']) {
                $this->updatePermissions();
            }

            $this->disableMaintenanceMode();
            $this->runSmokeTests();

            // Success
            $this->updateVersion($version);
            $this->log("Deployment completed successfully: $version", 'SUCCESS');
            $this->success("🎉 Deployment completed successfully!");

            if ($this->config['post_deployment']['send_notification']) {
                $this->sendNotification($version, 'success');
            }

        } catch (Exception $e) {
            $this->log("Deployment failed: " . $e->getMessage(), 'ERROR');
            $this->error("❌ Deployment failed: " . $e->getMessage());

            if ($this->config['rollback']['auto_rollback_on_failure']) {
                $this->rollback();
            }

            $this->disableMaintenanceMode();
            throw $e;
        }
    }

    /**
     * Rollback to previous version
     */
    public function rollback($toVersion = null)
    {
        $this->printHeader('STARTING ROLLBACK');

        try {
            $backupVersion = $toVersion ?: $this->getLatestBackupVersion();
            if (!$backupVersion) {
                throw new Exception('No backup version available for rollback');
            }

            $this->log("Starting rollback to version: $backupVersion", 'INFO');
            $this->enableMaintenanceMode();

            $this->restoreFiles($backupVersion);
            $this->restoreDatabase($backupVersion);
            $this->updateVersion($backupVersion);

            $this->disableMaintenanceMode();
            $this->log("Rollback completed successfully: $backupVersion", 'SUCCESS');
            $this->success("🔄 Rollback completed successfully!");

        } catch (Exception $e) {
            $this->log("Rollback failed: " . $e->getMessage(), 'ERROR');
            $this->error("❌ Rollback failed: " . $e->getMessage());
            $this->disableMaintenanceMode();
            throw $e;
        }
    }

    /**
     * Run automated tests
     */
    private function runTests()
    {
        $this->log('Running automated tests...', 'INFO');
        $this->info('Running automated tests...');

        $testCommand = 'php run_tests.php';
        $output = [];
        $returnCode = 0;

        exec($testCommand . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            $this->log('Tests failed: ' . implode('\n', $output), 'ERROR');
            throw new Exception('Automated tests failed. Deployment aborted.');
        }

        $this->log('All tests passed', 'SUCCESS');
        $this->success('✓ All tests passed');
    }

    /**
     * Backup database
     */
    private function backupDatabase($version)
    {
        $this->log('Backing up database...', 'INFO');
        $this->info('Backing up database...');

        $db = $this->config['database'];
        $backupFile = $this->backupPath . "/database_backup_{$version}.sql";

        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }

        $command = sprintf(
            'mysqldump -h%s -u%s %s %s > %s',
            escapeshellarg($db['host']),
            escapeshellarg($db['user']),
            !empty($db['password']) ? '-p' . escapeshellarg($db['password']) : '',
            escapeshellarg($db['name']),
            escapeshellarg($backupFile)
        );

        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            $this->log('Database backup failed: ' . implode('\n', $output), 'ERROR');
            throw new Exception('Database backup failed');
        }

        $this->log("Database backed up to: $backupFile", 'SUCCESS');
        $this->success('✓ Database backed up');
    }

    /**
     * Backup files
     */
    private function backupFiles($version)
    {
        $this->log('Backing up files...', 'INFO');
        $this->info('Backing up files...');

        $backupFile = $this->backupPath . "/files_backup_{$version}.tar.gz";

        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }

        // Exclude certain directories from backup
        $excludes = ['--exclude=logs', '--exclude=backups', '--exclude=.git', '--exclude=node_modules'];
        $excludeStr = implode(' ', $excludes);

        $command = sprintf(
            'tar %s -czf %s -C %s .',
            $excludeStr,
            escapeshellarg($backupFile),
            escapeshellarg($this->deploymentPath)
        );

        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            $this->log('File backup failed: ' . implode('\n', $output), 'ERROR');
            throw new Exception('File backup failed');
        }

        $this->log("Files backed up to: $backupFile", 'SUCCESS');
        $this->success('✓ Files backed up');
    }

    /**
     * Deploy code (placeholder - adapt for your deployment method)
     */
    private function deployCode($version)
    {
        $this->log('Deploying code...', 'INFO');
        $this->info('Deploying application code...');

        // Example: Git deployment
        if (is_dir($this->deploymentPath . '/.git')) {
            $commands = [
                'git fetch origin',
                'git checkout main',
                'git pull origin main'
            ];

            foreach ($commands as $command) {
                $output = [];
                $returnCode = 0;
                exec("cd {$this->deploymentPath} && $command 2>&1", $output, $returnCode);

                if ($returnCode !== 0) {
                    throw new Exception("Git command failed: $command");
                }
            }
        }

        $this->log('Code deployment completed', 'SUCCESS');
        $this->success('✓ Code deployed');
    }

    /**
     * Update database schema
     */
    private function updateDatabase()
    {
        $this->log('Updating database...', 'INFO');
        $this->info('Updating database schema...');

        // Run database migrations if they exist
        $migrationFile = $this->deploymentPath . '/database_migrations.sql';
        if (file_exists($migrationFile)) {
            $db = $this->config['database'];
            $command = sprintf(
                'mysql -h%s -u%s %s %s < %s',
                escapeshellarg($db['host']),
                escapeshellarg($db['user']),
                !empty($db['password']) ? '-p' . escapeshellarg($db['password']) : '',
                escapeshellarg($db['name']),
                escapeshellarg($migrationFile)
            );

            $output = [];
            $returnCode = 0;
            exec($command . ' 2>&1', $output, $returnCode);

            if ($returnCode !== 0) {
                throw new Exception('Database migration failed');
            }

            $this->log('Database migrations applied', 'SUCCESS');
        }

        $this->success('✓ Database updated');
    }

    /**
     * Update configuration files
     */
    private function updateConfiguration()
    {
        $this->log('Updating configuration...', 'INFO');
        $this->info('Updating configuration files...');

        // Update any environment-specific configurations here
        // Example: Copy production config files

        $this->success('✓ Configuration updated');
    }

    /**
     * Clear application cache
     */
    private function clearCache()
    {
        $this->log('Clearing cache...', 'INFO');
        $this->info('Clearing application cache...');

        $cacheDir = $this->deploymentPath . '/cache';
        if (is_dir($cacheDir)) {
            $this->deleteDirectory($cacheDir);
            mkdir($cacheDir, 0755, true);
        }

        // Clear OPCache if available
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        $this->success('✓ Cache cleared');
    }

    /**
     * Update file permissions
     */
    private function updatePermissions()
    {
        $this->log('Updating permissions...', 'INFO');
        $this->info('Updating file permissions...');

        $permissions = [
            'uploads' => 0755,
            'logs' => 0755,
            'backups' => 0755,
            'config/database.php' => 0644,
            'admin/php/*.php' => 0644
        ];

        foreach ($permissions as $path => $perm) {
            $fullPath = $this->deploymentPath . '/' . $path;
            if (file_exists($fullPath)) {
                chmod($fullPath, $perm);
            }
        }

        $this->success('✓ Permissions updated');
    }

    /**
     * Enable maintenance mode
     */
    private function enableMaintenanceMode()
    {
        $this->log('Enabling maintenance mode...', 'INFO');
        $this->info('🚧 Enabling maintenance mode...');

        $maintenanceFile = $this->deploymentPath . '/.maintenance';
        file_put_contents($maintenanceFile, json_encode([
            'enabled' => true,
            'timestamp' => time(),
            'message' => 'Site is temporarily under maintenance. Please try again shortly.'
        ]));

        $this->success('✓ Maintenance mode enabled');
    }

    /**
     * Disable maintenance mode
     */
    private function disableMaintenanceMode()
    {
        $this->log('Disabling maintenance mode...', 'INFO');
        $this->info('Disabling maintenance mode...');

        $maintenanceFile = $this->deploymentPath . '/.maintenance';
        if (file_exists($maintenanceFile)) {
            unlink($maintenanceFile);
        }

        $this->success('✓ Maintenance mode disabled');
    }

    /**
     * Run smoke tests after deployment
     */
    private function runSmokeTests()
    {
        $this->log('Running smoke tests...', 'INFO');
        $this->info('Running post-deployment smoke tests...');

        $tests = [
            'Home page loads' => $this->testHttpResponse('/'),
            'Admin login page loads' => $this->testHttpResponse('/admin/'),
            'Database connection' => $this->testDatabaseConnection()
        ];

        foreach ($tests as $testName => $result) {
            if (!$result) {
                throw new Exception("Smoke test failed: $testName");
            }
            $this->log("Smoke test passed: $testName", 'SUCCESS');
        }

        $this->success('✓ Smoke tests passed');
    }

    /**
     * Test HTTP response
     */
    private function testHttpResponse($path)
    {
        // This is a basic implementation - you might want to use curl or similar
        $baseUrl = $this->config['base_url'] ?? 'http://localhost';
        $url = rtrim($baseUrl, '/') . $path;

        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'GET'
            ]
        ]);

        $result = @file_get_contents($url, false, $context);
        return $result !== false;
    }

    /**
     * Test database connection
     */
    private function testDatabaseConnection()
    {
        try {
            $db = $this->config['database'];
            $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $db['user'], $db['password']);
            $pdo->query('SELECT 1');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Restore files from backup
     */
    private function restoreFiles($version)
    {
        $backupFile = $this->backupPath . "/files_backup_{$version}.tar.gz";

        if (!file_exists($backupFile)) {
            throw new Exception("Backup file not found: $backupFile");
        }

        $command = sprintf(
            'tar -xzf %s -C %s',
            escapeshellarg($backupFile),
            escapeshellarg($this->deploymentPath)
        );

        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception('File restore failed');
        }
    }

    /**
     * Restore database from backup
     */
    private function restoreDatabase($version)
    {
        $backupFile = $this->backupPath . "/database_backup_{$version}.sql";

        if (!file_exists($backupFile)) {
            throw new Exception("Database backup file not found: $backupFile");
        }

        $db = $this->config['database'];
        $command = sprintf(
            'mysql -h%s -u%s %s %s < %s',
            escapeshellarg($db['host']),
            escapeshellarg($db['user']),
            !empty($db['password']) ? '-p' . escapeshellarg($db['password']) : '',
            escapeshellarg($db['name']),
            escapeshellarg($backupFile)
        );

        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception('Database restore failed');
        }
    }

    /**
     * Send deployment notification
     */
    private function sendNotification($version, $status)
    {
        if (!$this->config['notifications']['email']['enabled']) {
            return;
        }

        $subject = "Akanyenyeri Deployment " . ucfirst($status) . ": $version";
        $message = "Deployment of version $version completed with status: $status\n\n";
        $message .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";
        $message .= "Environment: " . ($this->config['environment'] ?? 'production') . "\n";

        $headers = 'From: ' . $this->config['notifications']['email']['from'];

        mail($this->config['notifications']['email']['to'], $subject, $message, $headers);
    }

    /**
     * Generate version number
     */
    private function generateVersion()
    {
        return 'v' . date('Y.m.d.Hi');
    }

    /**
     * Get current version
     */
    private function getCurrentVersion()
    {
        $versionFile = $this->deploymentPath . '/VERSION';
        return file_exists($versionFile) ? trim(file_get_contents($versionFile)) : 'unknown';
    }

    /**
     * Update version file
     */
    private function updateVersion($version)
    {
        $versionFile = $this->deploymentPath . '/VERSION';
        file_put_contents($versionFile, $version);
    }

    /**
     * Get latest backup version
     */
    private function getLatestBackupVersion()
    {
        $backups = glob($this->backupPath . '/database_backup_*.sql');
        if (empty($backups)) {
            return null;
        }

        usort($backups, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $latestBackup = basename($backups[0]);
        preg_match('/database_backup_(.+)\.sql/', $latestBackup, $matches);

        return $matches[1] ?? null;
    }

    /**
     * Delete directory recursively
     */
    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Logging functions
     */
    private function log($message, $level = 'INFO')
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);

        if ($this->verbose) {
            echo $logMessage;
        }
    }

    /**
     * Console output functions
     */
    private function printHeader($title)
    {
        echo DeployColors::CYAN . str_repeat("=", 60) . DeployColors::NC . "\n";
        echo DeployColors::WHITE . $title . DeployColors::NC . "\n";
        echo DeployColors::CYAN . str_repeat("=", 60) . DeployColors::NC . "\n";
        echo "Started: " . date('Y-m-d H:i:s') . "\n";
        echo "Current version: " . $this->currentVersion . "\n\n";
    }

    private function success($message)
    {
        echo DeployColors::GREEN . $message . DeployColors::NC . "\n";
    }

    private function info($message)
    {
        echo DeployColors::BLUE . $message . DeployColors::NC . "\n";
    }

    private function warning($message)
    {
        echo DeployColors::YELLOW . $message . DeployColors::NC . "\n";
    }

    private function error($message)
    {
        echo DeployColors::RED . $message . DeployColors::NC . "\n";
    }
}

// CLI Interface
function showHelp()
{
    echo "Akanyenyeri Deployment Tool\n\n";
    echo "Usage: php deploy.php [command] [options]\n\n";
    echo "Commands:\n";
    echo "  deploy [version]  Deploy the application (optionally specify version)\n";
    echo "  rollback [version] Rollback to previous version (optionally specify version)\n";
    echo "  status            Show current deployment status\n";
    echo "  test              Run deployment tests\n\n";
    echo "Options:\n";
    echo "  --config=FILE     Use custom configuration file (default: deploy.json)\n";
    echo "  --verbose, -v     Show verbose output\n";
    echo "  --help, -h        Show this help message\n\n";
    echo "Examples:\n";
    echo "  php deploy.php deploy              # Deploy with auto-generated version\n";
    echo "  php deploy.php deploy v1.2.3      # Deploy specific version\n";
    echo "  php deploy.php rollback            # Rollback to previous version\n";
    echo "  php deploy.php rollback v1.2.2    # Rollback to specific version\n";
    echo "  php deploy.php --verbose deploy    # Deploy with verbose output\n\n";
}

// Parse command line arguments
$command = $argv[1] ?? 'help';
$version = $argv[2] ?? null;

$options = getopt('hv', ['help', 'verbose', 'config:']);
$verbose = isset($options['v']) || isset($options['verbose']);
$configFile = $options['config'] ?? 'deploy.json';

if (isset($options['h']) || isset($options['help']) || $command === 'help') {
    showHelp();
    exit(0);
}

try {
    $deployer = new AkanyenyeriDeployer($configFile, $verbose);

    switch ($command) {
        case 'deploy':
            $deployer->deploy($version);
            break;

        case 'rollback':
            $deployer->rollback($version);
            break;

        case 'status':
            echo "Current version: " . $deployer->getCurrentVersion() . "\n";
            echo "Deployment path: " . $deployer->deploymentPath . "\n";
            echo "Backup path: " . $deployer->backupPath . "\n";
            break;

        case 'test':
            echo "Running deployment tests...\n";
            exec('php run_tests.php', $output, $returnCode);
            echo implode("\n", $output) . "\n";
            exit($returnCode);
            break;

        default:
            echo "Unknown command: $command\n";
            echo "Use --help for usage information.\n";
            exit(1);
    }

} catch (Exception $e) {
    echo DeployColors::RED . "Deployment error: " . $e->getMessage() . DeployColors::NC . "\n";
    exit(1);
}
