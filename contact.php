<?php
/**
 * Contact Page Template for Akanyenyeri Magazine
 */

require_once 'config/database.php';

// Get database connection
$pdo = getDB();

// Fetch all categories for menu
function getCategories($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}
$categories = getCategories($pdo);

// Handle form submission (Mock)
$messageSent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // In a real app, validation and email sending would go here
    $messageSent = true;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Akanyenyeri Magazine</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Text:wght@400;600;700&family=Inter:wght@400;500;600;700&family=Mulish:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Theme CSS -->
    <link rel="stylesheet" href="header/header.css">
    <link rel="stylesheet" href="css/visibility-improvements.css">
    <link rel="stylesheet" href="css/frontend-enhancements.css">
    
    <style>
        /* Contact Page Specific Styles */
        .contact-info-box {
            background: #f9f9f9;
            padding: 30px;
            margin-bottom: 30px;
            border-radius: 5px;
            color: #333;
        }
        [data-theme="dark"] .contact-info-box {
            background: #1e1e1e;
            color: #e0e0e0;
        }
        
        .contact-info-box h3 {
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .contact-info-box p {
            margin-bottom: 10px;
        }
        
        .contact-info-box i {
            width: 25px;
            color: var(--color-primary);
        }
        
        .contact-form {
            background: #fff;
            padding: 30px;
            border: 1px solid #eee;
            border-radius: 5px;
        }
        [data-theme="dark"] .contact-form {
            background: #1e1e1e;
            border-color: #333;
        }
        
        .contact-form .form-group {
            margin-bottom: 20px;
        }
        
        .contact-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-main);
        }
        
        .contact-form input[type="text"],
        .contact-form input[type="email"],
        .contact-form textarea,
        .contact-form select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
            background: var(--bg-body);
            color: var(--text-main);
        }
        
        .contact-form input[type="text"]:focus,
        .contact-form input[type="email"]:focus,
        .contact-form textarea:focus,
        .contact-form select:focus {
            border-color: var(--color-primary);
            outline: none;
        }
        
        .contact-form textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .contact-form .submit-btn {
            background: var(--color-primary);
            color: #fff;
            padding: 15px 40px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.3s;
        }
        
        .contact-form .submit-btn:hover {
            opacity: 0.9;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body class="page-template-contact right-sidebar rectified-magazine-fontawesome-version-6">
    <div id="page" class="site">
        <header id="masthead" class="site-header">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="container-inner">
                    <div class="top-left-col">
                        <span class="ct-clock"><i class="fa fa-clock"></i> <span id="current-date"><?php echo date('F j, Y'); ?></span></span>
                        <nav class="top-menu">
                            <ul>
                                <li><a href="#">About Us</a></li>
                                <li><a href="contact.php">Contact</a></li>
                            </ul>
                        </nav>
                    </div>
                    <div class="top-right-col">
                        <div class="theme-switch-wrapper">
                            <label class="theme-switch" for="checkbox">
                                <input type="checkbox" id="checkbox" />
                                <div class="slider round">
                                    <i class="fa fa-sun"></i>
                                    <i class="fa fa-moon"></i>
                                    <div class="slider-toggle"></div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Site Branding -->
            <div class="site-branding">
                <div class="container-inner">
                    <div class="logo-wrapper">
                        <h1 class="site-title"><a href="index.php">Akanyenyeri</a></h1>
                        <p class="site-description">Your Trusted News Source</p>
                    </div>
                </div>
            </div>

            <!-- Primary Navigation -->
            <nav id="site-navigation" class="main-navigation rectified-magazine-header-block">
                <div class="rectified-magazine-menu-container">
                    <div class="container-inner">
                        <div class="main-navigation">
                            <ul id="primary-menu" class="navbar-nav">
                                <li><a href="index.php">Home</a></li>
                                <?php foreach ($categories as $cat): ?>
                                <li><a href="category.php?slug=<?php echo htmlspecialchars($cat['slug']); ?>"><?php echo htmlspecialchars($cat['name']); ?></a></li>
                                <?php endforeach; ?>
                                <li class="current-menu-item"><a href="contact.php">Contact</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
        </header>

        <!-- Main Content -->
        <div class="ct-container-main">
            <div class="container-inner clearfix">
                <main id="primary" class="site-main">
                    <header class="entry-header">
                        <h1 class="entry-title">Contact Us</h1>
                    </header>

                    <div class="entry-content">
                        <?php if ($messageSent): ?>
                        <div class="success-message">
                            Thank you for contacting us! We will get back to you shortly.
                        </div>
                        <?php endif; ?>

                        <div class="contact-info-box">
                            <h3>Get in Touch</h3>
                            <p><i class="fa fa-map-marker-alt"></i> 123 News Street, Kigali, Rwanda</p>
                            <p><i class="fa fa-phone"></i> +250 123 456 789</p>
                            <p><i class="fa fa-envelope"></i> contact@akanyenyeri.com</p>
                        </div>

                        <form class="contact-form" method="POST" action="">
                            <div class="form-group">
                                <label for="name">Name</label>
                                <input type="text" id="name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="subject">Subject</label>
                                <input type="text" id="subject" name="subject" required>
                            </div>
                            <div class="form-group">
                                <label for="message">Message</label>
                                <textarea id="message" name="message" required></textarea>
                            </div>
                            <button type="submit" class="submit-btn">Send Message</button>
                        </form>
                    </div>
                </main>
            </div>
        </div>
        
        <footer class="site-footer">
            <div class="container-inner">
                <div class="footer-copyright">
                    <p>&copy; <?php echo date('Y'); ?> Akanyenyeri Magazine. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>
    
    <script src="js/theme.js"></script>
</body>
</html>
