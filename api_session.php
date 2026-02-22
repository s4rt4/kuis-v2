<?php
// api_session.php — Simpan hasil sesi kuis
require 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$package_id   = intval($_POST['package_id']   ?? 0);
$player_name  = trim($_POST['player_name']    ?? 'Siswa');
$score        = intval($_POST['score']        ?? 0);
$total_q      = intval($_POST['total_q']      ?? 0);
$correct      = intval($_POST['correct']      ?? 0);
$wrong        = intval($_POST['wrong']        ?? 0);
$duration_sec = intval($_POST['duration_sec'] ?? 0);

if (!$package_id || !$total_q) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

$stmt = $db->prepare("
    INSERT INTO quiz_sessions
      (package_id, player_name, score, total_q, correct, wrong, duration_sec)
    VALUES
      (:pid, :name, :score, :total, :correct, :wrong, :dur)
");
$stmt->execute([
    ':pid'     => $package_id,
    ':name'    => $player_name,
    ':score'   => $score,
    ':total'   => $total_q,
    ':correct' => $correct,
    ':wrong'   => $wrong,
    ':dur'     => $duration_sec,
]);

echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
