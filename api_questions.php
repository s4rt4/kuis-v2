<?php
require 'config.php';
$action = $_POST['action'] ?? '';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $data = $db->query("SELECT q.*, c.name as category_name, p.name as package_name FROM questions q LEFT JOIN categories c ON q.category_id=c.id LEFT JOIN packages p ON q.package_id=p.id ORDER BY q.id")->fetchAll();
    echo json_encode(['success'=>true,'data'=>$data]); exit;
}

if ($action==='create') {
    $category_id = intval($_POST['category_id'] ?? 0);
    $package_id = intval($_POST['package_id'] ?? 0);
    $qtext = $_POST['question_text'] ?? '';
    $a = $_POST['option_a'] ?? '';
    $b = $_POST['option_b'] ?? '';
    $c = $_POST['option_c'] ?? '';
    $d = $_POST['option_d'] ?? '';
    $correct = strtoupper(substr(trim($_POST['correct_option'] ?? 'A'),0,1));
    $sort = intval($_POST['sort_order'] ?? 0);
    $stmt = $db->prepare("INSERT INTO questions (category_id,package_id,question_text,option_a,option_b,option_c,option_d,correct_option,sort_order) VALUES (:cid,:pid,:q,:a,:b,:c,:d,:co,:so)");
    $stmt->execute([
      ':cid'=>$category_id,':pid'=>$package_id,':q'=>$qtext,':a'=>$a,':b'=>$b,':c'=>$c,':d'=>$d,':co'=>$correct,':so'=>$sort
    ]);
    echo json_encode(['success'=>true,'id'=>$db->lastInsertId()]); exit;
}

if ($action==='update') {
    $id = intval($_POST['id'] ?? 0);
    $stmt = $db->prepare("UPDATE questions SET category_id=:cid, package_id=:pid, question_text=:q, option_a=:a, option_b=:b, option_c=:c, option_d=:d, correct_option=:co, sort_order=:so WHERE id=:id");
    $stmt->execute([
      ':cid'=>intval($_POST['category_id']),':pid'=>intval($_POST['package_id']),':q'=>$_POST['question_text'],':a'=>$_POST['option_a'],':b'=>$_POST['option_b'],':c'=>$_POST['option_c'],':d'=>$_POST['option_d'],':co'=>$_POST['correct_option'],':so'=>intval($_POST['sort_order']),':id'=>$id
    ]);
    echo json_encode(['success'=>true]); exit;
}

if ($action==='delete') {
    $id = intval($_POST['id'] ?? 0);
    $stmt = $db->prepare("DELETE FROM questions WHERE id=:id");
    $stmt->execute([':id'=>$id]);
    echo json_encode(['success'=>true]); exit;
}

echo json_encode(['success'=>false,'message'=>'Unknown action']);
