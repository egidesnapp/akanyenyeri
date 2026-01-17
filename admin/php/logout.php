<?php
/**
 * Admin Logout Handler for Akanyenyeri Magazine
 * Handles secure user logout and session cleanup
 */

session_start();
require_once __DIR__ . "/../../database/config/database.php";

class AdminLogout
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = getDB();
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

    /**
     * Clear remember me token
     */
    public function clearRememberToken()
    {
        if (isset($_COOKIE["admin_remember_token"])) {
            $token = $_COOKIE["admin_remember_token"];

            try {
                // Remove token from database
                $stmt = $this->pdo->prepare("
                    DELETE FROM user_remember_tokens
                    WHERE user_id = ? AND token = ?
                ");
                $stmt->execute([
                    $_SESSION["admin_user_id"] ?? 0,
                    hash("sha256", $token),
                ]);
            } catch (PDOException $e) {
                error_log("Remember token cleanup error: " . $e->getMessage());
            }

            // Clear the cookie
            setcookie(
                "admin_remember_token",
                "",
                time() - 3600,
                "/",
                "",
                true,
                true,
            );
        }
    }

    /**
     * Perform secure logout
     */
    public function logout()
    {
        $username = $_SESSION["admin_username"] ?? "unknown";

        // Log the logout event before destroying session
        $this->logSecurityEvent("logout", "User {$username} logged out");

        // Clear remember me token
        $this->clearRememberToken();

        // Clear all session variables
        $_SESSION = [];

        // Delete the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), "", time() - 3600, "/");
        }

        // Destroy the session
        session_destroy();

        // Clear any other admin-related cookies
        $cookies_to_clear = [
            "admin_logged_in",
            "admin_user",
            "admin_preferences",
            "admin_theme",
        ];

        foreach ($cookies_to_clear as $cookie) {
            if (isset($_COOKIE[$cookie])) {
                setcookie($cookie, "", time() - 3600, "/", "", true, true);
            }
        }
    }
}

// Check if user is logged in before logging out
if (isset($_SESSION["admin_logged_in"]) && $_SESSION["admin_logged_in"]) {
    $logout = new AdminLogout();
    $logout->logout();

    // If it's an AJAX request, return JSON
    if (
        !empty($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
        strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest"
    ) {
        header("Content-Type: application/json");
        echo json_encode([
            "success" => true,
            "message" => "Logged out successfully",
            "redirect" => "login.php",
        ]);
        exit();
    }
}

// Redirect to login page
header("Location: login.php?logged_out=1");
exit();
?>
