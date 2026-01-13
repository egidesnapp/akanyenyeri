<?php
/**
 * Authentication Check Utility for Akanyenyeri Magazine Admin
 * Provides session validation and security checks
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/../../database/config/database.php";

class AuthCheck
{
    private $pdo;
    private $session_timeout = 3600; // 1 hour in seconds

    public function __construct()
    {
        $this->pdo = getDB();
    }

    /**
     * Check if user is authenticated and session is valid
     */
    public function isAuthenticated()
    {
        // Check if basic session exists
        if (
            !isset($_SESSION["admin_logged_in"]) ||
            !$_SESSION["admin_logged_in"]
        ) {
            return false;
        }

        // Check if required session data exists
        $required_fields = [
            "admin_user_id",
            "admin_username",
            "admin_role",
            "admin_login_time",
        ];
        foreach ($required_fields as $field) {
            if (!isset($_SESSION[$field])) {
                $this->destroySession();
                return false;
            }
        }

        // Check session timeout
        if ($this->isSessionExpired()) {
            $this->destroySession();
            return false;
        }

        // Update last activity timestamp
        $_SESSION["admin_last_activity"] = time();

        // Verify user still exists and is active in database
        if (!$this->verifyUserInDatabase()) {
            $this->destroySession();
            return false;
        }

        return true;
    }

    /**
     * Check if session has expired
     */
    private function isSessionExpired()
    {
        if (!isset($_SESSION["admin_last_activity"])) {
            return true;
        }

        return time() - $_SESSION["admin_last_activity"] >
            $this->session_timeout;
    }

    /**
     * Verify user still exists and is active in database
     */
    private function verifyUserInDatabase()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, status, role
                FROM users
                WHERE id = ? AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$_SESSION["admin_user_id"]]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return false;
            }

            // Check if user still has admin privileges
            if (!in_array($user["role"], ["admin", "editor"])) {
                return false;
            }

            // Update session role if it changed
            if ($user["role"] !== $_SESSION["admin_role"]) {
                $_SESSION["admin_role"] = $user["role"];
            }

            return true;
        } catch (PDOException $e) {
            error_log("Database verification error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user has specific role or permission level
     */
    public function hasRole($required_role)
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        $role_hierarchy = [
            "admin" => 3,
            "editor" => 2,
            "author" => 1,
        ];

        $user_level = $role_hierarchy[$_SESSION["admin_role"]] ?? 0;
        $required_level = $role_hierarchy[$required_role] ?? 0;

        return $user_level >= $required_level;
    }

    /**
     * Check if user can perform specific action
     */
    public function canPerformAction($action)
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        $permissions = [
            "admin" => [
                "manage_users",
                "manage_settings",
                "delete_posts",
                "publish_posts",
                "edit_posts",
                "create_posts",
                "manage_categories",
                "manage_media",
                "view_analytics",
                "moderate_comments",
                "export_data",
            ],
            "editor" => [
                "publish_posts",
                "edit_posts",
                "create_posts",
                "manage_categories",
                "manage_media",
                "view_analytics",
                "moderate_comments",
            ],
            "author" => ["create_posts", "edit_own_posts"],
        ];

        $user_role = $_SESSION["admin_role"];
        return in_array($action, $permissions[$user_role] ?? []);
    }

    /**
     * Validate CSRF token
     */
    public function validateCSRF($token)
    {
        if (!isset($_SESSION["csrf_token"])) {
            return false;
        }

        return hash_equals($_SESSION["csrf_token"], $token);
    }

    /**
     * Generate new CSRF token
     */
    public function generateCSRF()
    {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
        return $_SESSION["csrf_token"];
    }

    /**
     * Get current CSRF token
     */
    public function getCSRF()
    {
        if (!isset($_SESSION["csrf_token"])) {
            return $this->generateCSRF();
        }
        return $_SESSION["csrf_token"];
    }

    /**
     * Destroy current session
     */
    public function destroySession()
    {
        $_SESSION = [];

        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), "", time() - 3600, "/");
        }

        session_destroy();
    }

    /**
     * Get current user information
     */
    public function getCurrentUser()
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return [
            "id" => $_SESSION["admin_user_id"],
            "username" => $_SESSION["admin_username"],
            "full_name" =>
                $_SESSION["admin_full_name"] ?? $_SESSION["admin_username"],
            "email" => $_SESSION["admin_email"] ?? "",
            "role" => $_SESSION["admin_role"],
            "login_time" => $_SESSION["admin_login_time"],
            "last_activity" => $_SESSION["admin_last_activity"],
        ];
    }

    /**
     * Log security event
     */
    public function logSecurityEvent($event, $details = "")
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO security_logs (event_type, user_id, ip_address, user_agent, details, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $event,
                $_SESSION["admin_user_id"] ?? null,
                $_SERVER["REMOTE_ADDR"] ?? "unknown",
                $_SERVER["HTTP_USER_AGENT"] ?? "unknown",
                $details,
            ]);
        } catch (PDOException $e) {
            error_log("Security log error: " . $e->getMessage());
        }
    }

    /**
     * Check for suspicious activity
     */
    public function checkSuspiciousActivity()
    {
        // Check for session hijacking
        if (
            isset($_SESSION["admin_ip"]) &&
            $_SESSION["admin_ip"] !== $_SERVER["REMOTE_ADDR"]
        ) {
            $this->logSecurityEvent(
                "session_hijacking_attempt",
                "IP mismatch detected",
            );
            $this->destroySession();
            return false;
        }

        // Check for user agent changes
        if (
            isset($_SESSION["admin_user_agent"]) &&
            $_SESSION["admin_user_agent"] !== $_SERVER["HTTP_USER_AGENT"]
        ) {
            $this->logSecurityEvent(
                "session_hijacking_attempt",
                "User agent mismatch detected",
            );
            $this->destroySession();
            return false;
        }

        // Store current IP and user agent if not set
        if (!isset($_SESSION["admin_ip"])) {
            $_SESSION["admin_ip"] = $_SERVER["REMOTE_ADDR"];
        }

        if (!isset($_SESSION["admin_user_agent"])) {
            $_SESSION["admin_user_agent"] = $_SERVER["HTTP_USER_AGENT"];
        }

        return true;
    }

    /**
     * Require authentication - redirect to login if not authenticated
     */
    public function requireAuth($redirect_url = "../index.html")
    {
        if (!$this->isAuthenticated() || !$this->checkSuspiciousActivity()) {
            // If it's an AJAX request, return JSON
            if (
                !empty($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
                strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) ===
                    "xmlhttprequest"
            ) {
                header("Content-Type: application/json");
                http_response_code(401);
                echo json_encode([
                    "success" => false,
                    "message" => "Authentication required",
                    "redirect" => $redirect_url,
                ]);
                exit();
            }

            // Regular request - redirect to login
            header("Location: $redirect_url");
            exit();
        }
    }

    /**
     * Require specific role - show error if insufficient permissions
     */
    public function requireRole(
        $required_role,
        $error_message = "Insufficient permissions",
    ) {
        $this->requireAuth();

        if (!$this->hasRole($required_role)) {
            // If it's an AJAX request, return JSON
            if (
                !empty($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
                strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) ===
                    "xmlhttprequest"
            ) {
                header("Content-Type: application/json");
                http_response_code(403);
                echo json_encode([
                    "success" => false,
                    "message" => $error_message,
                ]);
                exit();
            }

            // Regular request - show error page or redirect
            die("<h1>Access Denied</h1><p>$error_message</p>");
        }
    }
}

// Create global instance for easy access
$auth = new AuthCheck();

// Helper functions for quick access
function isLoggedIn()
{
    global $auth;
    return $auth->isAuthenticated();
}

function getCurrentUser()
{
    global $auth;
    return $auth->getCurrentUser();
}

function hasRole($role)
{
    global $auth;
    return $auth->hasRole($role);
}

function canDo($action)
{
    global $auth;
    return $auth->canPerformAction($action);
}

function requireAuth($redirect = "login.php")
{
    global $auth;
    $auth->requireAuth($redirect);
}

function requireRole($role, $message = "Insufficient permissions")
{
    global $auth;
    $auth->requireRole($role, $message);
}

function getCSRFToken()
{
    global $auth;
    return $auth->getCSRF();
}

function validateCSRF($token)
{
    global $auth;
    return $auth->validateCSRF($token);
}
?>
