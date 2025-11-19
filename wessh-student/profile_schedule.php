<?php
session_start();

// Proper session check
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

include dirname(__DIR__) . '/includes/db.php';

$user_id = $_SESSION['user_id'];  // User ID from users table
$message = '';
$errors = [];

// Simple CSRF token for form security (generate once per session)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    // CSRF check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $first_name = filter_input(INPUT_POST, 'firstname', FILTER_SANITIZE_STRING);
        $last_name = filter_input(INPUT_POST, 'lastname', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $contact_number = filter_input(INPUT_POST, 'contact_number', FILTER_SANITIZE_STRING);

        // Basic email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format.';
        }

        if (empty($errors)) {
            try {
                // Update users table for firstname, lastname, email
                $stmt = $pdo->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ? WHERE user_id = ?");
                $stmt->execute([$first_name, $last_name, $email, $user_id]);

                // Update students table for contact_number
                $stmt_student = $pdo->prepare("UPDATE students SET contact_number = ? WHERE student_id = ?");
                $stmt_student->execute([$contact_number, $user_id]); // Assuming student_id = user_id

                $message = 'Profile updated successfully!';
            } catch (PDOException $e) {
                $errors[] = 'Failed to update profile. Please try again. Error: ' . $e->getMessage();
            }
        }
    }
}

// Fetch user data from users table and join with students for contact_number
try {
    $stmt = $pdo->prepare("
        SELECT u.*, s.contact_number
        FROM users u
        LEFT JOIN students s ON u.user_id = s.student_id
        WHERE u.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $errors[] = 'User data not found.';
    }
} catch (PDOException $e) {
    $errors[] = 'Failed to load user data. Error: ' . $e->getMessage();
}

// Fetch schedule - FIXED: Join with enrollments to filter by student
$schedules = [];
if (empty($errors)) {
    try {
        $stmt_sched = $pdo->prepare("
            SELECT 
                s.schedule_id,
                sub.subject_code,
                u.firstname AS teacher_first,
                u.lastname AS teacher_last,
                b.block_name,
                s.day,
                s.start_time,
                s.end_time,
                s.room
            FROM schedules s
            JOIN subjects sub ON s.subject_id = sub.subject_id
            JOIN users u ON s.teacher_id = u.user_id
            JOIN blocks b ON s.block_id = b.block_id
            JOIN enrollments e ON s.block_id = e.block_id
            WHERE e.student_id = ?  -- Filter by student_id from enrollments table
            ORDER BY FIELD(s.day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), s.start_time ASC
        ");
        $stmt_sched->execute([$user_id]);
        $schedules = $stmt_sched->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errors[] = 'Failed to load schedule. Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>WESSH - Student Dashboard</title>

    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">

    <!-- === SIDEBAR ACTIVE HIGHLIGHT === -->
    <style>
        #accordionSidebar .nav-item.active .nav-link {
            font-weight: 700 !important;
        }

        #accordionSidebar .nav-item.active .nav-link i {
            font-weight: 900 !important;
        }

        #accordionSidebar .nav-item.active .nav-link {
            background-color: rgba(255, 255, 255, .1);
        }
    </style>

    <style>
        .btn-block i {
            width: 1.25em;
            text-align: center;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include 'student_sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">

                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fas fa-bars"></i>
                    </button>
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php echo htmlspecialchars($user['firstname'] ?? 'Unknown') . ' ' . htmlspecialchars($user['lastname'] ?? 'User'); ?>
                                </span>
                                <img class="img-profile rounded-circle" src="../img/undraw_profile.svg">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in">
                                <a class="dropdown-item" href="profile_schedule.php">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i> Profile & Schedule
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="../logout.php">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>

                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Profile & Schedule</h1>

                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Profile Form -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Profile Information</h6>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <input type="hidden" name="csrf_token"
                                            value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <div class="form-group">
                                            <label for="firstname">First Name</label>
                                            <input type="text" class="form-control" id="firstname" name="firstname"
                                                value="<?php echo htmlspecialchars($user['firstname'] ?? ''); ?>"
                                                required>
                                        </div>
                                        <div class="form-group">
                                            <label for="lastname">Last Name</label>
                                            <input type="text" class="form-control" id="lastname" name="lastname"
                                                value="<?php echo htmlspecialchars($user['lastname'] ?? ''); ?>"
                                                required>
                                        </div>
                                        <div class="form-group">
                                            <label for="email">Email</label>
                                            <input type="email" class="form-control" id="email" name="email"
                                                value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="contact_number">Contact Number</label>
                                            <input type="text" class="form-control" id="contact_number"
                                                name="contact_number"
                                                value="<?php echo htmlspecialchars($user['contact_number'] ?? ''); ?>"
                                                required>
                                        </div>
                                        <button type="submit" name="update_profile" class="btn btn-primary">Update
                                            Profile</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Class Schedule -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Class Schedule</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($schedules)): ?>
                                        <p>No schedule assigned yet. Complete enrollment to view your classes.</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered" width="100%" cellspacing="0">
                                                <thead>
                                                    <tr>
                                                        <th>Subject</th>
                                                        <th>Teacher</th>
                                                        <th>Day</th>
                                                        <th>Time</th>
                                                        <th>Room</th>
                                                        <th>Block</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($schedules as $sched): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($sched['subject_code']); ?></td>
                                                            <td><?php echo htmlspecialchars(($sched['teacher_first'] ?? 'Unknown') . ' ' . ($sched['teacher_last'] ?? 'Teacher')); ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($sched['day']); ?></td>
                                                            <td><?php echo htmlspecialchars(($sched['start_time'] ?? 'TBA') . ' - ' . ($sched['end_time'] ?? 'TBA')); ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($sched['room'] ?? 'TBA'); ?></td>
                                                            <td><?php echo htmlspecialchars($sched['block_name']); ?></td>
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
                </div>
            </div>

            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright © WESSH 2024</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../js/sb-admin-2.min.js"></script>
</body>

</html>