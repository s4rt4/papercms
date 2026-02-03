<?php
/**
 * Public Footer Component
 * Include this at the bottom of all public pages
 */

$siteTitle = getSetting('site_title', 'Paper CMS');
$ownerName = getSetting('site_owner_name', 'Paper CMS');
?>
<footer class="main-footer">
    <div class="footer-container">
        <div class="footer-content">
            <p>&copy; <?= date('Y') ?> <?= e($ownerName) ?>. All rights reserved.</p>
            <p class="footer-powered">Powered by <a href="https://github.com/s4rt4/papercms" target="_blank">Paper CMS</a></p>
        </div>
    </div>
</footer>

<!-- Dark Mode Toggle Button -->
<button id="darkModeToggle" class="dark-mode-toggle fixed-toggle" title="Toggle Dark Mode">
    <span class="icon icon-sun" style="mask-image: url('assets/icons/ui-icon_sun.svg'); -webkit-mask-image: url('assets/icons/ui-icon_sun.svg');"></span>
    <span class="icon icon-moon" style="mask-image: url('assets/icons/ui-icon_moon.svg'); -webkit-mask-image: url('assets/icons/ui-icon_moon.svg');"></span>
</button>

<script src="assets/js/dark-mode.js"></script>
<script>
// Mobile nav toggle
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.querySelector('.nav-toggle');
    const menu = document.querySelector('.nav-menu');
    
    if (toggle && menu) {
        toggle.addEventListener('click', function() {
            menu.classList.toggle('active');
            toggle.classList.toggle('active');
        });
    }
});
</script>
