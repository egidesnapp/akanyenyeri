<?php
/**
 * Media Actions - AJAX endpoint for media operations
 * Handles delete and other media file operations
 */

session_start();
require_once "auth_check.php";
require_once "../../database/config/database.php";

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

        case "upload":
            handleUpload();
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
        SELECT filename, file_path, original_name, uploaded_by
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
    $file_path = "../../uploads/" . $media["file_path"];
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
 * Handle media file upload
 */
function handleUpload()
{
    // Check permissions
    if (!canDo("manage_media")) {
        throw new Exception("You do not have permission to upload media files");
    }

    // Check if file was uploaded
    if (!isset($_FILES["image"]) || $_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
        throw new Exception("No file uploaded or upload error occurred");
    }

    $file = $_FILES["image"];

    // Validate file size (15MB limit)
    $max_size = 15 * 1024 * 1024; // 15MB
    if ($file["size"] > $max_size) {
        throw new Exception("File size must be less than 15MB");
    }

    // Get actual MIME type from file content
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $actual_mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    // Basic security: block potentially dangerous file types
    $dangerous_types = [
        'application/x-php',
        'application/php',
        'application/x-httpd-php',
        'application/x-httpd-php-source',
        'text/php',
        'application/javascript',
        'application/x-javascript',
        'text/javascript'
    ];

    if (in_array($actual_mime, $dangerous_types) || in_array($file["type"], $dangerous_types)) {
        throw new Exception("This file type is not allowed for security reasons");
    }

    // Generate unique filename
    $file_extension = pathinfo($file["name"], PATHINFO_EXTENSION);
    $new_filename = time() . "_" . bin2hex(random_bytes(8)) . "." . $file_extension;

    // Determine directory based on file type
    $file_type = $file["type"];
    if (strpos($file_type, 'image/') === 0) {
        $sub_dir = 'images';
    } elseif (strpos($file_type, 'video/') === 0) {
        $sub_dir = 'videos';
    } elseif (strpos($file_type, 'audio/') === 0) {
        $sub_dir = 'audio';
    } elseif (in_array(strtolower($file_extension), ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf'])) {
        $sub_dir = 'documents';
    } else {
        $sub_dir = 'others';
    }

    // Create uploads sub-directory if it doesn't exist
    $upload_dir = "../../uploads/{$sub_dir}/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    $target = $upload_dir . $new_filename;

    // Move uploaded file
    if (!move_uploaded_file($file["tmp_name"], $target)) {
        throw new Exception("Failed to save uploaded file");
    }

    // Get database connection
    $pdo = getDB();

    // Insert media record
    $stmt = $pdo->prepare("
        INSERT INTO media (
            filename, original_name, file_path, file_size, mime_type, uploaded_by, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");

    $file_path = "{$sub_dir}/" . $new_filename; // Relative path for database
    $result = $stmt->execute([
        $new_filename,
        $file["name"],
        $file_path,
        $file["size"],
        $file["type"],
        $_SESSION["admin_user_id"]
    ]);

    if (!$result) {
        // Clean up uploaded file if database insert failed
        unlink($target);
        throw new Exception("Failed to save media record to database");
    }

    $media_id = $pdo->lastInsertId();

    // Log the upload
    logAction("media_uploaded", "Uploaded media file: {$file["name"]} to {$sub_dir}");

    // Return success response with file URL
    $file_url = "../uploads/{$sub_dir}/" . $new_filename;
    echo json_encode([
        "success" => true,
        "message" => "File uploaded successfully to {$sub_dir}",
        "url" => $file_url,
        "id" => $media_id,
        "filename" => $new_filename,
        "directory" => $sub_dir
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
