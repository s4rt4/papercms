<?php
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance()->getConnection();

// Pagination
$page = (int)($_GET['page'] ?? 1);
$limit = (int)getSetting('posts_per_page', 10);
$offset = ($page - 1) * $limit;

// Filter by category
$categorySlug = $_GET['category'] ?? null;
$tagSlug = $_GET['tag'] ?? null;

$where = "WHERE p.status = 'published'";
$params = [];

if ($categorySlug) {
    $where .= " AND c.slug = ?";
    $params[] = $categorySlug;
}

if ($tagSlug) {
    $where .= " AND p.id IN (SELECT post_id FROM post_tags pt JOIN tags t ON pt.tag_id = t.id WHERE t.slug = ?)";
    $params[] = $tagSlug;
}

// Count total
$countSql = "SELECT COUNT(*) as total FROM posts p LEFT JOIN categories c ON p.category_id = c.id $where";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$total = $countStmt->fetch()['total'];
$totalPages = ceil($total / $limit);

// Fetch posts
$sql = "
    SELECT p.*, c.name as category_name, c.slug as category_slug
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    $where
    ORDER BY p.published_at DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

// Get all categories for sidebar
$categories = $db->query("
    SELECT c.*, (SELECT COUNT(*) FROM posts WHERE category_id = c.id AND status = 'published') as count
    FROM categories c
    ORDER BY name
")->fetchAll();

// Get recent tags
$tags = $db->query("
    SELECT t.*, COUNT(pt.post_id) as count
    FROM tags t
    JOIN post_tags pt ON t.id = pt.tag_id
    JOIN posts p ON pt.post_id = p.id AND p.status = 'published'
    GROUP BY t.id
    ORDER BY count DESC
    LIMIT 20
")->fetchAll();

$siteTitle = getSetting('site_title', 'Paper CMS');
$siteDesc = getSetting('site_description', '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($siteTitle) ?></title>
    
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
        
        /* Dark Mode Overrides for Blog */
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
        html.dark .card-header {
            background: #333;
            border-color: #444;
        }
        html.dark h1, html.dark h2, html.dark h3 {
            color: #fff;
        }
        html.dark a {
            color: #6db3f2;
        }
        html.dark footer {
            border-color: #444;
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
        .navbar-title {
            font-size: 0.9rem;
            color: #666;
            margin-left: 1rem;
            padding-left: 1rem;
            border-left: 1px solid #ddd;
        }
        html.dark .navbar-title {
            color: #aaa;
            border-color: #555;
        }
        
        /* 2-Column Post Grid */
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        @media (max-width: 768px) {
            .posts-grid {
                grid-template-columns: 1fr;
            }
        }
        .post-card {
            margin-bottom: 0 !important;
        }
        .post-card .post-thumbnail {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        .post-card .card-body {
            padding: 1rem;
        }
        .post-card .post-title {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        .post-card .post-excerpt {
            font-size: 0.9rem;
            line-height: 1.4;
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
        
        /* Wider container */
        .container {
            max-width: 1400px;
            padding-left: 2rem;
            padding-right: 2rem;
        }
        
        /* Scrollable sidebar sections */
        .sidebar-scroll {
            max-height: 200px;
            overflow-y: auto;
        }
        .category-list {
            margin: 0;
            padding-left: 1.2rem;
        }
        .category-list li {
            margin-bottom: 0.3rem;
        }
        .tag-cloud {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
        }
    </style>
</head>
<body>
    <nav class="navbar border fixed">
        <div class="container">
            <div style="display:flex;align-items:center;">
                <a href="blog.php" class="navbar-brand"><?= e($siteTitle) ?></a>
                <span class="navbar-title">
                    <?php if ($categorySlug): ?>
                        Category: <?= e($categorySlug) ?>
                    <?php elseif ($tagSlug): ?>
                        Tag: <?= e($tagSlug) ?>
                    <?php else: ?>
                        Latest Posts
                    <?php endif; ?>
                </span>
            </div>
            <div class="navbar-menu">
                <a href="blog.php">Blog</a>
                <a href="tambah-post.php">+ New Post</a>
            </div>
        </div>
    </nav>

    <div class="container margin-top-large padding-top-large">
        <div class="row">
            <!-- Main Content -->
            <div class="col sm-12 md-8">
                <?php if (empty($posts)): ?>
                    <div class="card">
                        <div class="card-body text-center">
                            <p>No posts found.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="posts-grid">
                        <?php foreach ($posts as $post): ?>
                            <article class="card post-card">
                                <?php if ($post['featured_image']): ?>
                                    <a href="post.php?slug=<?= e($post['slug']) ?>">
                                        <img src="<?= e($post['featured_image']) ?>" class="post-thumbnail" alt="<?= e($post['title']) ?>">
                                    </a>
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <h2 class="post-title">
                                        <a href="post.php?slug=<?= e($post['slug']) ?>"><?= e($post['title']) ?></a>
                                    </h2>
                                    
                                    <div class="post-meta text-muted margin-bottom-small" style="font-size:0.8rem;">
                                        <span><?= formatDate($post['published_at']) ?></span>
                                        <?php if ($post['category_name']): ?>
                                            <span> • </span>
                                            <a href="blog.php?category=<?= e($post['category_slug']) ?>"><?= e($post['category_name']) ?></a>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <p class="post-excerpt"><?= e(substr($post['excerpt'], 0, 100)) ?>...</p>
                                    
                                    <a href="post.php?slug=<?= e($post['slug']) ?>" class="paper-btn btn-small">Read More</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>" class="paper-btn btn-small">← Prev</a>
                            <?php endif; ?>
                            
                            <span class="page-info">Page <?= $page ?> of <?= $totalPages ?></span>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= $page + 1 ?>" class="paper-btn btn-small">Next →</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col sm-12 md-4">
                <!-- Categories -->
                <div class="card margin-bottom">
                    <div class="card-header">Categories</div>
                    <div class="card-body sidebar-scroll">
                        <ul class="category-list">
                            <?php foreach ($categories as $cat): ?>
                                <li>
                                    <a href="blog.php?category=<?= e($cat['slug']) ?>">
                                        <?= e($cat['name']) ?> (<?= $cat['count'] ?>)
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Tags -->
                <div class="card margin-bottom">
                    <div class="card-header">Tags</div>
                    <div class="card-body sidebar-scroll">
                        <div class="tag-cloud">
                            <?php foreach ($tags as $tag): ?>
                                <a href="blog.php?tag=<?= e($tag['slug']) ?>" class="badge"><?= e($tag['name']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center padding margin-top-large border-top">
        <p>&copy; <?= date('Y') ?> <?= e($siteTitle) ?>. Powered by Paper CSS.</p>
    </footer>

    <!-- Dark Mode Toggle Button -->
    <button id="darkModeToggle" class="dark-mode-toggle fixed-toggle" title="Toggle Dark Mode (Ctrl+Shift+D)">
        <span class="icon icon-sun" style="mask-image: url('assets/icons/ui-icon_sun.svg'); -webkit-mask-image: url('assets/icons/ui-icon_sun.svg');"></span>
        <span class="icon icon-moon" style="mask-image: url('assets/icons/ui-icon_moon.svg'); -webkit-mask-image: url('assets/icons/ui-icon_moon.svg');"></span>
    </button>

    <script src="assets/js/dark-mode.js"></script>
</body>
</html>
