<?php
include 'includes/db.php';

try {
    $stmt = $pdo->query('DESCRIBE enrollments');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Enrollments table structure:\n";
    foreach ($columns as $col) {
        echo $col['Field'] . ' - ' . $col['Type'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>