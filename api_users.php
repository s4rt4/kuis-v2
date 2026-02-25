<?php
require 'config.php';
session_start();

header('Content-Type: application/json');

// Ensure user is authorized
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($action)) {
    // Return all users
    $stmt = $db->query("SELECT id, username, display_name, role, level, is_active, last_login, created_at FROM users ORDER BY role, id");
    $data = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

if ($action === 'create') {
    $username     = trim($_POST['username']     ?? '');
    $password     = $_POST['password']          ?? '';
    $display_name = trim($_POST['display_name'] ?? '');
    $role         = $_POST['role']              ?? 'teacher';
    $level        = $_POST['level']             ?? 'sd';
    $is_active    = intval($_POST['is_active']  ?? 1);

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username and password are required']);
        exit;
    }

    // Check if username already exists
    $stmtCheck = $db->prepare("SELECT id FROM users WHERE username = :u LIMIT 1");
    $stmtCheck->execute([':u' => $username]);
    if ($stmtCheck->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username sudah digunakan']);
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Step 1: INSERT user dulu (tanpa avatar), dapatkan ID baru
    $stmt = $db->prepare("INSERT INTO users (username, password, password_hash, display_name, role, level, is_active, avatar) VALUES (:u, :p, :ph, :dn, :r, :lvl, :ia, :av)");
    $stmt->execute([
        ':u'  => $username,
        ':p'  => $hashed_password,
        ':ph' => $hashed_password,
        ':dn' => $display_name,
        ':r'  => $role,
        ':lvl'=> $level,
        ':ia' => $is_active,
        ':av' => 'assets/png/avatar.png', // default dulu
    ]);
    $new_id = $db->lastInsertId();

    // Step 2: Upload avatar (setelah user berhasil dibuat, pakai ID yang benar)
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file    = $_FILES['avatar'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed) && $file['size'] <= 2 * 1024 * 1024) {
            $upload_dir   = 'uploads/avatars/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            // Nama file pakai user ID yang pasti unik — tidak akan ada orphan file
            $new_filename = 'avatar_u' . $new_id . '_' . time() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_filename)) {
                $avatar_url = $upload_dir . $new_filename;
                // Step 3: UPDATE kolom avatar dengan path yang benar
                $db->prepare("UPDATE users SET avatar = :av WHERE id = :id")
                   ->execute([':av' => $avatar_url, ':id' => $new_id]);
            }
        }
    }

    echo json_encode(['success' => true, 'id' => $new_id]);
    exit;
}

if ($action === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $display_name = trim($_POST['display_name'] ?? '');
    $role = $_POST['role'] ?? 'teacher';
    $level = $_POST['level'] ?? 'sd';
    $is_active = intval($_POST['is_active'] ?? 1);
    $password = $_POST['password'] ?? ''; // Optional on update

    if (empty($id) || empty($username)) {
        echo json_encode(['success' => false, 'message' => 'ID and Username are required']);
        exit;
    }

    // Check if username belongs to someone else
    $stmtCheck = $db->prepare("SELECT id FROM users WHERE username = :u AND id != :id LIMIT 1");
    $stmtCheck->execute([':u' => $username, ':id' => $id]);
    if ($stmtCheck->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username sudah digunakan oleh user lain']);
        exit;
    }

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET username=:u, password=:p, password_hash=:ph, display_name=:dn, role=:r, level=:lvl, is_active=:ia WHERE id=:id");
        $stmt->execute([
            ':u' => $username, ':p' => $hashed_password, ':ph' => $hashed_password, ':dn' => $display_name, ':r' => $role, ':lvl' => $level, ':ia' => $is_active, ':id' => $id
        ]);
    } else {
        $stmt = $db->prepare("UPDATE users SET username=:u, display_name=:dn, role=:r, level=:lvl, is_active=:ia WHERE id=:id");
        $stmt->execute([
            ':u' => $username, ':dn' => $display_name, ':r' => $role, ':lvl' => $level, ':ia' => $is_active, ':id' => $id
        ]);
    }

    // Bug #6: Handle avatar upload on update
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file    = $_FILES['avatar'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed) && $file['size'] <= 2 * 1024 * 1024) {
            $upload_dir = 'uploads/avatars/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $new_filename = 'avatar_u' . $id . '_' . time() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_filename)) {
                // Delete old avatar
                $oldStmt = $db->prepare("SELECT avatar FROM users WHERE id = :id");
                $oldStmt->execute([':id' => $id]);
                $old = $oldStmt->fetchColumn();
                if ($old && file_exists($old) && $old !== 'assets/png/avatar.png') {
                    @unlink($old);
                }
                $db->prepare("UPDATE users SET avatar = :av WHERE id = :id")
                   ->execute([':av' => $upload_dir . $new_filename, ':id' => $id]);
            }
        }
    }

    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);

    // Prevent deleting self (admin)
    if ($id === $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Tidak dapat menghapus akun sendiri']);
        exit;
    }

    // Bug #5: Hapus file avatar sebelum delete user
    $avatarStmt = $db->prepare("SELECT avatar FROM users WHERE id = :id");
    $avatarStmt->execute([':id' => $id]);
    $avatarPath = $avatarStmt->fetchColumn();
    if ($avatarPath && file_exists($avatarPath) && $avatarPath !== 'assets/png/avatar.png') {
        @unlink($avatarPath);
    }

    $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute([':id' => $id]);

    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
