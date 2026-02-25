<?php
// api_grades.php — Kelola Nilai (read + remedial)
require 'config.php';
session_start();
header('Content-Type: application/json');

// Only admin & teacher
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$role      = $_SESSION['user_role'];
$userLevel = strtolower($_SESSION['user_level'] ?? 'sd'); // for teacher filtering
$action    = $_POST['action'] ?? ($_GET['action'] ?? 'list');

// ─── LIST grades ────────────────────────────────────────────────
if ($action === 'list') {
    $level = strtolower($_GET['level'] ?? '');

    // Admin can see all levels; teacher only their own level (except level='all')
    if ($role === 'teacher' && $userLevel !== 'all') {
        $level = $userLevel;
    } elseif ($role === 'teacher' && $userLevel === 'all') {
        $level = ''; // no level restriction
    }

    $whereParts = ["qs.status = 'completed'"];
    $params     = [];

    if ($level && in_array($level, ['sd', 'smp', 'sma'])) {
        $whereParts[] = "LOWER(p.target_level) = :lv";
        $params[':lv'] = $level;
    }

    $where = implode(' AND ', $whereParts);

    $stmt = $db->prepare("
        SELECT
            qs.id,
            qs.player_name,
            qs.score,
            qs.total_q,
            qs.correct,
            qs.wrong,
            qs.duration_sec,
            qs.start_time,
            qs.is_remedial,
            qs.remedial_note,
            p.name  AS package_name,
            c.name  AS category_name,
            UPPER(p.target_level) AS level,
            u.username,
            u.display_name
        FROM quiz_sessions qs
        LEFT JOIN packages p   ON p.id = qs.package_id
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN users u      ON u.id = qs.user_id
        WHERE $where
        ORDER BY qs.start_time DESC
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
}

// ─── REMEDIAL: insert new session row ───────────────────────────
if ($action === 'remedial') {
    $session_id   = intval($_POST['session_id']  ?? 0);
    $new_score    = intval($_POST['score']        ?? 0);
    $note         = trim($_POST['note']           ?? '');

    if (!$session_id) {
        echo json_encode(['success' => false, 'message' => 'Session ID diperlukan']);
        exit;
    }
    if ($new_score < 0 || $new_score > 100) {
        echo json_encode(['success' => false, 'message' => 'Nilai harus antara 0–100']);
        exit;
    }

    // Get original session
    $orig = $db->prepare("SELECT * FROM quiz_sessions WHERE id = :id LIMIT 1");
    $orig->execute([':id' => $session_id]);
    $original = $orig->fetch(PDO::FETCH_ASSOC);

    if (!$original) {
        echo json_encode(['success' => false, 'message' => 'Sesi tidak ditemukan']);
        exit;
    }

    // Teacher can only remedial their level
    if ($role === 'teacher') {
        // Verify level matches
        $lvStmt = $db->prepare("
            SELECT LOWER(p.target_level) as level
            FROM packages p
            WHERE p.id = :pid LIMIT 1
        ");
        $lvStmt->execute([':pid' => $original['package_id']]);
        $lvRow = $lvStmt->fetch(PDO::FETCH_ASSOC);
        if (!$lvRow || $lvRow['level'] !== $userLevel) {
            echo json_encode(['success' => false, 'message' => 'Tidak ada akses ke level ini']);
            exit;
        }
    }

    // Insert remedial row (preserves original)
    $stmt = $db->prepare("
        INSERT INTO quiz_sessions
          (user_id, package_id, player_name, score, total_q, correct, wrong,
           duration_sec, status, is_remedial, remedial_note, start_time)
        VALUES
          (:uid, :pid, :name, :score, :total, :correct, :wrong,
           0, 'completed', 1, :note, NOW())
    ");
    $stmt->execute([
        ':uid'     => $original['user_id'],
        ':pid'     => $original['package_id'],
        ':name'    => $original['player_name'],
        ':score'   => $new_score,
        ':total'   => $original['total_q'],
        ':correct' => 0, // remedial is manual, no answer log
        ':wrong'   => 0,
        ':note'    => $note ?: 'Nilai Remedial',
    ]);

    echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
