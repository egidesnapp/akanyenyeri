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
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Lato:wght@400;700&display=swap" rel="stylesheet">
    
    <!-- Custom Bootstrap Overrides -->
    <link rel="stylesheet" href="/simple_news/css/bootstrap-override.css">
</head>
<body>
    <a class="skip-link" href="#content">Skip to content</a>
    
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
                <img src="../uploads/akanyenyeri-logo.svg" alt="<?php echo htmlspecialchars($site_name); ?> Logo" style="height: 45px; width: auto;">
                <span><?php echo htmlspecialchars($site_name); ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?category=business">Business</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?category=politics">Politics</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?category=technology">Technology</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?category=sports">Sports</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?category=entertainment">Entertainment</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../admin/login.php"><i class="fas fa-user"></i> Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Header Section -->
    <header class="bg-gradient text-white py-5 border-bottom">
        <div class="container-fluid px-4">
            <div class="d-flex align-items-center gap-4 mb-3">
                <img src="../uploads/akanyenyeri-logo.svg" alt="<?php echo htmlspecialchars($site_name); ?> Logo" style="height: 80px; width: auto; filter: drop-shadow(0 2px 4px rgba(255,255,255,0.3));">
                <div>
                    <h1 class="display-4 fw-bold mb-2"><?php echo htmlspecialchars($site_name); ?></h1>
                    <p class="lead mb-0"><?php echo htmlspecialchars($site_description); ?></p>
                </div>
            </div>
        </div>
    </header>
    
    <main id="content">
