<?php
/**
 * Database Initialization - Web Accessible
 * Run this once to create all tables and sample data
 */

// Hardcoded connection (same as config)
$host = "localhost";
$username = "root";
$password = "";
$database = "akanyenyeri_db";

echo "<h1>Initializing Akanyenyeri Database</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;}</style>";

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Drop database if exists and recreate
    try {
        $pdo->exec("DROP DATABASE IF EXISTS $database");
        echo "<p class='success'>✓ Old database dropped.</p>";
    } catch (Exception $e) {
        echo "<p class='success'>✓ Database exists, will recreate tables.</p>";
    }

    try {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "<p class='success'>✓ Database ready.</p>";
    } catch (Exception $e) {
        echo "<p class='success'>✓ Database already exists.</p>";
    }

    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create all tables
    $pdo->exec("CREATE TABLE users (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50) UNIQUE NOT NULL, email VARCHAR(100) UNIQUE NOT NULL, password VARCHAR(255) NOT NULL, full_name VARCHAR(100), profile_picture VARCHAR(255), role ENUM('admin', 'editor', 'author') DEFAULT 'author', status ENUM('active', 'inactive') DEFAULT 'active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE TABLE categories (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, slug VARCHAR(100) UNIQUE NOT NULL, description TEXT, color VARCHAR(7) DEFAULT '#667eea', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE TABLE posts (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, slug VARCHAR(255) UNIQUE NOT NULL, content LONGTEXT NOT NULL, excerpt TEXT, featured_image VARCHAR(255), author_id INT NOT NULL, category_id INT NOT NULL, status ENUM('draft', 'published', 'pending') DEFAULT 'draft', views INT DEFAULT 0, is_featured BOOLEAN DEFAULT FALSE, meta_title VARCHAR(255), meta_description TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE)");
    $pdo->exec("CREATE TABLE tags (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) NOT NULL, slug VARCHAR(50) UNIQUE NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE TABLE post_tags (post_id INT NOT NULL, tag_id INT NOT NULL, PRIMARY KEY (post_id, tag_id), FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE, FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE)");
    $pdo->exec("CREATE TABLE media (id INT AUTO_INCREMENT PRIMARY KEY, filename VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL, file_path VARCHAR(500) NOT NULL, file_size INT NOT NULL, mime_type VARCHAR(100) NOT NULL, uploaded_by INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE)");
    $pdo->exec("CREATE TABLE comments (id INT AUTO_INCREMENT PRIMARY KEY, post_id INT NOT NULL, author_name VARCHAR(100) NOT NULL, author_email VARCHAR(100) NOT NULL, content TEXT NOT NULL, status ENUM('approved', 'pending', 'spam') DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE)");
    $pdo->exec("CREATE TABLE site_settings (id INT AUTO_INCREMENT PRIMARY KEY, setting_key VARCHAR(100) UNIQUE NOT NULL, setting_value TEXT, setting_type ENUM('text', 'textarea', 'boolean', 'number') DEFAULT 'text', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE TABLE user_remember_tokens (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, token VARCHAR(255) NOT NULL, expires_at TIMESTAMP NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE)");
    $pdo->exec("CREATE TABLE security_logs (id INT AUTO_INCREMENT PRIMARY KEY, event_type VARCHAR(50) NOT NULL, user_id INT, ip_address VARCHAR(45) NOT NULL, user_agent TEXT, details TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

    echo "<p class='success'>✓ All tables created</p>";

    // Insert admin user
    $pdo->exec("INSERT INTO users (username, email, password, full_name, role) VALUES ('egide', 'egide@akanyenyeri.com', '" . password_hash("egide123", PASSWORD_DEFAULT) . "', 'Egide Administrator', 'admin')");

    // Insert categories
    $categories = [
        ["Politics", "politics", "Political news and analysis", "#e74c3c"],
        ["Technology", "technology", "Tech news and innovations", "#3498db"],
        ["Sports", "sports", "Sports news and updates", "#27ae60"],
        ["Business", "business", "Business and economic news", "#f39c12"],
        ["Entertainment", "entertainment", "Entertainment and celebrity news", "#9b59b6"],
        ["Health", "health", "Health and wellness news", "#1abc9c"],
        ["Travel", "travel", "Travel guides and destinations", "#e67e22"],
        ["Lifestyle", "lifestyle", "Lifestyle and culture news", "#34495e"],
    ];

    $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, color) VALUES (?, ?, ?, ?)");
    foreach ($categories as $cat) {
        $stmt->execute($cat);
    }

    // Insert sample posts
    $posts = [
        ["Welcome to Akanyenyeri Magazine", "welcome-to-akanyenyeri-magazine", "<p>Welcome to our new magazine platform! We bring you the latest news and insights.</p>", "Welcome to our new magazine platform!", "https://via.placeholder.com/800x500/3498db/ffffff?text=Welcome", 1, 1, "published", 100, 1],
        ["Technology Trends 2024", "technology-trends-2024", "<p>Explore the latest technology trends shaping our future.</p>", "Explore the latest technology trends", "https://via.placeholder.com/800x500/3498db/ffffff?text=Tech+Trends", 1, 2, "published", 75, 0],
        ["Sports Championship Updates", "sports-championship-updates", "<p>Get the latest updates from ongoing championships.</p>", "Latest sports championship news", "https://via.placeholder.com/800x500/27ae60/ffffff?text=Sports", 1, 3, "published", 50, 0],
        ["Business Market Growth", "business-market-growth", "<p>Recent economic policies drive significant market growth.</p>", "New policies fuel market growth", "https://via.placeholder.com/800x500/f39c12/ffffff?text=Business", 1, 4, "published", 40, 0],
        ["Entertainment News", "entertainment-news", "<p>Celebrity chef opens revolutionary restaurant concept.</p>", "Celebrity chef introduces innovative restaurant", "https://via.placeholder.com/800x500/9b59b6/ffffff?text=Entertainment", 1, 5, "published", 35, 0],
    ];

    $stmt = $pdo->prepare("INSERT INTO posts (title, slug, content, excerpt, featured_image, author_id, category_id, status, views, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($posts as $post) {
        $stmt->execute($post);
    }

    echo "<p class='success'>✓ Admin user and sample data inserted</p>";
    echo "<br><h2>✅ Database Ready!</h2>";
    echo "<p><strong>Admin Login:</strong> egide / egide123</p>";
    echo "<p><a href='index.php'>← Go to Homepage</a></p>";
    echo "<p><a href='admin/'>Go to Admin Dashboard</a></p>";

} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
