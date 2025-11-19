<?php
include 'includes/db.php';

try {
    $stmt = $pdo->prepare("UPDATE documents SET enrollment_id = (SELECT enrollment_id FROM enrollments WHERE student_id = documents.student_id LIMIT 1) WHERE enrollment_id IS NULL");
    $stmt->execute();
    echo "Updated documents with enrollment_id.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>