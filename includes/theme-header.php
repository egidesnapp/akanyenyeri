<?php
// Compute base path so asset URLs work when served from a subdirectory
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base === '/' || $base === '\\' || $base === '.') {
    $base = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akanyenyeri Magazine</title>
    
    <!-- Fonts -->
    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Mulish:wght@200..1000|Crimson+Text:ital,wght@0,400;0,600;0,700;1,400">
    
    <!-- CSS Assets -->
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/theme/rectified/assets/framework/Font-Awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/theme/rectified/assets/framework/slick/slick.css">
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/theme/rectified/assets/framework/slick/slick-theme.css">
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/theme/rectified/css/style.css">
    
    <!-- Redesign & Animation Assets -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base; ?>/css/redesign.css">
    
    <!-- Custom CSS Overrides -->
    <style>
        /* Fix path issues if any */
        @font-face {
            font-family: 'FontAwesome';
            src: url('<?php echo $base; ?>/assets/theme/rectified/assets/framework/Font-Awesome/fonts/fontawesome-webfont.eot?v=4.7.0');
            src: url('<?php echo $base; ?>/assets/theme/rectified/assets/framework/Font-Awesome/fonts/fontawesome-webfont.eot?#iefix&v=4.7.0') format('embedded-opentype'), 
                 url('<?php echo $base; ?>/assets/theme/rectified/assets/framework/Font-Awesome/fonts/fontawesome-webfont.woff2?v=4.7.0') format('woff2'), 
                 url('<?php echo $base; ?>/assets/theme/rectified/assets/framework/Font-Awesome/fonts/fontawesome-webfont.woff?v=4.7.0') format('woff'), 
                 url('<?php echo $base; ?>/assets/theme/rectified/assets/framework/Font-Awesome/fonts/fontawesome-webfont.ttf?v=4.7.0') format('truetype'), 
                 url('<?php echo $base; ?>/assets/theme/rectified/assets/framework/Font-Awesome/fonts/fontawesome-webfont.svg?v=4.7.0#fontawesomeregular') format('svg');
            font-weight: normal;
            font-style: normal;
        }
        
        /* Ensure layout works without WP classes */
        .container-inner {
            max-width: 1170px;
            margin: 0 auto;
            padding: 0 15px;
        }
    </style>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <?php
    // Allow pages to inject extra CSS into the head by setting $extra_css
    if (!empty($extra_css)) {
        echo $extra_css;
    }
    ?>
</head>
<body class="home blog">
    <div id="page" class="site">
        <a class="skip-link screen-reader-text" href="#content">Skip to content</a>
        
        <header id="masthead" class="site-header">
            <div class="overlay"></div>
            
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="container-inner clearfix">
                    <div class="top-left-col clearfix">
                        <!-- Date -->
                        <div class="ct-clock float-left">
                            <div id="ct-date">
                                <?php echo date('l, F d, Y'); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Social Menu -->
                    <div class="rectified-magazine-social-top">
                        <ul class="rectified-magazine-menu-social">
                            <li><a href="#"><i class="fa fa-facebook"></i></a></li>
                            <li><a href="#"><i class="fa fa-twitter"></i></a></li>
                            <li><a href="#"><i class="fa fa-instagram"></i></a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Main Header -->
            <div class="logo-wrapper-block">
                <div class="container-inner clearfix logo-wrapper-container">
                    <div class="logo-wrapper full-wrapper text-center">
                        <div class="site-branding">
                            <div class="rectified-magazine-logo-container">
                                <h1 class="site-title"><a href="index.php" rel="home">Akanyenyeri Magazine</a></h1>
                                <p class="site-description">Breaking News, Analysis, and Stories</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="rectified-magazine-menu-container">
                <div class="container-inner clearfix">
                    <nav id="site-navigation" class="main-navigation">
                        <button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
                            <span></span>
                        </button>
                        <ul id="primary-menu" class="nav navbar-nav nav-menu">
                            <li class="current-menu-item"><a href="index.php"><i class="fa fa-home"></i></a></li>
                            <?php if(isset($categories) && is_array($categories)): ?>
                                <?php foreach(array_slice($categories, 0, 6) as $cat): ?>
                                    <li><a href="category.php?slug=<?php echo htmlspecialchars($cat['slug']); ?>"><?php echo htmlspecialchars($cat['name']); ?></a></li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li><a href="index.php">Home</a></li>
                            <?php endif; ?>
                            <li><a href="admin/login.php">Login</a></li>
                        </ul>
                    </nav>
                    
                    <div class="ct-menu-search">
                        <a class="search-icon-box" href="#"> <i class="fa fa-search"></i> </a>
                    </div>
                    <div class="top-bar-search">
                        <form role="search" method="get" class="search-form" action="search.php">
                            <label>
                                <span class="screen-reader-text">Search for:</span>
                                <input type="search" class="search-field" placeholder="Search &hellip;" value="" name="s" />
                            </label>
                            <input type="submit" class="search-submit" value="Search" />
                        </form>
                        <button type="button" class="close"></button>
                    </div>
                </div>
            </div>
        </header>
