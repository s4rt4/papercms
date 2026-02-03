<?php
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance()->getConnection();
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: blog.php');
    exit;
}

// Fetch post
$stmt = $db->prepare("
    SELECT p.*, c.name as category_name, c.slug as category_slug
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.slug = ? AND p.status = 'published'
");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    die('Post not found');
}

// Get tags
$tagStmt = $db->prepare("
    SELECT t.* FROM tags t
    JOIN post_tags pt ON t.id = pt.tag_id
    WHERE pt.post_id = ?
");
$tagStmt->execute([$post['id']]);
$postTags = $tagStmt->fetchAll();

// Get related posts (same category)
$relatedStmt = $db->prepare("
    SELECT id, title, slug, featured_image, published_at
    FROM posts
    WHERE category_id = ? AND id != ? AND status = 'published'
    ORDER BY published_at DESC
    LIMIT 3
");
$relatedStmt->execute([$post['category_id'], $post['id']]);
$relatedPosts = $relatedStmt->fetchAll();

$siteTitle = getSetting('site_title', 'Paper CMS');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($post['title']) ?> - <?= e($siteTitle) ?></title>
    <meta name="description" content="<?= e($post['excerpt']) ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= e($post['title']) ?>">
    <meta property="og:description" content="<?= e($post['excerpt']) ?>">
    <?php if ($post['featured_image']): ?>
        <meta property="og:image" content="<?= SITE_URL . '/' . e($post['featured_image']) ?>">
    <?php endif; ?>
    
    <!-- CRITICAL: Dark mode detection BEFORE any CSS -->
    <script>
        (function() {
            const saved = localStorage.getItem('paperCMS_darkMode');
            const isDark = saved === 'dark' || 
                (saved === null && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
            
            if (isDark) {
                document.documentElement.classList.add('dark');
                document.write('<link rel="stylesheet" id="prism-theme" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css">');
            } else {
                document.write('<link rel="stylesheet" id="prism-theme" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css">');
            }
        })();
    </script>
    
    <link rel="stylesheet" href="css/paper.css">
    <link rel="stylesheet" href="css/blog.css">
    
    <style>
        /* Dark Mode Toggle Button */
        .dark-mode-toggle {
            background: #f5f5f5;
            border: 2px solid #41403e;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border-bottom-left-radius: 255px 15px;
            border-bottom-right-radius: 15px 255px;
            border-top-left-radius: 15px 255px;
            border-top-right-radius: 255px 15px;
        }
        .dark-mode-toggle:hover {
            transform: scale(1.1);
            box-shadow: 2px 4px 8px rgba(0,0,0,0.2);
        }
        .dark-mode-toggle.fixed-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
        }
        .dark-mode-toggle .icon {
            position: absolute;
            width: 24px;
            height: 24px;
            background-color: #41403e;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }
        .dark-mode-toggle .icon-moon {
            transform: rotate(-180deg) scale(0);
            opacity: 0;
        }
        .dark-mode-toggle .icon-sun {
            transform: rotate(0) scale(1);
            opacity: 1;
        }
        html.dark .dark-mode-toggle {
            background: #2d2d2d;
            border-color: #555;
        }
        html.dark .dark-mode-toggle .icon {
            background-color: #e0e0e0;
        }
        html.dark .dark-mode-toggle .icon-moon {
            transform: rotate(0) scale(1);
            opacity: 1;
        }
        html.dark .dark-mode-toggle .icon-sun {
            transform: rotate(180deg) scale(0);
            opacity: 0;
        }
        
        /* Dark Mode Overrides for Post */
        html.dark body {
            background-color: #1a1a1a;
            color: #e0e0e0;
        }
        html.dark .navbar {
            background: #2d2d2d;
            border-color: #444;
        }
        html.dark .navbar a {
            color: #e0e0e0;
        }
        html.dark .card {
            background: #2d2d2d;
            border-color: #444;
        }
        html.dark h1, html.dark h2, html.dark h3, html.dark h4 {
            color: #fff;
        }
        html.dark a {
            color: #6db3f2;
        }
        html.dark footer {
            border-color: #444;
        }
        html.dark .post-content {
            color: #e0e0e0;
        }
        html.dark .badge {
            background: #444;
            color: #e0e0e0;
        }
        
        /* Navbar fix */
        .navbar {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
        }
        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        .navbar-brand {
            font-weight: bold;
            white-space: nowrap;
        }
        .navbar-menu {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        /* Wider container */
        .container {
            max-width: 1200px;
            padding-left: 2rem;
            padding-right: 2rem;
        }
        
        /* Wider article area */
        .article-content {
            max-width: 900px;
            margin: 0 auto;
        }
        
        /* Fix toggle icon */
        .dark-mode-toggle .icon {
            -webkit-mask-size: contain;
            mask-size: contain;
            -webkit-mask-repeat: no-repeat;
            mask-repeat: no-repeat;
            -webkit-mask-position: center;
            mask-position: center;
        }
        
        /* Calendar icon */
        .icon-calendar {
            display: inline-block;
            width: 16px;
            height: 16px;
            background-color: currentColor;
            -webkit-mask-image: url('assets/icons/calendar.svg');
            mask-image: url('assets/icons/calendar.svg');
            -webkit-mask-size: contain;
            mask-size: contain;
            -webkit-mask-repeat: no-repeat;
            mask-repeat: no-repeat;
            vertical-align: middle;
            margin-right: 4px;
        }
    </style>
</head>
<body>
    <nav class="navbar border fixed">
        <div class="container">
            <a href="index.php" class="navbar-brand"><?= e($siteTitle) ?></a>
            <div class="navbar-menu">
                <a href="index.php">Home</a>
                <a href="about.php">About</a>
                <a href="blog.php">Blog</a>
                <a href="contact.php">Contact</a>
            </div>
        </div>
    </nav>

    <article class="container margin-top-large padding-top-large">
        <div class="article-content">
                
                <!-- Post Header -->
                <header class="post-header margin-bottom-large">
                    <?php if ($post['category_name']): ?>
                        <a href="blog.php?category=<?= e($post['category_slug']) ?>" class="badge secondary">
                            <?= e($post['category_name']) ?>
                        </a>
                    <?php endif; ?>
                    
                    <h1 class="post-title-single"><?= e($post['title']) ?></h1>
                    
                    <div class="post-meta text-muted">
                        <span class="icon-calendar"></span>
                        <span><?= formatDate($post['published_at'], 'd F Y') ?></span>
                    </div>
                </header>

                <!-- Featured Image -->
                <?php if ($post['featured_image']): ?>
                    <figure class="post-featured-image margin-bottom-large">
                        <img src="<?= e($post['featured_image']) ?>" alt="<?= e($post['title']) ?>">
                    </figure>
                <?php endif; ?>

                <!-- Post Content -->
                <div class="post-content paper">
                    <?= $post['content'] ?>
                </div>

                <!-- Tags -->
                <?php if (!empty($postTags)): ?>
                    <div class="post-tags margin-top-large padding-top border-top">
                        <strong>Tags:</strong>
                        <?php foreach ($postTags as $tag): ?>
                            <a href="blog.php?tag=<?= e($tag['slug']) ?>" class="badge"><?= e($tag['name']) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Post Navigation -->
                <div class="post-navigation margin-top-large padding-top border-top">
                    <a href="blog.php" class="paper-btn">← Back to Blog</a>
                </div>

                <!-- Related Posts -->
                <?php if (!empty($relatedPosts)): ?>
                    <div class="related-posts margin-top-large padding-top border-top">
                        <h3>Related Posts</h3>
                        <div class="row">
                            <?php foreach ($relatedPosts as $related): ?>
                                <div class="col sm-12 md-4">
                                    <div class="card">
                                        <?php if ($related['featured_image']): ?>
                                            <a href="post.php?slug=<?= e($related['slug']) ?>">
                                                <img src="<?= e($related['featured_image']) ?>" class="related-thumb">
                                            </a>
                                        <?php endif; ?>
                                        <div class="card-body">
                                            <h4><a href="post.php?slug=<?= e($related['slug']) ?>"><?= e($related['title']) ?></a></h4>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

        </div>
    </article>

    <footer class="text-center padding margin-top-large border-top">
        <p>&copy; <?= date('Y') ?> <?= e($siteTitle) ?>. Powered by Paper CSS.</p>
    </footer>

    <!-- Dark Mode Toggle Button -->
    <button id="darkModeToggle" class="dark-mode-toggle fixed-toggle" title="Toggle Dark Mode (Ctrl+Shift+D)">
        <span class="icon icon-sun" style="mask-image: url('assets/icons/ui-icon_sun.svg'); -webkit-mask-image: url('assets/icons/ui-icon_sun.svg');"></span>
        <span class="icon icon-moon" style="mask-image: url('assets/icons/ui-icon_moon.svg'); -webkit-mask-image: url('assets/icons/ui-icon_moon.svg');"></span>
    </button>

    <!-- Prism.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-php.min.js"></script>
    <script src="assets/js/dark-mode.js"></script>
</body>
</html>
