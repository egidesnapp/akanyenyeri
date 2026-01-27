<?php
/**
 * Simple Router for Akanyenyeri Magazine
 * This router works without requiring .htaccess/mod_rewrite
 * Place this in your main directory and set it as the default document
 */

// Get the request path
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$request_path = parse_url($request_uri, PHP_URL_PATH);

// Remove the base path (assuming site is in /akanyenyeri/)
$base_path = '/akanyenyeri';
$request_path = str_replace($base_path, '', $request_path);
$request_path = trim($request_path, '/');

// Split path into parts
$path_parts = explode('/', $request_path);
$requested_page = $path_parts[0] ?? '';

// Route to appropriate page
if (empty($requested_page)) {
    // Homepage
    include 'public/index.php';
} elseif ($requested_page === 'about') {
    include 'public/about.php';
} elseif ($requested_page === 'services') {
    include 'public/services.php';
} elseif ($requested_page === 'privacy') {
    include 'public/privacy.php';
} elseif ($requested_page === 'terms') {
    include 'public/terms.php';
} elseif ($requested_page === 'cookies') {
    include 'public/cookies.php';
} elseif ($requested_page === 'contact') {
    include 'public/contact.php';
} elseif ($requested_page === 'category' && !empty($path_parts[1])) {
    $_GET['slug'] = $path_parts[1];
    include 'public/category.php';
} else {
    // Try as a post slug
    $_GET['slug'] = $requested_page;
    include 'public/single.php';
}
