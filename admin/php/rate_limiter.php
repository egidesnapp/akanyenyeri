<?php
/**
 * Rate Limiting System for Akanyenyeri Magazine Admin
 * Provides protection against brute force attacks and abuse
 */

class RateLimiter
{
    private $pdo;
    private $cache = [];

    // Rate limiting rules (attempts per time window)
    private $rules = [
        'login' => ['attempts' => 5, 'window' => 900], // 5 attempts per 15 minutes
        'password_reset' => ['attempts' => 3, 'window' => 1800], // 3 attempts per 30 minutes
        'contact_form' => ['attempts' => 10, 'window' => 3600], // 10 attempts per hour
        'comment_post' => ['attempts' => 20, 'window' => 3600], // 20 comments per hour
        'media_upload' => ['attempts' => 50, 'window' => 3600], // 50 uploads per hour
        'post_create' => ['attempts' => 30, 'window' => 3600], // 30 posts per hour
        'api_request' => ['attempts' => 100, 'window' => 3600], // 100 API calls per hour
        'admin_action' => ['attempts' => 200, 'window' => 3600], // 200 admin actions per hour
    ];

    public function __construct()
    {
        $this->pdo = getDB();
        $this->createRateLimitTable();
        $this->cleanupOldRecords();
    }

    /**
     * Create rate limit tracking table if it doesn't exist
     */
    private function createRateLimitTable()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS rate_limits (
                id INT AUTO_INCREMENT PRIMARY KEY,
                identifier VARCHAR(255) NOT NULL,
                action_type VARCHAR(50) NOT NULL,
                attempts INT DEFAULT 1,
                first_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                blocked_until TIMESTAMP NULL,
                INDEX idx_identifier_action (identifier, action_type),
                INDEX idx_blocked_until (blocked_until),
                INDEX idx_last_attempt (last_attempt)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";

        $this->pdo->exec($sql);
    }

    /**
     * Get identifier for rate limiting (IP + User Agent hash)
     */
    private function getIdentifier($custom_identifier = null)
    {
        if ($custom_identifier) {
            return $custom_identifier;
        }

        $ip = $this->getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        // Create hash to preserve privacy while maintaining uniqueness
        return hash('sha256', $ip . '|' . $userAgent);
    }

    /**
     * Get real client IP address
     */
    private function getClientIP()
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Check if action is allowed under rate limiting rules
     */
    public function isAllowed($action_type, $identifier = null)
    {
        if (!isset($this->rules[$action_type])) {
            return true; // No rule defined, allow action
        }

        $identifier = $this->getIdentifier($identifier);
        $rule = $this->rules[$action_type];

        // Check if currently blocked
        if ($this->isBlocked($identifier, $action_type)) {
            return false;
        }

        // Get current attempts within the time window
        $attempts = $this->getCurrentAttempts($identifier, $action_type, $rule['window']);

        return $attempts < $rule['attempts'];
    }

    /**
     * Record an attempt for rate limiting
     */
    public function recordAttempt($action_type, $success = false, $identifier = null)
    {
        if (!isset($this->rules[$action_type])) {
            return; // No rule defined, don't track
        }

        $identifier = $this->getIdentifier($identifier);
        $rule = $this->rules[$action_type];

        // Check if record exists for this identifier and action
        $stmt = $this->pdo->prepare("
            SELECT id, attempts, first_attempt
            FROM rate_limits
            WHERE identifier = ? AND action_type = ?
            AND first_attempt > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ORDER BY first_attempt DESC
            LIMIT 1
        ");

        $stmt->execute([$identifier, $action_type, $rule['window']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Update existing record
            $new_attempts = $existing['attempts'] + 1;
            $blocked_until = null;

            // Check if we should block after this attempt
            if (!$success && $new_attempts >= $rule['attempts']) {
                $blocked_until = date('Y-m-d H:i:s', time() + $this->getBlockDuration($action_type, $new_attempts));
            }

            $stmt = $this->pdo->prepare("
                UPDATE rate_limits
                SET attempts = ?, blocked_until = ?
                WHERE id = ?
            ");

            $stmt->execute([$new_attempts, $blocked_until, $existing['id']]);

            if ($blocked_until) {
                $this->logSecurityEvent('rate_limit_exceeded', [
                    'action_type' => $action_type,
                    'attempts' => $new_attempts,
                    'blocked_until' => $blocked_until
                ]);
            }

        } else {
            // Create new record
            $stmt = $this->pdo->prepare("
                INSERT INTO rate_limits (identifier, action_type, attempts, blocked_until)
                VALUES (?, ?, 1, NULL)
            ");

            $stmt->execute([$identifier, $action_type]);
        }

        // If successful, consider clearing the block (for some actions)
        if ($success && in_array($action_type, ['login'])) {
            $this->clearBlock($identifier, $action_type);
        }
    }

    /**
     * Check if identifier is currently blocked for an action
     */
    private function isBlocked($identifier, $action_type)
    {
        $stmt = $this->pdo->prepare("
            SELECT blocked_until
            FROM rate_limits
            WHERE identifier = ? AND action_type = ?
            AND blocked_until IS NOT NULL
            AND blocked_until > NOW()
            ORDER BY blocked_until DESC
            LIMIT 1
        ");

        $stmt->execute([$identifier, $action_type]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result !== false;
    }

    /**
     * Get current attempts count within time window
     */
    private function getCurrentAttempts($identifier, $action_type, $window)
    {
        $stmt = $this->pdo->prepare("
            SELECT attempts
            FROM rate_limits
            WHERE identifier = ? AND action_type = ?
            AND first_attempt > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ORDER BY first_attempt DESC
            LIMIT 1
        ");

        $stmt->execute([$identifier, $action_type, $window]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? (int)$result['attempts'] : 0;
    }

    /**
     * Get block duration based on action type and attempt count
     */
    private function getBlockDuration($action_type, $attempts)
    {
        $base_durations = [
            'login' => 900,          // 15 minutes base
            'password_reset' => 1800, // 30 minutes base
            'contact_form' => 3600,   // 1 hour base
            'comment_post' => 1800,   // 30 minutes base
            'media_upload' => 3600,   // 1 hour base
            'post_create' => 1800,    // 30 minutes base
            'api_request' => 900,     // 15 minutes base
            'admin_action' => 600,    // 10 minutes base
        ];

        $base = $base_durations[$action_type] ?? 900;

        // Exponential backoff: double the duration for each additional attempt
        $multiplier = pow(2, max(0, $attempts - $this->rules[$action_type]['attempts']));

        return min($base * $multiplier, 86400); // Cap at 24 hours
    }

    /**
     * Clear block for successful action
     */
    public function clearBlock($identifier, $action_type)
    {
        $identifier = $this->getIdentifier($identifier);

        $stmt = $this->pdo->prepare("
            UPDATE rate_limits
            SET blocked_until = NULL
            WHERE identifier = ? AND action_type = ?
        ");

        $stmt->execute([$identifier, $action_type]);
    }

    /**
     * Get remaining time until unblocked
     */
    public function getTimeUntilUnblocked($action_type, $identifier = null)
    {
        $identifier = $this->getIdentifier($identifier);

        $stmt = $this->pdo->prepare("
            SELECT blocked_until
            FROM rate_limits
            WHERE identifier = ? AND action_type = ?
            AND blocked_until IS NOT NULL
            AND blocked_until > NOW()
            ORDER BY blocked_until DESC
            LIMIT 1
        ");

        $stmt->execute([$identifier, $action_type]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return strtotime($result['blocked_until']) - time();
        }

        return 0;
    }

    /**
     * Get attempts remaining before rate limit
     */
    public function getAttemptsRemaining($action_type, $identifier = null)
    {
        if (!isset($this->rules[$action_type])) {
            return PHP_INT_MAX; // No limit
        }

        $identifier = $this->getIdentifier($identifier);
        $rule = $this->rules[$action_type];

        if ($this->isBlocked($identifier, $action_type)) {
            return 0;
        }

        $current_attempts = $this->getCurrentAttempts($identifier, $action_type, $rule['window']);
        return max(0, $rule['attempts'] - $current_attempts);
    }

    /**
     * Get rate limit info for an action
     */
    public function getRateLimitInfo($action_type, $identifier = null)
    {
        if (!isset($this->rules[$action_type])) {
            return null;
        }

        $identifier = $this->getIdentifier($identifier);
        $rule = $this->rules[$action_type];

        return [
            'action_type' => $action_type,
            'max_attempts' => $rule['attempts'],
            'time_window' => $rule['window'],
            'current_attempts' => $this->getCurrentAttempts($identifier, $action_type, $rule['window']),
            'attempts_remaining' => $this->getAttemptsRemaining($action_type, $identifier),
            'is_blocked' => $this->isBlocked($identifier, $action_type),
            'time_until_unblocked' => $this->getTimeUntilUnblocked($action_type, $identifier),
            'window_resets_in' => $rule['window']
        ];
    }

    /**
     * Whitelist an IP address (bypass rate limiting)
     */
    public function addToWhitelist($ip, $reason = '')
    {
        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO rate_limit_whitelist (ip_address, reason, created_at)
            VALUES (?, ?, NOW())
        ");

        $stmt->execute([$ip, $reason]);

        // Create whitelist table if it doesn't exist
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS rate_limit_whitelist (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) UNIQUE NOT NULL,
                reason TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ip (ip_address)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Check if IP is whitelisted
     */
    public function isWhitelisted($ip = null)
    {
        if (!$ip) {
            $ip = $this->getClientIP();
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM rate_limit_whitelist
                WHERE ip_address = ?
            ");
            $stmt->execute([$ip]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false; // Table might not exist yet
        }
    }

    /**
     * Clean up old rate limit records
     */
    private function cleanupOldRecords()
    {
        // Only run cleanup occasionally to avoid performance impact
        if (rand(1, 100) <= 5) { // 5% chance
            $stmt = $this->pdo->prepare("
                DELETE FROM rate_limits
                WHERE first_attempt < DATE_SUB(NOW(), INTERVAL 24 HOUR)
                AND (blocked_until IS NULL OR blocked_until < NOW())
            ");
            $stmt->execute();
        }
    }

    /**
     * Log security event (if security logging is available)
     */
    private function logSecurityEvent($event_type, $details)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO security_logs (event_type, ip_address, user_agent, details, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $event_type,
                $this->getClientIP(),
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                json_encode($details)
            ]);
        } catch (PDOException $e) {
            // Ignore if security_logs table doesn't exist
            error_log("Rate limiter security log error: " . $e->getMessage());
        }
    }

    /**
     * Manually block an identifier for a specific duration
     */
    public function manualBlock($identifier, $action_type, $duration_seconds, $reason = '')
    {
        $blocked_until = date('Y-m-d H:i:s', time() + $duration_seconds);

        $stmt = $this->pdo->prepare("
            INSERT INTO rate_limits (identifier, action_type, attempts, blocked_until, first_attempt)
            VALUES (?, ?, 999, ?, NOW())
            ON DUPLICATE KEY UPDATE blocked_until = VALUES(blocked_until), attempts = 999
        ");

        $stmt->execute([$identifier, $action_type, $blocked_until]);

        $this->logSecurityEvent('manual_block', [
            'identifier' => $identifier,
            'action_type' => $action_type,
            'duration' => $duration_seconds,
            'reason' => $reason,
            'blocked_until' => $blocked_until
        ]);
    }

    /**
     * Get rate limiting statistics
     */
    public function getStatistics()
    {
        $stats = [];

        // Current blocks by action type
        $stmt = $this->pdo->query("
            SELECT action_type, COUNT(*) as blocked_count
            FROM rate_limits
            WHERE blocked_until IS NOT NULL AND blocked_until > NOW()
            GROUP BY action_type
        ");

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats['current_blocks'][$row['action_type']] = (int)$row['blocked_count'];
        }

        // Attempts in last hour by action type
        $stmt = $this->pdo->query("
            SELECT action_type, SUM(attempts) as total_attempts
            FROM rate_limits
            WHERE first_attempt > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY action_type
        ");

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats['hourly_attempts'][$row['action_type']] = (int)$row['total_attempts'];
        }

        return $stats;
    }
}

// Global rate limiter instance
$rateLimiter = new RateLimiter();

// Helper functions for easy access
function checkRateLimit($action_type, $identifier = null)
{
    global $rateLimiter;

    // Skip rate limiting if whitelisted
    if ($rateLimiter->isWhitelisted()) {
        return true;
    }

    return $rateLimiter->isAllowed($action_type, $identifier);
}

function recordAttempt($action_type, $success = false, $identifier = null)
{
    global $rateLimiter;

    // Skip recording if whitelisted
    if ($rateLimiter->isWhitelisted()) {
        return;
    }

    $rateLimiter->recordAttempt($action_type, $success, $identifier);
}

function getRateLimitInfo($action_type, $identifier = null)
{
    global $rateLimiter;
    return $rateLimiter->getRateLimitInfo($action_type, $identifier);
}

function requireRateLimit($action_type, $identifier = null)
{
    global $rateLimiter;

    if (!$rateLimiter->isWhitelisted() && !$rateLimiter->isAllowed($action_type, $identifier)) {
        $time_remaining = $rateLimiter->getTimeUntilUnblocked($action_type, $identifier);

        // Return JSON for AJAX requests
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {

            header('Content-Type: application/json');
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'message' => 'Rate limit exceeded. Please try again later.',
                'time_remaining' => $time_remaining,
                'retry_after' => date('Y-m-d H:i:s', time() + $time_remaining)
            ]);
            exit();
        }

        // Regular request - show error page
        http_response_code(429);
        header('Retry-After: ' . $time_remaining);
        die('<h1>Rate Limit Exceeded</h1><p>Too many requests. Please try again in ' .
            ceil($time_remaining / 60) . ' minute(s).</p>');
    }
}
?>
