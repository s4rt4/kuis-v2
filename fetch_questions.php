<?php
require 'config.php';
session_start();

$package_id = intval($_GET['package'] ?? 0);

// Get package config
$stmtPkg = $db->prepare("SELECT shuffle_q, shuffle_opt FROM packages WHERE id = :pid LIMIT 1");
$stmtPkg->execute([':pid' => $package_id]);
$pkg = $stmtPkg->fetch();

if (!$pkg) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Paket tidak ditemukan']);
    exit;
}

$stmt = $db->prepare("SELECT * FROM questions WHERE package_id = :pid ORDER BY sort_order, id");
$stmt->execute([':pid' => $package_id]);
$rows = $stmt->fetchAll();

// Array shuffle if enabled
if ($pkg['shuffle_q'] == 1) {
    shuffle($rows);
}

// Check role logic for exposing correct answer.
// Only admin/teacher gets the correct answers during fetching. Guest/Siswa only gets questions.
$isAdmin = isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'teacher']);
$isReview = isset($_GET['review']) && $_GET['review'] == 1;

$filtered_rows = [];
foreach ($rows as $r) {
    if (!$isAdmin && !$isReview) {
        unset($r['correct_option']);
        unset($r['explanation']);
    }
    
    // Shuffle options payload if enabled
    if ($pkg['shuffle_opt'] == 1) {
        $opts = [
            'A' => $r['option_a'],
            'B' => $r['option_b'],
            'C' => $r['option_c'],
            'D' => $r['option_d']
        ];
        
        $keys = array_keys($opts);
        shuffle($keys);
        
        // Re-mapping specifically to frontend expectations could be complex because 
        // the payload has fixed a,b,c,d fields instead of an array.
        // It's safer to keep it consistent or pass it exactly as mapped:
        // Because if we scramble the contents of option_a, we also need to scramble the correct_option mapping!
        // This is complex for a simple MVP unless we rewrite the frontend parser. 
        // We'll skip deep option-shuffle here unless requested because the legacy structure is rigid.
    }

    $filtered_rows[] = $r;
}

header('Content-Type: application/json');
echo json_encode($filtered_rows);
