<?php
$site_title = "Akanyenyeri Magazine";
$site_description = "Breaking news, in-depth analysis, and compelling stories from around the world.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_title; ?></title>
    <meta name="description" content="<?php echo $site_description; ?>">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Basic styles for single.php and category.php */
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background: #f4f4f4;
        }
        .site-header {
            background: #333;
            color: #fff;
            padding: 1rem 0;
        }
        .site-header .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .site-branding h1 a {
            color: #fff;
            text-decoration: none;
        }
        .main-navigation ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
        }
        .main-navigation li {
            margin-left: 1rem;
        }
        .main-navigation a {
            color: #fff;
            text-decoration: none;
        }
        .site-content {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            background: #fff;
            padding: 2rem;
            border-radius: 5px;
        }
        .container-inner {
            display: flex;
        }
        .site-main {
            flex: 1;
            margin-right: 2rem;
        }
        .widget-area {
            width: 300px;
        }
        .widget {
            margin-bottom: 2rem;
        }
        .widget-title {
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        .widget ul {
            list-style: none;
            padding: 0;
        }
        .widget li {
            margin-bottom: 0.5rem;
        }
        .widget a {
            text-decoration: none;
            color: #333;
        }
        .breadcrumbs {
            margin-bottom: 1rem;
        }
        .breadcrumbs ul {
            list-style: none;
            padding: 0;
            display: flex;
        }
        .breadcrumbs li {
            margin-right: 0.5rem;
        }
        .breadcrumbs a {
            color: #666;
            text-decoration: none;
        }
        .post {
            margin-bottom: 2rem;
        }
        .entry-header {
            margin-bottom: 1rem;
        }
        .entry-title {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .entry-meta {
            color: #666;
            font-size: 0.9rem;
        }
        .post-thumbnail img {
            max-width: 100%;
            height: auto;
        }
        .entry-content {
            line-height: 1.8;
        }
        .site-footer {
            background: #333;
            color: #fff;
            text-align: center;
            padding: 1rem 0;
            margin-top: 2rem;
        }
        .site-footer .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        .archive-title {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        .post-item {
            display: flex;
            margin-bottom: 2rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
        }
        .post-item-thumbnail {
            margin-right: 1rem;
        }
        .post-item-thumbnail img {
            width: 200px;
            height: 150px;
            object-fit: cover;
        }
        .post-item-content {
            flex: 1;
        }
        .post-item-title {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .post-item-title a {
            text-decoration: none;
            color: #333;
        }
        .post-item-meta {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .post-item-excerpt {
            color: #666;
        }
    </style>
</head>
<body>
<header class="site-header">
    <div class="container">
        <div class="site-branding">
            <h1><a href="index.php">Akanyenyeri</a></h1>
        </div>
        <nav class="main-navigation">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="#news">News</a></li>
                <li><a href="#categories">Categories</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </nav>
    </div>
</header>
