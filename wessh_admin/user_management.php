<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session and check admin access
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Include MySQLi database connection
include '../db_connect.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        // Safely get and trim inputs with null coalescing
        $password = trim($_POST['password'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $user_type = $_POST['user_type'] ?? '';
        $status = $_POST['status'] ?? '';
        $firstname = trim($_POST['firstname'] ?? '');
        $lastname = trim($_POST['lastname'] ?? '');

        // Basic validation
        $errors = [];
        if (empty($password))
            $errors[] = "Password is required.";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
            $errors[] = "Valid email is required.";
        if (empty($user_type) || !in_array($user_type, ['teacher', 'registrar']))
            $errors[] = "Invalid user type.";
        if (empty($status) || !in_array($status, ['approved', 'pending', 'rejected', 'deleted']))
            $errors[] = "Invalid status.";
        if (empty($firstname))
            $errors[] = "First name is required.";
        if (empty($lastname))
            $errors[] = "Last name is required.";

        // Staff-specific validation
        if ($user_type === 'teacher' || $user_type === 'registrar') {
            $middle_name = trim($_POST['middle_name'] ?? '');
            $name_suffix = trim($_POST['name_suffix'] ?? '');
            $gender = $_POST['gender'] ?? '';
            $birth_date = trim($_POST['birthday'] ?? '');
            $mobile_number = trim($_POST['mobile_number'] ?? '');
            $rank = trim($_POST['rank'] ?? '');
            $employment_status = $_POST['employment_status'] ?? '';
            $date_hired = trim($_POST['date_hired'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if (empty($gender) || !in_array($gender, ['M', 'F', 'X']))
                $errors[] = "Valid gender is required.";
            if (empty($birth_date) || !strtotime($birth_date))
                $errors[] = "Valid birth date is required.";
            if (empty($mobile_number))
                $errors[] = "Mobile number is required.";
            if (empty($rank))
                $errors[] = "Rank is required.";
            if (empty($employment_status) || !in_array($employment_status, ['Regular', 'Probationary', 'Part-time']))
                $errors[] = "Valid employment status is required.";
            if (empty($date_hired) || !strtotime($date_hired))
                $errors[] = "Valid date hired is required.";
        }

        if (empty($errors)) {
            // Check email duplicate
            $stmt_check = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $count = $stmt_check->get_result()->fetch_row()[0];
            $stmt_check->close();

            if ($count > 0) {
                $errors[] = "Email already exists.";
            } else {
                // Insert into users
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (password, email, user_type, firstname, lastname, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $hashed_password, $email, $user_type, $firstname, $lastname, $status);

                if ($stmt->execute()) {
                    $user_id = $conn->insert_id;

                    // Insert into staff if teacher/registrar
                    if ($user_type === 'teacher' || $user_type === 'registrar') {
                        $stmt_staff = $conn->prepare("
                            INSERT INTO staff (
                                user_id, first_name, middle_name, last_name, name_suffix, gender, birth_date, mobile_number, email,
                                rank, employment_status, date_hired, is_active
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt_staff->bind_param(
                            "isssssssssssi",
                            $user_id,
                            $firstname,
                            $middle_name,
                            $lastname,
                            $name_suffix,
                            $gender,
                            $birth_date,
                            $mobile_number,
                            $email,
                            $rank,
                            $employment_status,
                            $date_hired,
                            $is_active
                        );
                        if ($stmt_staff->execute()) {
                            $success = "User and staff details added successfully.";
                        } else {
                            $error = "Error adding staff details: " . $conn->error;
                        }
                        $stmt_staff->close();
                    }
                    if (!isset($success)) {
                        $success = "User added successfully.";
                    }
                } else {
                    $error = "Error adding user: " . $conn->error;
                }
                $stmt->close();
            }
        }
        if (!empty($errors)) {
            $error = implode("<br>", $errors);
        }
    } elseif (isset($_POST['approve_user'])) {
        $user_id = $_POST['user_id'] ?? 0;
        $user_type = $_POST['user_type'] ?? 'user';
        if ($user_type === 'student') {
            $stmt = $conn->prepare("UPDATE students SET status = 'approved' WHERE student_id = ?");
            $stmt->bind_param("i", $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET status = 'approved' WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
        }
        $stmt->execute() ? $success = "Approved successfully." : $error = "Error: " . $conn->error;
        $stmt->close();
    } elseif (isset($_POST['reject_user'])) {
        $user_id = $_POST['user_id'] ?? 0;
        $user_type = $_POST['user_type'] ?? 'user';
        if ($user_type === 'student') {
            $stmt = $conn->prepare("UPDATE students SET status = 'rejected' WHERE student_id = ?");
            $stmt->bind_param("i", $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET status = 'rejected' WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
        }
        $stmt->execute() ? $success = "Rejected successfully." : $error = "Error: " . $conn->error;
        $stmt->close();
    } elseif (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'] ?? 0;
        $user_type = $_POST['user_type'] ?? 'user';
        if ($user_type === 'student') {
            $stmt = $conn->prepare("DELETE FROM students WHERE student_id = ?");
            $stmt->bind_param("i", $user_id);
        } else {
            $stmt = $conn->prepare("SELECT user_type FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($user && in_array($user['user_type'], ['teacher', 'registrar'])) {
                $stmt_staff = $conn->prepare("DELETE FROM staff WHERE user_id = ?");
                $stmt_staff->bind_param("i", $user_id);
                $stmt_staff->execute();
                $stmt_staff->close();
            }
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
        }
        $stmt->execute() ? $success = "Deleted successfully." : $error = "Error: " . $conn->error;
        $stmt->close();
    }
}

// Fetch data
$stmt_users = $conn->prepare("SELECT user_id, email, user_type, firstname, lastname, status FROM users ORDER BY FIELD(status, 'approved', 'pending', 'rejected', 'deleted'), user_type ASC");
$stmt_users->execute();
$users = $stmt_users->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_users->close();

$stmt_students = $conn->prepare("SELECT student_id as user_id, email, 'student' as user_type, first_name as firstname, last_name as lastname, status FROM students ORDER BY date_registered DESC");
$stmt_students->execute();
$students = $stmt_students->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_students->close();

// Group
$grouped_students = ['approved' => [], 'pending' => [], 'rejected' => [], 'deleted' => []];
foreach ($students as $s)
    $grouped_students[$s['status']][] = $s;

$grouped_staff = ['approved' => [], 'pending' => [], 'rejected' => [], 'deleted' => []];
foreach ($users as $u)
    $grouped_staff[$u['status']][] = $u;

// Staff details
$stmt_staff = $conn->prepare("
    SELECT u.user_id, u.email, u.user_type, u.firstname, u.lastname, u.status,
           CONCAT(s.first_name, ' ', s.last_name) AS full_name, s.birth_date,
           s.rank, s.employment_status, s.date_hired
    FROM users u
    LEFT JOIN staff s ON u.user_id = s.user_id
    WHERE u.user_type IN ('teacher', 'registrar')
    ORDER BY u.status, u.user_type ASC
");
$stmt_staff->execute();
$staff_users = $stmt_staff->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_staff->close();

// Messages
$stmt_messages = $conn->prepare("SELECT m.id, m.message, m.status, u.email as sender FROM messages m JOIN users u ON m.sender_id = u.user_id WHERE m.receiver_id = ? ORDER BY m.id DESC");
$stmt_messages->bind_param("i", $_SESSION['user_id']);
$stmt_messages->execute();
$messages = $stmt_messages->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_messages->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>WESSH - User Management</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include 'sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">Admin User</span>
                                <img class="img-profile rounded-circle" src="../img/undraw_profile.svg">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in">
                                <a class="dropdown-item" href="#"><i
                                        class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i> Profile</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="../logout.php"><i
                                        class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Logout</a>
                            </div>
                        </li>
                    </ul>
                </nav>

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">User Management</h1>
                        <button class="btn btn-primary" data-toggle="modal" data-target="#addUserModal"><i
                                class="fas fa-plus"></i> Add User</button>
                    </div>

                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <!-- STUDENT MANAGEMENT (TOP) -->
                    <div class="row">
                        <div class="col-lg-12 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Student Management</h6>
                                </div>
                                <div class="card-body">
                                    <ul class="nav nav-tabs" id="studentTabs" role="tablist">
                                        <li class="nav-item"><a class="nav-link active" data-toggle="tab"
                                                href="#student-approved">Approved</a></li>
                                        <li class="nav-item"><a class="nav-link" data-toggle="tab"
                                                href="#student-pending">Pending</a></li>
                                        <li class="nav-item"><a class="nav-link" data-toggle="tab"
                                                href="#student-rejected">Rejected</a></li>
                                        <li class="nav-item"><a class="nav-link" data-toggle="tab"
                                                href="#student-deleted">Deleted</a></li>
                                    </ul>
                                    <div class="tab-content" id="studentTabsContent">
                                        <!-- Approved Students -->
                                        <div class="tab-pane fade show active" id="student-approved">
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>ID</th>
                                                            <th>Email</th>
                                                            <th>Name</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($grouped_students['approved'] as $s): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($s['user_id']); ?></td>
                                                                <td><?php echo htmlspecialchars($s['email']); ?></td>
                                                                <td><?php echo htmlspecialchars($s['firstname'] . ' ' . $s['lastname']); ?>
                                                                </td>
                                                                <td>
                                                                    <button class="btn btn-danger btn-sm"
                                                                        onclick="rejectUser(<?php echo $s['user_id']; ?>, 'student')">Reject</button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <!-- Pending Students -->
                                        <div class="tab-pane fade" id="student-pending">
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>ID</th>
                                                            <th>Email</th>
                                                            <th>Name</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($grouped_students['pending'] as $s): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($s['user_id']); ?></td>
                                                                <td><?php echo htmlspecialchars($s['email']); ?></td>
                                                                <td><?php echo htmlspecialchars($s['firstname'] . ' ' . $s['lastname']); ?>
                                                                </td>
                                                                <td>
                                                                    <button class="btn btn-success btn-sm"
                                                                        onclick="approveUser(<?php echo $s['user_id']; ?>, 'student')">Approve</button>
                                                                    <button class="btn btn-warning btn-sm"
                                                                        onclick="rejectUser(<?php echo $s['user_id']; ?>, 'student')">Reject</button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <!-- Rejected Students -->
                                        <div class="tab-pane fade" id="student-rejected">
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>ID</th>
                                                            <th>Email</th>
                                                            <th>Name</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($grouped_students['rejected'] as $s): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($s['user_id']); ?></td>
                                                                <td><?php echo htmlspecialchars($s['email']); ?></td>
                                                                <td><?php echo htmlspecialchars($s['firstname'] . ' ' . $s['lastname']); ?>
                                                                </td>
                                                                <td>
                                                                    <button class="btn btn-danger btn-sm"
                                                                        onclick="confirmDelete(<?php echo $s['user_id']; ?>, 'student')">Delete</button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <!-- Deleted Students -->
                                        <div class="tab-pane fade" id="student-deleted">
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>ID</th>
                                                            <th>Email</th>
                                                            <th>Name</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($grouped_students['deleted'] as $s): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($s['user_id']); ?></td>
                                                                <td><?php echo htmlspecialchars($s['email']); ?></td>
                                                                <td><?php echo htmlspecialchars($s['firstname'] . ' ' . $s['lastname']); ?>
                                                                </td>
                                                                <td>
                                                                    <button class="btn btn-danger btn-sm"
                                                                        onclick="confirmDelete(<?php echo $s['user_id']; ?>, 'student')">Delete</button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STAFF MANAGEMENT (BELOW) -->
                    <div class="row">
                        <div class="col-lg-12 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Staff Management</h6>
                                </div>
                                <div class="card-body">
                                    <ul class="nav nav-tabs" id="staffTabs" role="tablist">
                                        <li class="nav-item"><a class="nav-link active" data-toggle="tab"
                                                href="#staff-approved">Approved</a></li>
                                        <li class="nav-item"><a class="nav-link" data-toggle="tab"
                                                href="#staff-pending">Pending</a></li>
                                        <li class="nav-item"><a class="nav-link" data-toggle="tab"
                                                href="#staff-rejected">Rejected</a></li>
                                        <li class="nav-item"><a class="nav-link" data-toggle="tab"
                                                href="#staff-deleted">Deleted</a></li>
                                    </ul>
                                    <div class="tab-content" id="staffTabsContent">
                                        <!-- Approved Staff -->
                                        <div class="tab-pane fade show active" id="staff-approved">
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>ID</th>
                                                            <th>Email</th>
                                                            <th>User Type</th>
                                                            <th>Name</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($grouped_staff['approved'] as $s): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($s['user_id']); ?></td>
                                                                <td><?php echo htmlspecialchars($s['email']); ?></td>
                                                                <td><?php echo htmlspecialchars($s['user_type']); ?></td>
                                                                <td><?php echo htmlspecialchars($s['firstname'] . ' ' . $s['lastname']); ?>
                                                                </td>
                                                                <td>
                                                                    <button class="btn btn-danger btn-sm"
                                                                        onclick="rejectUser(<?php echo $s['user_id']; ?>, '<?php echo $s['user_type']; ?>')">Reject</button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <!-- Pending, Rejected, Deleted — same structure -->
                                        <!-- (Omitted for brevity — same as above) -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- REGISTERED STAFF INFO -->
                    <div class="row">
                        <div class="col-lg-12 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Registered Staff Information (Teachers
                                        & Registrars)</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($staff_users)): ?>
                                        <p>No staff registered yet.</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Name</th>
                                                        <th>Email</th>
                                                        <th>User Type</th>
                                                        <th>Position/Rank</th>
                                                        <th>Employment Status</th>
                                                        <th>Date Joined</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($staff_users as $s): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($s['user_id']); ?></td>
                                                            <td><?php echo htmlspecialchars($s['full_name'] ?? 'N/A'); ?></td>
                                                            <td><?php echo htmlspecialchars($s['email']); ?></td>
                                                            <td><?php echo htmlspecialchars($s['user_type']); ?></td>
                                                            <td><?php echo htmlspecialchars($s['rank'] ?? 'N/A'); ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($s['employment_status'] ?? 'N/A'); ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($s['date_hired'] ?? 'N/A'); ?></td>
                                                            <td><?php echo htmlspecialchars($s['status']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MESSAGES -->
                    <div class="row">
                        <div class="col-lg-12 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Messages</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($messages)): ?>
                                        <p>No messages.</p>
                                    <?php else: ?>
                                        <ul class="list-group">
                                            <?php foreach ($messages as $m): ?>
                                                <li class="list-group-item">
                                                    <strong>From: <?php echo htmlspecialchars($m['sender']); ?></strong><br>
                                                    <?php echo htmlspecialchars($m['message']); ?>
                                                    <span
                                                        class="badge badge-<?php echo $m['status'] === 'unread' ? 'warning' : 'success'; ?>">
                                                        <?php echo $m['status']; ?>
                                                    </span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright © WESSH 2025</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- DELETE MODAL -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button class="close" type="button" data-dismiss="modal">×</button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <p>Are you sure you want to delete this user?</p>
                        <input type="hidden" id="delete_user_id" name="user_id">
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_user" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ADD USER MODAL -->
    <div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="firstname">First Name</label>
                            <input type="text" class="form-control" id="firstname" name="firstname" required>
                        </div>
                        <div class="form-group">
                            <label for="lastname">Last Name</label>
                            <input type="text" class="form-control" id="lastname" name="lastname" required>
                        </div>
                        <div class="form-group">
                            <label for="middle_name">Middle Name</label>
                            <input type="text" class="form-control" id="middle_name" name="middle_name">
                        </div>
                        <div class="form-group">
                            <label for="name_suffix">Name Suffix</label>
                            <input type="text" class="form-control" id="name_suffix" name="name_suffix"
                                placeholder="e.g., Jr., Sr.">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="user_type">User Type</label>
                            <select class="form-control" id="user_type" name="user_type" required>
                                <option value="">Select Type</option>
                                <option value="teacher">Teacher</option>
                                <option value="registrar">Registrar</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="approved">Approved</option>
                                <option value="pending">Pending</option>
                                <option value="rejected">Rejected</option>
                                <option value="deleted">Deleted</option>
                            </select>
                        </div>
                        <div id="staffDetails" style="display: none;">
                            <h6>Staff Details</h6>
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select class="form-control" id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="M">Male</option>
                                    <option value="F">Female</option>
                                    <option value="X">Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="birthday">Birth Date</label>
                                <input type="date" class="form-control" id="birthday" name="birthday" required>
                            </div>
                            <div class="form-group">
                                <label for="age">Age</label>
                                <input type="text" class="form-control" id="age" name="age" readonly>
                            </div>
                            <div class="form-group">
                                <label for="mobile_number">Mobile Number</label>
                                <input type="text" class="form-control" id="mobile_number" name="mobile_number"
                                    required>
                            </div>
                            <div class="form-group">
                                <label for="rank">Rank</label>
                                <input type="text" class="form-control" id="rank" name="rank" required>
                            </div>
                            <div class="form-group">
                                <label for="employment_status">Employment Status</label>
                                <select class="form-control" id="employment_status" name="employment_status" required>
                                    <option value="">Select Status</option>
                                    <option value="Regular">Regular</option>
                                    <option value="Probationary">Probationary</option>
                                    <option value="Part-time">Part-time</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="date_hired">Date Hired</label>
                                <input type="date" class="form-control" id="date_hired" name="date_hired" required>
                            </div>
                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                        checked>
                                    <label class="form-check-label" for="is_active">
                                        Is Active
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../js/sb-admin-2.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#user_type').change(function () {
                const type = $(this).val();
                $('#nameFields').toggle(type === 'teacher' || type === 'registrar');
                $('#staffDetails').toggle(type === 'teacher' || type === 'registrar');
                $('#teacherFields').toggle(type === 'teacher');
            });

            // Calculate age when birth date changes
            $('#birthday').change(function () {
                const birthDate = new Date($(this).val());
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                $('#age').val(age);
            });
        });

        function confirmDelete(id, type) {
            $('#delete_user_id').val(id);
            $('#deleteModal').modal('show');
        }

        function approveUser(id, type) {
            if (confirm('Approve this user?')) {
                const f = document.createElement('form');
                f.method = 'POST';
                f.innerHTML = `<input type="hidden" name="approve_user" value="1">
                               <input type="hidden" name="user_id" value="${id}">
                               <input type="hidden" name="user_type" value="${type}">`;
                document.body.appendChild(f); f.submit();
            }
        }

        function rejectUser(id, type) {
            if (confirm('Reject this user?')) {
                const f = document.createElement('form');
                f.method = 'POST';
                f.innerHTML = `<input type="hidden" name="reject_user" value="1">
                               <input type="hidden" name="user_id" value="${id}">
                               <input type="hidden" name="user_type" value="${type}">`;
                document.body.appendChild(f); f.submit();
            }
        }
    </script>
</body>

</html>