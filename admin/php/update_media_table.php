<?php
/**
 * Database Update Script for Media Table
 * Adds missing columns to the media table for full functionality
 */

require_once "../../database/config/database.php";

try {
    // Get database connection
    $pdo = getDB();

    echo "Starting media table update...<br>";

    // Check if columns exist and add them if missing
    $columns_to_add = [
        'alt_text' => "ADD COLUMN alt_text VARCHAR(255) DEFAULT NULL AFTER mime_type",
        'caption' => "ADD COLUMN caption TEXT DEFAULT NULL AFTER alt_text",
        'updated_at' => "ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at"
    ];

    // Get existing columns
    $stmt = $pdo->query("DESCRIBE media");
    $existing_columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_columns[] = $row['Field'];
    }

    // Add missing columns
    foreach ($columns_to_add as $column => $sql) {
        if (!in_array($column, $existing_columns)) {
            $pdo->exec("ALTER TABLE media $sql");
            echo "✓ Added column '$column' to media table.<br>";
        } else {
            echo "• Column '$column' already exists.<br>";
        }
    }

    // Create admin_users table if it doesn't exist (for compatibility)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            display_name VARCHAR(100),
            role ENUM('super_admin', 'admin', 'editor') DEFAULT 'editor',
            permissions JSON DEFAULT NULL,
            last_login TIMESTAMP NULL,
            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "✓ Admin users table created/verified.<br>";

    // Create admin_logs table for logging actions
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(100) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ");
    echo "✓ Admin logs table created/verified.<br>";

    // Update file_path column to be optional (since we're using uploads/ directly)
    $pdo->exec("ALTER TABLE media MODIFY COLUMN file_path VARCHAR(500) DEFAULT NULL");
    echo "✓ Updated file_path column to be optional.<br>";

    // Create uploads directory if it doesn't exist
    $uploads_dir = "../../uploads";
    if (!is_dir($uploads_dir)) {
        mkdir($uploads_dir, 0755, true);
        echo "✓ Created uploads directory.<br>";
    }

    // Create .htaccess file for security
    $htaccess_content = "# Prevent execution of PHP files in uploads
<Files *.php>
    deny from all
</Files>
<Files *.phtml>
    deny from all
</Files>
<Files *.php3>
    deny from all
</Files>
<Files *.php4>
    deny from all
</Files>
<Files *.php5>
    deny from all
</Files>
<Files *.pl>
    deny from all
</Files>
<Files *.py>
    deny from all
</Files>
<Files *.jsp>
    deny from all
</Files>
<Files *.asp>
    deny from all
</Files>
<Files *.sh>
    deny from all
</Files>
";

    $htaccess_path = $uploads_dir . "/.htaccess";
    if (!file_exists($htaccess_path)) {
        file_put_contents($htaccess_path, $htaccess_content);
        echo "✓ Created .htaccess security file in uploads directory.<br>";
    }

    echo "<br><strong>✓ Media table update completed successfully!</strong><br>";
    echo "<br>The media library should now work properly with all features.<br>";

} catch (Exception $e) {
    echo "<strong>✗ Error:</strong> " . $e->getMessage() . "<br>";
    exit(1);
}
?>
