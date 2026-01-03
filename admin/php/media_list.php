<?php
/**
 * Media List - AJAX endpoint for image gallery in post editor
 * Returns list of uploaded media files in JSON format
 */

session_start();
require_once "auth_check.php";
require_once "../../config/database.php";

// Require authentication
requireAuth();

// Set JSON header
header("Content-Type: application/json");

try {
    $pdo = getDB();

    // Fetch all media files (images only)
    $stmt = $pdo->query("
        SELECT 
            id,
            filename,
            original_name,
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
        // Construct full URL path - adjust based on where files are actually stored
        $file_url = '../uploads/images/' . $file['filename'];
        
        // Verify file exists
        $file_path = __DIR__ . '/../../uploads/images/' . $file['filename'];
        
        if (file_exists($file_path)) {
            $images[] = [
                'id' => $file['id'],
                'url' => $file_url,
                'name' => $file['original_name'],
                'alt' => $file['alt_text'] ?? $file['original_name'],
                'caption' => $file['caption'] ?? '',
                'date' => $file['created_at']
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

