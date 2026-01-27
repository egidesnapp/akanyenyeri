<?php
/**
 * 404 Page - Not Found
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
        }
        .container {
            max-width: 600px;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        h1 {
            font-size: 48px;
            color: #3498db;
            margin-bottom: 0.5rem;
        }
        p {
            font-size: 18px;
            color: #666;
            margin-bottom: 1rem;
        }
        .btn {
            background: #3182ce;
            color: white;
            padding: 10px 16px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #2c5282;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>404</h1>
        <p>We couldn't find the page you're looking for.</p>
        <p>The page may have been moved, deleted, or the URL may be incorrect.</p>
        <p><a href="/akanyenyeri/public/index.php" class="btn">Return Home</a></p>
    </div>
</body>
</html>
