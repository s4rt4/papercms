<?php
/**
 * Contact Page - Paper CMS
 */
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance()->getConnection();

// Get site settings
$siteTitle = getSetting('site_title', 'Paper CMS');
$ownerName = getSetting('site_owner_name', 'John Doe');
$ownerAvatar = getSetting('site_owner_avatar', 'assets/img/index_avatar-male-1.png');
$siteAddress = getSetting('site_address', 'Your Address Here');
$sitePhone = getSetting('site_phone', '+62 123 456 789');
$siteEmail = getSetting('site_email', 'hello@example.com');
$socialFacebook = getSetting('social_facebook', 'https://facebook.com');
$socialInstagram = getSetting('social_instagram', 'https://instagram.com');
$socialTwitter = getSetting('social_twitter', 'https://twitter.com');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - <?= e($siteTitle) ?></title>
    <?php include 'includes/header.php'; ?>
</head>
<body>
    <div class="page-content">
        <section class="section">
            <h1 class="section-title">Contact Me</h1>
            
            <div class="contact-section">
                <!-- Profile & Contact Info -->
                <div class="contact-info">
                    <div class="text-center mb-3">
                        <img src="<?= e($ownerAvatar) ?>" alt="<?= e($ownerName) ?>" class="profile-avatar">
                        <h2><?= e($ownerName) ?></h2>
                    </div>
                    
                    <!-- Address -->
                    <div class="contact-item">
                        <span class="contact-icon" style="mask-image: url('assets/icons/location-pin.svg'); -webkit-mask-image: url('assets/icons/location-pin.svg');"></span>
                        <div class="contact-item-text">
                            <h4>Address</h4>
                            <p><?= e($siteAddress) ?></p>
                        </div>
                    </div>
                    
                    <!-- Phone -->
                    <div class="contact-item">
                        <span class="contact-icon" style="mask-image: url('assets/icons/call.svg'); -webkit-mask-image: url('assets/icons/call.svg');"></span>
                        <div class="contact-item-text">
                            <h4>Phone</h4>
                            <p><a href="tel:<?= e($sitePhone) ?>"><?= e($sitePhone) ?></a></p>
                        </div>
                    </div>
                    
                    <!-- Email -->
                    <div class="contact-item">
                        <span class="contact-icon" style="mask-image: url('assets/icons/mail.svg'); -webkit-mask-image: url('assets/icons/mail.svg');"></span>
                        <div class="contact-item-text">
                            <h4>Email</h4>
                            <p><a href="mailto:<?= e($siteEmail) ?>"><?= e($siteEmail) ?></a></p>
                        </div>
                    </div>
                    
                    <!-- Social Links -->
                    <div class="social-links">
                        <?php if ($socialFacebook): ?>
                            <a href="<?= e($socialFacebook) ?>" class="social-link" target="_blank" title="Facebook">
                                <img src="assets/icons/facebook.svg" alt="Facebook" width="24" height="24">
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($socialInstagram): ?>
                            <a href="<?= e($socialInstagram) ?>" class="social-link" target="_blank" title="Instagram">
                                <img src="assets/icons/instagram.svg" alt="Instagram" width="24" height="24">
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($socialTwitter): ?>
                            <a href="<?= e($socialTwitter) ?>" class="social-link" target="_blank" title="Twitter">
                                <img src="assets/icons/twitter.svg" alt="Twitter" width="24" height="24">
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Contact Form (optional, redirect to index form) -->
                <div class="quote-section" style="flex: 1; min-width: 300px; margin: 0;">
                    <h3 class="text-center mb-2">Send a Message</h3>
                    <form method="POST" action="index.php" class="quote-form">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name" required placeholder="Your name">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required placeholder="your@email.com">
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" rows="4" placeholder="Your message..."></textarea>
                        </div>
                        <button type="submit" name="quote_submit" class="btn btn-accent" style="width: 100%;">Send Message</button>
                    </form>
                </div>
            </div>
        </section>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
