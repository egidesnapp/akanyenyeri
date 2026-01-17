<?php
/**
 * Admin Contact Handler for Akanyenyeri Magazine
 * Handles direct contact requests to administrators
 */

session_start();
require_once __DIR__ . "/../../database/config/database.php";

class AdminContactHandler
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = getDB();
    }

    /**
     * Handle admin contact request
     */
    public function sendContactRequest($email, $message = "")
    {
        try {
            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ["success" => false, "message" => "Invalid email address."];
            }

            // Get admin email
            $adminEmail = $this->getAdminEmail();

            // Check if user exists with this email
            $stmt = $this->pdo->prepare("
                SELECT id, username, full_name, role FROM users
                WHERE email = ? AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $userInfo = $user ? " (User: {$user['username']}, Role: {$user['role']})" : " (Non-registered email)";

            // Send email to admin
            $emailSent = $this->sendContactEmail($adminEmail, $email, $message, $userInfo);

            if ($emailSent) {
                // Log the contact attempt
                $this->logContactEvent($email, $user ? $user['id'] : null, "Admin contact request from: $email$userInfo");

                return [
                    "success" => true,
                    "message" => "Your request has been sent to the administrator. You will receive assistance soon."
                ];
            } else {
                // Email failed - provide manual contact info
                $adminEmail = $this->getAdminEmail();
                return [
                    "success" => false,
                    "message" => "Email sending failed. Please contact the administrator directly at: $adminEmail with your recovery request."
                ];
            }

        } catch (Exception $e) {
            error_log("Admin contact error: " . $e->getMessage());
            return ["success" => false, "message" => "An error occurred. Please try again."];
        }
    }

    /**
     * Send contact email to admin
     */
    private function sendContactEmail($adminEmail, $userEmail, $message, $userInfo)
    {
        $subject = "Akanyenyeri - Password Recovery Assistance Request";

        $emailContent = "
        <html>
        <head>
            <title>Password Recovery Request</title>
        </head>
        <body>
            <h2>Password Recovery Assistance Request</h2>
            <p>A user has requested assistance with password recovery:</p>
            <div style='background-color: #f0f0f0; padding: 15px; border-radius: 5px; margin: 10px 0;'>
                <strong>Requester Email:</strong> {$userEmail}<br>
                <strong>User Status:</strong> {$userInfo}<br>
                <strong>Request Time:</strong> " . date('Y-m-d H:i:s') . "
            </div>
        ";

        if (!empty($message)) {
            $emailContent .= "
            <div style='background-color: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #2196f3;'>
                <strong>Additional Message:</strong><br>
                " . nl2br(htmlspecialchars($message)) . "
            </div>
            ";
        }

        $emailContent .= "
            <p><strong>Action Required:</strong> Please contact this user directly to assist with their password recovery.</p>
            <p>You can reply directly to this email to contact them.</p>
            <br>
            <p>Best regards,<br>Akanyenyeri System</p>
        </body>
        </html>
        ";

        return $this->sendEmail($adminEmail, $subject, $emailContent, $userEmail);
    }

    /**
     * Send email with reply-to header
     */
    private function sendEmail($to, $subject, $message, $replyTo = null)
    {
        try {
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: Akanyenyeri Support <support@akanyenyeri.com>',
                'X-Mailer: PHP/' . phpversion()
            ];

            if ($replyTo) {
                $headers[] = 'Reply-To: ' . $replyTo;
            }

            // Log email attempt
            error_log("Sending admin contact email to: $to, Reply-To: $replyTo");

            $result = mail($to, $subject, $message, implode("\r\n", $headers));

            if (!$result) {
                error_log("Admin contact email failed for: $to");
                return false;
            }

            error_log("Admin contact email sent successfully to: $to");
            return true;

        } catch (Exception $e) {
            error_log("Email sending exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get admin email from settings
     */
    private function getAdminEmail()
    {
        try {
            // First try to get from site settings
            $stmt = $this->pdo->prepare("
                SELECT setting_value
                FROM site_settings
                WHERE setting_key = 'admin_email'
                LIMIT 1
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && !empty($result['setting_value'])) {
                return $result['setting_value'];
            }

            // If no setting found, get the email of the first active admin user
            $stmt = $this->pdo->prepare("
                SELECT email
                FROM users
                WHERE role = 'admin' AND status = 'active'
                ORDER BY created_at ASC
                LIMIT 1
            ");
            $stmt->execute();
            $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);

            return $adminUser ? $adminUser['email'] : 'admin@akanyenyeri.com';
        } catch (Exception $e) {
            error_log("Error getting admin email: " . $e->getMessage());
            return 'admin@akanyenyeri.com';
        }
    }

    /**
     * Get admin contact info for display
     */
    public function getAdminContactInfo()
    {
        $email = $this->getAdminEmail();

        // Try to get admin user details
        try {
            $stmt = $this->pdo->prepare("
                SELECT full_name
                FROM users
                WHERE email = ? AND role = 'admin' AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'email' => $email,
                'name' => $admin ? $admin['full_name'] : 'Administrator'
            ];
        } catch (Exception $e) {
            return [
                'email' => $email,
                'name' => 'Administrator'
            ];
        }
    }

    /**
     * Log contact event
     */
    private function logContactEvent($email, $userId, $details = "")
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO security_logs (event_type, user_id, ip_address, user_agent, details, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                'admin_contact_request',
                $userId,
                $_SERVER["REMOTE_ADDR"] ?? "unknown",
                $_SERVER["HTTP_USER_AGENT"] ?? "unknown",
                $details,
            ]);
        } catch (Exception $e) {
            error_log("Contact log error: " . $e->getMessage());
        }
    }
}

// Handle GET request for admin contact info
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["action"]) && $_GET["action"] === "get_contact_info") {
    header("Content-Type: application/json");

    $handler = new AdminContactHandler();
    $contactInfo = $handler->getAdminContactInfo();

    echo json_encode([
        "success" => true,
        "contact_info" => $contactInfo
    ]);
    exit();
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
    $message = trim($input["message"] ?? "");

    // Basic validation
    if (empty($email)) {
        echo json_encode([
            "success" => false,
            "message" => "Email address is required.",
        ]);
        exit();
    }

    // Handle the contact request
    $handler = new AdminContactHandler();
    $result = $handler->sendContactRequest($email, $message);

    echo json_encode($result);
    exit();
}

// If we get here, redirect to recovery options
header("Location: ../recovery_options.php");
exit();
?>
