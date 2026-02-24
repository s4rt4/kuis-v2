<?php
// api_session.php — Simpan hasil sesi kuis
require 'config.php';
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$user_id      = $_SESSION['user_id'] ?? null;
$package_id   = intval($_POST['package_id']   ?? 0);
$player_name  = trim($_POST['player_name']    ?? ($_SESSION['user_name'] ?? 'Siswa'));
$score        = intval($_POST['score']        ?? 0);
$total_q      = intval($_POST['total_q']      ?? 0);
$correct      = intval($_POST['correct']      ?? 0);
$wrong        = intval($_POST['wrong']        ?? 0);
$duration_sec = intval($_POST['duration_sec'] ?? 0);
$answers_json = $_POST['answers'] ?? '[]'; 

if (!$package_id || !$total_q) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

try {
    $db->beginTransaction();

    $stmt = $db->prepare("
        INSERT INTO quiz_sessions
          (user_id, package_id, player_name, score, total_q, correct, wrong, duration_sec, status)
        VALUES
          (:uid, :pid, :name, :score, :total, :correct, :wrong, :dur, 'completed')
    ");
    
    $stmt->execute([
        ':uid'     => $user_id,
        ':pid'     => $package_id,
        ':name'    => $player_name,
        ':score'   => $score,
        ':total'   => $total_q,
        ':correct' => $correct,
        ':wrong'   => $wrong,
        ':dur'     => $duration_sec,
    ]);
    
    $session_id = $db->lastInsertId();
    
    $answers = json_decode($answers_json, true);
    if (is_array($answers)) {
        $stmtLog = $db->prepare("
            INSERT INTO answer_logs (session_id, question_id, selected_option, is_correct)
            VALUES (:sid, :qid, :sel, :isc)
        ");
        
        foreach ($answers as $ans) {
            // ans object expects { qId: INT, chosen: 'A', isCorrect: bool }
            // Note: we need the actual question ID from the frontend. We will assume the frontend sends 'qId'.
            if (isset($ans['qId'])) {
                $stmtLog->execute([
                    ':sid' => $session_id,
                    ':qid' => intval($ans['qId']),
                    ':sel' => $ans['chosen'] ?: null, // Can be null if timeout
                    ':isc' => $ans['isCorrect'] ? 1 : 0
                ]);
            }
        }
    }
    
    $db->commit();
    echo json_encode(['success' => true, 'id' => $session_id]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
