<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';

$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    // GET - List categories
    case 'GET':
        $stmt = $db->query("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM posts WHERE category_id = c.id AND status = 'published') as post_count
            FROM categories c
            ORDER BY c.name ASC
        ");
        $categories = $stmt->fetchAll();
        jsonResponse(['success' => true, 'categories' => $categories]);
        break;
    
    // POST - Create category
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $name = trim($input['name'] ?? '');
        $description = $input['description'] ?? '';
        $parentId = $input['parent_id'] ?? null;
        
        if (empty($name)) {
            jsonResponse(['success' => false, 'message' => 'Name is required'], 400);
        }
        
        $slug = uniqueSlug(slugify($name), 'categories');
        
        $stmt = $db->prepare("INSERT INTO categories (name, slug, description, parent_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $slug, $description, $parentId]);
        
        jsonResponse([
            'success' => true,
            'message' => 'Category created',
            'category_id' => $db->lastInsertId(),
            'slug' => $slug
        ]);
        break;
    
    // DELETE - Delete category
    case 'DELETE':
        $id = $_GET['id'] ?? null;
        
        if (!$id || $id == 1) { // Protect default category
            jsonResponse(['success' => false, 'message' => 'Cannot delete this category'], 400);
        }
        
        // Move posts to Uncategorized (id=1)
        $db->prepare("UPDATE posts SET category_id = 1 WHERE category_id = ?")->execute([$id]);
        
        // Delete category
        $db->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
        
        jsonResponse(['success' => true, 'message' => 'Category deleted']);
        break;
    
    default:
        jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}
