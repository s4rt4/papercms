<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';

$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    // GET - List tags
    case 'GET':
        $search = $_GET['search'] ?? '';
        
        if ($search) {
            $stmt = $db->prepare("SELECT * FROM tags WHERE name LIKE ? ORDER BY name LIMIT 20");
            $stmt->execute(['%' . $search . '%']);
        } else {
            $stmt = $db->query("SELECT * FROM tags ORDER BY name");
        }
        
        jsonResponse(['success' => true, 'tags' => $stmt->fetchAll()]);
        break;
    
    // POST - Create tag(s)
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        $names = $input['names'] ?? []; // Array of tag names
        $name = $input['name'] ?? '';   // Single tag name
        
        // Support both single and multiple
        if (!empty($name)) {
            $names = [$name];
        }
        
        $createdTags = [];
        
        foreach ($names as $tagName) {
            $tagName = trim($tagName);
            if (empty($tagName)) continue;
            
            $slug = slugify($tagName);
            
            // Check if exists
            $stmt = $db->prepare("SELECT * FROM tags WHERE slug = ?");
            $stmt->execute([$slug]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $createdTags[] = $existing;
            } else {
                $stmt = $db->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
                $stmt->execute([$tagName, $slug]);
                $createdTags[] = [
                    'id' => $db->lastInsertId(),
                    'name' => $tagName,
                    'slug' => $slug
                ];
            }
        }
        
        jsonResponse(['success' => true, 'tags' => $createdTags]);
        break;
    
    // DELETE - Delete tag
    case 'DELETE':
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            jsonResponse(['success' => false, 'message' => 'Tag ID required'], 400);
        }
        
        $db->prepare("DELETE FROM tags WHERE id = ?")->execute([$id]);
        
        jsonResponse(['success' => true, 'message' => 'Tag deleted']);
        break;
    
    default:
        jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}
