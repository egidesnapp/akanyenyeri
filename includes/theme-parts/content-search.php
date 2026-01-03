<?php
/**
 * Search result item renderer (non-WP)
 * Expects `$post` array and optional `$aos_delay`.
 */
if (!isset($post) || !is_array($post)) return;

// Reuse content.php logic; keep minimal here
include __DIR__ . '/content.php';
