<?php
$config = require __DIR__ . '/../../../config/config.php';

$appUrl = rtrim($config['app_url'], '/');
$appName = $config['app_name'] ?? 'Royal Splash';

$currentUser = $_SESSION['user'] ?? null;
$userRole = $currentUser->role ?? null;

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$basePath = rtrim(parse_url($appUrl, PHP_URL_PATH) ?? '', '/');

$currentRelativePath = $currentPath;

if ($basePath !== '' && str_starts_with($currentRelativePath, $basePath)) {
    $currentRelativePath = substr($currentRelativePath, strlen($basePath));
}

if ($currentRelativePath === '') {
    $currentRelativePath = '/';
}

function nav_active(string $targetPath, string $currentRelativePath): string
{
    if ($targetPath === '/') {
        return $currentRelativePath === '/' ? 'active' : '';
    }

if ($targetPath === '/admin') {
    return $currentRelativePath === '/admin' ? 'active' : '';
}

return str_starts_with($currentRelativePath, $targetPath) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>
<?= htmlspecialchars($pageTitle ?? $appName) ?> | <?= htmlspecialchars($appName) ?>
</title>

<link rel="stylesheet" href="<?= $appUrl ?>/assets/css/styles.css">
</head>

<body>
<header class="site-header">
<div class="site-header-inner">
<a href="<?= $appUrl ?>/events" class="site-brand">
<img
src="<?= $appUrl ?>/assets/images/royal-splash-logo.png"
alt="Royal Splash"
onerror="this.style.display='none'"
>

<span>Royal Splash</span>
</a>

<input type="checkbox" id="navToggle" class="nav-toggle-checkbox">

<label for="navToggle" class="nav-toggle-button" aria-label="Toggle navigation">
<span></span>
<span></span>
<span></span>
</label>

<nav class="site-nav">
<?php if (!$currentUser): ?>
<a href="<?= $appUrl ?>/events" class="<?= nav_active('/events', $currentRelativePath) ?>">
Events
</a>

<a href="<?= $appUrl ?>/login" class="<?= nav_active('/login', $currentRelativePath) ?>">
Login
</a>

<a href="<?= $appUrl ?>/register" class="<?= nav_active('/register', $currentRelativePath) ?>">
Register
</a>

<?php elseif ($userRole === 'admin'): ?>
<a href="<?= $appUrl ?>/admin" class="<?= nav_active('/admin', $currentRelativePath) ?>">
Dashboard
</a>

<a href="<?= $appUrl ?>/admin/orders" class="<?= nav_active('/admin/orders', $currentRelativePath) ?>">
Orders
</a>

<a href="<?= $appUrl ?>/admin/tickets" class="<?= nav_active('/admin/tickets', $currentRelativePath) ?>">
Tickets
</a>

<a href="<?= $appUrl ?>/admin/events" class="<?= nav_active('/admin/events', $currentRelativePath) ?>">
Events
</a>

<a href="<?= $appUrl ?>/admin/ticket-categories" class="<?= nav_active('/admin/ticket-categories', $currentRelativePath) ?>">
Categories
</a>

<a href="<?= $appUrl ?>/admin/reports/scans" class="<?= nav_active('/admin/reports', $currentRelativePath) ?>">
Reports
</a>
<a href="<?= $appUrl ?>/admin/scanners" class="<?= nav_active('/admin/scanners', $currentRelativePath) ?>">
Staff
</a>
<a href="<?= $appUrl ?>/scanner" class="<?= nav_active('/scanner', $currentRelativePath) ?>">
Scanner
</a>

<a href="<?= $appUrl ?>/logout">
Logout
</a>

<?php elseif ($userRole === 'scanner'): ?>
<a href="<?= $appUrl ?>/scanner" class="<?= nav_active('/scanner', $currentRelativePath) ?>">
Scanner
</a>

<a href="<?= $appUrl ?>/events" class="<?= nav_active('/events', $currentRelativePath) ?>">
Events
</a>

<a href="<?= $appUrl ?>/logout">
Logout
</a>

<?php else: ?>
<a href="<?= $appUrl ?>/events" class="<?= nav_active('/events', $currentRelativePath) ?>">
Events
</a>

<a href="<?= $appUrl ?>/my-tickets" class="<?= nav_active('/my-tickets', $currentRelativePath) ?>">
My Tickets
</a>

<a href="<?= $appUrl ?>/logout">
Logout
</a>
<?php endif; ?>

<?php if ($currentUser): ?>
<span class="nav-user-pill">
<?= htmlspecialchars($currentUser->full_name ?? 'User') ?>
<small><?= htmlspecialchars(ucfirst($userRole ?? 'customer')) ?></small>
</span>
<?php endif; ?>
</nav>
</div>
</header>

<main class="site-main">
