<?php
/**
 * Security Questions Recovery Handler for Akanyenyeri Magazine
 * Handles password recovery using security questions
 */

session_start();
require_once __DIR__ . "/../../database/config/database.php";

class SecurityQuestionsRecovery
{
    private $pdo;
    private $defaultQuestions = [
        "What was the name of your first pet?",
        "What is your mother's maiden name?",
        "What was the name of your first school?",
        "What is your favorite childhood memory?",
        "What was your childhood nickname?",
        "What is the name of the city where you were born?",
        "What was your favorite subject in school?",
        "What was the make and model of your first car?",
        "What is your favorite book or movie?",
        "What was the name of your best friend in childhood?"
    ];

    public function __construct()
    {
        $this->pdo = getDB();
    }

    /**
     * Load security questions for a user
     */
    public function loadQuestions($username)
    {
        try {
            // Get user ID
            $stmt = $this->pdo->prepare("
                SELECT id FROM users
                WHERE username = ? AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ["success" => false, "message" => "User not found."];
            }

            // Get user's security questions
            $stmt = $this->pdo->prepare("
                SELECT question FROM user_security_questions
                WHERE user_id = ?
                ORDER BY created_at ASC
                LIMIT 3
            ");
            $stmt->execute([$user['id']]);
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($questions)) {
                return ["success" => false, "message" => "No security questions have been set up for this account yet. Please contact an administrator or use email recovery if available."];
            }

            return [
                "success" => true,
                "questions" => array_map(function($q) { return ['question' => $q['question']]; }, $questions)
            ];

        } catch (Exception $e) {
            error_log("Load questions error: " . $e->getMessage());
            return ["success" => false, "message" => "An error occurred. Please try again."];
        }
    }

    /**
     * Verify security question answers
     */
    public function verifyAnswers($username, $answers)
    {
        try {
            // Get user ID
            $stmt = $this->pdo->prepare("
                SELECT id FROM users
                WHERE username = ? AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ["success" => false, "message" => "User not found."];
            }

            // Verify answers
            $correctCount = 0;
            foreach ($answers as $answer) {
                $stmt = $this->pdo->prepare("
                    SELECT answer_hash FROM user_security_questions
                    WHERE user_id = ? AND question = ?
                    LIMIT 1
                ");
                $stmt->execute([$user['id'], $answer['question']]);
                $stored = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($stored && password_verify(strtolower(trim($answer['answer'])), $stored['answer_hash'])) {
                    $correctCount++;
                }
            }

            // Require at least 2 correct answers out of 3
            if ($correctCount >= 2) {
                return ["success" => true, "message" => "Answers verified successfully."];
            } else {
                return ["success" => false, "message" => "Incorrect answers. Please try again."];
            }

        } catch (Exception $e) {
            error_log("Verify answers error: " . $e->getMessage());
            return ["success" => false, "message" => "An error occurred. Please try again."];
        }
    }

    /**
     * Reset password using verified security questions
     */
    public function resetPassword($username, $newPassword, $verifiedAnswers)
    {
        try {
            // Validate password strength
            if (!$this->validatePasswordStrength($newPassword)) {
                return ["success" => false, "message" => "Password does not meet security requirements."];
            }

            // Get user ID
            $stmt = $this->pdo->prepare("
                SELECT id FROM users
                WHERE username = ? AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ["success" => false, "message" => "User not found."];
            }

            // Double-check answers are still valid
            $verification = $this->verifyAnswers($username, $verifiedAnswers);
            if (!$verification['success']) {
                return ["success" => false, "message" => "Security verification failed."];
            }

            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("
                UPDATE users
                SET password = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$hashedPassword, $user['id']]);

            // Log security event
            $this->logSecurityEvent('password_reset_security_questions', $user['id'], "Password reset using security questions");

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
     * Set up security questions for a user
     */
    public function setupQuestions($userId, $questions)
    {
        try {
            // Remove existing questions
            $stmt = $this->pdo->prepare("DELETE FROM user_security_questions WHERE user_id = ?");
            $stmt->execute([$userId]);

            // Add new questions
            $stmt = $this->pdo->prepare("
                INSERT INTO user_security_questions (user_id, question, answer_hash)
                VALUES (?, ?, ?)
            ");

            foreach ($questions as $question) {
                $answerHash = password_hash(strtolower(trim($question['answer'])), PASSWORD_DEFAULT);
                $stmt->execute([$userId, $question['question'], $answerHash]);
            }

            return ["success" => true, "message" => "Security questions set up successfully."];

        } catch (Exception $e) {
            error_log("Setup questions error: " . $e->getMessage());
            return ["success" => false, "message" => "Failed to set up security questions."];
        }
    }

    /**
     * Get default security questions
     */
    public function getDefaultQuestions()
    {
        return $this->defaultQuestions;
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

    $action = $input["action"] ?? "";
    $handler = new SecurityQuestionsRecovery();

    switch ($action) {
        case 'load_questions':
            $username = trim($input["username"] ?? "");
            if (empty($username)) {
                echo json_encode(["success" => false, "message" => "Username is required."]);
                exit();
            }
            $result = $handler->loadQuestions($username);
            break;

        case 'verify_answers':
            $username = trim($input["username"] ?? "");
            $answers = $input["answers"] ?? [];

            if (empty($username) || empty($answers)) {
                echo json_encode(["success" => false, "message" => "Username and answers are required."]);
                exit();
            }
            $result = $handler->verifyAnswers($username, $answers);
            break;

        case 'reset_password':
            $username = trim($input["username"] ?? "");
            $newPassword = $input["new_password"] ?? "";
            $answers = $input["answers"] ?? [];

            if (empty($username) || empty($newPassword) || empty($answers)) {
                echo json_encode(["success" => false, "message" => "All fields are required."]);
                exit();
            }
            $result = $handler->resetPassword($username, $newPassword, $answers);
            break;

        default:
            $result = ["success" => false, "message" => "Invalid action."];
    }

    echo json_encode($result);
    exit();
}

// If we get here, redirect to recovery options
header("Location: ../recovery_options.php");
exit();
?>
