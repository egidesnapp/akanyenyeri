<?php
// Get dynamic website settings
$site_name = 'Akanyenyeri Magazine';
$site_description = 'Breaking News, Analysis, and Stories';

try {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDB();
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('site_name', 'site_description')");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    if (!empty($settings['site_name'])) $site_name = $settings['site_name'];
    if (!empty($settings['site_description'])) $site_description = $settings['site_description'];
} catch (Exception $e) {
    // Use defaults if database not available
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($site_name); ?></title>
    
    <!-- Rectified Magazine CSS -->
    <link rel="stylesheet" href="../assets/css/rectified.css">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body <?php body_class(); ?>>
<div id="page" class="site">
    <a class="skip-link screen-reader-text" href="#content"><?php _e( 'Skip to content', 'rectified-magazine' ); ?></a>

    <!-- Header -->
    <header id="masthead" class="site-header">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="container-inner">
                <div class="top-left-col">
                    <div class="ct-clock">
                        <i class="fa fa-clock-o"></i> <?php echo date('l, F j, Y'); ?>
                    </div>
                    <ul class="top-menu">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="index.php?category=politics">Politics</a></li>
                        <li><a href="index.php?category=business">Business</a></li>
                        <li><a href="../admin/login.php">Admin</a></li>
                    </ul>
                </div>
                <div class="top-right-col">
                    <div class="rectified-magazine-social-top">
                        <div>
                            <span class="rectified-magazine-social-text">Follow Us:</span>
                        </div>
                        <ul class="rectified-magazine-menu-social">
                            <li><a href="#" target="_blank"><i class="fa fa-facebook"></i></a></li>
                            <li><a href="#" target="_blank"><i class="fa fa-twitter"></i></a></li>
                            <li><a href="#" target="_blank"><i class="fa fa-instagram"></i></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Site Branding and Menu -->
        <div class="rectified-magazine-menu-container">
            <div class="container-inner">
                <div class="rectified-magazine-header-left-logo">
                    <div class="rectified-magazine-logo-main-container">
                        <div class="rectified-magazine-logo-container">
                            <div class="site-branding">
                                <div class="site-branding-wrapper">
                                    <a href="index.php" class="custom-logo-link" rel="home">
                                        <img src="../uploads/akanyenyeri-logo.svg" alt="<?php echo htmlspecialchars($site_name); ?>" class="custom-logo" style="height: 60px;">
                                    </a>
                                    <h1 class="site-title">
                                        <a href="index.php" rel="home"><?php echo htmlspecialchars($site_name); ?></a>
                                    </h1>
                                    <p class="site-description"><?php echo htmlspecialchars($site_description); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="rectified-magazine-menu-container">
                        <nav id="site-navigation" class="main-navigation">
                            <button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
                                <span class="screen-reader-text">Primary Menu</span>
                                <span></span>
                                <span></span>
                                <span></span>
                            </button>
                            <div class="menu-main-menu-container">
                                <ul id="primary-menu" class="menu">
                                    <li class="menu-item"><a href="index.php">Home</a></li>
                                    <li class="menu-item"><a href="index.php?category=politics">Politics</a></li>
                                    <li class="menu-item"><a href="index.php?category=business">Business</a></li>
                                    <li class="menu-item"><a href="index.php?category=technology">Technology</a></li>
                                    <li class="menu-item"><a href="index.php?category=sports">Sports</a></li>
                                    <li class="menu-item"><a href="../admin/login.php">Admin</a></li>
                                </ul>
                            </div>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div id="content" class="site-content">
        <div class="container-inner ct-container-main">
