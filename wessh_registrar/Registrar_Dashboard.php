<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'registrar') {
    header("Location: registrar_login.php");
    exit;
}
// Fetch user name from database
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT CONCAT(firstname, ' ', lastname) as name FROM staff WHERE staff_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $staff = $stmt->fetch();
        if ($staff) {
            $_SESSION['name'] = $staff['name'];
        }
    } catch (PDOException $e) {
        // Ignore if table doesn't exist or error
    }
}

include dirname(__DIR__) . '/includes/db.php';

// Fetch metrics
try {
    // Total subjects
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM subjects");
    $stmt->execute();
    $total_subjects = $stmt->fetch()['count'];

    // Total teachers
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM staff WHERE role = 'Teacher'");
    $stmt->execute();
    $total_teachers = $stmt->fetch()['count'];

    // Total blocks
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM blocks");
    $stmt->execute();
    $total_blocks = $stmt->fetch()['count'];

    // Pending enrollments
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM enrollments WHERE status = 'Pending'");
    $stmt->execute();
    $pending_enrollments = $stmt->fetch()['count'];

    // Approved enrollments
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM enrollments WHERE status = 'Approved'");
    $stmt->execute();
    $approved_enrollments = $stmt->fetch()['count'];

    // Total enrollments
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM enrollments");
    $stmt->execute();
    $total_enrollments = $stmt->fetch()['count'];

    // Fetch students per block for approved enrollments (aggregated)
    $stmt = $pdo->prepare("
        SELECT b.block_name, COUNT(e.student_id) as student_count
        FROM enrollments e
        JOIN blocks b ON e.block_id = b.block_id
        WHERE e.status = 'Approved'
        GROUP BY b.block_id, b.block_name
        ORDER BY b.block_name
    ");
    $stmt->execute();
    $students_per_block = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get unique block names for filter
    $block_names = array_unique(array_column($students_per_block, 'block_name'));
    sort($block_names);

} catch (PDOException $e) {
    $total_subjects = 0;
    $total_teachers = 0;
    $total_blocks = 0;
    $pending_enrollments = 0;
    $approved_enrollments = 0;
    $total_enrollments = 0;
    $students_per_block = [];
    $block_names = [];
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
    <title>Registrar Dashboard - WESSH Enrollment</title>
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- Custom styles from template -->
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper" class="d-flex">
        <?php include 'sidebar.php'; ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <!-- Sidebar Toggle -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>
                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php echo htmlspecialchars($_SESSION['name'] ?? 'Registrar'); ?>
                                </span>
                                <i class="fas fa-user fa-sm fa-fw text-gray-400"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in">
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Logout
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
                        <h1 class="h3 mb-0 text-gray-800">Registrar Dashboard</h1>
                    </div>

                    <!-- Content Row for Metrics -->
                    <div class="row">
                        <!-- Total Subjects Card -->
                        <div class="col-xl-3 col-md-6 col-12 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Subjects</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $total_subjects; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-book fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Total Teachers Card -->
                        <div class="col-xl-3 col-md-6 col-12 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Total Teachers</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $total_teachers; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Total Blocks Card -->
                        <div class="col-xl-3 col-md-6 col-12 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Total Blocks</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $total_blocks; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-cubes fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Pending Enrollments Card -->
                        <div class="col-xl-3 col-md-6 col-12 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Pending Enrollments</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $pending_enrollments; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Metrics Row -->
                    <div class="row">
                        <!-- Approved Enrollments Card -->
                        <div class="col-xl-6 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Approved Enrollments</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $approved_enrollments; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Total Enrollments Card -->
                        <div class="col-xl-6 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Enrollments</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $total_enrollments; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-list fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Students per Block -->
                    <div class="row">
                        <div class="col-lg-12 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Students per Block</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="blockFilter">Filter by Block:</label>
                                        <select id="blockFilter" class="form-control">
                                            <option value="">All Blocks</option>
                                            <?php foreach ($block_names as $block): ?>
                                                <option value="<?php echo htmlspecialchars($block); ?>">
                                                    <?php echo htmlspecialchars($block); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="studentsTable" width="100%"
                                            cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Block</th>
                                                    <th>Number of Students</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($students_per_block as $block): ?>
                                                    <tr data-block="<?php echo htmlspecialchars($block['block_name']); ?>">
                                                        <td><?php echo htmlspecialchars($block['block_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($block['student_count']); ?></td>
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

    <!-- Custom script for filtering -->
    <script>
        $(document).ready(function () {
            $('#blockFilter').on('change', function () {
                var selectedBlock = $(this).val();
                if (selectedBlock === '') {
                    $('#studentsTable tbody tr').show();
                } else {
                    $('#studentsTable tbody tr').hide();
                    $('#studentsTable tbody tr[data-block="' + selectedBlock + '"]').show();
                }
            });
        });
    </script>
</body>

</html>