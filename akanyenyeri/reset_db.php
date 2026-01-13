<?php
/**
 * Database Reset Script - Handles corrupted databases
 */

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "akanyenyeri_db";

echo "<h1>Resetting Akanyenyeri Database</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;}</style>";

try {
    // Connect without specifying database
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Try to drop the database (this might fail if corrupted)
    try {
        $pdo->exec("DROP DATABASE $database");
        echo "<p class='success'>✓ Database dropped successfully</p>";
    } catch (Exception $e) {
        echo "<p class='error'>Could not drop database normally: " . $e->getMessage() . "</p>";
        echo "<p class='success'>Trying alternative method...</p>";

        // Alternative: Create a new database with a different name, then rename
        $temp_db = $database . "_temp";
        try {
            $pdo->exec("CREATE DATABASE $temp_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("DROP DATABASE $database");
            $pdo->exec("CREATE DATABASE $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("DROP DATABASE $temp_db");
            echo "<p class='success'>✓ Database recreated using alternative method</p>";
        } catch (Exception $e2) {
            echo "<p class='error'>Alternative method also failed: " . $e2->getMessage() . "</p>";
            echo "<p>Please manually drop the database in phpMyAdmin and run init_db.php again.</p>";
            exit;
        }
    }

    // Create fresh database
    $pdo->exec("CREATE DATABASE $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p class='success'>✓ Fresh database created</p>";

    // Now run the initialization
    echo "<p>Now initializing database...</p>";
    echo "<script>window.location.href='init_db.php';</script>";

} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
