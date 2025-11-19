<?php
// session_start();
// if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
//     header("Location: login.php");
//     exit;
// }

require 'vendor/autoload.php';
include 'includes/db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Create new Spreadsheet object
$spreadsheet = new Spreadsheet();

// Sheet 1: Blocks Stats
$sheet1 = $spreadsheet->getActiveSheet();
$sheet1->setTitle('Blocks Stats');

// Fetch blocks stats
try {
    $stmt_blocks = $pdo->prepare("SELECT section AS block, COUNT(*) AS count FROM enrollments WHERE YEAR(created_at) = ? GROUP BY section ORDER BY section");
    $stmt_blocks->execute([$selected_year]);
    $blocks_stats = $stmt_blocks->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $blocks_stats = [];
}

// Headers
$sheet1->setCellValue('A1', 'Block');
$sheet1->setCellValue('B1', 'Count');

// Data
$row = 2;
foreach ($blocks_stats as $stat) {
    $sheet1->setCellValue('A' . $row, $stat['block']);
    $sheet1->setCellValue('B' . $row, $stat['count']);
    $row++;
}

// Sheet 2: Years Stats
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('Years Stats');

// Fetch years stats
try {
    $stmt_years = $pdo->query("SELECT YEAR(created_at) AS year, COUNT(*) AS count FROM enrollments GROUP BY YEAR(created_at) ORDER BY year DESC");
    $years_stats = $stmt_years->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $years_stats = [];
}

// Headers
$sheet2->setCellValue('A1', 'Year');
$sheet2->setCellValue('B1', 'Count');

// Data
$row = 2;
foreach ($years_stats as $stat) {
    $sheet2->setCellValue('A' . $row, $stat['year']);
    $sheet2->setCellValue('B' . $row, $stat['count']);
    $row++;
}

// Sheet 3: Strands Stats
$sheet3 = $spreadsheet->createSheet();
$sheet3->setTitle('Strands Stats');

// Fetch strands stats
try {
    $query_strand = "SELECT strand, COUNT(*) AS count FROM enrollments WHERE YEAR(created_at) = ? GROUP BY strand ORDER BY strand";
    $stmt_strand = $pdo->prepare($query_strand);
    $stmt_strand->execute([$selected_year]);
    $strand_stats = $stmt_strand->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $strand_stats = [];
}

// Headers
$sheet3->setCellValue('A1', 'Strand');
$sheet3->setCellValue('B1', 'Count');

// Data
$row = 2;
foreach ($strand_stats as $stat) {
    $sheet3->setCellValue('A' . $row, $stat['strand']);
    $sheet3->setCellValue('B' . $row, $stat['count']);
    $row++;
}

// Sheet 4: Detailed Student Reports
$sheet4 = $spreadsheet->createSheet();
$sheet4->setTitle('Detailed Student Reports');

// Fetch detailed student data
try {
    $query_students = "SELECT id, first_name, last_name, section AS block, strand, grade_level, status, created_at FROM enrollments WHERE YEAR(created_at) = ? ORDER BY section, last_name, first_name";
    $stmt_students = $pdo->prepare($query_students);
    $stmt_students->execute([$selected_year]);
    $students_data = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $students_data = [];
}

// Headers
$sheet4->setCellValue('A1', 'ID');
$sheet4->setCellValue('B1', 'First Name');
$sheet4->setCellValue('C1', 'Last Name');
$sheet4->setCellValue('D1', 'Block');
$sheet4->setCellValue('E1', 'Strand');
$sheet4->setCellValue('F1', 'Grade Level');
$sheet4->setCellValue('G1', 'Status');
$sheet4->setCellValue('H1', 'Enrollment Date');

// Data
$row = 2;
foreach ($students_data as $student) {
    $sheet4->setCellValue('A' . $row, $student['id']);
    $sheet4->setCellValue('B' . $row, $student['first_name']);
    $sheet4->setCellValue('C' . $row, $student['last_name']);
    $sheet4->setCellValue('D' . $row, $student['block']);
    $sheet4->setCellValue('E' . $row, $student['strand']);
    $sheet4->setCellValue('F' . $row, $student['grade_level']);
    $sheet4->setCellValue('G' . $row, $student['status']);
    $sheet4->setCellValue('H' . $row, date('Y-m-d', strtotime($student['created_at'])));
    $row++;
}

// Set active sheet back to first sheet
$spreadsheet->setActiveSheetIndex(0);

// Redirect output to a client’s web browser (Xlsx)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="statistics_' . $selected_year . '-' . ($selected_year + 1) . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>