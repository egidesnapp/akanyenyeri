<?php
/**
 * Admin Login Handler for Akanyenyeri Magazine
 * Handles user authentication and session management
 */

session_start();
require_once __DIR__ . "/../../database/config/database.php";
require_once __DIR__ . "/rate_limiter.php";

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set("display_errors", 1);

// Test database connection
try {
    $db = getDB();
    if (!$db) {
        throw new Exception("Database connection failed");
    }
} catch (Exception $e) {
    header("Content-Type: application/json");
    echo json_encode([
        "success" => false,
        "message" => "Database connection error. Please contact administrator.",
    ]);
    exit();
}

class AdminAuth
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = getDB();
    }

    /**
     * Authenticate user credentials
     */
    public function authenticate($username, $password)
    {
        try {
            // Prepare statement to prevent SQL injection
            $stmt = $this->pdo->prepare("
                SELECT id, username, email, password, full_name, role, status
                FROM users
                WHERE (username = ? OR email = ?) AND status = 'active'
                LIMIT 1
            ");

            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verify user exists and password is correct
            if ($user && password_verify($password, $user["password"])) {
                // Allow all active users to log in
                return $user;
            } else {
                return ["error" => "Invalid username/email or password."];
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return ["error" => "Database connection error. Please try again."];
        }
    }

    /**
     * Create user session
     */
    public function createSession($user, $remember = false)
    {
        // Regenerate session ID for security
        session_regenerate_id(true);

        // Set session variables
        $_SESSION["admin_logged_in"] = true;
        $_SESSION["admin_user_id"] = $user["id"];
        $_SESSION["admin_username"] = $user["username"];
        $_SESSION["admin_full_name"] = $user["full_name"];
        $_SESSION["admin_email"] = $user["email"];
        $_SESSION["admin_role"] = $user["role"];
        $_SESSION["admin_login_time"] = time();
        $_SESSION["admin_last_activity"] = time();

        // Generate CSRF token
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

        // Set remember me cookie if requested
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expiry = time() + 30 * 24 * 60 * 60; // 30 days

            setcookie(
                "admin_remember_token",
                $token,
                $expiry,
                "/",
                "",
                true,
                true,
            );

            // Store token in database (you might want to create a remember_tokens table)
            try {
                $stmt = $this->pdo->prepare("
                    INSERT INTO user_remember_tokens (user_id, token, expires_at)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE token = ?, expires_at = ?
                ");
                $expires = date("Y-m-d H:i:s", $expiry);
                $stmt->execute([
                    $user["id"],
                    hash("sha256", $token),
                    $expires,
                    hash("sha256", $token),
                    $expires,
                ]);
            } catch (PDOException $e) {
                // Remember token creation failed, but login still successful
                error_log("Remember token error: " . $e->getMessage());
            }
        }

        // Update last login time
        try {
            $stmt = $this->pdo->prepare("
                UPDATE users
                SET updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$user["id"]]);
        } catch (PDOException $e) {
            error_log("Last login update error: " . $e->getMessage());
        }
    }

    /**
     * Validate CSRF token
     */
    public function validateCSRF($token)
    {
        return isset($_SESSION["csrf_token"]) &&
            hash_equals($_SESSION["csrf_token"], $token);
    }

    /**
     * Log security events
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
            // Security logging failed, but don't interrupt the main flow
            error_log("Security log error: " . $e->getMessage());
        }
    }
}

// Initialize rate limiter
global $rateLimiter;
if (!isset($rateLimiter)) {
    $rateLimiter = new RateLimiter();
}

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header("Content-Type: application/json");

    // Get input data
    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input) {
        $input = $_POST; // Fallback to form data
    }

    $username = trim($input["username"] ?? "");
    $password = $input["password"] ?? "";
    $remember = isset($input["remember"]) && $input["remember"];
    $csrf_token = $input["csrf_token"] ?? "";

    // Basic validation
    if (empty($username) || empty($password)) {
        echo json_encode([
            "success" => false,
            "message" => "Please enter both username/email and password.",
        ]);
        exit();
    }

    // Rate limiting check using the new RateLimiter
    if (!checkRateLimit("login")) {
        $rateLimitInfo = getRateLimitInfo("login");
        $timeRemaining = $rateLimitInfo["time_until_unblocked"];

        echo json_encode([
            "success" => false,
            "message" =>
                "Too many failed login attempts. Please try again in " .
                ceil($timeRemaining / 60) .
                " minute(s).",
            "retry_after" => $timeRemaining,
        ]);
        exit();
    }

    // Authenticate user
    $auth = new AdminAuth();
    $result = $auth->authenticate($username, $password);

    if (isset($result["error"])) {
        // Record failed login attempt
        recordAttempt("login", false);

        $auth->logSecurityEvent(
            "login_failed",
            "Failed login attempt for username: {$username}",
        );

        echo json_encode([
            "success" => false,
            "message" => $result["error"],
        ]);
        exit();
    }

    // Authentication successful - record successful attempt
    recordAttempt("login", true);

    $auth->createSession($result, $remember);
    $auth->logSecurityEvent(
        "login_success",
        "User {$result["username"]} logged in",
    );

    echo json_encode([
        "success" => true,
        "message" => "Login successful",
        "redirect" => "dashboard.php",
        "user" => [
            "name" => $result["full_name"],
            "role" => $result["role"],
        ],
    ]);
    exit();
}

// Handle GET request - show login form or redirect if already logged in
if (isset($_SESSION["admin_logged_in"]) && $_SESSION["admin_logged_in"]) {
    header("Location: ../dashboard.php");
    exit();
}

// If we get here, redirect to login page
header("Location: ../login.php");
exit();
?>
