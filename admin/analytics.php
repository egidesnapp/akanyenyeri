<?php
/**
 * Analytics Dashboard - Akanyenyeri Magazine Admin
 * Enhanced analytics page with real-time updates and better data visualization
 */

session_start();
require_once "php/auth_check.php";
require_once "php/dashboard_data.php";

// Require authentication and admin role
requireAuth();
requireRole('admin', 'You need admin privileges to access analytics');

// Get database connection
$pdo = getDB();

// Handle AJAX requests
if (isset($_GET["action"]) && $_GET["action"] === "get_realtime_data") {
    header("Content-Type: application/json");

    try {
        $realtime_data = [
            "current_online" => 0, // Real online users count
            "today_posts" => 0,
            "today_comments" => 0,
            "today_views" => 0,
        ];

        // Real online users (active users in database)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE status = 'active'");
        $stmt->execute();
        $realtime_data["current_online"] = $stmt->fetchColumn();

        // Today's posts
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM posts WHERE DATE(created_at) = CURDATE() AND status = 'published'",
        );
        $stmt->execute();
        $realtime_data["today_posts"] = $stmt->fetchColumn();

        // Today's comments
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM comments WHERE DATE(created_at) = CURDATE() AND status = 'approved'",
        );
        $stmt->execute();
        $realtime_data["today_comments"] = $stmt->fetchColumn();

        // Today's actual views (from posts created today, not inflated sample data)
        $stmt = $pdo->prepare(
            "SELECT SUM(views) FROM posts WHERE DATE(created_at) = CURDATE() AND status = 'published'",
        );
        $stmt->execute();
        $realtime_data["today_views"] = $stmt->fetchColumn() ?: 0;

        echo json_encode($realtime_data);
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
    exit();
}

// Date range filter
$date_range = $_GET["range"] ?? "30";
$custom_start = $_GET["start_date"] ?? "";
$custom_end = $_GET["end_date"] ?? "";

// Calculate date range
switch ($date_range) {
    case "7":
        $start_date = date("Y-m-d", strtotime("-7 days"));
        $end_date = date("Y-m-d");
        break;
    case "30":
        $start_date = date("Y-m-d", strtotime("-30 days"));
        $end_date = date("Y-m-d");
        break;
    case "90":
        $start_date = date("Y-m-d", strtotime("-90 days"));
        $end_date = date("Y-m-d");
        break;
    case "custom":
        $start_date = $custom_start ?: date("Y-m-d", strtotime("-30 days"));
        $end_date = $custom_end ?: date("Y-m-d");
        break;
    default:
        $start_date = date("Y-m-d", strtotime("-30 days"));
        $end_date = date("Y-m-d");
}

// Get dashboard data using the same class as the main dashboard
$dashboard_data = $dashboard->getDashboardSummary();

// Extract data for analytics (use all data since analytics shows overall stats)
$overview = [
    "total_posts" => $dashboard_data["total_posts"] ?? 0,
    "total_views" => $dashboard_data["total_views"] ?? 0,
    "total_comments" => $dashboard_data["total_comments"] ?? 0,
    "total_users" => $dashboard_data["total_users"] ?? 0,
];

// Use data from dashboard summary
$popular_posts = $dashboard_data["popular_posts"] ?? [];
$categories_data = $dashboard_data["posts_by_category"] ?? [];
$top_authors = $dashboard_data["top_authors"] ?? [];
$recent_comments = $dashboard_data["recent_comments"] ?? [];

// For daily stats, we need to get it separately since dashboard summary doesn't include date-filtered data
try {
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as posts, SUM(views) as views
        FROM posts
        WHERE status = 'published'
        AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$start_date, $end_date]);
    $daily_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $daily_stats = [];
}

// Traffic sources - not implemented yet (would require tracking user sessions/referrers)
$traffic_sources = null;

// Get current user info
$current_user = getCurrentUser();

// Helper functions
function formatNumber($number)
{
    if ($number >= 1000000) {
        return round($number / 1000000, 1) . "M";
    } elseif ($number >= 1000) {
        return round($number / 1000, 1) . "K";
    }
    return number_format($number);
}

function timeAgo($datetime)
{
    $time = time() - strtotime($datetime);
    if ($time < 60) {
        return "just now";
    }
    if ($time < 3600) {
        return floor($time / 60) . " min ago";
    }
    if ($time < 86400) {
        return floor($time / 3600) . " hours ago";
    }
    if ($time < 2592000) {
        return floor($time / 86400) . " days ago";
    }
    return date("M j, Y", strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Akanyenyeri Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .analytics-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .real-time-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #10b981;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-left: 1rem;
        }

        .real-time-dot {
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .date-filter {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .date-filter select,
        .date-filter input {
            padding: 0.5rem;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-color);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .stat-icon.posts { background: #3498db; }
        .stat-icon.views { background: #e74c3c; }
        .stat-icon.comments { background: #2ecc71; }
        .stat-icon.users { background: #f39c12; }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1d2327;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #646970;
            font-size: 0.9rem;
        }

        .analytics-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .chart-container {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f0f0f1;
        }

        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1d2327;
        }

        .sidebar-stats {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .stats-section {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .stats-section h3 {
            margin: 0 0 1rem 0;
            color: #1d2327;
            font-size: 1.1rem;
            font-weight: 600;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #f0f0f1;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f9f9f9;
        }

        .stat-item:last-child {
            border-bottom: none;
        }

        .stat-item-label {
            font-weight: 500;
            color: #1d2327;
        }

        .stat-item-value {
            color: #646970;
            font-size: 0.9rem;
        }

        .popular-posts-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .popular-post {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f1;
        }

        .popular-post:last-child {
            border-bottom: none;
        }

        .post-info {
            flex: 1;
            margin-right: 1rem;
        }

        .post-title {
            font-weight: 500;
            color: #1d2327;
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
            line-height: 1.3;
        }

        .post-category {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            font-size: 0.7rem;
            color: white;
            margin-bottom: 0.25rem;
        }

        .post-views {
            color: #646970;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .comment-item {
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f1;
        }

        .comment-item:last-child {
            border-bottom: none;
        }

        .comment-author {
            font-weight: 500;
            color: #1d2327;
            margin-bottom: 0.25rem;
        }

        .comment-content {
            color: #646970;
            font-size: 0.9rem;
            line-height: 1.4;
            margin-bottom: 0.25rem;
        }

        .comment-meta {
            font-size: 0.8rem;
            color: #8c8f94;
        }

        .no-data {
            text-align: center;
            padding: 2rem;
            color: #646970;
        }

        .no-data i {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #c3c4c7;
        }

        .realtime-stats {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }

        .realtime-stats h3 {
            margin: 0 0 1rem 0;
            color: #1d2327;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .realtime-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .realtime-item {
            text-align: center;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 6px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .realtime-item:hover {
            border-color: #3498db;
            background: #e3f2fd;
            transform: translateY(-2px);
        }

        .realtime-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2271b1;
            margin-bottom: 0.5rem;
        }

        .realtime-label {
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 500;
        }

        .traffic-sources {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }

        .traffic-sources h3 {
            margin: 0 0 1rem 0;
            color: #1d2327;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .traffic-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .traffic-item:last-child {
            border-bottom: none;
        }

        .traffic-bar {
            width: 100px;
            height: 6px;
            background: #f1f5f9;
            border-radius: 3px;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .traffic-fill {
            height: 100%;
            background: linear-gradient(90deg, #3498db, #2980b9);
            border-radius: 3px;
            transition: width 1s ease;
        }

        @media (max-width: 768px) {
            .analytics-header {
                flex-direction: column;
                gap: 1rem;
            }

            .date-filter {
                width: 100%;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .analytics-content {
                flex-direction: column;
            }

            .realtime-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .traffic-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .traffic-bar {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Include Sidebar -->
        <?php include "includes/sidebar.php"; ?>

        <div class="main-content">
            <!-- Include Header -->
            <?php include "includes/header.php"; ?>

            <div class="content-area">
                <!-- Analytics Header -->
                <div class="analytics-header">
                    <div>
                        <h1><i class="fas fa-chart-bar"></i> Analytics Dashboard</h1>
                        <div class="real-time-indicator">
                            <div class="real-time-dot"></div>
                            <span>Live Data</span>
                        </div>
                    </div>

                    <div class="date-filter">
                        <form method="GET" id="dateFilterForm">
                            <select name="range" id="dateRange" onchange="toggleCustomDates()">
                                <option value="7" <?php echo $date_range === "7"
                                    ? "selected"
                                    : ""; ?>>Last 7 days</option>
                                <option value="30" <?php echo $date_range ===
                                "30"
                                    ? "selected"
                                    : ""; ?>>Last 30 days</option>
                                <option value="90" <?php echo $date_range ===
                                "90"
                                    ? "selected"
                                    : ""; ?>>Last 90 days</option>
                                <option value="custom" <?php echo $date_range ===
                                "custom"
                                    ? "selected"
                                    : ""; ?>>Custom range</option>
                            </select>

                            <div id="customDates" style="display: <?php echo $date_range ===
                            "custom"
                                ? "flex"
                                : "none"; ?>; gap: 0.5rem;">
                                <input type="date" name="start_date" value="<?php echo htmlspecialchars(
                                    $custom_start,
                                ); ?>" max="<?php echo date("Y-m-d"); ?>">
                                <input type="date" name="end_date" value="<?php echo htmlspecialchars(
                                    $custom_end,
                                ); ?>" max="<?php echo date("Y-m-d"); ?>">
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Apply
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Real-time Stats -->
                <div class="realtime-stats">
                    <h3><i class="fas fa-clock"></i> Today's Activity</h3>
                    <div class="realtime-grid">
                        <div class="realtime-item">
                            <div class="realtime-value" id="onlineUsers">--</div>
                            <div class="realtime-label">Users Online</div>
                        </div>
                        <div class="realtime-item">
                            <div class="realtime-value" id="todayPosts">--</div>
                            <div class="realtime-label">Posts Today</div>
                        </div>
                        <div class="realtime-item">
                            <div class="realtime-value" id="todayComments">--</div>
                            <div class="realtime-label">Comments Today</div>
                        </div>
                        <div class="realtime-item">
                            <div class="realtime-value" id="todayViews">--</div>
                            <div class="realtime-label">Views Today</div>
                        </div>
                    </div>
                </div>

                <!-- Overview Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon posts">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-value"><?php echo formatNumber(
                            $overview["total_posts"],
                        ); ?></div>
                        <div class="stat-label">Posts Published</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon views">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="stat-value"><?php echo formatNumber(
                            $overview["total_views"],
                        ); ?></div>
                        <div class="stat-label">Total Views</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon comments">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="stat-value"><?php echo formatNumber(
                            $overview["total_comments"],
                        ); ?></div>
                        <div class="stat-label">Comments</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon users">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value"><?php echo formatNumber(
                            $overview["total_users"],
                        ); ?></div>
                        <div class="stat-label">New Users</div>
                    </div>
                </div>

                <!-- Charts and Sidebar -->
                <div class="analytics-content">
                    <!-- Charts -->
                    <div>
                        <!-- Daily Stats Chart -->
                        <div class="chart-container">
                            <div class="chart-header">
                                <h3 class="chart-title">Daily Activity</h3>
                            </div>
                            <?php if (!empty($daily_stats)): ?>
                            <canvas id="dailyChart" width="400" height="200"></canvas>
                            <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-chart-line"></i>
                                <p>No data available for the selected date range</p>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Categories Chart -->
                        <div class="chart-container" style="margin-top: 1.5rem;">
                            <div class="chart-header">
                                <h3 class="chart-title">Posts by Category</h3>
                            </div>
                            <?php if (!empty($categories_data)): ?>
                            <canvas id="categoriesChart" width="400" height="200"></canvas>
                            <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-chart-pie"></i>
                                <p>No category data available</p>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Traffic Sources - Not implemented yet -->
                        <div class="traffic-sources">
                            <h3><i class="fas fa-globe"></i> Traffic Sources</h3>
                            <div class="no-data">
                                <i class="fas fa-chart-bar"></i>
                                <p>Traffic source tracking not implemented yet.<br>Would require session/referrer logging.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar Stats -->
                    <div class="sidebar-stats">
                        <!-- Popular Posts -->
                        <div class="stats-section">
                            <h3><i class="fas fa-fire"></i> Most Popular Posts</h3>
                            <?php if (!empty($popular_posts)): ?>
                            <div class="popular-posts-list">
                                <?php foreach ($popular_posts as $post): ?>
                                <div class="popular-post">
                                    <div class="post-info">
                                        <div class="post-title"><?php echo htmlspecialchars(
                                            $post["title"],
                                        ); ?></div>
                                        <?php if ($post["category_name"]): ?>
                                        <div class="post-category" style="background-color: <?php echo htmlspecialchars(
                                            $post["category_color"] ??
                                                "#2271b1",
                                        ); ?>">
                                            <?php echo htmlspecialchars(
                                                $post["category_name"],
                                            ); ?>
                                        </div>
                                        <?php endif; ?>
                                        <div class="post-views">
                                            <i class="fas fa-eye"></i>
                                            <?php echo formatNumber(
                                                $post["views"],
                                            ); ?> views
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-file-alt"></i>
                                <p>No posts found</p>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Top Authors -->
                        <div class="stats-section">
                            <h3><i class="fas fa-pen"></i> Top Authors</h3>
                            <?php if (!empty($top_authors)): ?>
                            <?php foreach ($top_authors as $author): ?>
                            <div class="stat-item">
                                <div class="stat-item-label"><?php echo htmlspecialchars(
                                    $author["full_name"],
                                ); ?></div>
                                <div class="stat-item-value">
                                    <?php echo $author[
                                        "post_count"
                                    ]; ?> posts, <?php echo formatNumber(
     $author["total_views"],
 ); ?> views
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-user-edit"></i>
                                <p>No author data found</p>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Recent Comments -->
                        <div class="stats-section">
                            <h3><i class="fas fa-comments"></i> Recent Comments</h3>
                            <?php if (!empty($recent_comments)): ?>
                            <?php foreach (
                                array_slice($recent_comments, 0, 5)
                                as $comment
                            ): ?>
                            <div class="comment-item">
                                <div class="comment-author"><?php echo htmlspecialchars(
                                    $comment["author_name"],
                                ); ?></div>
                                <div class="comment-content"><?php echo htmlspecialchars(
                                    substr($comment["content"], 0, 100),
                                ) .
                                    (strlen($comment["content"]) > 100
                                        ? "..."
                                        : ""); ?></div>
                                <div class="comment-meta">
                                    on <a href="../single.php?slug=<?php echo htmlspecialchars(
                                        $comment["post_slug"],
                                    ); ?>" target="_blank"><?php echo htmlspecialchars(
    $comment["post_title"],
); ?></a>
                                    · <?php echo timeAgo(
                                        $comment["created_at"],
                                    ); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-comment"></i>
                                <p>No recent comments</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleCustomDates() {
            const dateRange = document.getElementById('dateRange').value;
            const customDates = document.getElementById('customDates');

            if (dateRange === 'custom') {
                customDates.style.display = 'flex';
            } else {
                customDates.style.display = 'none';
                document.getElementById('dateFilterForm').submit();
            }
        }

        // Daily Chart
        <?php if (!empty($daily_stats)): ?>
        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        const dailyChart = new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(
                    ",",
                    array_map(function ($stat) {
                        return "'" .
                            date("M j", strtotime($stat["date"])) .
                            "'";
                    }, $daily_stats),
                ); ?>],
                datasets: [{
                    label: 'Posts',
                    data: [<?php echo implode(
                        ",",
                        array_column($daily_stats, "posts"),
                    ); ?>],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Views',
                    data: [<?php echo implode(
                        ",",
                        array_column($daily_stats, "views"),
                    ); ?>],
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Posts'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Views'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
        <?php endif; ?>

        // Categories Chart
        <?php if (!empty($categories_data)): ?>
        const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
        const categoriesChart = new Chart(categoriesCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php echo implode(
                    ",",
                    array_map(function ($cat) {
                        return "'" . addslashes($cat["name"]) . "'";
                    }, $categories_data),
                ); ?>],
                datasets: [{
                    data: [<?php echo implode(
                        ",",
                        array_column($categories_data, "post_count"),
                    ); ?>],
                    backgroundColor: [<?php echo implode(
                        ",",
                        array_map(function ($cat) {
                            return "'" . ($cat["color"] ?: "#2271b1") . "'";
                        }, $categories_data),
                    ); ?>],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
        <?php endif; ?>

        // Real-time data updates
        function updateRealTimeData() {
            fetch('analytics.php?action=get_realtime_data')
                .then(response => response.json())
                .then(data => {
                    if (!data.error) {
                        document.getElementById('onlineUsers').textContent = data.current_online;
                        document.getElementById('todayPosts').textContent = data.today_posts;
                        document.getElementById('todayComments').textContent = data.today_comments;
                        document.getElementById('todayViews').textContent = formatNumberJS(data.today_views);
                    }
                })
                .catch(error => {
                    console.error('Error fetching real-time data:', error);
                });
        }

        // Helper function to format numbers in JavaScript
        function formatNumberJS(number) {
            if (number >= 1000000) {
                return (number / 1000000).toFixed(1) + 'M';
            } else if (number >= 1000) {
                return (number / 1000).toFixed(1) + 'K';
            }
            return number.toLocaleString();
        }

        // Update real-time data every 30 seconds
        updateRealTimeData();
        setInterval(updateRealTimeData, 30000);

        // Add smooth animations to stat cards
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = (index * 0.1) + 's';
                card.classList.add('animate-in');
            });

            // Add click handlers for interactive elements
            const realtimeItems = document.querySelectorAll('.realtime-item');
            realtimeItems.forEach(item => {
                item.addEventListener('click', function() {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                });
            });
        });

        // Export functionality
        function exportAnalytics() {
            const data = {
                overview: <?php echo json_encode($overview); ?>,
                dateRange: '<?php echo $start_date; ?> to <?php echo $end_date; ?>',
                popularPosts: <?php echo json_encode($popular_posts); ?>,
                topAuthors: <?php echo json_encode($top_authors); ?>,
                categories: <?php echo json_encode($categories_data); ?>
            };

            const dataStr = JSON.stringify(data, null, 2);
            const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);

            const exportFileDefaultName = 'analytics_' + new Date().toISOString().split('T')[0] + '.json';

            const linkElement = document.createElement('a');
            linkElement.setAttribute('href', dataUri);
            linkElement.setAttribute('download', exportFileDefaultName);
            linkElement.click();
        }

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            .animate-in {
                animation: slideInUp 0.6s ease-out forwards;
                opacity: 0;
                transform: translateY(20px);
            }

            @keyframes slideInUp {
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .stat-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            }

            .chart-container:hover {
                box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            }
        `;
        document.head.appendChild(style);
    </script>

    <!-- Export Button -->
    <div style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">
        <button onclick="exportAnalytics()" class="btn btn-secondary" title="Export Analytics Data"
                style="border-radius: 50px; padding: 12px 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            <i class="fas fa-download"></i> Export
        </button>
    </div>

    <script>
        // Auto-submit form when custom dates change
        document.querySelectorAll('#customDates input').forEach(input => {
            input.addEventListener('change', function() {
                document.getElementById('dateFilterForm').submit();
            });
        });
    </script>
</body>
</html>
