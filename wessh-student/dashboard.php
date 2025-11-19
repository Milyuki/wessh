<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

include dirname(__DIR__) . '/includes/db.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT firstname, lastname FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt_enroll = $pdo->prepare("
    SELECT status, submission_date
    FROM enrollments
    WHERE student_id = ?
    ORDER BY submission_date DESC
    LIMIT 1
");
$stmt_enroll->execute([$user_id]);
$enrollment = $stmt_enroll->fetch(PDO::FETCH_ASSOC);

$stmt_docs = $pdo->prepare("
    SELECT COUNT(*) AS count
    FROM documents d
    WHERE d.student_id = ?
");
$stmt_docs->execute([$user_id]);
$docs_count = $stmt_docs->fetch(PDO::FETCH_ASSOC)['count'];

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
    WHERE e.student_id = ?
    ORDER BY FIELD(s.day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), s.start_time ASC
");
$stmt_sched->execute([$user_id]);
$schedules = $stmt_sched->fetchAll(PDO::FETCH_ASSOC);

if (!$enrollment) {
    $enrollment_status = 'Not yet Enroll';
} else {
    $status = $enrollment['status'];
    if ($status === 'Not Started') {
        $enrollment_status = 'Not yet Enroll';
    } elseif ($docs_count < 5) {
        $enrollment_status = 'under reviewing';
    } elseif ($status === 'For Checking') {
        $enrollment_status = 'under reviewing';
    } elseif ($status === 'Partially Enrolled') {
        $enrollment_status = 'under reviewing';
    } elseif ($status === 'Approved') {
        $enrollment_status = 'Enrolled';
    } elseif ($status === 'Rejected') {
        $enrollment_status = 'under reviewing';
    } else {
        $enrollment_status = 'under reviewing';
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

<body id="page-top"
    class="<?php echo isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] === 'true' ? 'dark-mode' : ''; ?>">

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
                                    <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
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
                    <h1 class="h3 mb-4 text-gray-800">Student Dashboard</h1>

                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Welcome</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                Hello,
                                                <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>!
                                            </div>
                                            <div class="small text-gray-600">Welcome to the WESSH Student Portal</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-user fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xl-6 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Enrollment Status</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="enrollment-status">
                                                <?php echo htmlspecialchars($enrollment_status); ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-6 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Submitted Documents</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="docs-count">
                                                <?php echo $docs_count; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">

                        <div class="col-lg-6 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Announcements</h6>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Fetch latest enrollment schedule announcement
                                    $stmt_announcement = $pdo->prepare("
                                        SELECT title, content, created_at
                                        FROM announcements
                                        WHERE announcement_type = 'enrollment_schedule' AND is_active = TRUE
                                        ORDER BY created_at DESC
                                        LIMIT 1
                                    ");
                                    $stmt_announcement->execute();
                                    $announcement = $stmt_announcement->fetch(PDO::FETCH_ASSOC);

                                    if ($announcement) {
                                        echo '<h5>' . htmlspecialchars($announcement['title']) . '</h5>';
                                        echo '<p>' . nl2br(htmlspecialchars($announcement['content'])) . '</p>';
                                        echo '<small class="text-muted">Posted on: ' . htmlspecialchars(date('F j, Y', strtotime($announcement['created_at']))) . '</small>';
                                    } else {
                                        echo '<h5>Enrollment Schedule</h5>';
                                        echo '<p><strong>Date:</strong> Wait for Further Announcements</p>';
                                        echo '<p><strong>Time:</strong> Wait for Further Announcements</p>';
                                        echo '<p>Please submit <strong>all 5 required documents</strong> before the deadline.</p>';
                                    }
                                    ?>
                                </div>
                            </div>

                            <?php if ($enrollment_status === 'Not Started'): ?>
                                <div class="card shadow mb-4 border-left-warning">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-warning">Start Enrollment</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <p>You haven't started yet.</p>
                                        <a href="../enroll.php" class="btn btn-warning btn-block">
                                            <i class="fas fa-edit fa-fw mr-2"></i>Start Enrollment
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-lg-6 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Class Schedule</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($schedules)): ?>
                                        <?php echo $enrollment_status === 'Not Started' ? '<p>Not Yet Enrolled</p>' : '<p>No Class Schedule yet</p>'; ?>
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

                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
                                </div>
                                <div class="card-body">
                                    <p>Last submission:
                                        <strong><?php echo $enrollment ? htmlspecialchars($enrollment['submission_date']) : 'None'; ?></strong>
                                    </p>
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

            <script>
                function refreshStatus() {
                    $.ajax({
                        url: 'get_status.php',
                        type: 'GET',
                        dataType: 'json',
                        success: function (data) {
                            $('#enrollment-status').text(data.status);
                            $('#docs-count').text(data.docs_count);
                        },
                        error: function () {
                            alert('Refresh failed. Please try again.');
                        }
                    });
                }
            </script>

</body>

</html>