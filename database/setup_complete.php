<?php
/**
 * Complete Database Setup and Fix Script
 * Creates all tables, adds missing columns, and populates with sample data
 */

require_once "config/database.php";

try {
    $pdo = getDB();
    echo "<h1>🔧 Complete Database Setup & Fix</h1>";
    echo "<pre>";

    // Drop existing tables if they exist (for clean setup)
    echo "🗑️  Dropping existing tables...\n";

    // Disable foreign key checks to allow dropping tables with dependencies
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    $tables = ['security_logs', 'user_remember_tokens', 'post_tags', 'media', 'posts', 'tags', 'categories', 'site_settings', 'advertisements', 'users'];
    foreach ($tables as $table) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
            echo "✓ Dropped $table\n";
        } catch (Exception $e) {
            echo "⚠️  Could not drop $table: " . $e->getMessage() . "\n";
        }
    }

    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "\n📋 Creating tables...\n";

    // Create users table
    $pdo->exec("
        CREATE TABLE users (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created users table\n";

    // Create categories table
    $pdo->exec("
        CREATE TABLE categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) UNIQUE NOT NULL,
            description TEXT,
            color VARCHAR(7) DEFAULT '#667eea',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created categories table\n";

    // Create tags table
    $pdo->exec("
        CREATE TABLE tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            slug VARCHAR(50) UNIQUE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created tags table\n";

    // Create posts table
    $pdo->exec("
        CREATE TABLE posts (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created posts table\n";

    // Create post_tags junction table
    $pdo->exec("
        CREATE TABLE post_tags (
            post_id INT NOT NULL,
            tag_id INT NOT NULL,
            PRIMARY KEY (post_id, tag_id),
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created post_tags table\n";

    // Create media table with all columns
    $pdo->exec("
        CREATE TABLE media (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size INT NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            alt_text VARCHAR(255) DEFAULT NULL,
            caption TEXT DEFAULT NULL,
            uploaded_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created media table\n";

    // Create site_settings table
    $pdo->exec("
        CREATE TABLE site_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value LONGTEXT,
            setting_type VARCHAR(50) DEFAULT 'text',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created site_settings table\n";

    // Create user_remember_tokens table
    $pdo->exec("
        CREATE TABLE user_remember_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_token (token),
            INDEX idx_user_expires (user_id, expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created user_remember_tokens table\n";

    // Create security_logs table
    $pdo->exec("
        CREATE TABLE security_logs (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created security_logs table\n";

    // Create advertisements table
    $pdo->exec("
        CREATE TABLE advertisements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            image_path VARCHAR(500) NOT NULL,
            link_url VARCHAR(500),
            category VARCHAR(100),
            type ENUM('background', 'content') DEFAULT 'background',
            display_order INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            start_date TIMESTAMP NULL,
            end_date TIMESTAMP NULL,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_active (is_active),
            INDEX idx_category (category),
            INDEX idx_display_order (display_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created advertisements table\n";

    echo "\n👤 Inserting sample data...\n";

    // Insert admin user
    $pdo->exec("
        INSERT INTO users (username, email, password, full_name, role) VALUES
        ('Akanyenyeri', 'admin@akanyenyeri.com', '" . password_hash("99%Complex", PASSWORD_DEFAULT) . "', 'Akanyenyeri Administrator', 'admin')
    ");
    echo "✓ Created admin user (Akanyenyeri/99%Complex)\n";

    // Insert categories
    $pdo->exec("
        INSERT INTO categories (name, slug, description, color) VALUES
        ('Politics', 'politics', 'Political news and analysis', '#e53e3e'),
        ('Sports', 'sports', 'Sports news and updates', '#d69e2e'),
        ('Technology', 'technology', 'Tech news and innovations', '#3182ce'),
        ('Business', 'business', 'Business and economic news', '#38a169'),
        ('Entertainment', 'entertainment', 'Entertainment and celebrity news', '#805ad5'),
        ('Health', 'health', 'Health and medical news', '#dd6b20')
    ");
    echo "✓ Created categories\n";

    // Insert sample posts
    $samplePosts = [
        [
            'title' => 'Breaking: Major Tech Merger Announced',
            'slug' => 'breaking-major-tech-merger-announced',
            'content' => '<p>In a stunning development that will reshape the technology landscape, two of Silicon Valley\'s biggest players announced a historic merger today. The $250 billion deal promises to create the world\'s largest technology company by market capitalization.</p><p>The merger, which still requires regulatory approval, is expected to face intense scrutiny from antitrust authorities. Industry analysts predict this could lead to unprecedented innovation in artificial intelligence and cloud computing.</p><p>Stock prices for both companies surged following the announcement, with investors betting on the combined entity\'s ability to dominate emerging technologies like quantum computing and autonomous vehicles.</p>',
            'excerpt' => 'Two Silicon Valley giants announce a historic $250 billion merger that could reshape the technology industry.',
            'category_id' => 3,
            'status' => 'published',
            'is_featured' => 1,
            'views' => 1250
        ],
        [
            'title' => 'Global Economy Shows Signs of Recovery',
            'slug' => 'global-economy-shows-signs-of-recovery',
            'content' => '<p>Economic indicators from around the world point to a stronger-than-expected recovery from the recent global slowdown. Manufacturing data, employment figures, and consumer spending all show positive trends that economists hadn\'t anticipated.</p><p>The latest GDP figures released today indicate a 3.2% growth rate for the quarter, surpassing all analyst expectations. Central banks worldwide are now debating when to begin normalizing monetary policy.</p><p>However, experts warn that inflation remains a concern, with energy prices continuing to put pressure on household budgets. The housing market shows mixed signals, with some regions experiencing price corrections while others continue to boom.</p>',
            'excerpt' => 'Economic data shows stronger recovery than expected, with GDP growth surprising analysts worldwide.',
            'category_id' => 4,
            'status' => 'published',
            'is_featured' => 0,
            'views' => 890
        ],
        [
            'title' => 'New Climate Agreement Signed by 50 Nations',
            'slug' => 'new-climate-agreement-signed-by-50-nations',
            'content' => '<p>Fifty nations have signed a groundbreaking climate agreement that commits signatories to ambitious carbon reduction targets. The accord, signed in the capital today, represents the most comprehensive international effort to combat climate change in decades.</p><p>The agreement includes binding commitments to reduce greenhouse gas emissions by 50% by 2030, with developed nations pledging financial support to help developing countries transition to clean energy sources.</p><p>Environmental groups praised the agreement as a "turning point" in global climate action, while some critics argue the targets aren\'t ambitious enough. Implementation will be key, with monitoring mechanisms built into the treaty to ensure compliance.</p>',
            'excerpt' => 'Fifty nations sign historic climate accord committing to 50% emissions reduction by 2030.',
            'category_id' => 1,
            'status' => 'published',
            'is_featured' => 0,
            'views' => 675
        ],
        [
            'title' => 'Championship Final Breaks Viewership Records',
            'slug' => 'championship-final-breaks-viewership-records',
            'content' => '<p>The championship final shattered all previous viewership records, with over 150 million people tuning in worldwide to watch the dramatic conclusion. The match, which went into extra time and penalties, kept fans on the edge of their seats for over two hours.</p><p>The winning goal, scored in the 89th minute, became an instant classic and is already being hailed as one of the greatest moments in sports history. Social media exploded with reactions, with the game\'s hashtag trending worldwide for hours after the final whistle.</p><p>Broadcasters reported record advertising revenue, while the winning team\'s jersey became the best-selling sports merchandise item of all time within 24 hours of the victory.</p>',
            'excerpt' => 'Championship final sets new viewership record with 150 million global viewers.',
            'category_id' => 2,
            'status' => 'published',
            'is_featured' => 1,
            'views' => 2100
        ],
        [
            'title' => 'Medical Breakthrough in Cancer Treatment',
            'slug' => 'medical-breakthrough-in-cancer-treatment',
            'content' => '<p>Scientists have announced a major breakthrough in cancer treatment that could revolutionize how the disease is fought. The new therapy, which combines immunotherapy with targeted genetic treatments, has shown unprecedented success rates in clinical trials.</p><p>The treatment works by reprogramming the patient\'s own immune system to recognize and destroy cancer cells while leaving healthy cells unharmed. Early results from Phase 3 trials show an 85% success rate, far higher than traditional treatments.</p><p>Medical experts are calling this the most significant advancement in oncology since the development of chemotherapy. The treatment is expected to be available to patients within the next 18 months, pending regulatory approval.</p>',
            'excerpt' => 'Scientists announce breakthrough cancer therapy with 85% success rate in clinical trials.',
            'category_id' => 6,
            'status' => 'published',
            'is_featured' => 0,
            'views' => 1450
        ]
    ];

    foreach ($samplePosts as $post) {
        $stmt = $pdo->prepare("
            INSERT INTO posts (title, slug, content, excerpt, category_id, author_id, status, is_featured, views, created_at)
            VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?, NOW() - INTERVAL ? DAY)
        ");
        $daysAgo = rand(0, 30);
        $stmt->execute([
            $post['title'],
            $post['slug'],
            $post['content'],
            $post['excerpt'],
            $post['category_id'],
            $post['status'],
            $post['is_featured'],
            $post['views'],
            $daysAgo
        ]);
    }
    echo "✓ Created sample posts\n";

    // Insert site settings
    $pdo->exec("
        INSERT INTO site_settings (setting_key, setting_value, setting_type) VALUES
        ('site_name', 'Akanyenyeri Magazine', 'text'),
        ('site_description', 'Your Trusted News Source', 'text'),
        ('admin_email', 'admin@akanyenyeri.com', 'text')
    ");
    echo "✓ Created site settings\n";

    // Create upload directories
    $dirs = ['../../uploads', '../../uploads/images', '../../uploads/videos', '../../uploads/audio', '../../uploads/documents', '../../uploads/others'];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "✓ Created directory: $dir\n";
        }
    }

    // Create .htaccess for uploads
    $htaccess = "../../uploads/.htaccess";
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "# Prevent execution of PHP files in uploads
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
");
        echo "✓ Created .htaccess security file\n";
    }

    echo "\n🎉 Database setup completed successfully!\n";
    echo "\n📊 Summary:\n";
    echo "- All tables created\n";
    echo "- Sample admin user: Akanyenyeri/99%Complex\n";
    echo "- 6 categories created\n";
    echo "- 5 sample posts created\n";
    echo "- Upload directories created\n";
    echo "- Security files configured\n";

    echo "\n🔗 Next steps:\n";
    echo "1. Visit: http://localhost/akanyenyeri/admin/login.php\n";
    echo "2. Login with: Akanyenyeri / 99%Complex\n";
    echo "3. Check the website: http://localhost/akanyenyeri/public/index.php\n";
    echo "4. Upload media: http://localhost/akanyenyeri/admin/media.php\n";

    echo "</pre><h2>✅ Setup Complete!</h2>";

} catch (Exception $e) {
    echo "<h2>❌ Error:</h2><pre>" . $e->getMessage() . "</pre>";
}
?>
