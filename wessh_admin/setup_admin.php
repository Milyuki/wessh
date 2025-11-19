<?php
include __DIR__ . '/../includes/db.php';

try {
    // Fixed password hash from wessh_db.sql for 'admin123'
    $password = '$2y$10$UIty3IVnKjEBOMm7GtaY0.Je9PB3QaP1ICXICJ1bp5kAyqbDdAkFa';
    $email = 'admin@wessh.com';
    $user_type = 'admin';
    $firstname = 'Admin';
    $lastname = 'User';

    // Check if admin exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_type = 'admin' LIMIT 1");
    $stmt->execute();
    $existing = $stmt->fetch();

    if (!$existing) {
        // Insert new admin
        $stmt = $pdo->prepare("INSERT INTO users (password, email, user_type, firstname, lastname, status) VALUES (?, ?, ?, ?, ?, 'approved')");
        $stmt->execute([$password, $email, $user_type, $firstname, $lastname]);
        echo "Admin user created successfully. Email: admin@wessh.com, Password: admin123";
    } else {
        // Update existing admin with correct password and details
        $stmt = $pdo->prepare("UPDATE users SET password = ?, email = ?, firstname = ?, lastname = ?, status = 'approved' WHERE user_type = 'admin'");
        $stmt->execute([$password, $email, $firstname, $lastname]);
        echo "Admin user updated successfully. Email: admin@wessh.com, Password: admin123";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>