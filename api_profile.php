<?php
require 'config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'update_profile') {
    $user_id = $_SESSION['user_id'];
    $username = trim($_POST['username'] ?? '');
    $display_name = trim($_POST['display_name'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username)) {
        echo json_encode(['success' => false, 'message' => 'Username tidak boleh kosong']);
        exit;
    }

    // Check if username is taken by another user
    $stmtCheck = $db->prepare("SELECT id FROM users WHERE username = :u AND id != :id LIMIT 1");
    $stmtCheck->execute([':u' => $username, ':id' => $user_id]);
    if ($stmtCheck->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username sudah digunakan oleh pengguna lain']);
        exit;
    }

    $avatar_url = null;

    // Handle Avatar Upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($ext, $allowed)) {
            // max 2MB
            if ($file['size'] <= 2 * 1024 * 1024) {
                // Buat direktori jika belum ada
                $upload_dir = 'uploads/avatars/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
                $target_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    $avatar_url = $target_path;
                    
                    // Hapus avatar lama jika ada
                    $stmtOld = $db->prepare("SELECT avatar FROM users WHERE id = :id");
                    $stmtOld->execute([':id' => $user_id]);
                    $old = $stmtOld->fetchColumn();
                    if ($old && file_exists($old) && $old !== 'assets/png/avatar.png') {
                        @unlink($old);
                    }
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Ukuran gambar maksimal 2MB']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Format file tidak didukung']);
            exit;
        }
    }

    // Update query
    $sql = "UPDATE users SET username = :u, display_name = :dn";
    $params = [':u' => $username, ':dn' => $display_name, ':id' => $user_id];

    if (!empty($password)) {
        $sql .= ", password = :p";
        $params[':p'] = password_hash($password, PASSWORD_DEFAULT);
    }
    
    if ($avatar_url !== null) {
        $sql .= ", avatar = :av";
        $params[':av'] = $avatar_url;
    }

    $sql .= " WHERE id = :id";

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        // Update session
        $_SESSION['user_username'] = $username;
        $_SESSION['user_name'] = $display_name;
        if ($avatar_url !== null) {
            $_SESSION['user_avatar'] = $avatar_url; // Not strictly used right now but good to have
        }

        echo json_encode(['success' => true, 'avatar_url' => $avatar_url]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
