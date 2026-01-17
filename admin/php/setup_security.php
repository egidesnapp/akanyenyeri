<?php
/**
 * Security Questions Setup Handler for Akanyenyeri Magazine
 * Handles setting up security questions for password recovery
 */

session_start();
require_once 'auth_check.php';

// Require authentication
requireAuth();

class SecuritySetupHandler
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = getDB();
    }

    /**
     * Set up security questions for the current user
     */
    public function setupQuestions($questions)
    {
        try {
            $userId = $_SESSION['admin_user_id'];

            // Validate input
            if (empty($questions) || count($questions) < 3) {
                return ["success" => false, "message" => "At least 3 security questions are required."];
            }

            if (count($questions) > 5) {
                return ["success" => false, "message" => "Maximum 5 security questions allowed."];
            }

            // Validate each question and answer
            $validatedQuestions = [];
            foreach ($questions as $q) {
                $question = trim($q['question'] ?? '');
                $answer = trim($q['answer'] ?? '');

                if (empty($question) || empty($answer)) {
                    return ["success" => false, "message" => "All questions and answers are required."];
                }

                if (strlen($question) > 255) {
                    return ["success" => false, "message" => "Questions must be less than 255 characters."];
                }

                if (strlen($answer) > 255) {
                    return ["success" => false, "message" => "Answers must be less than 255 characters."];
                }

                $validatedQuestions[] = [
                    'question' => $question,
                    'answer' => $answer
                ];
            }

            // Check for duplicate questions
            $questionTexts = array_map(function($q) { return strtolower($q['question']); }, $validatedQuestions);
            if (count($questionTexts) !== count(array_unique($questionTexts))) {
                return ["success" => false, "message" => "Duplicate questions are not allowed."];
            }

            // Remove existing questions for this user
            $stmt = $this->pdo->prepare("DELETE FROM user_security_questions WHERE user_id = ?");
            $stmt->execute([$userId]);

            // Insert new questions
            $stmt = $this->pdo->prepare("
                INSERT INTO user_security_questions (user_id, question, answer_hash)
                VALUES (?, ?, ?)
            ");

            foreach ($validatedQuestions as $q) {
                $answerHash = password_hash(strtolower($q['answer']), PASSWORD_DEFAULT);
                $stmt->execute([$userId, $q['question'], $answerHash]);
            }

            // Log security event
            $this->logSecurityEvent('security_questions_setup', $userId, "Set up " . count($validatedQuestions) . " security questions");

            return [
                "success" => true,
                "message" => "Security questions have been set up successfully. You can now use them for password recovery."
            ];

        } catch (Exception $e) {
            error_log("Setup security questions error: " . $e->getMessage());
            return ["success" => false, "message" => "An error occurred. Please try again."];
        }
    }

    /**
     * Get current user's security questions (without answers)
     */
    public function getUserQuestions()
    {
        try {
            $userId = $_SESSION['admin_user_id'];

            $stmt = $this->pdo->prepare("
                SELECT question FROM user_security_questions
                WHERE user_id = ?
                ORDER BY created_at ASC
            ");
            $stmt->execute([$userId]);

            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Check if user has security questions set up
     */
    public function hasSecurityQuestions()
    {
        try {
            $userId = $_SESSION['admin_user_id'];

            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM user_security_questions
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);

            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
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

    $questions = $input["questions"] ?? [];

    // Basic validation
    if (empty($questions)) {
        echo json_encode([
            "success" => false,
            "message" => "Security questions data is required.",
        ]);
        exit();
    }

    // Handle the setup
    $handler = new SecuritySetupHandler();
    $result = $handler->setupQuestions($questions);

    echo json_encode($result);
    exit();
}

// If we get here, redirect to setup page
header("Location: ../setup_security.php");
exit();
?>
