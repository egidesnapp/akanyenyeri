<?php
/**
 * Admin Dashboard - Akanyenyeri Magazine
 * Main dashboard with properly integrated sidebar and responsive layout
 */

session_start();
require_once "php/auth_check.php";
require_once "php/dashboard_data.php";

// Require authentication
requireAuth();

// Get dashboard data
$stats = $dashboard->getDashboardSummary();
$quickStats = $dashboard->getQuickStats();

// Get current user info
$current_user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Akanyenyeri Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/chart.js/3.9.1/chart.min.css">
    <link rel="stylesheet" href="css/admin-layout.css">
    <style>
        /* Dashboard specific styles */
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .welcome-section h1 {
            font-size: 1.875rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .welcome-section p {
            opacity: 0.9;
            font-size: 1rem;
        }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border-left: 4px solid;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .stat-card.posts { border-left-color: #3182ce; }
        .stat-card.comments { border-left-color: #38a169; }
        .stat-card.users { border-left-color: #d69e2e; }
        .stat-card.views { border-left-color: #e53e3e; }

        .stat-card-icon {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            opacity: 0.2;
        }

        .stat-card.posts .stat-card-icon {
            background: rgba(49, 130, 206, 0.1);
            color: #3182ce;
        }

        .stat-card.comments .stat-card-icon {
            background: rgba(56, 161, 105, 0.1);
            color: #38a169;
        }

        .stat-card.users .stat-card-icon {
            background: rgba(214, 158, 46, 0.1);
            color: #d69e2e;
        }

        .stat-card.views .stat-card-icon {
            background: rgba(229, 62, 62, 0.1);
            color: #e53e3e;
        }

        .stat-number {
            font-size: 2.25rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #718096;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .stat-detail {
            font-size: 0.8125rem;
            color: #a0aec0;
        }

        .dashboard-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .main-sections {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .sidebar-sections {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .dashboard-section {
            background: #ffffff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .section-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: #3182ce;
        }

        .chart-container {
            height: 300px;
            position: relative;
        }

        .post-item, .comment-item {
            padding: 0.875rem 0;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .post-item:last-child, .comment-item:last-child {
            border-bottom: none;
        }

        .post-title {
            font-weight: 600;
            color: #2d3748;
            text-decoration: none;
            margin-bottom: 0.25rem;
            display: block;
        }

        .post-title:hover {
            color: #3182ce;
        }

        .post-meta {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            font-size: 0.8125rem;
            color: #718096;
        }

        .status-badge {
            padding: 0.2rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-published {
            background: rgba(56, 161, 105, 0.1);
            color: #2f855a;
        }

        .status-draft {
            background: rgba(214, 158, 46, 0.1);
            color: #c05621;
        }

        .status-pending {
            background: rgba(237, 137, 54, 0.1);
            color: #c05621;
        }

        .status-approved {
            background: rgba(56, 161, 105, 0.1);
            color: #2f855a;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .quick-action-card {
            background: #ffffff;
            border-radius: 8px;
            padding: 1.25rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-align: center;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .quick-action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            color: inherit;
            text-decoration: none;
        }

        .quick-action-icon {
            width: 3rem;
            height: 3rem;
            background: #f7fafc;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem;
            font-size: 1.25rem;
            color: #3182ce;
        }

        .quick-action-title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }

        .quick-action-desc {
            font-size: 0.8125rem;
            color: #718096;
        }

        .activity-feed {
            max-height: 400px;
            overflow-y: auto;
        }

        .activity-item {
            display: flex;
            gap: 0.75rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 2rem;
            height: 2rem;
            background: #f7fafc;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            color: #3182ce;
            flex-shrink: 0;
        }

        .activity-content {
            flex: 1;
        }

        .activity-text {
            font-size: 0.875rem;
            color: #4a5568;
            margin-bottom: 0.25rem;
        }

        .activity-time {
            font-size: 0.75rem;
            color: #a0aec0;
        }

        @media (max-width: 1024px) {
            .dashboard-content {
                grid-template-columns: 1fr;
            }

            .dashboard-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .dashboard-stats {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .welcome-section {
                padding: 1.5rem;
            }

            .welcome-section h1 {
                font-size: 1.5rem;
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
                <!-- Welcome Section -->
                <div class="welcome-section">
                    <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px;">
                        <img src="../logo/akanyenyeri logo.png" alt="Akanyenyeri Logo" style="height: 60px; width: auto;">
                        <div>
                            <h1 style="margin: 0; margin-bottom: 5px;"><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                            <p style="margin: 0;">Welcome back, <?= htmlspecialchars(
                                $current_user["full_name"] ?? "Admin",
                            ) ?>! Here's what's happening with your magazine.</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <a href="post-new.php" class="quick-action-card">
                        <div class="quick-action-icon">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div class="quick-action-title">New Post</div>
                        <div class="quick-action-desc">Create a new article</div>
                    </a>
                    <a href="media.php" class="quick-action-card">
                        <div class="quick-action-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="quick-action-title">Upload Media</div>
                        <div class="quick-action-desc">Add images and files</div>
                    </a>
                    <a href="comments.php" class="quick-action-card">
                        <div class="quick-action-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="quick-action-title">Moderate</div>
                        <div class="quick-action-desc">Review comments</div>
                    </a>
                    <a href="analytics.php" class="quick-action-card">
                        <div class="quick-action-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="quick-action-title">Analytics</div>
                        <div class="quick-action-desc">View site statistics</div>
                    </a>
                </div>

                <!-- Statistics Cards -->
                <div class="dashboard-stats">
                    <div class="stat-card posts">
                        <div class="stat-card-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-number">
                            <?= DashboardData::formatNumber(
                                $stats["total_posts"],
                            ) ?>
                        </div>
                        <div class="stat-label">Total Posts</div>
                        <div class="stat-detail">
                            <?= $stats["published_posts"] ?> Published,
                            <?= $stats["draft_posts"] ?> Drafts
                        </div>
                    </div>

                    <div class="stat-card comments">
                        <div class="stat-card-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="stat-number">
                            <?= DashboardData::formatNumber(
                                $stats["total_comments"],
                            ) ?>
                        </div>
                        <div class="stat-label">Comments</div>
                        <div class="stat-detail">
                            <?= $stats["pending_comments"] ?> Pending Approval
                        </div>
                    </div>

                    <div class="stat-card users">
                        <div class="stat-card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-number">
                            <?= DashboardData::formatNumber(
                                $stats["total_users"],
                            ) ?>
                        </div>
                        <div class="stat-label">Users</div>
                        <div class="stat-detail">
                            Active community members
                        </div>
                    </div>

                    <div class="stat-card views">
                        <div class="stat-card-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="stat-number">
                            <?= DashboardData::formatNumber(
                                $stats["total_views"],
                            ) ?>
                        </div>
                        <div class="stat-label">Total Views</div>
                        <div class="stat-detail">
                            Avg: <?= $quickStats["views"]["average"] ?> per post
                        </div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="dashboard-content">
                    <!-- Main Sections -->
                    <div class="main-sections">
                        <!-- Category Distribution Chart -->
                        <div class="dashboard-section">
                            <h3 class="section-title">
                                <i class="fas fa-chart-pie"></i>
                                Content Distribution
                            </h3>
                            <div class="chart-container">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>

                        <!-- Recent Posts -->
                        <div class="dashboard-section">
                            <h3 class="section-title">
                                <i class="fas fa-clock"></i>
                                Recent Posts
                            </h3>
                            <?php if (!empty($stats["recent_posts"])): ?>
                                <?php foreach (
                                    $stats["recent_posts"]
                                    as $post
                                ): ?>
                                <div class="post-item">
                                    <div>
                                        <a href="post-edit.php?id=<?= $post[
                                            "id"
                                        ] ?>" class="post-title">
                                            <?= htmlspecialchars(
                                                $post["title"],
                                            ) ?>
                                        </a>
                                        <div class="post-meta">
                                            <span class="status-badge status-<?= $post[
                                                "status"
                                            ] ?>">
                                                <?= $post["status"] ?>
                                            </span>
                                            <span><?= $post[
                                                "category_name"
                                            ] ?></span>
                                            <span><?= $post[
                                                "views"
                                            ] ?> views</span>
                                        </div>
                                    </div>
                                    <div class="post-meta" style="flex-direction: column; align-items: flex-end; text-align: right;">
                                        <div>By <?= htmlspecialchars(
                                            $post["author_name"],
                                        ) ?></div>
                                        <div><?= DashboardData::timeAgo(
                                            $post["created_at"],
                                        ) ?></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color: #718096; text-align: center; padding: 2rem;">No posts found</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Sidebar Sections -->
                    <div class="sidebar-sections">
                        <!-- Popular Posts -->
                        <div class="dashboard-section">
                            <h3 class="section-title">
                                <i class="fas fa-fire"></i>
                                Popular Posts
                            </h3>
                            <?php if (!empty($stats["popular_posts"])): ?>
                                <?php foreach (
                                    array_slice($stats["popular_posts"], 0, 5)
                                    as $post
                                ): ?>
                                <div class="post-item">
                                    <div>
                                        <a href="post-edit.php?id=<?= $post[
                                            "id"
                                        ] ?>" class="post-title">
                                            <?= htmlspecialchars(
                                                $post["title"],
                                            ) ?>
                                        </a>
                                        <div class="post-meta">
                                            <span><?= $post[
                                                "category_name"
                                            ] ?></span>
                                            <span><strong><?= $post[
                                                "views"
                                            ] ?> views</strong></span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color: #718096; text-align: center;">No popular posts</p>
                            <?php endif; ?>
                        </div>

                        <!-- Recent Comments -->
                        <div class="dashboard-section">
                            <h3 class="section-title">
                                <i class="fas fa-comment-dots"></i>
                                Recent Comments
                            </h3>
                            <?php if (!empty($stats["recent_comments"])): ?>
                                <?php foreach (
                                    array_slice($stats["recent_comments"], 0, 5)
                                    as $comment
                                ): ?>
                                <div class="comment-item">
                                    <div>
                                        <div class="post-title" style="font-size: 0.875rem;">
                                            <?= htmlspecialchars(
                                                substr(
                                                    $comment["content"],
                                                    0,
                                                    60,
                                                ),
                                            ) ?>...
                                        </div>
                                        <div class="post-meta">
                                            <span class="status-badge status-<?= $comment[
                                                "status"
                                            ] ?>">
                                                <?= $comment["status"] ?>
                                            </span>
                                            <span>By <?= htmlspecialchars(
                                                $comment["author_name"],
                                            ) ?></span>
                                        </div>
                                        <div style="font-size: 0.75rem; color: #a0aec0; margin-top: 0.25rem;">
                                            On: <?= htmlspecialchars(
                                                $comment["post_title"],
                                            ) ?>
                                        </div>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #a0aec0;">
                                        <?= DashboardData::timeAgo(
                                            $comment["created_at"],
                                        ) ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color: #718096; text-align: center;">No recent comments</p>
                            <?php endif; ?>
                        </div>

                        <!-- Top Authors -->
                        <?php if (!empty($stats["top_authors"])): ?>
                        <div class="dashboard-section">
                            <h3 class="section-title">
                                <i class="fas fa-users"></i>
                                Top Authors
                            </h3>
                            <?php foreach (
                                array_slice($stats["top_authors"], 0, 5)
                                as $author
                            ): ?>
                            <div class="post-item">
                                <div>
                                    <div class="post-title">
                                        <?= htmlspecialchars(
                                            $author["full_name"],
                                        ) ?>
                                    </div>
                                    <div class="post-meta">
                                        <span>@<?= htmlspecialchars(
                                            $author["username"],
                                        ) ?></span>
                                        <span><?= $author[
                                            "post_count"
                                        ] ?> posts</span>
                                        <span><?= DashboardData::formatNumber(
                                            $author["total_views"],
                                        ) ?> views</span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar overlay for mobile -->
    <div class="sidebar-overlay" onclick="toggleMobileSidebar()"></div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chart.js/3.9.1/chart.min.js"></script>
    <script>
        // Mobile sidebar toggle
        function toggleMobileSidebar() {
            const sidebar = document.querySelector('.admin-sidebar');
            const overlay = document.querySelector('.sidebar-overlay');

            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
        }

        // Add mobile menu toggle button functionality if it exists
        document.addEventListener('DOMContentLoaded', function() {
            const mobileToggle = document.querySelector('.mobile-menu-toggle');
            if (mobileToggle) {
                mobileToggle.addEventListener('click', toggleMobileSidebar);
            }
        });

        // Category Distribution Chart
        <?php if (!empty($stats["posts_by_category"])): ?>
        const categoryData = <?= json_encode($stats["posts_by_category"]) ?>;
        const ctx = document.getElementById('categoryChart').getContext('2d');

        const categoryChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: categoryData.map(item => item.name),
                datasets: [{
                    data: categoryData.map(item => item.post_count),
                    backgroundColor: categoryData.map(item => item.color || '#3182ce'),
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverBorderWidth: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: {
                                family: 'Inter',
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((context.parsed / total) * 100);
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                },
                animation: {
                    animateScale: true,
                    animateRotate: true
                }
            }
        });
        <?php endif; ?>

        // Auto refresh dashboard data every 5 minutes
        setInterval(function() {
            // You can implement auto-refresh logic here
            console.log('Dashboard auto-refresh triggered');
        }, 300000);

        // Add loading states and interactions
        document.querySelectorAll('.quick-action-card').forEach(card => {
            card.addEventListener('click', function() {
                this.style.opacity = '0.7';
                setTimeout(() => {
                    this.style.opacity = '1';
                }, 200);
            });
        });
    </script>
</body>
</html>
