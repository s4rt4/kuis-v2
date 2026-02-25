<?php
// download_template.php - Download CSV Template for Excel/GSheets Import
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

$filename = "Template_Soal_Kuis_V2.csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Headers
fputcsv($output, [
    'Teks Soal', 
    'Opsi A', 
    'Opsi B', 
    'Opsi C', 
    'Opsi D', 
    'Jawaban Benar (A/B/C/D)', 
    'Pembahasan (Opsional)',
    'URL Gambar Soal (Opsional)'
]);

// Dummy Data Row 1
fputcsv($output, [
    'Berapakah hasil dari 5 x 5?',
    '10',
    '20',
    '25',
    '30',
    'C',
    '5 dikali 5 sama dengan 25',
    ''
]);

// Dummy Data Row 2
fputcsv($output, [
    'Apa nama ibukota Indonesia?',
    'Jakarta',
    'Bandung',
    'Surabaya',
    'Medan',
    'A',
    '',
    'https://example.com/jakarta.jpg'
]);

fclose($output);
exit;
