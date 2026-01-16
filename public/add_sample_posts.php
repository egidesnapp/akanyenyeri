<?php
/**
 * Add Sample Posts to Database
 * This script adds some sample published posts to demonstrate the magazine functionality
 */

require_once __DIR__ . '/../database/config/database.php';

// Get database connection
$pdo = getDB();

try {
    // Sample posts data
    $samplePosts = [
        [
            'title' => 'Breaking: Major Tech Merger Announced',
            'slug' => 'breaking-major-tech-merger-announced',
            'content' => '<p>In a stunning development that will reshape the technology landscape, two of Silicon Valley\'s biggest players announced a historic merger today. The $250 billion deal promises to create the world\'s largest technology company by market capitalization.</p><p>The merger, which still requires regulatory approval, is expected to face intense scrutiny from antitrust authorities. Industry analysts predict this could lead to unprecedented innovation in artificial intelligence and cloud computing.</p><p>Stock prices for both companies surged following the announcement, with investors betting on the combined entity\'s ability to dominate emerging technologies like quantum computing and autonomous vehicles.</p>',
            'excerpt' => 'Two Silicon Valley giants announce a historic $250 billion merger that could reshape the technology industry.',
            'featured_image' => 'https://via.placeholder.com/800x400/3498db/ffffff?text=Tech+Merger+News',
            'category_id' => 4, // Technology
            'author_id' => 1,
            'status' => 'published',
            'is_featured' => 1,
            'views' => 1250
        ],
        [
            'title' => 'Global Economy Shows Signs of Recovery',
            'slug' => 'global-economy-shows-signs-of-recovery',
            'content' => '<p>Economic indicators from around the world point to a stronger-than-expected recovery from the recent global slowdown. Manufacturing data, employment figures, and consumer spending all show positive trends that economists hadn\'t anticipated.</p><p>The latest GDP figures released today indicate a 3.2% growth rate for the quarter, surpassing all analyst expectations. Central banks worldwide are now debating when to begin normalizing monetary policy.</p><p>However, experts warn that inflation remains a concern, with energy prices continuing to put pressure on household budgets. The housing market shows mixed signals, with some regions experiencing price corrections while others continue to boom.</p>',
            'excerpt' => 'Economic data shows stronger recovery than expected, with GDP growth surprising analysts worldwide.',
            'featured_image' => 'https://via.placeholder.com/800x400/27ae60/ffffff?text=Economy+Recovery',
            'category_id' => 3, // Business
            'author_id' => 1,
            'status' => 'published',
            'is_featured' => 0,
            'views' => 890
        ],
        [
            'title' => 'New Climate Agreement Signed by 50 Nations',
            'slug' => 'new-climate-agreement-signed-by-50-nations',
            'content' => '<p>Fifty nations have signed a groundbreaking climate agreement that commits signatories to ambitious carbon reduction targets. The accord, signed in the capital today, represents the most comprehensive international effort to combat climate change in decades.</p><p>The agreement includes binding commitments to reduce greenhouse gas emissions by 50% by 2030, with developed nations pledging financial support to help developing countries transition to clean energy sources.</p><p>Environmental groups praised the agreement as a "turning point" in global climate action, while some critics argue the targets aren\'t ambitious enough. Implementation will be key, with monitoring mechanisms built into the treaty to ensure compliance.</p>',
            'excerpt' => 'Fifty nations sign historic climate accord committing to 50% emissions reduction by 2030.',
            'featured_image' => 'https://via.placeholder.com/800x400/2ecc71/ffffff?text=Climate+Agreement',
            'category_id' => 1, // Politics
            'author_id' => 1,
            'status' => 'published',
            'is_featured' => 0,
            'views' => 675
        ],
        [
            'title' => 'Championship Final Breaks Viewership Records',
            'slug' => 'championship-final-breaks-viewership-records',
            'content' => '<p>The championship final shattered all previous viewership records, with over 150 million people tuning in worldwide to watch the dramatic conclusion. The match, which went into extra time and penalties, kept fans on the edge of their seats for over two hours.</p><p>The winning goal, scored in the 89th minute, became an instant classic and is already being hailed as one of the greatest moments in sports history. Social media exploded with reactions, with the game\'s hashtag trending worldwide for hours after the final whistle.</p><p>Broadcasters reported record advertising revenue, while the winning team\'s jersey became the best-selling sports merchandise item of all time within 24 hours of the victory.</p>',
            'excerpt' => 'Championship final sets new viewership record with 150 million global viewers.',
            'featured_image' => 'https://via.placeholder.com/800x400/e74c3c/ffffff?text=Sports+Championship',
            'category_id' => 2, // Sports
            'author_id' => 1,
            'status' => 'published',
            'is_featured' => 1,
            'views' => 2100
        ],
        [
            'title' => 'Medical Breakthrough in Cancer Treatment',
            'slug' => 'medical-breakthrough-in-cancer-treatment',
            'content' => '<p>Scientists have announced a major breakthrough in cancer treatment that could revolutionize how the disease is fought. The new therapy, which combines immunotherapy with targeted genetic treatments, has shown unprecedented success rates in clinical trials.</p><p>The treatment works by reprogramming the patient\'s own immune system to recognize and destroy cancer cells while leaving healthy cells unharmed. Early results from Phase 3 trials show an 85% success rate, far higher than traditional treatments.</p><p>Medical experts are calling this the most significant advancement in oncology since the development of chemotherapy. The treatment is expected to be available to patients within the next 18 months, pending regulatory approval.</p>',
            'excerpt' => 'Scientists announce breakthrough cancer therapy with 85% success rate in clinical trials.',
            'featured_image' => 'https://via.placeholder.com/800x400/9b59b6/ffffff?text=Cancer+Breakthrough',
            'category_id' => 5, // Health/Entertainment (we'll use Entertainment for now)
            'author_id' => 1,
            'status' => 'published',
            'is_featured' => 0,
            'views' => 1450
        ]
    ];

    $inserted = 0;

    foreach ($samplePosts as $post) {
        // Check if post already exists
        $stmt = $pdo->prepare("SELECT id FROM posts WHERE slug = ?");
        $stmt->execute([$post['slug']]);
        $existing = $stmt->fetch();

        if (!$existing) {
            // Insert the post
            $stmt = $pdo->prepare("
                INSERT INTO posts (title, slug, content, excerpt, featured_image, category_id, author_id, status, is_featured, views, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW() - INTERVAL ? DAY)
            ");

            $daysAgo = rand(0, 30); // Random days ago for variety
            $stmt->execute([
                $post['title'],
                $post['slug'],
                $post['content'],
                $post['excerpt'],
                $post['featured_image'],
                $post['category_id'],
                $post['author_id'],
                $post['status'],
                $post['is_featured'],
                $post['views'],
                $daysAgo
            ]);

            $inserted++;
        }
    }

    echo "<h2>Sample Posts Added Successfully!</h2>";
    echo "<p>Inserted $inserted new sample posts into the database.</p>";
    echo "<p><a href='index.php'>View the website</a> to see the posts.</p>";
    echo "<p><a href='../admin/login.php'>Go to Admin Panel</a> to manage posts.</p>";

} catch (Exception $e) {
    echo "<h2>Error Adding Sample Posts</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
