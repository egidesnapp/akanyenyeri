<?php
/**
 * Security Audit and Monitoring Tool for Akanyenyeri Magazine Admin
 * Provides comprehensive security analysis and monitoring capabilities
 */

session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/php/auth_check.php";
require_once __DIR__ . "/php/rate_limiter.php";

// Require admin access
requireRole('admin');

class SecurityAudit
{
    private $pdo;
    private $findings = [];
    private $stats = [];

    public function __construct()
    {
        $this->pdo = getDB();
    }

    /**
     * Run complete security audit
     */
    public function runAudit()
    {
        $this->checkDatabaseSecurity();
        $this->checkFilePermissions();
        $this->checkSystemConfiguration();
        $this->checkUserSecurity();
        $this->checkSessionSecurity();
        $this->checkRateLimiting();
        $this->checkPasswordPolicies();
        $this->checkServerSecurity();
        $this->analyzeSecurityLogs();
        $this->checkBackupSecurity();

        return [
            'findings' => $this->findings,
            'stats' => $this->stats,
            'score' => $this->calculateSecurityScore()
        ];
    }

    /**
     * Check database security configuration
     */
    private function checkDatabaseSecurity()
    {
        $issues = [];

        try {
            // Check for default database credentials
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $user = $_ENV['DB_USER'] ?? 'root';
            $pass = $_ENV['DB_PASS'] ?? '';

            if (empty($pass) && $user === 'root') {
                $issues[] = [
                    'severity' => 'HIGH',
                    'message' => 'Database using default root credentials with no password'
                ];
            }

            // Check database connection encryption
            $stmt = $this->pdo->query("SHOW STATUS LIKE 'Ssl_cipher'");
            $ssl = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$ssl || empty($ssl['Value'])) {
                $issues[] = [
                    'severity' => 'MEDIUM',
                    'message' => 'Database connection is not encrypted (SSL/TLS not enabled)'
                ];
            }

            // Check for SQL injection vulnerable queries (basic check)
            $vulnerable_patterns = [
                "SELECT.*\$_GET",
                "SELECT.*\$_POST",
                "UPDATE.*\$_GET",
                "UPDATE.*\$_POST",
                "DELETE.*\$_GET",
                "DELETE.*\$_POST"
            ];

            $php_files = glob(__DIR__ . "/../**/*.php", GLOB_BRACE);
            foreach ($php_files as $file) {
                $content = file_get_contents($file);
                foreach ($vulnerable_patterns as $pattern) {
                    if (preg_match("/$pattern/i", $content)) {
                        $issues[] = [
                            'severity' => 'HIGH',
                            'message' => "Potential SQL injection vulnerability in " . basename($file)
                        ];
                    }
                }
            }

            // Check database user privileges
            $stmt = $this->pdo->query("SHOW GRANTS");
            $grants = $stmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($grants as $grant) {
                if (strpos($grant, 'ALL PRIVILEGES') !== false) {
                    $issues[] = [
                        'severity' => 'MEDIUM',
                        'message' => 'Database user has ALL PRIVILEGES - consider using principle of least privilege'
                    ];
                }
            }

        } catch (Exception $e) {
            $issues[] = [
                'severity' => 'LOW',
                'message' => 'Unable to complete database security check: ' . $e->getMessage()
            ];
        }

        $this->findings['database'] = $issues;
    }

    /**
     * Check file permissions and security
     */
    private function checkFilePermissions()
    {
        $issues = [];
        $project_root = dirname(__DIR__);

        // Critical files that should not be writable by web server
        $critical_files = [
            'config/database.php',
            'admin/php/auth_check.php',
            'admin/php/login.php',
            'setup_database.php'
        ];

        foreach ($critical_files as $file) {
            $filepath = $project_root . '/' . $file;
            if (file_exists($filepath)) {
                $perms = fileperms($filepath);

                // Check if world-writable
                if ($perms & 0x0002) {
                    $issues[] = [
                        'severity' => 'HIGH',
                        'message' => "Critical file $file is world-writable"
                    ];
                }

                // Check if group-writable
                if ($perms & 0x0010) {
                    $issues[] = [
                        'severity' => 'MEDIUM',
                        'message' => "Critical file $file is group-writable"
                    ];
                }
            }
        }

        // Check upload directory permissions
        $upload_dir = $project_root . '/uploads';
        if (is_dir($upload_dir)) {
            $perms = fileperms($upload_dir);
            if (!($perms & 0x0080)) {
                $issues[] = [
                    'severity' => 'MEDIUM',
                    'message' => 'Upload directory is not writable by web server'
                ];
            }

            // Check for .htaccess in upload directory
            if (!file_exists($upload_dir . '/.htaccess')) {
                $issues[] = [
                    'severity' => 'HIGH',
                    'message' => 'Upload directory missing .htaccess protection'
                ];
            }
        }

        // Check for sensitive files in web root
        $sensitive_files = ['.env', '.git', 'composer.json', 'package.json'];
        foreach ($sensitive_files as $file) {
            if (file_exists($project_root . '/' . $file)) {
                $issues[] = [
                    'severity' => 'MEDIUM',
                    'message' => "Sensitive file $file is accessible from web root"
                ];
            }
        }

        $this->findings['files'] = $issues;
    }

    /**
     * Check system configuration
     */
    private function checkSystemConfiguration()
    {
        $issues = [];

        // Check PHP configuration
        if (ini_get('display_errors')) {
            $issues[] = [
                'severity' => 'HIGH',
                'message' => 'PHP display_errors is enabled - should be disabled in production'
            ];
        }

        if (ini_get('expose_php')) {
            $issues[] = [
                'severity' => 'LOW',
                'message' => 'PHP version is exposed in headers'
            ];
        }

        if (!ini_get('session.cookie_httponly')) {
            $issues[] = [
                'severity' => 'MEDIUM',
                'message' => 'Session cookies are not HTTP-only'
            ];
        }

        if (!ini_get('session.cookie_secure') && isset($_SERVER['HTTPS'])) {
            $issues[] = [
                'severity' => 'MEDIUM',
                'message' => 'Session cookies are not secure (HTTPS only)'
            ];
        }

        // Check for dangerous functions
        $dangerous_functions = ['eval', 'exec', 'system', 'shell_exec', 'passthru'];
        foreach ($dangerous_functions as $func) {
            if (function_exists($func)) {
                $issues[] = [
                    'severity' => 'LOW',
                    'message' => "Dangerous function '$func' is available"
                ];
            }
        }

        $this->findings['system'] = $issues;
    }

    /**
     * Check user account security
     */
    private function checkUserSecurity()
    {
        $issues = [];

        try {
            // Check for users with weak passwords (common patterns)
            $stmt = $this->pdo->query("SELECT username FROM users WHERE password = '' OR password IS NULL");
            $empty_passwords = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($empty_passwords)) {
                $issues[] = [
                    'severity' => 'HIGH',
                    'message' => 'Users with empty passwords: ' . implode(', ', $empty_passwords)
                ];
            }

            // Check for inactive admin accounts that should be disabled
            $stmt = $this->pdo->query("
                SELECT username FROM users
                WHERE role = 'admin' AND status = 'active'
                AND updated_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
            ");
            $inactive_admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($inactive_admins)) {
                $issues[] = [
                    'severity' => 'MEDIUM',
                    'message' => 'Admin accounts inactive for 90+ days: ' . implode(', ', $inactive_admins)
                ];
            }

            // Check for duplicate email addresses
            $stmt = $this->pdo->query("
                SELECT email, COUNT(*) as count
                FROM users
                GROUP BY email
                HAVING count > 1
            ");
            $duplicate_emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($duplicate_emails)) {
                $emails = array_column($duplicate_emails, 'email');
                $issues[] = [
                    'severity' => 'MEDIUM',
                    'message' => 'Duplicate email addresses: ' . implode(', ', $emails)
                ];
            }

            // Get user statistics
            $stmt = $this->pdo->query("SELECT role, status, COUNT(*) as count FROM users GROUP BY role, status");
            $user_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->stats['users'] = $user_stats;

        } catch (Exception $e) {
            $issues[] = [
                'severity' => 'LOW',
                'message' => 'Unable to complete user security check: ' . $e->getMessage()
            ];
        }

        $this->findings['users'] = $issues;
    }

    /**
     * Check session security
     */
    private function checkSessionSecurity()
    {
        $issues = [];

        // Check session configuration
        if (session_status() === PHP_SESSION_ACTIVE) {
            $session_name = session_name();
            if ($session_name === 'PHPSESSID') {
                $issues[] = [
                    'severity' => 'LOW',
                    'message' => 'Using default PHP session name'
                ];
            }

            // Check session lifetime
            $lifetime = ini_get('session.gc_maxlifetime');
            if ($lifetime > 3600) {
                $issues[] = [
                    'severity' => 'MEDIUM',
                    'message' => "Session lifetime is too long: {$lifetime} seconds"
                ];
            }
        }

        $this->findings['sessions'] = $issues;
    }

    /**
     * Check rate limiting effectiveness
     */
    private function checkRateLimiting()
    {
        $issues = [];

        try {
            global $rateLimiter;
            $stats = $rateLimiter->getStatistics();

            // Check if rate limiting is working
            if (empty($stats['current_blocks']) && empty($stats['hourly_attempts'])) {
                $issues[] = [
                    'severity' => 'LOW',
                    'message' => 'Rate limiting appears inactive - no recent activity logged'
                ];
            }

            // Check for excessive blocking
            if (!empty($stats['current_blocks'])) {
                foreach ($stats['current_blocks'] as $action => $count) {
                    if ($count > 10) {
                        $issues[] = [
                            'severity' => 'MEDIUM',
                            'message' => "High number of rate limit blocks for $action: $count"
                        ];
                    }
                }
            }

            $this->stats['rate_limiting'] = $stats;

        } catch (Exception $e) {
            $issues[] = [
                'severity' => 'MEDIUM',
                'message' => 'Rate limiting system error: ' . $e->getMessage()
            ];
        }

        $this->findings['rate_limiting'] = $issues;
    }

    /**
     * Check password policies
     */
    private function checkPasswordPolicies()
    {
        $issues = [];

        // This is a basic check - in a real system you'd want more sophisticated analysis
        $issues[] = [
            'severity' => 'INFO',
            'message' => 'Password policy enforcement should be implemented client-side and server-side'
        ];

        $this->findings['passwords'] = $issues;
    }

    /**
     * Check server security headers
     */
    private function checkServerSecurity()
    {
        $issues = [];

        // Check if running on HTTPS
        if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
            $issues[] = [
                'severity' => 'HIGH',
                'message' => 'Site not running on HTTPS'
            ];
        }

        // Check for security headers (this would need to be tested externally)
        $security_headers = [
            'X-Frame-Options',
            'X-XSS-Protection',
            'X-Content-Type-Options',
            'Strict-Transport-Security',
            'Content-Security-Policy'
        ];

        foreach ($security_headers as $header) {
            $issues[] = [
                'severity' => 'INFO',
                'message' => "Security header '$header' should be configured"
            ];
        }

        $this->findings['server'] = $issues;
    }

    /**
     * Analyze security logs for patterns
     */
    private function analyzeSecurityLogs()
    {
        $issues = [];

        try {
            // Check for recent suspicious activity
            $stmt = $this->pdo->query("
                SELECT event_type, COUNT(*) as count
                FROM security_logs
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY event_type
                ORDER BY count DESC
            ");
            $recent_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($recent_events as $event) {
                if ($event['count'] > 100) {
                    $issues[] = [
                        'severity' => 'HIGH',
                        'message' => "High volume of {$event['event_type']} events: {$event['count']} in 24h"
                    ];
                } elseif ($event['count'] > 50) {
                    $issues[] = [
                        'severity' => 'MEDIUM',
                        'message' => "Elevated {$event['event_type']} events: {$event['count']} in 24h"
                    ];
                }
            }

            // Check for brute force attempts
            $stmt = $this->pdo->query("
                SELECT ip_address, COUNT(*) as attempts
                FROM security_logs
                WHERE event_type = 'login_failed'
                AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                GROUP BY ip_address
                HAVING attempts > 10
            ");
            $brute_force = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($brute_force as $bf) {
                $issues[] = [
                    'severity' => 'HIGH',
                    'message' => "Potential brute force attack from {$bf['ip_address']}: {$bf['attempts']} failed logins"
                ];
            }

            $this->stats['security_logs'] = $recent_events;

        } catch (Exception $e) {
            $issues[] = [
                'severity' => 'LOW',
                'message' => 'Unable to analyze security logs: ' . $e->getMessage()
            ];
        }

        $this->findings['logs'] = $issues;
    }

    /**
     * Check backup and recovery security
     */
    private function checkBackupSecurity()
    {
        $issues = [];

        // Check if backup files are accessible
        $project_root = dirname(__DIR__);
        $backup_patterns = ['*.sql', '*.bak', '*backup*', '*.dump'];

        foreach ($backup_patterns as $pattern) {
            $files = glob($project_root . '/' . $pattern);
            if (!empty($files)) {
                $issues[] = [
                    'severity' => 'HIGH',
                    'message' => 'Backup files found in web-accessible directory: ' . implode(', ', array_map('basename', $files))
                ];
            }
        }

        $this->findings['backups'] = $issues;
    }

    /**
     * Calculate overall security score
     */
    private function calculateSecurityScore()
    {
        $total_issues = 0;
        $severity_weights = ['HIGH' => 10, 'MEDIUM' => 5, 'LOW' => 2, 'INFO' => 1];
        $penalty = 0;

        foreach ($this->findings as $category => $issues) {
            foreach ($issues as $issue) {
                $total_issues++;
                $penalty += $severity_weights[$issue['severity']] ?? 1;
            }
        }

        // Start with 100 and subtract penalties
        $score = max(0, 100 - $penalty);

        return [
            'score' => $score,
            'total_issues' => $total_issues,
            'penalty' => $penalty,
            'grade' => $this->getSecurityGrade($score)
        ];
    }

    /**
     * Get security grade based on score
     */
    private function getSecurityGrade($score)
    {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }

    /**
     * Generate security recommendations
     */
    public function generateRecommendations()
    {
        $recommendations = [];

        // High priority recommendations
        $high_issues = [];
        foreach ($this->findings as $category => $issues) {
            foreach ($issues as $issue) {
                if ($issue['severity'] === 'HIGH') {
                    $high_issues[] = $issue['message'];
                }
            }
        }

        if (!empty($high_issues)) {
            $recommendations[] = [
                'priority' => 'HIGH',
                'title' => 'Critical Security Issues',
                'items' => $high_issues
            ];
        }

        // General recommendations
        $recommendations[] = [
            'priority' => 'MEDIUM',
            'title' => 'Security Best Practices',
            'items' => [
                'Enable HTTPS with valid SSL certificate',
                'Configure security headers (CSP, HSTS, etc.)',
                'Implement two-factor authentication',
                'Regular security updates and patches',
                'Monitor security logs daily',
                'Backup data regularly and securely',
                'Use principle of least privilege for database users',
                'Implement proper input validation everywhere'
            ]
        ];

        return $recommendations;
    }
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    $audit = new SecurityAudit();

    switch ($_GET['action']) {
        case 'run_audit':
            echo json_encode($audit->runAudit());
            break;

        case 'get_recommendations':
            echo json_encode($audit->generateRecommendations());
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
    }
    exit();
}

// Get current user for display
$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Audit - Akanyenyeri Admin</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/admin-style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .security-score {
            font-size: 3rem;
            font-weight: bold;
        }
        .grade-A { color: #28a745; }
        .grade-B { color: #17a2b8; }
        .grade-C { color: #ffc107; }
        .grade-D { color: #fd7e14; }
        .grade-F { color: #dc3545; }
        .severity-HIGH { color: #dc3545; }
        .severity-MEDIUM { color: #fd7e14; }
        .severity-LOW { color: #ffc107; }
        .severity-INFO { color: #17a2b8; }
        .audit-loading { display: none; }
        .recommendation-item { margin-bottom: 0.5rem; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-shield-alt"></i> Security Audit</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-primary" id="runAudit">
                            <i class="fas fa-search"></i> Run Security Audit
                        </button>
                    </div>
                </div>

                <!-- Loading indicator -->
                <div class="audit-loading">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3">Running security audit...</p>
                    </div>
                </div>

                <!-- Audit Results -->
                <div id="auditResults" style="display: none;">
                    <!-- Security Score -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title">Security Score</h5>
                                    <div class="security-score" id="securityScore">-</div>
                                    <p class="card-text">Grade: <span id="securityGrade" class="badge">-</span></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title">Total Issues</h5>
                                    <div class="security-score text-warning" id="totalIssues">-</div>
                                    <p class="card-text">Found during audit</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title">Last Audit</h5>
                                    <div class="h4" id="lastAudit">Just now</div>
                                    <p class="card-text">Audit completed</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Security Findings -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-exclamation-triangle"></i> Security Findings</h5>
                                </div>
                                <div class="card-body">
                                    <div id="securityFindings">
                                        <p class="text-muted">No audit results yet. Click "Run Security Audit" to begin.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-lightbulb"></i> Recommendations</h5>
                                </div>
                                <div class="card-body">
                                    <div id="recommendations">
                                        <p class="text-muted">Recommendations will appear after audit.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('runAudit').addEventListener('click', function() {
            const button = this;
            const loading = document.querySelector('.audit-loading');
            const results = document.getElementById('auditResults');

            button.disabled = true;
            loading.style.display = 'block';
            results.style.display = 'none';

            fetch('security_audit.php?action=run_audit')
                .then(response => response.json())
                .then(data => {
                    displayAuditResults(data);
                    loadRecommendations();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to run security audit');
                })
                .finally(() => {
                    button.disabled = false;
                    loading.style.display = 'none';
                    results.style.display = 'block';
                });
        });

        function displayAuditResults(data) {
            // Update score display
            document.getElementById('securityScore').textContent = data.score.score;
            document.getElementById('securityGrade').textContent = data.score.grade;
            document.getElementById('securityGrade').className = 'badge grade-' + data.score.grade;
            document.getElementById('totalIssues').textContent = data.score.total_issues;

            // Display findings
            const findingsContainer = document.getElementById('securityFindings');
            findingsContainer.innerHTML = '';

            if (data.score.total_issues === 0) {
                findingsContainer.innerHTML = '<p class="text-success"><i class="fas fa-check-circle"></i> No security issues found!</p>';
                return;
            }

            Object.keys(data.findings).forEach(category => {
                const issues = data.findings[category];
                if (issues.length === 0) return;

                const categoryDiv = document.createElement('div');
                categoryDiv.className = 'mb-3';
                categoryDiv.innerHTML = `<h6 class="text-capitalize">${category.replace('_', ' ')}</h6>`;

                const issuesList = document.createElement('ul');
                issuesList.className = 'list-unstyled ms-3';

                issues.forEach(issue => {
                    const listItem = document.createElement('li');
                    listItem.className = 'mb-1';
                    listItem.innerHTML = `
                        <span class="badge severity-${issue.severity} me-2">${issue.severity}</span>
                        ${issue.message}
                    `;
                    issuesList.appendChild(listItem);
                });

                categoryDiv.appendChild(issuesList);
                findingsContainer.appendChild(categoryDiv);
            });
        }

        function loadRecommendations() {
            fetch('security_audit.php?action=get_recommendations')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('recommendations');
                    container.innerHTML = '';

                    data.forEach(section => {
                        const sectionDiv = document.createElement('div');
                        sectionDiv.className = 'mb-3';
                        sectionDiv.innerHTML = `<h6>${section.title}</h6>`;

                        const itemsList = document.createElement('ul');
                        itemsList.className = 'list-unstyled ms-2';

                        section.items.forEach(item => {
                            const listItem = document.createElement('li');
                            listItem.className = 'recommendation-item';
                            listItem.innerHTML = `<i class="fas fa-chevron-right text-muted me-2"></i><small>${item}</small>`;
                            itemsList.appendChild(listItem);
                        });

                        sectionDiv.appendChild(itemsList);
                        container.appendChild(sectionDiv);
                    });
                })
                .catch(error => {
                    console.error('Error loading recommendations:', error);
                });
        }
    </script>
</body>
</html>
