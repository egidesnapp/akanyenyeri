<?php
/**
 * Database Reset Script for Akanyenyeri Magazine
 * Drops and recreates the entire database
 */

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "akanyenyeri_db";

try {
    // Connect to MySQL server (without database)
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Drop database if exists
    $pdo->exec("DROP DATABASE IF EXISTS $database");
    echo "✓ Database '$database' dropped successfully.<br>";

    // Create database
    $pdo->exec(
        "CREATE DATABASE $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
    );
    echo "✓ Database '$database' created successfully.<br>";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
