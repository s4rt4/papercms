<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';

$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    // ========================================
    // GET - Fetch posts
    // ========================================
    case 'GET':
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            // Single post
            $stmt = $db->prepare("
                SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM posts p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            $post = $stmt->fetch();
            
            if ($post) {
                // Get tags
                $stmt = $db->prepare("
                    SELECT t.* FROM tags t
                    JOIN post_tags pt ON t.id = pt.tag_id
                    WHERE pt.post_id = ?
                ");
                $stmt->execute([$id]);
                $post['tags'] = $stmt->fetchAll();
                
                jsonResponse(['success' => true, 'post' => $post]);
            } else {
                jsonResponse(['success' => false, 'message' => 'Post not found'], 404);
            }
        } else {
            // List posts
            $status = $_GET['status'] ?? 'all';
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            $where = "WHERE 1=1";
            $params = [];
            
            if ($status !== 'all') {
                $where .= " AND p.status = ?";
                $params[] = $status;
            }
            
            // Count total
            $countStmt = $db->prepare("SELECT COUNT(*) as total FROM posts p $where");
            $countStmt->execute($params);
            $total = $countStmt->fetch()['total'];
            
            // Fetch posts
            $sql = "
                SELECT p.*, c.name as category_name
                FROM posts p
                LEFT JOIN categories c ON p.category_id = c.id
                $where
                ORDER BY p.created_at DESC
                LIMIT $limit OFFSET $offset
            ";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $posts = $stmt->fetchAll();
            
            jsonResponse([
                'success' => true,
                'posts' => $posts,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ]);
        }
        break;
    
    // ========================================
    // POST - Create post
    // ========================================
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        
        $title = trim($input['title'] ?? '');
        $content = $input['content'] ?? '';
        $excerpt = $input['excerpt'] ?? generateExcerpt($content);
        $status = $input['status'] ?? 'draft';
        // Support both single category_id and multiple category_ids
        $categoryIds = $input['category_ids'] ?? [];
        $categoryId = !empty($categoryIds) ? $categoryIds[0] : ($input['category_id'] ?? null);
        $featuredImage = $input['featured_image'] ?? null;
        $tags = $input['tags'] ?? [];
        
        if (empty($title)) {
            jsonResponse(['success' => false, 'message' => 'Title is required'], 400);
        }
        
        $slug = uniqueSlug(slugify($title), 'posts');
        $publishedAt = ($status === 'published') ? date('Y-m-d H:i:s') : null;
        
        try {
            $db->beginTransaction();
            
            // Insert post
            $stmt = $db->prepare("
                INSERT INTO posts (title, slug, content, excerpt, featured_image, status, category_id, published_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $slug, $content, $excerpt, $featuredImage, $status, $categoryId, $publishedAt]);
            $postId = $db->lastInsertId();
            
            // Insert tags
            if (!empty($tags)) {
                $tagStmt = $db->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)");
                foreach ($tags as $tagId) {
                    $tagStmt->execute([$postId, $tagId]);
                }
            }
            
            $db->commit();
            
            jsonResponse([
                'success' => true,
                'message' => 'Post created successfully',
                'post_id' => $postId,
                'slug' => $slug
            ]);
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
        break;
    
    // ========================================
    // PUT - Update post
    // ========================================
    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        
        if (!$id) {
            jsonResponse(['success' => false, 'message' => 'Post ID required'], 400);
        }
        
        $title = trim($input['title'] ?? '');
        $content = $input['content'] ?? '';
        $excerpt = $input['excerpt'] ?? generateExcerpt($content);
        $status = $input['status'] ?? 'draft';
        // Support both single category_id and multiple category_ids
        $categoryIds = $input['category_ids'] ?? [];
        $categoryId = !empty($categoryIds) ? $categoryIds[0] : ($input['category_id'] ?? null);
        $featuredImage = $input['featured_image'] ?? null;
        $tags = $input['tags'] ?? [];
        
        // Get existing post
        $stmt = $db->prepare("SELECT * FROM posts WHERE id = ?");
        $stmt->execute([$id]);
        $existingPost = $stmt->fetch();
        
        if (!$existingPost) {
            jsonResponse(['success' => false, 'message' => 'Post not found'], 404);
        }
        
        // Update slug only if title changed
        $slug = $existingPost['slug'];
        if ($title !== $existingPost['title']) {
            $slug = uniqueSlug(slugify($title), 'posts', $id);
        }
        
        // Set published_at jika baru publish
        $publishedAt = $existingPost['published_at'];
        if ($status === 'published' && $existingPost['status'] !== 'published') {
            $publishedAt = date('Y-m-d H:i:s');
        }
        
        try {
            $db->beginTransaction();
            
            // Update post
            $stmt = $db->prepare("
                UPDATE posts 
                SET title = ?, slug = ?, content = ?, excerpt = ?, 
                    featured_image = ?, status = ?, category_id = ?, published_at = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $slug, $content, $excerpt, $featuredImage, $status, $categoryId, $publishedAt, $id]);
            
            // Update tags - delete existing, insert new
            $db->prepare("DELETE FROM post_tags WHERE post_id = ?")->execute([$id]);
            
            if (!empty($tags)) {
                $tagStmt = $db->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)");
                foreach ($tags as $tagId) {
                    $tagStmt->execute([$id, $tagId]);
                }
            }
            
            $db->commit();
            
            jsonResponse([
                'success' => true,
                'message' => 'Post updated successfully',
                'slug' => $slug
            ]);
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
        break;
    
    // ========================================
    // DELETE - Delete post
    // ========================================
    case 'DELETE':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? $_GET['id'] ?? null;
        
        if (!$id) {
            jsonResponse(['success' => false, 'message' => 'Post ID required'], 400);
        }
        
        // Soft delete (move to trash) or hard delete
        $permanent = $input['permanent'] ?? false;
        
        if ($permanent) {
            $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
        } else {
            $stmt = $db->prepare("UPDATE posts SET status = 'trash' WHERE id = ?");
        }
        
        $stmt->execute([$id]);
        
        jsonResponse(['success' => true, 'message' => 'Post deleted successfully']);
        break;
    
    default:
        jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}
