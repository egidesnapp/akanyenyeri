<?php
/**
 * Forgot Password Handler for Akanyenyeri Magazine
 * Handles password reset requests with role-based logic
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
     * Handle forgot password request
     */
    public function handleRequest($email)
    {
        try {
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ["success" => false, "message" => "Invalid email format."];
            }

            // Find user by email
            $stmt = $this->pdo->prepare("
                SELECT id, username, email, role, full_name, status
                FROM users
                WHERE email = ? AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                // Don't reveal if email exists or not for security
                return [
                    "success" => false,
                    "message" => "Email sending failed. Please try the 'Contact Administrator' option below, or contact admin@akanyenyeri.com directly."
                ];
            }

            // Handle based on role
            if ($user['role'] === 'admin') {
                return $this->handleAdminReset($user);
            } else {
                return $this->handleUserReset($user);
            }

        } catch (Exception $e) {
            error_log("Forgot password error: " . $e->getMessage());
            return ["success" => false, "message" => "An error occurred. Please try again."];
        }
    }

    /**
     * Handle admin password reset (send email with reset link)
     */
    private function handleAdminReset($user)
    {
        try {
            // Generate secure token
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Clean up expired tokens for this user
            $this->cleanupExpiredTokens($user['id']);

            // Insert new reset token
            $stmt = $this->pdo->prepare("
                INSERT INTO password_reset_tokens (user_id, token, email, expires_at)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$user['id'], hash('sha256', $token), $user['email'], $expiresAt]);

            // Send reset email
            $resetLink = SITE_URL . "admin/reset_password.php?token=" . $token;
            $emailSent = $this->sendAdminResetEmail($user, $resetLink);

            if ($emailSent) {
                // Log security event
                $this->logSecurityEvent('password_reset_requested', $user['id'], "Admin password reset requested for {$user['email']}");

                return [
                    "success" => true,
                    "message" => "Password reset link has been sent to your email address."
                ];
            } else {
                return ["success" => false, "message" => "Failed to send reset email. Please try again later."];
            }

        } catch (Exception $e) {
            error_log("Admin reset error: " . $e->getMessage());
            return ["success" => false, "message" => "An error occurred. Please try again."];
        }
    }

    /**
     * Handle regular user password reset (send notification to admin)
     */
    private function handleUserReset($user)
    {
        try {
            // Get admin email from settings
            $adminEmail = $this->getAdminEmail();

            // Send notification email to admin
            $emailSent = $this->sendAdminNotificationEmail($user, $adminEmail);

            if ($emailSent) {
                // Log security event
                $this->logSecurityEvent('password_reset_requested', $user['id'], "User password reset requested for {$user['email']} - notification sent to admin");

                return [
                    "success" => true,
                    "message" => "Your password reset request has been sent to the administrator. You will be contacted soon."
                ];
            } else {
                return ["success" => false, "message" => "Failed to send request. Please contact the administrator directly."];
            }

        } catch (Exception $e) {
            error_log("User reset error: " . $e->getMessage());
            return ["success" => false, "message" => "An error occurred. Please try again."];
        }
    }

    /**
     * Clean up expired tokens for a user
     */
    private function cleanupExpiredTokens($userId)
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM password_reset_tokens
                WHERE user_id = ? AND expires_at < NOW()
            ");
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            // Log but don't fail the operation
            error_log("Token cleanup error: " . $e->getMessage());
        }
    }

    /**
     * Send password reset email to admin
     */
    private function sendAdminResetEmail($user, $resetLink)
    {
        $subject = "Akanyenyeri - Password Reset Request";
        $message = "
        <html>
        <head>
            <title>Password Reset</title>
        </head>
        <body>
            <h2>Password Reset Request</h2>
            <p>Hello {$user['full_name']},</p>
            <p>You have requested to reset your password for your Akanyenyeri admin account.</p>
            <p>Click the link below to reset your password:</p>
            <p><a href='{$resetLink}' style='background-color: #3182ce; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
            <p>If you didn't request this, please ignore this email.</p>
            <p>This link will expire in 1 hour.</p>
            <br>
            <p>Best regards,<br>Akanyenyeri Team</p>
        </body>
        </html>
        ";

        return $this->sendEmail($user['email'], $subject, $message);
    }

    /**
     * Send notification email to admin about user password reset request
     */
    private function sendAdminNotificationEmail($user, $adminEmail)
    {
        $subject = "Akanyenyeri - User Password Reset Request";
        $message = "
        <html>
        <head>
            <title>User Password Reset Request</title>
        </head>
        <body>
            <h2>User Password Reset Request</h2>
            <p>A user has requested a password reset:</p>
            <ul>
                <li><strong>Name:</strong> {$user['full_name']}</li>
                <li><strong>Username:</strong> {$user['username']}</li>
                <li><strong>Email:</strong> {$user['email']}</li>
                <li><strong>Role:</strong> {$user['role']}</li>
                <li><strong>Requested at:</strong> " . date('Y-m-d H:i:s') . "</li>
            </ul>
            <p>Please contact the user to assist with password reset.</p>
            <br>
            <p>Best regards,<br>Akanyenyeri System</p>
        </body>
        </html>
        ";

        return $this->sendEmail($adminEmail, $subject, $message);
    }

    /**
     * Get admin email from settings
     */
    private function getAdminEmail()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT setting_value
                FROM site_settings
                WHERE setting_key = 'admin_email'
                LIMIT 1
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['setting_value'] : 'admin@akanyenyeri.com';
        } catch (Exception $e) {
            return 'admin@akanyenyeri.com';
        }
    }

    /**
     * Send email using PHP mail function with better error handling
     */
    private function sendEmail($to, $subject, $message)
    {
        try {
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: Akanyenyeri <noreply@akanyenyeri.com>',
                'Reply-To: admin@akanyenyeri.com',
                'X-Mailer: PHP/' . phpversion()
            ];

            // Log email attempt for debugging
            error_log("Attempting to send email to: $to, Subject: $subject");

            $result = mail($to, $subject, $message, implode("\r\n", $headers));

            if (!$result) {
                error_log("Email sending failed for: $to");
                return false;
            }

            error_log("Email sent successfully to: $to");
            return true;

        } catch (Exception $e) {
            error_log("Email sending exception: " . $e->getMessage());
            return false;
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

    $email = trim($input["email"] ?? "");

    // Basic validation
    if (empty($email)) {
        echo json_encode([
            "success" => false,
            "message" => "Please enter your email address.",
        ]);
        exit();
    }

    // Handle the request
    $handler = new PasswordResetHandler();
    $result = $handler->handleRequest($email);

    echo json_encode($result);
    exit();
}

// If we get here, redirect to forgot password page
header("Location: ../forgot_password.php");
exit();
?>
