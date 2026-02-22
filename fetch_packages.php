<?php
require 'config.php';
$category_id = intval($_GET['category'] ?? 0);
$stmt = $db->prepare("SELECT * FROM packages WHERE category_id = :cid ORDER BY id");
$stmt->execute([':cid' => $category_id]);
$rows = $stmt->fetchAll();
header('Content-Type: application/json');
echo json_encode($rows);
