<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'registrar') {
    http_response_code(403);
    exit('Unauthorized');
}

include dirname(__DIR__) . '/includes/db.php';

if (isset($_GET['block_id'])) {
    $block_id = $_GET['block_id'];

    try {
        // Fetch subjects associated with the selected block via schedules
        $stmt = $pdo->prepare("
            SELECT DISTINCT s.subject_id, s.subject_name
            FROM subjects s
            JOIN schedules sch ON s.subject_id = sch.subject_id
            WHERE sch.block_id = ?
            ORDER BY s.subject_name
        ");
        $stmt->execute([$block_id]);
        $subjects = $stmt->fetchAll();

        if (!empty($subjects)) {
            foreach ($subjects as $subject) {
                echo '<option value="' . $subject['subject_id'] . '">' . htmlspecialchars($subject['subject_name']) . '</option>';
            }
        } else {
            echo '<option value="">No subjects found for this block</option>';
        }
    } catch (PDOException $e) {
        echo '<option value="">Error loading subjects</option>';
    }
} else {
    echo '<option value="">Invalid request</option>';
}
?>