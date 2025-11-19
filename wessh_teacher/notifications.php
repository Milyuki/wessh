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
$_SESSION['user_type'] = $_SESSION['user_type'] ?? 'teacher';


// Check if user is logged in and is a teacher
// if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
//     header('Location: login.php');
//     exit();
// }

include dirname(__DIR__) . '/includes/db.php';

// Fetch notifications
try {
    $stmt = $pdo->prepare("
        SELECT n.*, u.name as student_name, e.status as enrollment_status
        FROM notifications n
        JOIN enrollments e ON n.enrollment_id = e.id
        JOIN users u ON e.student_id = u.id
        ORDER BY n.sent_date DESC
    ");
    $stmt->execute();
    $notifications = $stmt->fetchAll();
} catch (PDOException $e) {
    // If database error, set to empty array
    $notifications = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Notifications - WESSH Teacher Dashboard</title>
    <!-- Custom fonts for this template-->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper" class="d-flex">
        <?php include 'teacher_sidebar.php'; ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column flex-grow-1">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span
                                    class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Teacher'); ?></span>
                                <img class="img-profile rounded-circle" src="../img/undraw_profile.svg">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Profile
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Settings
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Activity Log
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>

                    </ul>

                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Notifications Management</h1>
                        <a href="dashboard.php" class="btn btn-secondary btn-sm">Back to Dashboard</a>
                    </div>

                    <!-- Notifications Table -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Sent Notifications</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="notificationsTable" width="100%"
                                    cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Message</th>
                                            <th>Sent Date</th>
                                            <th>Enrollment Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($notifications as $notification): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($notification['student_name']); ?></td>
                                                <td><?php echo htmlspecialchars($notification['message']); ?></td>
                                                <td><?php echo htmlspecialchars($notification['sent_date']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php
                                                    $status = $notification['enrollment_status'];
                                                    if ($status == 'Pending')
                                                        echo 'warning';
                                                    elseif ($status == 'Complete')
                                                        echo 'success';
                                                    elseif ($status == 'Incomplete')
                                                        echo 'danger';
                                                    else
                                                        echo 'secondary';
                                                    ?>"><?php echo htmlspecialchars($status); ?></span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-info btn-sm"
                                                        onclick="viewNotification(<?php echo $notification['id']; ?>)">View</button>
                                                    <button class="btn btn-warning btn-sm"
                                                        onclick="resendNotification(<?php echo $notification['id']; ?>)">Resend</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright © WESSH 2024</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="../logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Modal -->
    <div class="modal fade" id="notificationModal" tabindex="-1" role="dialog" aria-labelledby="notificationModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notificationModalLabel">Notification Details</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="notificationContent">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="../js/sb-admin-2.min.js"></script>

    <!-- Custom JavaScript for notifications -->
    <script>
        function viewNotification(id) {
            // In a real implementation, this would fetch notification details via AJAX
            $('#notificationContent').html('<p>Notification ID: ' + id + '</p><p>This is a sample notification message.</p>');
            $('#notificationModal').modal('show');
        }

        function resendNotification(id) {
            if (confirm('Are you sure you want to resend this notification?')) {
                // In a real implementation, this would send an AJAX request to resend
                alert('Notification resent successfully!');
            }
        }
    </script>
</body>

</html>