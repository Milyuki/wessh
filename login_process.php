<?php
session_start();
require_once __DIR__ . '/db_connect.php';
// Assuming this is your MySQLi connection file

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password']);

    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
    }

    if ($_SESSION['login_attempts'] >= 3) {
        echo "Too many failed attempts. Try again later.";
        session_destroy();
        exit;
    }

    try {
        // First, check users table
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] !== 'approved') {
                echo "Your account is pending approval. Please wait for admin approval.";
                exit;
            }
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['name'] = $user['firstname'] . ' ' . $user['lastname'];

            // Redirect based on user_type
            if ($user['user_type'] === 'admin') {
                header("Location: wessh_admin/admin_dashboard.php");
            } elseif ($user['user_type'] === 'teacher') {
                header("Location: wessh_teacher/dashboard.php");
            } elseif ($user['user_type'] === 'registrar') {
                header("Location: wessh_registrar/Registrar_Dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        } else {
            // If not in users, check students table
            $stmt2 = $conn->prepare("SELECT * FROM students WHERE email = ?");
            $stmt2->bind_param("s", $email);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $student = $result2->fetch_assoc();

            if ($student && password_verify($password, $student['password'])) {
                if ($student['status'] !== 'approved') {
                    echo "Your account is pending approval. Please wait for admin approval.";
                    exit;
                }
                $_SESSION['user_id'] = $student['student_id'];
                $_SESSION['user_type'] = 'student';
                $_SESSION['name'] = $student['full_name'];
                header("Location: wessh-student/dashboard.php");
                exit;
            } else {
                // Invalid credentials
                $_SESSION['login_attempts']++;
                echo "Invalid credentials. Attempts left: " . (3 - $_SESSION['login_attempts']);
            }
            $stmt2->close();
        }

        $stmt->close();

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>