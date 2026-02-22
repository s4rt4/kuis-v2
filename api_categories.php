<?php
// api_categories.php — versi update (support level, color, icon, sort_order)
require 'config.php';
$method = $_SERVER['REQUEST_METHOD'];
header('Content-Type: application/json');

// GET: ambil semua atau filter per level
if ($method === 'GET') {
    $level = strtoupper(trim($_GET['level'] ?? ''));
    if (in_array($level, ['SD','SMP','SMA'])) {
        $stmt = $db->prepare("SELECT * FROM categories WHERE level = :lv ORDER BY sort_order, name");
        $stmt->execute([':lv' => $level]);
    } else {
        $stmt = $db->query("SELECT * FROM categories ORDER BY level, sort_order, name");
    }
    echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $name  = trim($_POST['name']  ?? '');
    $level = strtoupper(trim($_POST['level'] ?? 'SD'));
    $color = trim($_POST['color'] ?? '');
    $icon  = trim($_POST['icon']  ?? '');
    $sort  = intval($_POST['sort_order'] ?? 0);

    if ($name === '') { echo json_encode(['success'=>false,'message'=>'Nama kosong']); exit; }
    if (!in_array($level, ['SD','SMP','SMA'])) { echo json_encode(['success'=>false,'message'=>'Level tidak valid']); exit; }

    // Cek duplikat per level
    $chk = $db->prepare("SELECT id FROM categories WHERE name=:n AND level=:lv");
    $chk->execute([':n'=>$name,':lv'=>$level]);
    if ($chk->fetch()) { echo json_encode(['success'=>false,'message'=>'Kategori sudah ada di jenjang ini']); exit; }

    $stmt = $db->prepare("INSERT INTO categories (name,level,color,icon,sort_order) VALUES (:n,:lv,:c,:i,:s)");
    $stmt->execute([':n'=>$name,':lv'=>$level,':c'=>$color,':i'=>$icon,':s'=>$sort]);
    echo json_encode(['success'=>true,'id'=>$db->lastInsertId()]);
    exit;
}

if ($action === 'update') {
    $id    = intval($_POST['id'] ?? 0);
    $name  = trim($_POST['name']  ?? '');
    $level = strtoupper(trim($_POST['level'] ?? 'SD'));
    $color = trim($_POST['color'] ?? '');
    $icon  = trim($_POST['icon']  ?? '');
    $sort  = intval($_POST['sort_order'] ?? 0);

    $stmt = $db->prepare("UPDATE categories SET name=:n,level=:lv,color=:c,icon=:i,sort_order=:s WHERE id=:id");
    $stmt->execute([':n'=>$name,':lv'=>$level,':c'=>$color,':i'=>$icon,':s'=>$sort,':id'=>$id]);
    echo json_encode(['success'=>true]);
    exit;
}

if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    // Cek apakah masih ada paket di kategori ini
    $chk = $db->prepare("SELECT COUNT(*) FROM packages WHERE category_id=:id");
    $chk->execute([':id'=>$id]);
    if ($chk->fetchColumn() > 0) {
        echo json_encode(['success'=>false,'message'=>'Hapus paket di kategori ini terlebih dahulu']);
        exit;
    }
    $stmt = $db->prepare("DELETE FROM categories WHERE id=:id");
    $stmt->execute([':id'=>$id]);
    echo json_encode(['success'=>true]);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Unknown action']);
