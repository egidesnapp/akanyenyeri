<?php
/**
 * Media List - AJAX endpoint for image gallery in post editor
 * Returns list of uploaded media files in JSON format
 */

session_start();
require_once "auth_check.php";
require_once "../../database/config/database.php";

// Require authentication
requireAuth();

// Set JSON header
header("Content-Type: application/json");

try {
    $pdo = getDB();

    // Check if alt_text and caption columns exist
    $stmt = $pdo->query("DESCRIBE media");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $column_names = array_column($columns, 'Field');

    $has_alt_text = in_array('alt_text', $column_names);
    $has_caption = in_array('caption', $column_names);

    // Debug logging
    error_log("Media list called - has_alt_text: $has_alt_text, has_caption: $has_caption");

    // Fetch all media files (images only)
    $stmt = $pdo->query("
        SELECT
            id,
            filename,
            original_name,
            file_path,
            mime_type,
            alt_text,
            caption,
            created_at
        FROM media
        WHERE mime_type LIKE 'image/%'
        ORDER BY created_at DESC
        LIMIT 100
    ");

    $media_files = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $images = [];
    foreach ($media_files as $file) {
        // Construct full URL path based on file_path stored in database
        $file_url = '/akanyenyeri/uploads/' . $file['file_path'];

        // Verify file exists
        $file_path = __DIR__ . '/../../uploads/' . $file['file_path'];

        if (file_exists($file_path)) {
            $images[] = [
                'id' => $file['id'],
                'url' => $file_url,
                'name' => $file['original_name'],
                'alt' => ($has_alt_text ? ($file['alt_text'] ?? $file['original_name']) : $file['original_name']),
                'caption' => ($has_caption ? ($file['caption'] ?? '') : ''),
                'date' => $file['created_at'],
                'type' => $file['mime_type'],
                'directory' => dirname($file['file_path'])
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'count' => count($images),
        'images' => $images
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
