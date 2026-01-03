<?php
// Simple file-based cache for simple_news
function cache_get($key, $ttl = 60) {
    $dir = __DIR__ . '/cache';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $file = $dir . '/' . preg_replace('/[^a-z0-9_\-]/i','_', $key) . '.json';
    if (!file_exists($file)) return false;
    $meta = json_decode(@file_get_contents($file), true);
    if (!$meta) return false;
    if (time() - ($meta['ts'] ?? 0) > $ttl) { @unlink($file); return false; }
    return $meta['data'] ?? false;
}

function cache_set($key, $data) {
    $dir = __DIR__ . '/cache';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $file = $dir . '/' . preg_replace('/[^a-z0-9_\-]/i','_', $key) . '.json';
    $meta = ['ts' => time(), 'data' => $data];
    @file_put_contents($file, json_encode($meta));
}

function cache_clear_all() {
    $dir = __DIR__ . '/cache';
    if (!is_dir($dir)) return;
    $files = @glob($dir . '/*.json');
    if ($files) {
        foreach ($files as $file) {
            @unlink($file);
        }
    }
}

function cache_clear_posts() {
    $dir = __DIR__ . '/cache';
    if (!is_dir($dir)) return;
    $files = @glob($dir . '/posts_*.json');
    if ($files) {
        foreach ($files as $file) {
            @unlink($file);
        }
    }
}

