<?php
// New Professional Website Design - Akanyenyeri Magazine
$site_title = "Akanyenyeri Magazine - Modern News Platform";
$site_description = "Breaking news, in-depth analysis, and compelling stories from around the world.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_title; ?></title>
    <meta name="description" content="<?php echo $site_description; ?>">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        :root {
            /* Light Theme (Default) */
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --accent-color: #f59e0b;
            --dark-color: #0f172a;
            --light-color: #f8fafc;
            --text-color: #334155;
            --border-color: #e2e8f0;
            --bg-color: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --card-bg: white;
            --hero-bg: linear-gradient(135deg, rgba(37, 99, 235, 0.9) 0%, rgba(30, 64, 175, 0.9) 100%),
                      url('https://images.unsplash.com/photo-1504711434969-e33886168f5c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1470&q=80');
            --featured-bg: white;
            --newsletter-bg: var(--light-color);
        }

        [data-theme="dark"] {
            /* Dark Theme */
            --primary-color: #3b82f6;
            --secondary-color: #1d4ed8;
            --accent-color: #f59e0b;
            --dark-color: #1e293b;
            --light-color: #0f172a;
            --text-color: #e2e8f0;
            --border-color: #334155;
            --bg-color: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            --card-bg: #1e293b;
            --hero-bg: linear-gradient(135deg, rgba(59, 130, 246, 0.9) 0%, rgba(29, 78, 216, 0.9) 100%),
                      url('https://images.unsplash.com/photo-1504711434969-e33886168f5c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1470&q=80');
            --featured-bg: #1e293b;
            --newsletter-bg: #0f172a;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background: var(--bg-color);
            min-height: 100vh;
        }

        /* Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
            transition: all 0.3s ease;
        }

        [data-theme="dark"] .navbar {
            background: rgba(30, 41, 59, 0.95);
        }

        .navbar-brand {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 1.8rem;
            color: var(--primary-color) !important;
        }

        .nav-link {
            font-weight: 500;
            color: var(--text-color) !important;
            transition: color 0.3s ease;
        }

        html[data-theme="dark"] .nav-link {
            color: #ffffff !important;
        }

        .nav-link:hover {
            color: var(--primary-color) !important;
        }

        /* Hero Section */
        .hero-section {
            background: var(--hero-bg);
            background-size: cover;
            background-position: center;
            color: white;
            padding: 8rem 0 6rem;
            position: relative;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 4rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            line-height: 1.1;
        }

        .hero-subtitle {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .btn-custom {
            background: var(--accent-color);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-custom:hover {
            background: #d97706;
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }

        /* Enhanced Button Animations */
        .btn-custom, .btn-outline-primary, .btn-outline-success, .btn-outline-info, .btn-outline-warning, .btn-outline-danger, .btn-outline-secondary {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .btn-custom::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-custom:hover::before {
            left: 100%;
        }

        .btn-custom:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 25px rgba(245, 158, 11, 0.4);
        }

        .btn-outline-primary:hover, .btn-outline-success:hover, .btn-outline-info:hover,
        .btn-outline-warning:hover, .btn-outline-danger:hover, .btn-outline-secondary:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        /* Pulse animation for special buttons */
        @keyframes pulse-glow {
            0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
            100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
        }

        .btn-custom:focus {
            animation: pulse-glow 1.5s infinite;
        }

        /* Icon animations */
        .btn-custom i, .btn-outline-primary i, .btn-outline-success i, .btn-outline-info i,
        .btn-outline-warning i, .btn-outline-danger i, .btn-outline-secondary i {
            transition: transform 0.3s ease;
        }

        .btn-custom:hover i, .btn-outline-primary:hover i, .btn-outline-success:hover i,
        .btn-outline-info:hover i, .btn-outline-warning:hover i, .btn-outline-danger:hover i,
        .btn-outline-secondary:hover i {
            transform: translateX(3px);
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
            background: var(--card-bg);
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .card.no-hover:hover {
            transform: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .card-img-top {
            height: 250px;
            object-fit: cover;
        }

        .card-title {
            font-family: 'Playfair Display', serif;
            font-weight: 600;
            color: var(--text-color);
        }

        .card-text {
            color: var(--text-color);
        }

        /* Featured News */
        .featured-news {
            background: var(--featured-bg);
            padding: 5rem 0;
            margin: 3rem 0;
            border-radius: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 3rem;
            text-align: center;
        }

        /* Newsletter */
        .newsletter-section {
            background: var(--light-color);
            padding: 4rem 0;
            border-radius: 30px;
            margin: 3rem 0;
        }

        .newsletter-form {
            max-width: 500px;
            margin: 0 auto;
        }

        .form-control {
            border-radius: 50px;
            padding: 1rem 1.5rem;
            border: 2px solid var(--border-color);
            margin-bottom: 1rem;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        }

        /* Footer */
        .footer {
            background: var(--dark-color);
            color: white;
            padding: 4rem 0 2rem;
            margin-top: 3rem;
        }

        .footer h5 {
            color: white;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .footer a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer a:hover {
            color: var(--accent-color);
        }

        .social-links a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            margin-right: 10px;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: var(--accent-color);
            transform: translateY(-3px);
        }

        /* Animations */
        .fade-in {
            animation: fadeIn 1s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Typewriter Effect */
        .typewriter {
            overflow: hidden;
            border-right: 3px solid var(--accent-color);
            white-space: nowrap;
            animation: typing 3s steps(80, end), blink-caret 0.75s step-end infinite;
        }

        @keyframes typing {
            from { width: 0; }
            to { width: 100%; }
        }

        @keyframes blink-caret {
            from, to { border-color: transparent; }
            50% { border-color: var(--accent-color); }
        }

        .slide-in-left {
            animation: slideInLeft 1s ease-out;
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-50px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .slide-in-right {
            animation: slideInRight 1s ease-out;
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* Loading Animation */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: white;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }

        .loading-overlay.hidden {
            opacity: 0;
            visibility: hidden;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Modern Theme Toggle Button */
        .theme-toggle-container {
            display: flex;
            align-items: center;
        }

        .theme-toggle-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50px;
            width: 60px;
            height: 30px;
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .theme-toggle-btn:hover {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            transform: scale(1.05);
        }

        .toggle-slider {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 26px;
            height: 26px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border-radius: 50%;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
        }

        [data-theme="dark"] .toggle-slider {
            left: 32px;
            background: linear-gradient(135deg, #1e293b, #0f172a);
            box-shadow: 0 2px 8px rgba(30, 41, 59, 0.3);
        }

        .toggle-icon {
            font-size: 12px;
            color: white;
            transition: all 0.3s ease;
        }

        .light-icon {
            opacity: 1;
        }

        .dark-icon {
            opacity: 0;
            position: absolute;
        }

        [data-theme="dark"] .light-icon {
            opacity: 0;
        }

        [data-theme="dark"] .dark-icon {
            opacity: 1;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.1rem;
            }

            .section-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>
