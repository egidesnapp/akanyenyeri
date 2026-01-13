<?php
/**
 * Tables Reset Script for Akanyenyeri Magazine
 * Drops all tables and recreates them
 */

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "akanyenyeri_db";

try {
    // Connect to the database
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Drop all tables if they exist
    $tables = [
        'user_sessions',
        'user_remember_tokens',
        'security_logs',
        'site_settings',
        'comments',
        'media',
        'post_tags',
        'posts',
        'tags',
        'categories',
        'users'
    ];

    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS $table");
        echo "✓ Dropped table '$table'.<br>";
    }

    echo "✓ All tables dropped successfully.<br>";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
