<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';

$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    // GET - List media
    case 'GET':
        $type = $_GET['type'] ?? 'all'; // all, image, video
        
        $where = "WHERE 1=1";
        if ($type === 'image') {
            $where .= " AND file_type LIKE 'image/%'";
        } elseif ($type === 'video') {
            $where .= " AND file_type LIKE 'video/%'";
        }
        
        $stmt = $db->query("SELECT * FROM media $where ORDER BY uploaded_at DESC");
        jsonResponse(['success' => true, 'files' => $stmt->fetchAll()]);
        break;
    
    // POST - Upload file
    case 'POST':
        if (!isset($_FILES['file'])) {
            jsonResponse(['success' => false, 'message' => 'No file uploaded'], 400);
        }
        
        $file = $_FILES['file'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/webm'];
        $maxSize = 50 * 1024 * 1024; // 50MB
        
        // Validate
        if (!in_array($file['type'], $allowedTypes)) {
            jsonResponse(['success' => false, 'message' => 'File type not allowed'], 400);
        }
        
        if ($file['size'] > $maxSize) {
            jsonResponse(['success' => false, 'message' => 'File too large (max 50MB)'], 400);
        }
        
        // Generate filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('', true) . '.' . strtolower($ext);
        $filepath = UPLOAD_DIR . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            jsonResponse(['success' => false, 'message' => 'Failed to move file'], 500);
        }
        
        // Save to database
        $stmt = $db->prepare("
            INSERT INTO media (filename, original_name, file_path, file_type, file_size)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $filename,
            $file['name'],
            'upload/' . $filename,
            $file['type'],
            $file['size']
        ]);
        
        jsonResponse([
            'success' => true,
            'url' => 'upload/' . $filename,
            'media_id' => $db->lastInsertId()
        ]);
        break;
    
    // DELETE
    case 'DELETE':
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            jsonResponse(['success' => false, 'message' => 'Media ID required'], 400);
        }
        
        // Get file info
        $stmt = $db->prepare("SELECT * FROM media WHERE id = ?");
        $stmt->execute([$id]);
        $media = $stmt->fetch();
        
        if ($media) {
            // Delete file
            $filepath = UPLOAD_DIR . $media['filename'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            
            // Delete from DB
            $db->prepare("DELETE FROM media WHERE id = ?")->execute([$id]);
        }
        
        jsonResponse(['success' => true, 'message' => 'Media deleted']);
        break;
    
    default:
        jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}
