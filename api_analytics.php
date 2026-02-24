<?php
require 'config.php';
session_start();

header('Content-Type: application/json');

// Ensure user is authorized
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = strtolower($_GET['action'] ?? '');

if ($action === 'questions_success_rate') {
    $package_id = intval($_GET['package_id'] ?? 0);
    
    if (!$package_id) {
        echo json_encode(['success' => false, 'message' => 'Package ID required']);
        exit;
    }
    
    // Get all questions in the package along with their success rate
    $stmt = $db->prepare("
        SELECT 
            q.id as question_id,
            q.question_text,
            COUNT(al.id) as total_answers,
            SUM(al.is_correct) as correct_answers,
            ROUND(IFNULL(SUM(al.is_correct) / NULLIF(COUNT(al.id), 0) * 100, 0), 2) as success_rate
        FROM questions q
        LEFT JOIN answer_logs al ON q.id = al.question_id
        WHERE q.package_id = :pid
        GROUP BY q.id
        ORDER BY q.sort_order, q.id
    ");
    
    $stmt->execute([':pid' => $package_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $results]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
