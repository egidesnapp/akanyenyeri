<?php
/**
 * Archive content template (non-WP)
 * Expects `$post` array with keys: id, title, slug, excerpt, content, featured_image, created_at, author_name, category_name, category_slug
 */
if (!isset($post) || !is_array($post)) {
    return;
}

function theme_get_excerpt($content, $length = 40) {
    $text = strip_tags($content);
    $words = preg_split('/\s+/', $text);
    if (count($words) > $length) {
        return implode(' ', array_slice($words, 0, $length)) . '...';
    }
    return $text;
}
?>
<article id="post-<?php echo htmlspecialchars($post['id']); ?>" class="post type-post status-publish format-standard<?php echo $post['featured_image'] ? ' has-post-thumbnail' : ''; ?> hentry">
    <div class="rectified-magazine-content-container <?php echo $post['featured_image'] ? 'rectified-magazine-has-thumbnail' : 'rectified-magazine-no-thumbnail'; ?>">
        <div class="rectified-magazine-content-area">
            <header class="entry-header">
                <div class="entry-meta">
                    <span class="posted-on"><i class="fa fa-clock-o"></i> <time class="entry-date published"><?php echo date('M d, Y', strtotime($post['created_at'])); ?></time></span>
                    <span class="byline"> <i class="fa fa-user"></i> <span class="author vcard"><?php echo htmlspecialchars($post['author_name']); ?></span></span>
                </div>
                <h2 class="entry-title" style="font-size: 24px; margin: 10px 0;"><a href="single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" rel="bookmark"><?php echo htmlspecialchars($post['title']); ?></a></h2>
            </header>

            <?php if($post['featured_image']): ?>
            <div class="post-thumb" style="margin-bottom: 20px;">
                <div class="post-meta">
                    <span class="cat-links"><a href="category.php?slug=<?php echo htmlspecialchars($post['category_slug']); ?>"><?php echo htmlspecialchars($post['category_name']); ?></a></span>
                </div>
                <a href="single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>">
                    <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" style="width: 100%; height: auto; border-radius: 4px; transition: transform 0.3s ease;">
                </a>
            </div>
            <?php endif; ?>

            <div class="entry-content">
                <p><?php echo theme_get_excerpt($post['excerpt'] ? $post['excerpt'] : $post['content'], 40); ?></p>
                <p><a href="single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="read-more-text" style="text-transform: uppercase; font-weight: bold; font-size: 14px;">Read More</a></p>
            </div>
        </div>
    </div>
</article>
