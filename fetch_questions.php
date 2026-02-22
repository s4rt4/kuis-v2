<?php
require 'config.php';
$package_id = intval($_GET['package'] ?? 0);
$stmt = $db->prepare("SELECT * FROM questions WHERE package_id = :pid ORDER BY sort_order, id");
$stmt->execute([':pid' => $package_id]);
$rows = $stmt->fetchAll();
header('Content-Type: application/json');
echo json_encode($rows);
