<?php
/**
 * Page template part for static pages (non-WP)
 * Expects `$page` array with keys: id, title, content, created_at, author_name
 */
if (!isset($page) || !is_array($page)) return;
?>
<article id="page-<?php echo htmlspecialchars($page['id']); ?>" class="page hentry" style="margin-bottom: 40px;">
    <header class="entry-header">
        <h1 class="entry-title"><?php echo htmlspecialchars($page['title']); ?></h1>
        <div class="entry-meta"><span class="posted-on"><?php echo date('M d, Y', strtotime($page['created_at'])); ?></span></div>
    </header>
    <div class="entry-content">
        <?php echo $page['content']; ?>
    </div>
</article>
