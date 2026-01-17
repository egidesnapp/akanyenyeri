<?php
/**
 * Admin Password Reset Handler for Akanyenyeri Magazine
 * Handles admin-initiated password resets with temporary password generation
 */

session_start();
require_once 'auth_check.php';
require_once '../../database/config/database.php';

// Require authentication and admin role
requireAuth();
requireRole('admin', 'You need admin privileges to reset user passwords');

class AdminPasswordResetHandler
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = getDB();
    }

    /**
     * Handle admin password reset for a user
     */
    public function resetUserPassword($userId, $csrfToken)
    {
        try {
            // Validate CSRF token
            if (!validateCSRF($csrfToken)) {
                return ["success" => false, "message" => "Invalid security token."];
            }

            // Get user information
            $stmt = $this->pdo->prepare("
                SELECT id, username, email, full_name, role, status
                FROM users
                WHERE id = ? AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ["success" => false, "message" => "User not found or inactive."];
            }

            // Prevent admin from resetting their own password through this method
            if ($user['id'] == $_SESSION['admin_user_id']) {
                return ["success" => false, "message" => "You cannot reset your own password through this method."];
            }

            // Generate temporary password
            $tempPassword = $this->generateTempPassword();
            $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

            // Update user password
            $stmt = $this->pdo->prepare("
                UPDATE users
                SET password = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$hashedPassword, $userId]);

            // Send email with temporary password
            $emailSent = $this->sendTempPasswordEmail($user, $tempPassword);

            if ($emailSent) {
                // Log security event
                $this->logSecurityEvent('admin_password_reset', $userId, "Admin reset password for user {$user['username']}");

                return [
                    "success" => true,
                    "message" => "Password has been reset successfully. A temporary password has been emailed to the user."
                ];
            } else {
                return ["success" => false, "message" => "Password updated but failed to send email notification."];
            }

        } catch (Exception $e) {
            error_log("Admin password reset error: " . $e->getMessage());
            return ["success" => false, "message" => "An error occurred. Please try again."];
        }
    }

    /**
     * Generate a secure temporary password
     */
    private function generateTempPassword($length = 12)
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $password;
    }

    /**
     * Send email with temporary password
     */
    private function sendTempPasswordEmail($user, $tempPassword)
    {
        $subject = "Akanyenyeri - Your Password Has Been Reset";

        $message = "
        <html>
        <head>
            <title>Password Reset</title>
        </head>
        <body>
            <h2>Password Reset Notification</h2>
            <p>Hello {$user['full_name']},</p>
            <p>Your password for the Akanyenyeri admin system has been reset by an administrator.</p>
            <p><strong>Your temporary password is:</strong></p>
            <div style='background-color: #f0f0f0; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 18px; margin: 10px 0;'>
                {$tempPassword}
            </div>
            <p><strong>Important:</strong></p>
            <ul>
                <li>This password will expire in 24 hours</li>
                <li>You will be required to change this password on your next login</li>
                <li>For security reasons, please change your password immediately after logging in</li>
            </ul>
            <p>If you did not request this password reset or believe this was done in error, please contact the system administrator immediately.</p>
            <br>
            <p>You can log in at: <a href='" . ADMIN_URL . "'>" . ADMIN_URL . "</a></p>
            <br>
            <p>Best regards,<br>Akanyenyeri System Administrator</p>
        </body>
        </html>
        ";

        return $this->sendEmail($user['email'], $subject, $message);
    }

    /**
     * Send email using PHP mail function
     */
    private function sendEmail($to, $subject, $message)
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: Akanyenyeri Admin <admin@akanyenyeri.com>',
            'Reply-To: admin@akanyenyeri.com'
        ];

        return mail($to, $subject, $message, implode("\r\n", $headers));
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

    $userId = intval($input["user_id"] ?? 0);
    $csrfToken = $input["csrf_token"] ?? "";

    // Basic validation
    if (empty($userId) || empty($csrfToken)) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid request data.",
        ]);
        exit();
    }

    // Handle the reset
    $handler = new AdminPasswordResetHandler();
    $result = $handler->resetUserPassword($userId, $csrfToken);

    echo json_encode($result);
    exit();
}

// If we get here, redirect to users page
header("Location: ../users.php");
exit();
?>
