<?php
session_start();
// TEMPORARY LOGIN BYPASS FOR DEVELOPMENT
// Commented out original login redirect for now.
// Original code was:
// if (!isset($_SESSION['user_id'])) {
//     header('Location: login.php');
//     exit();
// }
$_SESSION['user_id'] = $_SESSION['user_id'] ?? 1;
$_SESSION['user_type'] = $_SESSION['user_type'] ?? 'student';
// session_start();
// if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
//     http_response_code(403);
//     exit;
// }
include dirname(__DIR__) . '/includes/db.php';

$user_id = $_SESSION['user_id'];

// Fetch enrollment status
$stmt_enroll = $pdo->prepare("SELECT status, submission_date FROM enrollments WHERE student_id = ? ORDER BY submission_date DESC LIMIT 1");
$stmt_enroll->execute([$user_id]);
$enrollment = $stmt_enroll->fetch(PDO::FETCH_ASSOC);

// Determine enrollment status based on conditions
if (!$enrollment) {
    $status = 'Not Started';
} else {
    $enrollment_status = $enrollment['status'];
    // Fetch documents count for this enrollment
    $stmt_docs = $pdo->prepare("SELECT COUNT(*) as count FROM documents WHERE enrollment_id = (SELECT enrollment_id FROM enrollments WHERE student_id = ? ORDER BY submission_date DESC LIMIT 1)");
    $stmt_docs->execute([$user_id]);
    $docs_count = $stmt_docs->fetch(PDO::FETCH_ASSOC)['count'];

    if ($enrollment_status == 'Not Started') {
        $status = 'Not yet Enroll';
    } elseif ($docs_count < 5) {
        $status = 'under reviewing';
    } elseif ($enrollment_status == 'For Checking') {
        $status = 'under reviewing';
    } elseif ($enrollment_status == 'Partially Enrolled') {
        $status = 'under reviewing';
    } elseif ($enrollment_status == 'Approved') {
        $status = 'Enrolled';
    } elseif ($enrollment_status == 'Rejected') {
        $status = 'under reviewing';
    } else {
        $status = 'under reviewing';
    }
}

// Fetch total submitted documents count
$stmt_total_docs = $pdo->prepare("SELECT COUNT(*) as count FROM documents WHERE student_id = ?");
$stmt_total_docs->execute([$user_id]);
$docs_count = $stmt_total_docs->fetch(PDO::FETCH_ASSOC)['count'];

header('Content-Type: application/json');
echo json_encode(['status' => $status, 'docs_count' => $docs_count]);
?>