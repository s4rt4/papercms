<?php
/**
 * Public Header Navigation Component
 * Include this at the top of all public pages
 */

$siteTitle = getSetting('site_title', 'Paper CMS');
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!-- CRITICAL: Dark mode detection BEFORE any CSS -->
<script>
    (function() {
        const saved = localStorage.getItem('paperCMS_darkMode');
        const isDark = saved === 'dark' || 
            (saved === null && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
        
        if (isDark) {
            document.documentElement.classList.add('dark');
        }
    })();
</script>

<link rel="stylesheet" href="css/paper.css">
<link rel="stylesheet" href="css/public.css">

<nav class="main-nav">
    <div class="nav-container">
        <a href="index.php" class="nav-logo"><?= e($siteTitle) ?></a>
        <button class="nav-toggle" aria-label="Toggle menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <ul class="nav-menu">
            <li><a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Home</a></li>
            <li><a href="about.php" class="<?= $currentPage === 'about.php' ? 'active' : '' ?>">About Me</a></li>
            <li><a href="blog.php" class="<?= $currentPage === 'blog.php' ? 'active' : '' ?>">Blog</a></li>
            <li><a href="contact.php" class="<?= $currentPage === 'contact.php' ? 'active' : '' ?>">Contact</a></li>
        </ul>
    </div>
</nav>
