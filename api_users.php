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
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $display_name = trim($_POST['display_name'] ?? '');
    $role = $_POST['role'] ?? 'teacher';
    $level = $_POST['level'] ?? 'sd';
    $is_active = intval($_POST['is_active'] ?? 1);

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

    // Hash password properly (or match the login logic. The login logic supports md5, plain, or password_verify)
    // To modernize we should use password_hash, but for compatibility with login let's just use password_hash
    // Actually our login.php does `password_verify($password, $user['password'])` as a fallback
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare("INSERT INTO users (username, password, display_name, role, level, is_active) VALUES (:u, :p, :dn, :r, :lvl, :ia)");
    $stmt->execute([
        ':u' => $username,
        ':p' => $hashed_password,
        ':dn' => $display_name,
        ':r' => $role,
        ':lvl' => $level,
        ':ia' => $is_active
    ]);
    echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
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
        $stmt = $db->prepare("UPDATE users SET username=:u, password=:p, display_name=:dn, role=:r, level=:lvl, is_active=:ia WHERE id=:id");
        $stmt->execute([
            ':u' => $username, ':p' => $hashed_password, ':dn' => $display_name, ':r' => $role, ':lvl' => $level, ':ia' => $is_active, ':id' => $id
        ]);
    } else {
        $stmt = $db->prepare("UPDATE users SET username=:u, display_name=:dn, role=:r, level=:lvl, is_active=:ia WHERE id=:id");
        $stmt->execute([
            ':u' => $username, ':dn' => $display_name, ':r' => $role, ':lvl' => $level, ':ia' => $is_active, ':id' => $id
        ]);
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

    $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute([':id' => $id]);
    
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
