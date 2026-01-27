<?php
require_once __DIR__ . '/../../database/config/database.php';
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
    <!-- Main CSS -->
    <link href="<?php echo SITE_URL; ?>public/css/main.css" rel="stylesheet">

    <style>
        /* Permanent Dark Theme */
        :root {
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
            background: rgba(30, 41, 59, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
            transition: all 0.3s ease;
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
            background: var(--primary-color);
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
            background: var(--secondary-color);
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
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.35);
        }

        .btn-outline-primary:hover, .btn-outline-success:hover, .btn-outline-info:hover,
        .btn-outline-warning:hover, .btn-outline-danger:hover, .btn-outline-secondary:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        /* Pulse animation for special buttons */
        @keyframes pulse-glow {
            0% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(37, 99, 235, 0); }
            100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0); }
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
            color: var(--text-color);
            padding: 2rem 0 1rem;
            margin-top: 3rem;
        }

        .footer h5, .footer h6 {
            color: var(--text-color);
            margin-bottom: 0.75rem;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .footer a {
            color: var(--text-color);
            opacity: 0.8;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }

        .footer a:hover {
            color: var(--accent-color);
        }

        /* New Compact Footer Styles */
        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }

        .footer-section {
            flex: 1;
            min-width: 200px;
        }

        .footer-section:first-child {
            flex: 0 0 250px;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .footer-text {
            font-size: 0.8rem;
            color: var(--text-color);
            opacity: 0.7;
            margin-bottom: 1rem;
            line-height: 1.4;
        }

        .footer-links-horizontal {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .footer-links-horizontal a {
            white-space: nowrap;
        }

        .footer-contact-compact {
            margin-bottom: 0.5rem;
        }

        .footer-contact-compact p {
            margin: 0;
            font-size: 0.8rem;
            color: var(--text-color);
            opacity: 0.7;
        }

        .social-links-compact {
            display: flex;
            gap: 0.5rem;
        }

        .social-links-compact .social-link {
            width: 30px;
            height: 30px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }

        .social-links-compact .social-link:hover {
            background: var(--accent-color);
            transform: translateY(-2px);
        }

        .footer-bottom-compact {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
            font-size: 0.75rem;
            color: var(--text-color);
            opacity: 0.6;
        }

        .footer-legal-compact {
            display: flex;
            gap: 1rem;
        }

        .footer-legal-compact a {
            font-size: 0.75rem;
            color: var(--text-color);
            opacity: 0.6;
            text-decoration: none;
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

        /* Loading Animation - Modern Star Animation */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease, visibility 0.5s ease;
            backdrop-filter: blur(10px);
        }

        .loading-overlay.hidden {
            opacity: 0;
            visibility: hidden;
        }

        .star-loader {
            position: relative;
            width: 80px;
            height: 80px;
        }

        .star {
            position: absolute;
            width: 100%;
            height: 100%;
            animation: starRotate 2s ease-in-out infinite;
        }

        .star:before,
        .star:after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, #ffd700, #ffed4e, #fff700);
            clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%);
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.6);
        }

        .star:after {
            transform: rotate(180deg);
            animation: starPulse 1.5s ease-in-out infinite alternate;
        }

        .star-orbit {
            position: absolute;
            width: 120px;
            height: 120px;
            border: 2px solid rgba(255, 215, 0, 0.3);
            border-radius: 50%;
            animation: orbitRotate 3s linear infinite;
        }

        .star-orbit:before,
        .star-orbit:after {
            content: '';
            position: absolute;
            width: 8px;
            height: 8px;
            background: radial-gradient(circle, #ffd700, #ffed4e);
            border-radius: 50%;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.8);
        }

        .star-orbit:before {
            top: -4px;
            left: 50%;
            transform: translateX(-50%);
        }

        .star-orbit:after {
            bottom: -4px;
            left: 50%;
            transform: translateX(-50%);
        }

        .loading-text {
            position: absolute;
            bottom: -60px;
            left: 50%;
            transform: translateX(-50%);
            color: #ffffff;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 2px;
            text-transform: uppercase;
            animation: textGlow 2s ease-in-out infinite alternate;
        }

        @keyframes starRotate {
            0%, 100% { transform: rotate(0deg) scale(1); }
            50% { transform: rotate(180deg) scale(1.1); }
        }

        @keyframes starPulse {
            0% { opacity: 0.7; transform: rotate(180deg) scale(0.9); }
            100% { opacity: 1; transform: rotate(180deg) scale(1.1); }
        }

        @keyframes orbitRotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes textGlow {
            0% { text-shadow: 0 0 5px rgba(255, 255, 255, 0.5); }
            100% { text-shadow: 0 0 15px rgba(255, 215, 0, 0.8); }
        }

        /* Advertisement Styles */
        .hero-advertisements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .hero-ad-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            opacity: 0;
            transition: opacity 0.8s ease-in-out;
            z-index: 1;
        }

        /* Ensure advertisements fit properly across all screen sizes */
        @media (max-width: 1200px) {
            .hero-ad-slide {
                background-size: cover;
                background-position: center center;
            }
        }

        @media (max-width: 992px) {
            .hero-ad-slide {
                background-size: cover;
                background-position: center center;
            }
        }

        @media (max-width: 768px) {
            .hero-ad-slide {
                background-size: cover;
                background-position: center center;
                /* Ensure important content stays visible on mobile */
                background-attachment: scroll;
            }
        }

        @media (max-width: 576px) {
            .hero-ad-slide {
                background-size: cover;
                background-position: center center;
            }
        }

        .hero-ad-slide.active {
            opacity: 1;
            z-index: 2;
        }

        .hero-ad-slide::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 3;
        }

        .hero-ad-link {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 4;
            cursor: pointer;
        }

        .hero-ad-dots {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 5;
        }

        .hero-ad-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            border: 2px solid rgba(255, 255, 255, 0.8);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .hero-ad-dot:hover,
        .hero-ad-dot.active {
            background: var(--accent-color);
            border-color: var(--accent-color);
            transform: scale(1.2);
        }

        /* Enhanced Hero Section Styles */
        .hero-custom-image {
            margin-bottom: 2rem;
            animation: fadeInUp 1s ease-out;
        }

        .hero-custom-image img {
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .hero-custom-image img:hover {
            transform: scale(1.05);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4);
        }

        .hero-title {
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            animation: slideInUp 1s ease-out;
        }

        .hero-subtitle {
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
            animation: slideInUp 1s ease-out 0.3s both;
        }

        .hero-section .btn-custom {
            animation: slideInUp 1s ease-out 0.6s both;
            position: relative;
            overflow: hidden;
        }

        /* Typewriter effect for hero subtitle */
        .typewriter {
            overflow: hidden;
            white-space: nowrap;
            margin: 0 auto;
            animation: typing 3s steps(40, end), blink-caret 0.75s step-end infinite;
        }

        @keyframes typing {
            from { width: 0; }
            to { width: 100%; }
        }

        @keyframes blink-caret {
            from, to { border-color: transparent; }
            50% { border-color: var(--accent-color); }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Animated Headings */
        .animated-heading {
            position: relative;
            overflow: hidden;
            color: var(--text-color) !important;
        }

        .animated-heading::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--accent-color);
            animation: headingUnderline 3s ease-in-out infinite;
        }

        @keyframes headingUnderline {
            0% { width: 0; }
            50% { width: 100%; }
            100% { width: 0; }
        }

        /* Enhanced heading animations for different heading levels */
        .article-title.animated-heading {
            transition: all 0.3s ease;
        }

        .article-title.animated-heading:hover {
            animation-play-state: paused;
            transform: translateY(-2px);
        }

        /* Load More Button Styles */
        #loadMoreBtn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
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

            .hero-ad-dots {
                bottom: 20px;
            }

            .hero-ad-dot {
                width: 10px;
                height: 10px;
            }

            /* Footer responsive styles */
            .footer-content {
                flex-direction: column;
                gap: 1.5rem;
            }

            .footer-section {
                min-width: 100%;
                text-align: center;
            }

            .footer-section:first-child {
                flex: 1;
            }

            .footer-links-horizontal {
                justify-content: center;
            }

            .social-links-compact {
                justify-content: center;
            }

            .footer-bottom-compact {
                flex-direction: column;
                gap: 0.5rem;
                text-align: center;
            }

            .footer-legal-compact {
                justify-content: center;
            }

            .animated-heading {
                animation-duration: 2s;
            }

            .animated-heading::before {
                animation-duration: 3s;
            }

            /* Enhanced mobile hero section */
            .hero-section {
                padding: 6rem 0 4rem;
            }

            .hero-custom-image img {
                max-height: 200px;
            }

            .hero-section .btn-custom {
                font-size: 1rem;
                padding: 0.8rem 1.5rem;
            }

            /* Disable typewriter effect on mobile for better performance */
            .typewriter {
                animation: none;
                white-space: normal;
                border-right: none;
            }
        }

        @media (max-width: 576px) {
            .hero-title {
                font-size: 2rem;
            }

            .hero-subtitle {
                font-size: 1rem;
            }

            .hero-section {
                padding: 5rem 0 3rem;
            }

            .hero-custom-image img {
                max-height: 150px;
            }

            .hero-section .btn-custom {
                font-size: 0.9rem;
                padding: 0.7rem 1.2rem;
            }
        }

        @media (max-width: 400px) {
            .hero-title {
                font-size: 1.8rem;
            }

            .hero-section {
                padding: 4rem 0 2.5rem;
            }

            .hero-custom-image img {
                max-height: 120px;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Overlay - Modern Star Animation -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="star-loader">
            <div class="star"></div>
            <div class="star-orbit"></div>
            <div class="loading-text">Loading</div>
        </div>
    </div>
