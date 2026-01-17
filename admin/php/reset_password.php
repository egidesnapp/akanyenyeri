<?php
/**
 * Reset Password Handler for Akanyenyeri Magazine
 * Handles password reset completion with token validation
 */

session_start();
require_once __DIR__ . "/../../database/config/database.php";

class PasswordResetHandler
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = getDB();
    }

    /**
     * Handle password reset completion
     */
    public function resetPassword($token, $newPassword, $confirmPassword)
    {
        try {
            // Validate passwords match
            if ($newPassword !== $confirmPassword) {
                return ["success" => false, "message" => "Passwords do not match."];
            }

            // Validate password strength
            if (!$this->validatePasswordStrength($newPassword)) {
                return ["success" => false, "message" => "Password does not meet security requirements."];
            }

            // Validate and get token data
            $tokenData = $this->validateResetToken($token);
            if (!$tokenData) {
                return ["success" => false, "message" => "Invalid or expired reset token."];
            }

            // Check if token has been used
            if ($tokenData['used']) {
                return ["success" => false, "message" => "This reset link has already been used."];
            }

            // Update user password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("
                UPDATE users
                SET password = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$hashedPassword, $tokenData['user_id']]);

            // Mark token as used
            $this->markTokenAsUsed($token);

            // Clean up expired tokens
            $this->cleanupExpiredTokens();

            // Log security event
            $this->logSecurityEvent('password_reset_completed', $tokenData['user_id'], "Password reset completed successfully");

            return [
                "success" => true,
                "message" => "Password has been reset successfully. You can now log in with your new password."
            ];

        } catch (Exception $e) {
            error_log("Reset password error: " . $e->getMessage());
            return ["success" => false, "message" => "An error occurred. Please try again."];
        }
    }

    /**
     * Validate reset token
     */
    private function validateResetToken($token)
    {
        try {
            $hashedToken = hash('sha256', $token);

            $stmt = $this->pdo->prepare("
                SELECT prt.*, u.username, u.email, u.full_name
                FROM password_reset_tokens prt
                JOIN users u ON prt.user_id = u.id
                WHERE prt.token = ? AND prt.used = FALSE AND prt.expires_at > NOW()
                LIMIT 1
            ");
            $stmt->execute([$hashedToken]);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Validate password strength
     */
    private function validatePasswordStrength($password)
    {
        // Check minimum length
        if (strlen($password) < 8) {
            return false;
        }

        // Check for at least one lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }

        // Check for at least one uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        // Check for at least one number
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }

        // Check for at least one special character
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return false;
        }

        return true;
    }

    /**
     * Mark token as used
     */
    private function markTokenAsUsed($token)
    {
        try {
            $hashedToken = hash('sha256', $token);
            $stmt = $this->pdo->prepare("
                UPDATE password_reset_tokens
                SET used = TRUE
                WHERE token = ?
            ");
            $stmt->execute([$hashedToken]);
        } catch (Exception $e) {
            // Log but don't fail the operation
            error_log("Mark token as used error: " . $e->getMessage());
        }
    }

    /**
     * Clean up expired tokens
     */
    private function cleanupExpiredTokens()
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM password_reset_tokens
                WHERE expires_at < NOW()
            ");
            $stmt->execute();
        } catch (Exception $e) {
            // Log but don't fail the operation
            error_log("Token cleanup error: " . $e->getMessage());
        }
    }

    /**
     * Log security event
     */
    private function logSecurityEvent($event, $userId, $details = "")
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO security_logs (event_type, user_id, ip_address, user_agent, details, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $event,
                $userId,
                $_SERVER["REMOTE_ADDR"] ?? "unknown",
                $_SERVER["HTTP_USER_AGENT"] ?? "unknown",
                $details,
            ]);
        } catch (Exception $e) {
            error_log("Security log error: " . $e->getMessage());
        }
    }
}

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header("Content-Type: application/json");

    // Get input data
    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input) {
        $input = $_POST; // Fallback to form data
    }

    $token = trim($input["token"] ?? "");
    $password = $input["password"] ?? "";
    $confirmPassword = $input["confirm_password"] ?? "";

    // Basic validation
    if (empty($token) || empty($password) || empty($confirmPassword)) {
        echo json_encode([
            "success" => false,
            "message" => "All fields are required.",
        ]);
        exit();
    }

    // Handle the reset
    $handler = new PasswordResetHandler();
    $result = $handler->resetPassword($token, $password, $confirmPassword);

    echo json_encode($result);
    exit();
}

// Handle GET request - validate token and show reset form
if (isset($_GET["token"])) {
    $token = trim($_GET["token"]);

    if (empty($token)) {
        header("Location: forgot_password.php");
        exit();
    }

    $handler = new PasswordResetHandler();
    $tokenData = $handler->validateResetToken($token);

    if (!$tokenData) {
        // Token is invalid or expired
        header("Location: forgot_password.php?error=invalid_token");
        exit();
    }

    // Token is valid, show the reset form (this is handled by the HTML page)
    // The JavaScript will handle the rest
    exit();
}

// If we get here, redirect to forgot password page
header("Location: ../forgot_password.php");
exit();
?>
