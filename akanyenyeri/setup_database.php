<?php
/**
 * Database Setup Script for Akanyenyeri Magazine
 * Run this file once to create database and populate with sample data
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

    // Create post_tags junction table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS post_tags (
            post_id INT NOT NULL,
            tag_id INT NOT NULL,
            PRIMARY KEY (post_id, tag_id),
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
        )
    ");
    echo "✓ Post tags table created.<br>";

    // Create media table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS media (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size INT NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            alt_text VARCHAR(255),
            uploaded_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "✓ Media table created.<br>";

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

    // Create site_settings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS site_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            setting_type ENUM('text', 'textarea', 'boolean', 'number') DEFAULT 'text',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "✓ Site settings table created.<br>";

    // Create security_logs table for authentication tracking
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS security_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_type VARCHAR(50) NOT NULL,
            user_id INT,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            details TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ");
    echo "✓ Security logs table created.<br>";

    // Create user_remember_tokens table for remember me functionality
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_remember_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_token (user_id, token),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "✓ User remember tokens table created.<br>";

    // Create user_sessions table for active session tracking
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            session_id VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_session (session_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "✓ User sessions table created.<br>";

    // Insert sample users
    $users = [
        [
            "egide",
            "egide@akanyenyeri.com",
            password_hash("egide123", PASSWORD_DEFAULT),
            "Egide Administrator",
            "admin",
        ],
        [
            "editor",
            "editor@akanyenyeri.com",
            password_hash("editor123", PASSWORD_DEFAULT),
            "Chief Editor",
            "editor",
        ],
        [
            "john_doe",
            "john@akanyenyeri.com",
            password_hash("author123", PASSWORD_DEFAULT),
            "John Doe",
            "author",
        ],
        [
            "jane_smith",
            "jane@akanyenyeri.com",
            password_hash("author123", PASSWORD_DEFAULT),
            "Jane Smith",
            "author",
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
        ["Business", "business", "Business and economic news", "#f39c12"],
        [
            "Entertainment",
            "entertainment",
            "Entertainment and celebrity news",
            "#9b59b6",
        ],
        ["Health", "health", "Health and wellness news", "#1abc9c"],
        ["Travel", "travel", "Travel guides and destinations", "#e67e22"],
        ["Lifestyle", "lifestyle", "Lifestyle and culture news", "#34495e"],
    ];

    $stmt = $pdo->prepare(
        "INSERT IGNORE INTO categories (name, slug, description, color) VALUES (?, ?, ?, ?)",
    );
    foreach ($categories as $category) {
        $stmt->execute($category);
    }
    echo "✓ Sample categories inserted.<br>";

    // Insert sample tags
    $tags = [
        ["Breaking News", "breaking-news"],
        ["Trending", "trending"],
        ["Analysis", "analysis"],
        ["Interview", "interview"],
        ["Opinion", "opinion"],
        ["Local News", "local-news"],
        ["International", "international"],
        ["Innovation", "innovation"],
        ["Startup", "startup"],
        ["Finance", "finance"],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO tags (name, slug) VALUES (?, ?)");
    foreach ($tags as $tag) {
        $stmt->execute($tag);
    }
    echo "✓ Sample tags inserted.<br>";

    // Insert sample posts
    $posts = [
        [
            "Revolutionary AI Technology Transforms Healthcare Industry",
            "revolutionary-ai-technology-transforms-healthcare-industry",
            "<p>Artificial Intelligence is revolutionizing the healthcare industry with groundbreaking innovations that are saving lives and improving patient outcomes. Recent developments in machine learning algorithms have enabled doctors to diagnose diseases with unprecedented accuracy.</p><p>From early cancer detection to personalized treatment plans, AI is becoming an indispensable tool in modern medicine. Major hospitals worldwide are implementing AI-driven systems to enhance their diagnostic capabilities.</p><p>The integration of AI in healthcare has reduced diagnostic errors by 40% and improved treatment efficiency by 60%. This technological advancement represents a significant leap forward in medical science.</p>",
            "AI technology is revolutionizing healthcare with improved diagnostics and treatment outcomes.",
            "https://via.placeholder.com/800x500/3498db/ffffff?text=AI+Healthcare",
            1,
            2,
            "published",
            1245,
            1,
        ],
        [
            "Climate Change Summit Reaches Historic Agreement",
            "climate-change-summit-reaches-historic-agreement",
            "<p>World leaders gathered at the Global Climate Summit have reached a landmark agreement to combat climate change through coordinated international efforts. The agreement outlines ambitious targets for carbon emission reductions over the next decade.</p><p>The summit, attended by representatives from over 190 countries, focused on practical solutions including renewable energy investments, reforestation programs, and sustainable development initiatives.</p><p>Environmental experts are calling this agreement a turning point in the global fight against climate change, with binding commitments from major industrial nations.</p>",
            "Historic climate agreement reached by world leaders with ambitious emission reduction targets.",
            "https://via.placeholder.com/800x500/27ae60/ffffff?text=Climate+Summit",
            2,
            1,
            "published",
            892,
            1,
        ],
        [
            "Local Football Team Wins Championship After Decade-Long Drought",
            "local-football-team-wins-championship-after-decade-long-drought",
            '<p>The Akanyenyeri Lions have finally broken their championship drought, winning their first title in over a decade with a thrilling 3-2 victory in the final match. The team\'s journey to victory has been nothing short of inspirational.</p><p>Led by coach Maria Santos, the Lions overcame numerous challenges throughout the season, including key player injuries and financial constraints. Their determination and teamwork ultimately paid off in spectacular fashion.</p><p>The championship victory has brought immense joy to the local community, with celebrations continuing throughout the city. This win marks a new chapter for the team and their devoted fans.</p>',
            "Local football team ends decade-long championship drought with spectacular victory.",
            "https://via.placeholder.com/800x500/27ae60/ffffff?text=Championship+Victory",
            3,
            3,
            "published",
            567,
            0,
        ],
        [
            "New Economic Policies Drive Market Growth",
            "new-economic-policies-drive-market-growth",
            '<p>Recent economic policy changes have resulted in significant market growth, with stock indices reaching record highs across major exchanges. The new policies focus on supporting small businesses and encouraging foreign investment.</p><p>Economists predict continued growth momentum as the policies begin to show their full impact. Key sectors including technology, healthcare, and renewable energy have shown particularly strong performance.</p><p>The government\'s strategic approach to economic recovery has gained praise from international financial institutions and business leaders worldwide.</p>',
            "New economic policies fuel market growth and investor confidence.",
            "https://via.placeholder.com/800x500/f39c12/ffffff?text=Market+Growth",
            1,
            4,
            "published",
            423,
            0,
        ],
        [
            "Celebrity Chef Opens Revolutionary Restaurant Concept",
            "celebrity-chef-opens-revolutionary-restaurant-concept",
            "<p>Renowned celebrity chef Alessandro Rodriguez has unveiled his latest culinary venture - a revolutionary restaurant concept that combines traditional cooking methods with cutting-edge technology and sustainable practices.</p><p>The restaurant features an innovative farm-to-table approach, with ingredients sourced from local organic farms and an on-site hydroponic garden. Diners can experience interactive cooking demonstrations and personalized menu recommendations.</p><p>This groundbreaking concept is already attracting food enthusiasts from around the world, with reservations booked solid for the next six months.</p>",
            "Celebrity chef introduces innovative restaurant concept combining tradition and technology.",
            "https://via.placeholder.com/800x500/9b59b6/ffffff?text=Restaurant+Concept",
            4,
            5,
            "published",
            334,
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

    // Insert sample comments
    $comments = [
        [
            1,
            "Sarah Johnson",
            "sarah@example.com",
            "Great article! AI in healthcare is indeed the future.",
            "approved",
        ],
        [
            1,
            "Mike Chen",
            "mike@example.com",
            "Very informative. Looking forward to more developments.",
            "approved",
        ],
        [
            2,
            "Emma Wilson",
            "emma@example.com",
            "Finally some progress on climate action!",
            "approved",
        ],
        [
            3,
            "Tom Rodriguez",
            "tom@example.com",
            "Go Lions! What a fantastic season!",
            "approved",
        ],
        [
            4,
            "Lisa Park",
            "lisa@example.com",
            "These policies seem promising for small businesses.",
            "pending",
        ],
    ];

    $stmt = $pdo->prepare(
        "INSERT INTO comments (post_id, author_name, author_email, content, status) VALUES (?, ?, ?, ?, ?)",
    );
    foreach ($comments as $comment) {
        $stmt->execute($comment);
    }
    echo "✓ Sample comments inserted.<br>";

    // Insert site settings
    $settings = [
        ["site_name", "Akanyenyeri Magazine", "text"],
        ["site_description", "Your Trusted News Source", "text"],
        ["posts_per_page", "10", "number"],
        ["enable_comments", "1", "boolean"],
        ["admin_email", "egide@akanyenyeri.com", "text"],
    ];

    $stmt = $pdo->prepare(
        "INSERT IGNORE INTO site_settings (setting_key, setting_value, setting_type) VALUES (?, ?, ?)",
    );
    foreach ($settings as $setting) {
        $stmt->execute($setting);
    }
    echo "✓ Site settings inserted.<br>";

    echo "<br><strong>🎉 Database setup completed successfully!</strong><br>";
    echo "<br><strong>Admin Login Details:</strong><br>";
    echo "Username: egide<br>";
    echo "Password: egide123<br>";
    echo "<br><a href='admin/'>Go to Admin Dashboard</a>";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
