<?php
// api_packages.php — versi update (support time_limit, shuffle, is_active)
require 'config.php';
$method = $_SERVER['REQUEST_METHOD'];
header('Content-Type: application/json');

if ($method === 'GET') {
    $data = $db->query("
        SELECT p.*, c.name as category_name
        FROM packages p
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY c.name, p.sort_order, p.id
    ")->fetchAll();
    echo json_encode(['success'=>true,'data'=>$data]); exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $name       = trim($_POST['name']        ?? '');
    $cat_id     = intval($_POST['category_id'] ?? 0);
    $desc       = trim($_POST['description']  ?? '');
    $timer      = intval($_POST['time_limit'] ?? 0);
    
    $access     = in_array($_POST['target_access'] ?? '', ['guest','internal','both']) ? $_POST['target_access'] : 'both';
    $timer_type = in_array($_POST['timer_type'] ?? '', ['none','per_packet','per_question']) ? $_POST['timer_type'] : 'none';
    $t_level    = in_array($_POST['target_level'] ?? '', ['sd','smp','sma','all']) ? $_POST['target_level'] : 'all';

    $shuffle_q  = intval($_POST['shuffle_q']  ?? 0);
    $shuffle_o  = intval($_POST['shuffle_opt']?? 0);
    $sort       = intval($_POST['sort_order'] ?? 0);
    $active     = intval($_POST['is_active']  ?? 1);

    if (!$name || !$cat_id) {
        echo json_encode(['success'=>false,'message'=>'Nama dan kategori wajib diisi']); exit;
    }

    $stmt = $db->prepare("
        INSERT INTO packages
          (category_id, name, description, time_limit, target_access, timer_type, target_level, shuffle_q, shuffle_opt, sort_order, is_active)
        VALUES (:cid,:n,:d,:tl,:acc,:tt,:tlvl,:sq,:so,:sort,:act)
    ");
    $stmt->execute([
        ':cid'=>$cat_id,':n'=>$name,':d'=>$desc,':tl'=>$timer,
        ':acc'=>$access,':tt'=>$timer_type,':tlvl'=>$t_level,
        ':sq'=>$shuffle_q,':so'=>$shuffle_o,':sort'=>$sort,':act'=>$active
    ]);
    echo json_encode(['success'=>true,'id'=>$db->lastInsertId()]); exit;
}

if ($action === 'update') {
    $id        = intval($_POST['id'] ?? 0);
    $name      = trim($_POST['name']        ?? '');
    $cat_id    = intval($_POST['category_id'] ?? 0);
    $desc      = trim($_POST['description']  ?? '');
    $timer     = intval($_POST['time_limit'] ?? 0);

    $access     = in_array($_POST['target_access'] ?? '', ['guest','internal','both']) ? $_POST['target_access'] : 'both';
    $timer_type = in_array($_POST['timer_type'] ?? '', ['none','per_packet','per_question']) ? $_POST['timer_type'] : 'none';
    $t_level    = in_array($_POST['target_level'] ?? '', ['sd','smp','sma','all']) ? $_POST['target_level'] : 'all';

    $shuffle_q = intval($_POST['shuffle_q']  ?? 0);
    $shuffle_o = intval($_POST['shuffle_opt']?? 0);
    $sort      = intval($_POST['sort_order'] ?? 0);
    $active    = intval($_POST['is_active']  ?? 1);

    $stmt = $db->prepare("
        UPDATE packages SET
          category_id=:cid, name=:n, description=:d, time_limit=:tl,
          target_access=:acc, timer_type=:tt, target_level=:tlvl,
          shuffle_q=:sq, shuffle_opt=:so, sort_order=:sort, is_active=:act
        WHERE id=:id
    ");
    $stmt->execute([
        ':cid'=>$cat_id,':n'=>$name,':d'=>$desc,':tl'=>$timer,
        ':acc'=>$access,':tt'=>$timer_type,':tlvl'=>$t_level,
        ':sq'=>$shuffle_q,':so'=>$shuffle_o,':sort'=>$sort,':act'=>$active,':id'=>$id
    ]);
    echo json_encode(['success'=>true]); exit;
}

if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    $stmt = $db->prepare("DELETE FROM packages WHERE id=:id");
    $stmt->execute([':id'=>$id]);
    echo json_encode(['success'=>true]); exit;
}

echo json_encode(['success'=>false,'message'=>'Unknown action']);
