<?php
/**
 * Video format content template (non-WP)
 * Expects `$post` array
 */
if (!isset($post) || !is_array($post)) {
    return;
}
?>
<article id="post-<?php echo htmlspecialchars($post['id']); ?>" class="post type-post format-video hentry">
    <div class="rectified-magazine-content-container">
        <div class="rectified-magazine-content-area">
            <header class="entry-header">
                <div class="entry-meta">
                    <span class="posted-on"><i class="fa fa-clock-o"></i> <time class="entry-date published"><?php echo date('M d, Y', strtotime($post['created_at'])); ?></time></span>
                    <span class="byline"> <i class="fa fa-user"></i> <span class="author vcard"><?php echo htmlspecialchars($post['author_name']); ?></span></span>
                </div>
                <h2 class="entry-title" style="font-size: 24px; margin: 10px 0;"><a href="single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" rel="bookmark"><?php echo htmlspecialchars($post['title']); ?></a></h2>
            </header>

            <?php if($post['featured_image']): ?>
            <div class="post-thumb" style="margin-bottom: 20px; position:relative;">
                <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" style="width: 100%; height: auto; border-radius: 4px;">
                <a href="single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:60px; height:60px; background:#3182ce; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-size:24px;">
                    <i class="fa fa-play"></i>
                </a>
            </div>
            <?php endif; ?>

            <div class="entry-content">
                <p><?php echo htmlspecialchars(substr($post['excerpt'] ? $post['excerpt'] : $post['content'], 0, 100)); ?></p>
                <p><a href="single.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="read-more-text" style="text-transform: uppercase; font-weight: bold; font-size: 14px;">Watch Video</a></p>
            </div>
        </div>
    </div>
</article>
