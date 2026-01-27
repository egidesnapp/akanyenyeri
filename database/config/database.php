<?php
/**
 * Database Configuration for Akanyenyeri Magazine
 * XAMPP Local Development Setup
 */

// Database configuration
define("DB_HOST", "127.0.0.1");
define("DB_NAME", "akanyenyeri_db");
define("DB_USER", "root");
define("DB_PASS", "");
define("DB_CHARSET", "utf8mb4");

// Site configuration
define("SITE_URL", "http://localhost/akanyenyeri/");
define("ADMIN_URL", SITE_URL . "admin/");
define("UPLOAD_PATH", "uploads/");
define("MAX_FILE_SIZE", 5242880); // 5MB

// Security
define("SESSION_TIMEOUT", 3600); // 1 hour
define("ADMIN_SESSION_NAME", "akanyenyeri_admin");

// Database connection class
class Database
{
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $charset = DB_CHARSET;
    public $conn;

    public function getConnection()
    {
        $this->conn = null;
        try {
            // Try socket connection first, fallback to TCP
            $socketPath = "C:/xampp/mysql/mysql.sock";
            if (file_exists($socketPath)) {
                $dsn = "mysql:unix_socket=" . $socketPath . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            } else {
                // Fallback to TCP connection - use the host property
                $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            }
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION,
            );
        } catch (PDOException $exception) {
            // Don't echo errors here - let the calling code handle them
            error_log("Database connection error: " . $exception->getMessage());
            return null;
        }
        return $this->conn;
    }
}

// Helper function for database connection
function getDB()
{
    $database = new Database();
    return $database->getConnection();
}

// Check if database exists and create if not
function initializeDatabase()
{
    try {
        // Try socket connection first, fallback to TCP
        $socketPath = "C:/xampp/mysql/mysql.sock";
        if (file_exists($socketPath)) {
            $dsn = "mysql:unix_socket=" . $socketPath . ";charset=" . DB_CHARSET;
        } else {
            $dsn = "mysql:host=127.0.0.1;charset=" . DB_CHARSET;
        }
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create database if not exists
        $pdo->exec(
            "CREATE DATABASE IF NOT EXISTS " .
                DB_NAME .
                " CHARACTER SET " .
                DB_CHARSET .
                " COLLATE utf8mb4_unicode_ci",
        );

        // Use the database
        $pdo->exec("USE " . DB_NAME);

        // Create tables
        createTables($pdo);

        return true;
    } catch (PDOException $e) {
        error_log("Database initialization error: " . $e->getMessage());
        return false;
    }
}

// Create necessary tables
function createTables($pdo)
{
    // Users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100),
            profile_picture VARCHAR(255),
            role ENUM('admin', 'editor', 'author') DEFAULT 'author',
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // Add profile_picture column if it doesn't exist (for existing databases)
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) AFTER password");
    } catch (Exception $e) {
        // Column already exists, skip
    }

    // Categories table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) UNIQUE NOT NULL,
            description TEXT,
            color VARCHAR(7) DEFAULT '#667eea',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Tags table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            slug VARCHAR(50) UNIQUE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Posts table
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

    // Post tags junction table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS post_tags (
            post_id INT NOT NULL,
            tag_id INT NOT NULL,
            PRIMARY KEY (post_id, tag_id),
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
        )
    ");

    // Media table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS media (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size INT NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            uploaded_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // Site Settings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS site_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value LONGTEXT,
            setting_type VARCHAR(50) DEFAULT 'text',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // User Remember Tokens table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_remember_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_token (token),
            INDEX idx_user_expires (user_id, expires_at)
        )
    ");

    // Password Reset Tokens table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(255) UNIQUE NOT NULL,
            email VARCHAR(100) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            used BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_token (token),
            INDEX idx_user_email (user_id, email),
            INDEX idx_expires (expires_at)
        )
    ");

    // User Security Questions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_security_questions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            question VARCHAR(255) NOT NULL,
            answer_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_question (user_id, question)
        )
    ");

    // Security Logs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS security_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_type VARCHAR(50) NOT NULL,
            user_id INT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            details TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_event_type (event_type),
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at)
        )
    ");

    // Insert default settings
    $pdo->exec("
        INSERT IGNORE INTO site_settings (setting_key, setting_value, setting_type) VALUES
        ('site_name', 'Akanyenyeri Magazine', 'text'),
        ('site_description', 'Your Trusted News Source', 'text'),
        ('admin_email', 'admin@akanyenyeri.com', 'text')
    ");
    $pdo->exec(
        "
        INSERT IGNORE INTO users (username, email, password, full_name, role)
        VALUES ('Akanyenyeri', 'admin@akanyenyeri.com', '" .
            password_hash("99%Complex", PASSWORD_DEFAULT) .
            "', 'Akanyenyeri Administrator', 'admin')
    ",
    );

    // Insert default categories
    $pdo->exec("
        INSERT IGNORE INTO categories (name, slug, description) VALUES
        ('Politics', 'politics', 'Political news and analysis'),
        ('Sports', 'sports', 'Sports news and updates'),
        ('Technology', 'technology', 'Tech news and innovations'),
        ('Business', 'business', 'Business and economic news'),
        ('Entertainment', 'entertainment', 'Entertainment and celebrity news')
    ");
}

// Initialize database on first run
if (!file_exists(__DIR__ . "/../.db_initialized")) {
    if (initializeDatabase()) {
        file_put_contents(
            __DIR__ . "/../.db_initialized",
            "Database initialized on " . date("Y-m-d H:i:s"),
        );
    }
}
?>
