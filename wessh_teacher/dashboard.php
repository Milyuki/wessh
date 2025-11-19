<?php

session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'teacher') {
    header('Location: ../login.php');
    exit();
}

include dirname(__DIR__) . '/includes/db.php';


// Fetch metrics
try {

    // Pending reviews
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM enrollments WHERE status = 'Pending'");
    $stmt->execute();
    $pending = $stmt->fetch()['count'];

    // Approved enrollments
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM enrollments WHERE status = 'Approved'");
    $stmt->execute();
    $approved = $stmt->fetch()['count'];

    // Rejected enrollments
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM enrollments WHERE status = 'Rejected'");
    $stmt->execute();
    $incomplete = $stmt->fetch()['count'];

    // Fetch enrollments for table (join with users)
    $stmt = $pdo->prepare("
        SELECT e.enrollment_id, CONCAT(e.first_name, ' ', e.last_name) as student_name, e.submission_date, e.status
        FROM enrollments e
        ORDER BY e.submission_date DESC
    ");
    $stmt->execute();
    $enrollments = $stmt->fetchAll();
} catch (PDOException $e) {
    // If database error, set to 0
    $pending = 0;
    $approved = 0;
    $incomplete = 0;
    $enrollments = [];
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
    <title>Teacher Dashboard - WESSH Enrollment</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Teacher Dashboard</h1>
                        <a href="reports.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                            <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
                        </a>
                    </div>

                    <!-- Content Row for Metrics -->
                    <div class="row">
                        <!-- Pending Reviews Card -->
                        <div class="col-xl-3 col-md-6 col-12 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Pending Reviews</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Approved Enrollments -->
                        <div class="col-xl-3 col-md-6 col-12 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Approved Enrollments</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $approved; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Incomplete Enrollments -->
                        <div class="col-xl-3 col-md-6 col-12 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Rejected Enrollments</div>
                                            <div class="row no-gutters align-items-center">
                                                <div class="col-auto">
                                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                                        <?php echo $incomplete; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Total Enrollments -->
                        <div class="col-xl-3 col-md-6 col-12 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total
                                                Enrollments</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo count($enrollments); ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Enrollment Table -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Student Enrollments</h6>
                        </div>
                        <div class="card-body">
                            <!-- Search and Filter -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="searchInput">Search Student:</label>
                                    <input type="text" id="searchInput" class="form-control"
                                        placeholder="Enter student name...">
                                </div>
                                <div class="col-md-6">
                                    <label for="statusFilter">Filter by Status:</label>
                                    <select id="statusFilter" class="form-control">
                                        <option value="">All</option>
                                        <option value="Pending">Pending</option>
                                        <option value="For Checking">For Checking</option>
                                        <option value="Approved">Approved</option>
                                        <option value="Rejected">Rejected</option>
                                        <option value="Partially Enrolled">Partially Enrolled</option>
                                    </select>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="enrollmentsTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Student Name</th>
                                            <th>Submission Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($enrollments)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No one Enrolled yet</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($enrollments as $enrollment): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($enrollment['student_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($enrollment['submission_date']); ?></td>
                                                    <td>
                                                        <?php
                                                        $status = $enrollment['status'];
                                                        $badgeClass = 'badge-secondary';
                                                        if ($status == 'Pending')
                                                            $badgeClass = 'badge-warning';
                                                        elseif ($status == 'For Checking')
                                                            $badgeClass = 'badge-info';
                                                        elseif ($status == 'Rejected')
                                                            $badgeClass = 'badge-danger';
                                                        elseif ($status == 'Approved')
                                                            $badgeClass = 'badge-success';
                                                        elseif ($status == 'Partially Enrolled')
                                                            $badgeClass = 'badge-primary';
                                                        ?>
                                                        <span
                                                            class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                                                    </td>
                                                    <td>
                                                        <a href="review.php?id=<?php echo $enrollment['enrollment_id']; ?>"
                                                            class="btn btn-primary btn-sm">Review</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
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

    <!-- Bootstrap core JavaScript-->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="../js/sb-admin-2.min.js"></script>

    <!-- Custom JavaScript for search and filtering -->
    <script>
        $(document).ready(function () {
            // Search functionality
            $('#searchInput').on('keyup', function () {
                var searchValue = $(this).val().toLowerCase();
                $('#enrollmentsTable tbody tr').filter(function () {
                    var studentName = $(this).find('td:first').text().toLowerCase();
                    $(this).toggle(studentName.indexOf(searchValue) > -1);
                });
            });

            // Status filter functionality
            $('#statusFilter').on('change', function () {
                var filterValue = $(this).val().toLowerCase();
                $('#enrollmentsTable tbody tr').filter(function () {
                    var statusText = $(this).find('td:nth-child(3) span').text().toLowerCase();
                    if (filterValue === '') {
                        $(this).show();
                    } else {
                        $(this).toggle(statusText === filterValue);
                    }
                });
            });
        });
    </script>
</body>

</html>