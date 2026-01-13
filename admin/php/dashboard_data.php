<?php
/**
 * Dashboard Data Handler for Akanyenyeri Magazine Admin
 * Handles all dashboard data operations and statistics
 */

require_once __DIR__ . "/../../database/config/database.php";

class DashboardData
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = getDB();
    }

    /**
     * Get total count of posts
     */
    public function getTotalPosts()
    {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM posts");
            return $stmt->fetch(PDO::FETCH_ASSOC)["total"];
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Get published posts count
     */
    public function getPublishedPosts()
    {
        try {
            $stmt = $this->pdo->query(
                "SELECT COUNT(*) as total FROM posts WHERE status = 'published'",
            );
            return $stmt->fetch(PDO::FETCH_ASSOC)["total"];
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Get draft posts count
     */
    public function getDraftPosts()
    {
        try {
            $stmt = $this->pdo->query(
                "SELECT COUNT(*) as total FROM posts WHERE status = 'draft'",
            );
            return $stmt->fetch(PDO::FETCH_ASSOC)["total"];
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Get total categories count
     */
    public function getTotalCategories()
    {
        try {
            $stmt = $this->pdo->query(
                "SELECT COUNT(*) as total FROM categories",
            );
            return $stmt->fetch(PDO::FETCH_ASSOC)["total"];
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Get total users count
     */
    public function getTotalUsers()
    {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM users");
            return $stmt->fetch(PDO::FETCH_ASSOC)["total"];
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Get total comments count
     */
    public function getTotalComments()
    {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM comments");
            return $stmt->fetch(PDO::FETCH_ASSOC)["total"];
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Get pending comments count
     */
    public function getPendingComments()
    {
        try {
            $stmt = $this->pdo->query(
                "SELECT COUNT(*) as total FROM comments WHERE status = 'pending'",
            );
            return $stmt->fetch(PDO::FETCH_ASSOC)["total"];
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Get total views for all posts
     */
    public function getTotalViews()
    {
        try {
            $stmt = $this->pdo->query("SELECT SUM(views) as total FROM posts");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result["total"] ?? 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Get recent posts (last 5)
     */
    public function getRecentPosts()
    {
        try {
            $stmt = $this->pdo->query("
                SELECT p.id, p.title, p.status, p.views, p.created_at, u.full_name as author_name, c.name as category_name
                FROM posts p
                LEFT JOIN users u ON p.author_id = u.id
                LEFT JOIN categories c ON p.category_id = c.id
                ORDER BY p.created_at DESC
                LIMIT 5
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get most popular posts (by views)
     */
    public function getPopularPosts()
    {
        try {
            $stmt = $this->pdo->query("
                SELECT p.id, p.title, p.views, p.created_at, u.full_name as author_name, c.name as category_name
                FROM posts p
                LEFT JOIN users u ON p.author_id = u.id
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.status = 'published'
                ORDER BY p.views DESC
                LIMIT 5
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get recent comments
     */
    public function getRecentComments()
    {
        try {
            $stmt = $this->pdo->query("
                SELECT c.id, c.author_name, c.content, c.status, c.created_at, p.title as post_title
                FROM comments c
                LEFT JOIN posts p ON c.post_id = p.id
                ORDER BY c.created_at DESC
                LIMIT 5
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get posts by category for chart
     */
    public function getPostsByCategory()
    {
        try {
            $stmt = $this->pdo->query("
                SELECT c.name, c.color, COUNT(p.id) as post_count
                FROM categories c
                LEFT JOIN posts p ON c.id = p.category_id AND p.status = 'published'
                GROUP BY c.id, c.name, c.color
                ORDER BY post_count DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get monthly post statistics for the last 6 months
     */
    public function getMonthlyStats()
    {
        try {
            $stmt = $this->pdo->query("
                SELECT
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as post_count,
                    SUM(views) as total_views
                FROM posts
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get user statistics by role
     */
    public function getUsersByRole()
    {
        try {
            $stmt = $this->pdo->query("
                SELECT role, COUNT(*) as user_count
                FROM users
                WHERE status = 'active'
                GROUP BY role
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get top authors by post count
     */
    public function getTopAuthors()
    {
        try {
            $stmt = $this->pdo->query("
                SELECT u.full_name, u.username, u.email, COUNT(p.id) as post_count, SUM(p.views) as total_views
                FROM users u
                LEFT JOIN posts p ON u.id = p.author_id AND p.status = 'published'
                WHERE u.role IN ('author', 'editor', 'admin')
                GROUP BY u.id, u.full_name, u.username, u.email
                ORDER BY post_count DESC
                LIMIT 5
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get dashboard summary data
     */
    public function getDashboardSummary()
    {
        return [
            "total_posts" => $this->getTotalPosts(),
            "published_posts" => $this->getPublishedPosts(),
            "draft_posts" => $this->getDraftPosts(),
            "total_categories" => $this->getTotalCategories(),
            "total_users" => $this->getTotalUsers(),
            "total_comments" => $this->getTotalComments(),
            "pending_comments" => $this->getPendingComments(),
            "total_views" => $this->getTotalViews(),
            "recent_posts" => $this->getRecentPosts(),
            "popular_posts" => $this->getPopularPosts(),
            "recent_comments" => $this->getRecentComments(),
            "posts_by_category" => $this->getPostsByCategory(),
            "monthly_stats" => $this->getMonthlyStats(),
            "users_by_role" => $this->getUsersByRole(),
            "top_authors" => $this->getTopAuthors(),
        ];
    }

    /**
     * Get quick stats for dashboard cards
     */
    public function getQuickStats()
    {
        return [
            "posts" => [
                "total" => $this->getTotalPosts(),
                "published" => $this->getPublishedPosts(),
                "draft" => $this->getDraftPosts(),
            ],
            "comments" => [
                "total" => $this->getTotalComments(),
                "pending" => $this->getPendingComments(),
                "approved" =>
                    $this->getTotalComments() - $this->getPendingComments(),
            ],
            "users" => [
                "total" => $this->getTotalUsers(),
                "by_role" => $this->getUsersByRole(),
            ],
            "views" => [
                "total" => $this->getTotalViews(),
                "average" =>
                    $this->getTotalPosts() > 0
                        ? round($this->getTotalViews() / $this->getTotalPosts())
                        : 0,
            ],
        ];
    }

    /**
     * Format number for display (e.g., 1.2k, 1.5M)
     */
    public static function formatNumber($number)
    {
        if ($number >= 1000000) {
            return round($number / 1000000, 1) . "M";
        } elseif ($number >= 1000) {
            return round($number / 1000, 1) . "K";
        }
        return $number;
    }

    /**
     * Format date for display
     */
    public static function formatDate($date)
    {
        return date("M j, Y", strtotime($date));
    }

    /**
     * Get time ago format
     */
    public static function timeAgo($datetime)
    {
        $time = time() - strtotime($datetime);

        if ($time < 60) {
            return "just now";
        }
        if ($time < 3600) {
            return floor($time / 60) . " minutes ago";
        }
        if ($time < 86400) {
            return floor($time / 3600) . " hours ago";
        }
        if ($time < 2592000) {
            return floor($time / 86400) . " days ago";
        }
        if ($time < 31536000) {
            return floor($time / 2592000) . " months ago";
        }
        return floor($time / 31536000) . " years ago";
    }
}

// Create global instance for easy access
$dashboard = new DashboardData();
?>
