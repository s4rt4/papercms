<?php
/**
 * Homepage - Paper CMS
 */
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance()->getConnection();

// Get latest published posts (4 for carousel)
$latestPosts = $db->query("
    SELECT p.*, c.name as category_name, c.slug as category_slug
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.status = 'published'
    ORDER BY p.published_at DESC
    LIMIT 4
")->fetchAll();

// Get site settings
$siteTitle = getSetting('site_title', 'Paper CMS');

// Handle quote form submission
$formMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quote_submit'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if ($name && $email) {
        try {
            $stmt = $db->prepare("INSERT INTO contact_submissions (name, email, message) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $message]);
            $formMessage = '<div class="alert alert-success">Thank you! Your message has been sent.</div>';
        } catch (Exception $e) {
            $formMessage = '<div class="alert alert-error">Sorry, there was an error. Please try again.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($siteTitle) ?> - Home</title>
    <?php include 'includes/header.php'; ?>
    <style>
        /* Carousel Styles */
        .carousel-container {
            position: relative;
            overflow: hidden;
            margin: 0 -2rem;
            padding: 0 2rem;
        }
        .carousel-track {
            display: flex;
            gap: 1.5rem;
            transition: transform 0.5s ease;
        }
        .carousel-slide {
            flex: 0 0 calc(25% - 1.125rem);
            min-width: 280px;
        }
        .carousel-nav {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .carousel-btn {
            width: 40px;
            height: 40px;
            border: 2px solid var(--primary-color);
            background: var(--card-bg);
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.3s;
        }
        .carousel-btn:hover {
            background: var(--primary-color);
            color: #fff;
        }
        @media (max-width: 992px) {
            .carousel-slide {
                flex: 0 0 calc(50% - 0.75rem);
            }
        }
        @media (max-width: 576px) {
            .carousel-slide {
                flex: 0 0 100%;
            }
        }
    </style>
</head>
<body>
    <div class="page-content">
        <!-- Hero Section - Full Width Image Only -->
        <section class="hero-banner">
            <img src="assets/img/index_hero-banner.png" alt="Hero Banner">
        </section>

        <!-- Latest Articles Carousel -->
        <section class="section">
            <h2 class="section-title">Latest Articles</h2>
            
            <?php if (empty($latestPosts)): ?>
                <p class="text-center text-muted">No articles yet. Check back soon!</p>
            <?php else: ?>
                <div class="carousel-container">
                    <div class="carousel-track" id="articleCarousel">
                        <?php foreach ($latestPosts as $post): ?>
                            <article class="carousel-slide article-card">
                                <?php if ($post['featured_image']): ?>
                                    <a href="post.php?slug=<?= e($post['slug']) ?>">
                                        <img src="<?= e($post['featured_image']) ?>" alt="<?= e($post['title']) ?>">
                                    </a>
                                <?php else: ?>
                                    <div style="height: 180px; background: linear-gradient(135deg, #888, #aaa);"></div>
                                <?php endif; ?>
                                <div class="article-card-body">
                                    <div class="meta">
                                        <?= formatDate($post['published_at']) ?>
                                        <?php if ($post['category_name']): ?>
                                            • <a href="blog.php?category=<?= e($post['category_slug']) ?>"><?= e($post['category_name']) ?></a>
                                        <?php endif; ?>
                                    </div>
                                    <h3><a href="post.php?slug=<?= e($post['slug']) ?>"><?= e($post['title']) ?></a></h3>
                                    <p><?= e(substr($post['excerpt'] ?? strip_tags($post['content']), 0, 100)) ?>...</p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="carousel-nav">
                    <button class="carousel-btn" onclick="moveCarousel(-1)">←</button>
                    <button class="carousel-btn" onclick="moveCarousel(1)">→</button>
                </div>
            <?php endif; ?>
        </section>

        <!-- Get a Quote Section -->
        <section class="section">
            <div class="quote-section">
                <h2 class="section-title">Get in Touch</h2>
                <p class="text-center text-muted mb-3">Have a question or want to work together? Drop me a message!</p>
                
                <?= $formMessage ?>
                
                <form method="POST" class="quote-form">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" required placeholder="Your name">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required placeholder="your@email.com">
                    </div>
                    <div class="form-group">
                        <label for="message">Message (optional)</label>
                        <textarea id="message" name="message" rows="4" placeholder="Tell me about your project..."></textarea>
                    </div>
                    <button type="submit" name="quote_submit" class="btn" style="width: 100%;">Send Message</button>
                </form>
            </div>
        </section>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Carousel functionality
        let carouselPosition = 0;
        const carousel = document.getElementById('articleCarousel');
        const slides = carousel ? carousel.querySelectorAll('.carousel-slide') : [];
        
        function getVisibleSlides() {
            if (window.innerWidth <= 576) return 1;
            if (window.innerWidth <= 992) return 2;
            return 4;
        }
        
        function moveCarousel(direction) {
            if (!carousel || slides.length === 0) return;
            
            const visibleSlides = getVisibleSlides();
            const maxPosition = Math.max(0, slides.length - visibleSlides);
            
            carouselPosition += direction;
            if (carouselPosition < 0) carouselPosition = 0;
            if (carouselPosition > maxPosition) carouselPosition = maxPosition;
            
            const slideWidth = slides[0].offsetWidth + 24; // 24px gap
            carousel.style.transform = `translateX(-${carouselPosition * slideWidth}px)`;
        }
    </script>
</body>
</html>
