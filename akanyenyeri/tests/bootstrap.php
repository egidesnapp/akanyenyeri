<?php
/**
 * PHPUnit Bootstrap File for Akanyenyeri Magazine Testing
 * Initializes the testing environment and provides test utilities
 */

// Set error reporting for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define testing constants
define('TESTING', true);
define('TEST_ROOT', __DIR__);
define('PROJECT_ROOT', dirname(__DIR__));

// Set timezone for consistent testing
date_default_timezone_set('UTC');

// Start output buffering to prevent header issues during tests
ob_start();

// Include the main project configuration
require_once PROJECT_ROOT . '/config/database.php';

/**
 * Test Database Helper Class
 */
class TestDatabaseHelper
{
    private static $pdo = null;
    private static $test_db_created = false;

    /**
     * Get test database connection
     */
    public static function getTestDB()
    {
        if (self::$pdo === null) {
            self::createTestDatabase();
        }
        return self::$pdo;
    }

    /**
     * Create test database and tables
     */
    private static function createTestDatabase()
    {
        try {
            // Connect to MySQL without specifying database
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $user = $_ENV['DB_USER'] ?? 'root';
            $pass = $_ENV['DB_PASS'] ?? '';
            $test_db_name = $_ENV['DB_NAME'] ?? 'akanyenyeri_test';

            $dsn = "mysql:host=$host;charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);

            // Create test database if it doesn't exist
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$test_db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$test_db_name`");

            // Create tables for testing
            self::createTestTables($pdo);

            self::$pdo = $pdo;
            self::$test_db_created = true;

        } catch (PDOException $e) {
            die("Test database setup failed: " . $e->getMessage());
        }
    }

    /**
     * Create test database tables
     */
    private static function createTestTables($pdo)
    {
        $tables = [
            'users' => "
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    email VARCHAR(100) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    full_name VARCHAR(100),
                    role ENUM('admin', 'editor', 'author') DEFAULT 'author',
                    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            'categories' => "
                CREATE TABLE IF NOT EXISTS categories (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) UNIQUE NOT NULL,
                    slug VARCHAR(100) UNIQUE NOT NULL,
                    description TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            'tags' => "
                CREATE TABLE IF NOT EXISTS tags (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(50) UNIQUE NOT NULL,
                    slug VARCHAR(50) UNIQUE NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            'posts' => "
                CREATE TABLE IF NOT EXISTS posts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) UNIQUE NOT NULL,
                    content LONGTEXT,
                    excerpt TEXT,
                    featured_image VARCHAR(255),
                    author_id INT NOT NULL,
                    category_id INT,
                    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
                    featured BOOLEAN DEFAULT FALSE,
                    views INT DEFAULT 0,
                    published_at TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            'comments' => "
                CREATE TABLE IF NOT EXISTS comments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    post_id INT NOT NULL,
                    author_name VARCHAR(100) NOT NULL,
                    author_email VARCHAR(100) NOT NULL,
                    content TEXT NOT NULL,
                    status ENUM('pending', 'approved', 'spam', 'trash') DEFAULT 'pending',
                    ip_address VARCHAR(45),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            'media' => "
                CREATE TABLE IF NOT EXISTS media (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    filename VARCHAR(255) NOT NULL,
                    original_name VARCHAR(255) NOT NULL,
                    mime_type VARCHAR(100) NOT NULL,
                    file_size INT NOT NULL,
                    alt_text VARCHAR(255),
                    caption TEXT,
                    uploaded_by INT NOT NULL,
                    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            'site_settings' => "
                CREATE TABLE IF NOT EXISTS site_settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    setting_key VARCHAR(100) UNIQUE NOT NULL,
                    setting_value TEXT,
                    setting_type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            'security_logs' => "
                CREATE TABLE IF NOT EXISTS security_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    event_type VARCHAR(50) NOT NULL,
                    user_id INT,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    details TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ];

        foreach ($tables as $table_name => $sql) {
            $pdo->exec($sql);
        }

        // Insert test data
        self::insertTestData($pdo);
    }

    /**
     * Insert basic test data
     */
    private static function insertTestData($pdo)
    {
        // Insert test admin user
        $pdo->exec("
            INSERT IGNORE INTO users (username, email, password, full_name, role, status)
            VALUES ('testadmin', 'admin@test.com', '" . password_hash('testpass', PASSWORD_DEFAULT) . "', 'Test Admin', 'admin', 'active')
        ");

        // Insert test category
        $pdo->exec("
            INSERT IGNORE INTO categories (name, slug, description)
            VALUES ('Test Category', 'test-category', 'A category for testing purposes')
        ");

        // Insert basic site settings
        $settings = [
            ['site_title', 'Akanyenyeri Test Site', 'text'],
            ['site_description', 'Test site for Akanyenyeri magazine', 'text'],
            ['posts_per_page', '10', 'number'],
            ['allow_comments', '1', 'boolean']
        ];

        $stmt = $pdo->prepare("INSERT IGNORE INTO site_settings (setting_key, setting_value, setting_type) VALUES (?, ?, ?)");
        foreach ($settings as $setting) {
            $stmt->execute($setting);
        }
    }

    /**
     * Clean test database
     */
    public static function cleanDatabase()
    {
        if (self::$pdo) {
            // Disable foreign key checks temporarily
            self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

            // Truncate all tables
            $tables = ['comments', 'posts', 'media', 'tags', 'categories', 'users', 'security_logs'];
            foreach ($tables as $table) {
                self::$pdo->exec("TRUNCATE TABLE $table");
            }

            // Re-enable foreign key checks
            self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

            // Re-insert basic test data
            self::insertTestData(self::$pdo);
        }
    }

    /**
     * Drop test database
     */
    public static function dropTestDatabase()
    {
        if (self::$test_db_created) {
            $test_db_name = $_ENV['DB_NAME'] ?? 'akanyenyeri_test';

            // Connect to MySQL without specifying database
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $user = $_ENV['DB_USER'] ?? 'root';
            $pass = $_ENV['DB_PASS'] ?? '';

            try {
                $dsn = "mysql:host=$host;charset=utf8mb4";
                $pdo = new PDO($dsn, $user, $pass);
                $pdo->exec("DROP DATABASE IF EXISTS `$test_db_name`");
            } catch (PDOException $e) {
                // Ignore errors when dropping test database
            }
        }
    }
}

/**
 * Test helper functions
 */

/**
 * Create a test user
 */
function createTestUser($data = [])
{
    $defaults = [
        'username' => 'testuser_' . uniqid(),
        'email' => 'test_' . uniqid() . '@example.com',
        'password' => password_hash('testpass', PASSWORD_DEFAULT),
        'full_name' => 'Test User',
        'role' => 'author',
        'status' => 'active'
    ];

    $userData = array_merge($defaults, $data);

    $pdo = TestDatabaseHelper::getTestDB();
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password, full_name, role, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $userData['username'],
        $userData['email'],
        $userData['password'],
        $userData['full_name'],
        $userData['role'],
        $userData['status']
    ]);

    $userData['id'] = $pdo->lastInsertId();
    return $userData;
}

/**
 * Create a test post
 */
function createTestPost($data = [])
{
    $defaults = [
        'title' => 'Test Post ' . uniqid(),
        'slug' => 'test-post-' . uniqid(),
        'content' => 'This is test post content.',
        'excerpt' => 'Test excerpt',
        'author_id' => 1, // Default to first user
        'status' => 'published',
        'featured' => false
    ];

    $postData = array_merge($defaults, $data);

    $pdo = TestDatabaseHelper::getTestDB();
    $stmt = $pdo->prepare("
        INSERT INTO posts (title, slug, content, excerpt, author_id, status, featured, published_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $postData['title'],
        $postData['slug'],
        $postData['content'],
        $postData['excerpt'],
        $postData['author_id'],
        $postData['status'],
        $postData['featured']
    ]);

    $postData['id'] = $pdo->lastInsertId();
    return $postData;
}

/**
 * Mock session for testing
 */
function mockAdminSession($userData = [])
{
    $defaults = [
        'admin_logged_in' => true,
        'admin_user_id' => 1,
        'admin_username' => 'testadmin',
        'admin_role' => 'admin',
        'admin_full_name' => 'Test Admin',
        'admin_email' => 'admin@test.com',
        'admin_login_time' => time(),
        'admin_last_activity' => time(),
        'csrf_token' => bin2hex(random_bytes(32))
    ];

    $_SESSION = array_merge($defaults, $userData);

    // Mock server variables for security checks
    $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? 'PHPUnit Test Agent';

    $_SESSION['admin_ip'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['admin_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
}

/**
 * Clean up after tests
 */
register_shutdown_function(function() {
    if (defined('TESTING') && TESTING) {
        // Clean up test database on shutdown
        TestDatabaseHelper::cleanDatabase();
    }
});

// Initialize test database
TestDatabaseHelper::getTestDB();

echo "Test environment initialized successfully.\n";
