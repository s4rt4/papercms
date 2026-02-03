<?php
header('Content-Type: application/json');

$uploadDir = 'upload/';
$files = [];

if (is_dir($uploadDir)) {
    // Scan directory
    $scanned = scandir($uploadDir);
    
    foreach ($scanned as $file) {
        if ($file !== '.' && $file !== '..') {
            // Check if image
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'mp4', 'webm', 'ogv'])) {
                // Add full path or relative path accessible by web
                $files[] = $uploadDir . $file;
            }
        }
    }
}

echo json_encode(['success' => true, 'files' => $files]);
