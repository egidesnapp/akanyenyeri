<?php
/**
 * Media Actions - AJAX endpoint for media operations
 * Handles delete and other media file operations
 */

session_start();
require_once "auth_check.php";
require_once "../../config/database.php";

// Require authentication
requireAuth();

// Set JSON header
header("Content-Type: application/json");

try {
    // Validate request method
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Only POST requests are allowed");
    }

    // Validate CSRF token
    if (!validateCSRF($_POST["csrf_token"] ?? "")) {
        throw new Exception("Invalid security token");
    }

    // Get action
    $action = $_POST["action"] ?? "";

    switch ($action) {
        case "delete":
            handleDelete();
            break;

        default:
            throw new Exception("Invalid action specified");
    }
} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage(),
    ]);
}

/**
 * Handle media file deletion
 */
function handleDelete()
{
    // Check permissions
    if (!canDo("manage_media")) {
        throw new Exception("You do not have permission to delete media files");
    }

    // Validate media ID
    if (!isset($_POST["media_id"])) {
        throw new Exception("Media ID is required");
    }

    $media_id = (int) $_POST["media_id"];
    if ($media_id <= 0) {
        throw new Exception("Invalid media ID");
    }

    // Get database connection
    $pdo = getDB();

    // Get media file details
    $stmt = $pdo->prepare("
        SELECT filename, original_name, uploaded_by
        FROM media
        WHERE id = ?
    ");
    $stmt->execute([$media_id]);
    $media = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$media) {
        throw new Exception("Media file not found");
    }

    // Check if current user can delete this file
    if (
        !canDo("delete_others_media") &&
        $media["uploaded_by"] != $_SESSION["admin_user_id"]
    ) {
        throw new Exception("You can only delete files you uploaded");
    }

    // Delete physical file
    $file_path = "../../uploads/" . $media["filename"];
    if (file_exists($file_path)) {
        if (!unlink($file_path)) {
            throw new Exception("Failed to delete physical file");
        }
    }

    // Delete database record
    $stmt = $pdo->prepare("DELETE FROM media WHERE id = ?");
    if (!$stmt->execute([$media_id])) {
        throw new Exception("Failed to delete media record from database");
    }

    // Log the deletion
    logAction("media_deleted", "Deleted media file: {$media["original_name"]}");

    // Return success response
    echo json_encode([
        "success" => true,
        "message" => "Media file deleted successfully",
    ]);
}

/**
 * Log admin actions
 */
function logAction($action, $details = "")
{
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            INSERT INTO admin_logs (user_id, action, details, ip_address, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $_SESSION["admin_user_id"],
            $action,
            $details,
            $_SERVER["REMOTE_ADDR"] ?? "unknown",
        ]);
    } catch (Exception $e) {
        // Log errors silently - don't break the main operation
        error_log("Failed to log action: " . $e->getMessage());
    }
}
?>
