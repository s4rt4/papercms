<?php
// upload_handler.php

header('Content-Type: application/json');

// Definition of upload directory
$uploadDir = 'upload/';

// Create directory if it doesn't exist
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    
    // File properties
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    
    // Validate file extension
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'ogv');
    
    if (in_array($fileExt, $allowed)) {
        if ($fileError === 0) {
            if ($fileSize < 50000000) { // 50MB limit
                // Generate unique name
                $newFileName = uniqid('', true) . "." . $fileExt;
                $fileDestination = $uploadDir . $newFileName;
                
                if (move_uploaded_file($fileTmpName, $fileDestination)) {
                    // Success
                    echo json_encode(['success' => true, 'url' => $fileDestination]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'File is too large. Max 50MB.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Error uploading file: ' . $fileError]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, WEBP, MP4, WEBM, OGV allowed.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
}
?>
