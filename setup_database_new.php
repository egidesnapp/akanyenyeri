<?php
/**
 * Database Setup Script for Akanyenyeri Magazine
 * Run this file once to create database and populate with sample data
 */

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "akanyenyeri_db_new";

try {
    // Connect to MySQL server (without database)
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if not exists
    $pdo->exec(
        "CREATE DATABASE IF NOT EXISTS $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
    );
    echo "✓ Database '$database' created successfully.<br>";

    // Connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            role ENUM('admin', 'editor', 'author') DEFAULT 'author',
            profile_image VARCHAR(255) DEFAULT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "✓ Users table created.<br>";

    // Create categories table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) UNIQUE NOT NULL,
            description TEXT,
            color VARCHAR(7) DEFAULT '#ff6b35',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ Categories table created.<br>";

    // Create tags table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            slug VARCHAR(50) UNIQUE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ Tags table created.<br>";

    // Create comments table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            author_name VARCHAR(100) NOT NULL,
            author_email VARCHAR(100) NOT NULL,
            content TEXT NOT NULL,
            status ENUM('approved', 'pending', 'spam') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
        )
    ");
    echo "✓ Comments table created.<br>";

    // Create posts table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) UNIQUE NOT NULL,
            content LONGTEXT NOT NULL,
            excerpt TEXT,
            featured_image VARCHAR(255),
            author_id INT NOT NULL,
            category_id INT NOT NULL,
            status ENUM('draft', 'published', 'pending') DEFAULT 'draft',
            views INT DEFAULT 0,
            is_featured BOOLEAN DEFAULT FALSE,
            meta_title VARCHAR(255),
            meta_description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        )
    ");
    echo "✓ Posts table created.<br>";

    // Insert sample users
    $users = [
        [
            "egide",
            "egide@akanyenyeri.com",
            password_hash("egide123", PASSWORD_DEFAULT),
            "Egide Administrator",
            "admin",
        ],
    ];

    $stmt = $pdo->prepare(
        "INSERT IGNORE INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)",
    );
    foreach ($users as $user) {
        $stmt->execute($user);
    }
    echo "✓ Sample users inserted.<br>";

    // Insert sample categories
    $categories = [
        ["Politics", "politics", "Political news and analysis", "#e74c3c"],
        ["Technology", "technology", "Tech news and innovations", "#3498db"],
        ["Sports", "sports", "Sports news and updates", "#27ae60"],
    ];

    $stmt = $pdo->prepare(
        "INSERT IGNORE INTO categories (name, slug, description, color) VALUES (?, ?, ?, ?)",
    );
    foreach ($categories as $category) {
        $stmt->execute($category);
    }
    echo "✓ Sample categories inserted.<br>";

    // Insert sample posts
    $posts = [
        [
            "Welcome to Akanyenyeri Magazine",
            "welcome-to-akanyenyeri-magazine",
            "<p>Welcome to Akanyenyeri Magazine, your trusted source for breaking news, in-depth analysis, and stories that matter. We're committed to delivering high-quality journalism that informs and engages our readers.</p><p>Our team of experienced journalists and editors work tirelessly to bring you the latest developments in politics, technology, sports, business, and entertainment.</p>",
            "Welcome to Akanyenyeri Magazine - your trusted news source.",
            "https://via.placeholder.com/800x500/3498db/ffffff?text=Welcome",
            1,
            1,
            "published",
            100,
            1,
        ],
        [
            "Technology Trends in 2026",
            "technology-trends-in-2026",
            "<p>As we move into 2026, several technology trends are shaping our world. From artificial intelligence advancements to quantum computing breakthroughs, the tech landscape is evolving rapidly.</p><p>Stay tuned to Akanyenyeri Magazine for comprehensive coverage of these exciting developments and their impact on our daily lives.</p>",
            "Exploring the key technology trends that will define 2026.",
            "https://via.placeholder.com/800x500/27ae60/ffffff?text=Tech+Trends",
            1,
            2,
            "published",
            75,
            0,
        ],
    ];

    $stmt = $pdo->prepare("
        INSERT IGNORE INTO posts (title, slug, content, excerpt, featured_image, author_id, category_id, status, views, is_featured)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($posts as $post) {
        $stmt->execute($post);
    }
    echo "✓ Sample posts inserted.<br>";

    echo "<br><strong>🎉 Database setup completed successfully!</strong><br>";
    echo "<br><strong>Admin Login Details:</strong><br>";
    echo "Username: egide<br>";
    echo "Password: egide123<br>";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
