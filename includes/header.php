<?php
/**
 * Master Header Partial
 * Injects HTML5 head, Tailwind CDN, theme variables, fonts & active theme attribute
 */
require_once __DIR__ . '/app.php';

$page_title = $page_title ?? get_setting('hospital_name', APP_NAME);
$root = get_root_url();
$active_theme = get_setting('theme', $APP_CONFIG['theme'] ?? 'default');
if (isset($_COOKIE['mhp_theme']) && in_array($_COOKIE['mhp_theme'], ['default', 'emerald', 'royal', 'warm'])) {
    $active_theme = $_COOKIE['mhp_theme'];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= e($active_theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> — <?= e(get_setting('hospital_short_name', APP_SHORT_NAME)) ?> ERP</title>
    <link rel="icon" type="image/svg+xml" href="<?= $root ?>assets/images/favicon.svg">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chart.js for ERP analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="<?= $root ?>assets/css/themes.css">
    <link rel="stylesheet" href="<?= $root ?>assets/css/app.css">
</head>
<body class="bg-appbg text-apptext flex h-screen overflow-hidden antialiased">
