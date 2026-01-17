<?php
/**
 * Get Media Details - AJAX endpoint for media modal
 * Returns media file information in JSON format
 */

session_start();
require_once "auth_check.php";
require_once "../../database/config/database.php";

// Require authentication
requireAuth();

// Set JSON header
header("Content-Type: application/json");

try {
    // Validate request
    if (!isset($_GET["id"])) {
        throw new Exception("Media ID is required");
    }

    $media_id = (int) $_GET["id"];
    if ($media_id <= 0) {
        throw new Exception("Invalid media ID");
    }

    // Get database connection
    $pdo = getDB();

    // Fetch media details
    $stmt = $pdo->prepare("
        SELECT
            m.id,
            m.filename,
            m.original_name,
            m.mime_type,
            m.file_size,
            m.alt_text,
            m.caption,
            m.created_at,
            m.updated_at,
            u.username as uploaded_by_name,
            u.display_name as uploaded_by_display_name
        FROM media m
        LEFT JOIN users u ON m.uploaded_by = u.id
        WHERE m.id = ?
    ");

    $stmt->execute([$media_id]);
    $media = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$media) {
        throw new Exception("Media file not found");
    }

    // Check if file exists on disk
    $file_path = "../../uploads/" . $media["filename"];
    if (!file_exists($file_path)) {
        throw new Exception("Media file not found on disk");
    }

    // Add additional computed properties
    $media["is_image"] = strpos($media["mime_type"], "image/") === 0;
    $media["file_size_formatted"] = formatFileSize($media["file_size"]);
    $media["created_at_formatted"] = date(
        "M j, Y g:i A",
        strtotime($media["created_at"]),
    );
    $media["url"] = "/uploads/" . $media["filename"];
    $media["full_url"] =
        (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on"
            ? "https"
            : "http") .
        "://" .
        $_SERVER["HTTP_HOST"] .
        "/uploads/" .
        $media["filename"];

    // Return success response
    echo json_encode([
        "success" => true,
        "media" => $media,
    ]);
} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage(),
    ]);
}

/**
 * Format file size helper function
 */
function formatFileSize($bytes)
{
    $units = ["B", "KB", "MB", "GB"];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= 1 << 10 * $pow;
    return round($bytes, 2) . " " . $units[$pow];
}
?>
