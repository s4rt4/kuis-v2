<?php
require 'config.php';

// Only admin/teacher can upload or manage images
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? ($_GET['action'] ?? '');
$uploadDir = 'assets/uploads/questions/';

// Create directory if not exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

header('Content-Type: application/json');

if ($action === 'list') {
    $images = [];
    $files = scandir($uploadDir);
    
    // Sort files by modification time descending (newest first)
    $filesWithTime = [];
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $filePath = $uploadDir . $file;
                $filesWithTime[$file] = filemtime($filePath);
            }
        }
    }
    arsort($filesWithTime); // Sort by newest
    
    foreach ($filesWithTime as $file => $mtime) {
        $images[] = [
            'filename' => $file,
            'url' => $uploadDir . $file,
            'time' => date('Y-m-d H:i:s', $mtime)
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $images]);
    exit;
}

if ($action === 'upload') {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== 0) {
        echo json_encode(['success' => false, 'message' => 'No valid image uploaded.']);
        exit;
    }

    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        echo json_encode(['success' => false, 'message' => 'Format file tidak diizinkan. Hanya JPG, PNG, GIF, WEBP.']);
        exit;
    }

    $filename = 'img_' . time() . '_' . rand(100, 999) . '.' . $ext;
    $targetPath = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
        echo json_encode([
            'success' => true,
            'url' => $targetPath,
            'filename' => $filename
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file di server.']);
    }
    exit;
}

if ($action === 'delete') {
    $filename = $_POST['filename'] ?? '';
    if (empty($filename)) {
        echo json_encode(['success' => false, 'message' => 'Filename required.']);
        exit;
    }
    
    // Basic security to prevent directory traversal
    $filename = basename($filename);
    $targetPath = $uploadDir . $filename;

    if (file_exists($targetPath)) {
        if (unlink($targetPath)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus file.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'File tidak ditemukan.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);
