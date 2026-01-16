<?php
/**
 * Populate Media Table Script
 * Scans existing files in uploads directory and adds them to media table
 */

// Skip authentication for CLI/setup usage
if (php_sapi_name() !== 'cli') {
    session_start();
    require_once "auth_check.php";
    require_once __DIR__ . "/../../database/config/database.php";
    // Require authentication for web access
    requireAuth();
} else {
    // CLI mode - direct database access
    require_once __DIR__ . "/../../database/config/database.php";
}

try {
    $pdo = getDB();
    echo "<h1>🔄 Populating Media Table with Existing Files</h1>";
    echo "<pre>";

    // Get admin user ID (assuming current user is admin)
    $admin_user_id = (php_sapi_name() !== 'cli' && isset($_SESSION['admin_user_id'])) ? $_SESSION['admin_user_id'] : 1; // Default to 1 if not set

    $base_upload_dir = __DIR__ . '/../../uploads';
    $sub_dirs = ['images', 'videos', 'audio', 'documents', 'others'];

    $total_added = 0;
    $total_skipped = 0;

    foreach ($sub_dirs as $sub_dir) {
        $full_dir = $base_upload_dir . '/' . $sub_dir;

        if (!is_dir($full_dir)) {
            echo "Directory $sub_dir does not exist, skipping...\n";
            continue;
        }

        echo "\n📁 Scanning $sub_dir directory...\n";

        $files = scandir($full_dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $file_path = $full_dir . '/' . $file;

            // Skip if not a file
            if (!is_file($file_path)) continue;

            // Check if file is already in media table
            $stmt = $pdo->prepare("SELECT id FROM media WHERE file_path = ?");
            $stmt->execute([$sub_dir . '/' . $file]);
            if ($stmt->fetch()) {
                echo "  ⏭️  Skipped $file (already in database)\n";
                $total_skipped++;
                continue;
            }

            // Get file info
            $file_size = filesize($file_path);
            $mime_type = mime_content_type($file_path);
            $original_name = $file; // We don't have the original name, so use the current filename

            // Insert into media table
            $stmt = $pdo->prepare("
                INSERT INTO media (
                    filename, original_name, file_path, file_size, mime_type,
                    uploaded_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");

            $result = $stmt->execute([
                $file,
                $original_name,
                $sub_dir . '/' . $file,
                $file_size,
                $mime_type,
                $admin_user_id
            ]);

            if ($result) {
                echo "  ✅ Added $file\n";
                $total_added++;
            } else {
                echo "  ❌ Failed to add $file\n";
            }
        }
    }

    echo "\n🎉 Media table population completed!\n";
    echo "- Files added: $total_added\n";
    echo "- Files skipped (already exist): $total_skipped\n";

    echo "\n🔗 Next steps:\n";
    echo "1. Visit Media Library: http://localhost/akanyenyeri/admin/media.php\n";
    echo "2. Check Post Editor: http://localhost/akanyenyeri/admin/post-new.php\n";

    echo "</pre><h2>✅ Media Table Populated!</h2>";

} catch (Exception $e) {
    echo "<h2>❌ Error:</h2><pre>" . $e->getMessage() . "</pre>";
}
?>
