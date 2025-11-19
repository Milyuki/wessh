<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}
include dirname(__DIR__) . '/includes/db.php';

// Fetch total students
$stmt_students = $pdo->query("SELECT COUNT(*) as total_students FROM users WHERE user_type = 'student'");
$total_students = $stmt_students->fetch()['total_students'];

// Fetch total enrollments
$stmt_total_enrollments = $pdo->query("SELECT COUNT(*) as total_enrollments FROM enrollments");
$total_enrollments = $stmt_total_enrollments->fetch()['total_enrollments'];

// Fetch enrollment stats per strand
$stmt_strand = $pdo->query("SELECT strand, COUNT(*) as count FROM enrollments GROUP BY strand");
$strand_stats = $stmt_strand->fetchAll(PDO::FETCH_ASSOC);

// Fetch enrollment stats per grade level
$stmt_grade = $pdo->query("SELECT grade_level, COUNT(*) as count FROM enrollments GROUP BY grade_level");
$grade_stats = $stmt_grade->fetchAll(PDO::FETCH_ASSOC);

// Fetch demographics: age groups (assuming age column exists; placeholder if not)
try {
    $stmt_age = $pdo->query("SELECT CASE WHEN age < 18 THEN '16-18' WHEN age BETWEEN 19 AND 21 THEN '19-21' ELSE 'Over 21' END as age_group, COUNT(*) as count FROM users WHERE user_type = 'student' GROUP BY age_group");
    $age_stats = $stmt_age->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $age_stats = [];
}

// Fetch demographics: gender ratios (assuming gender column exists; placeholder if not)
try {
    $stmt_gender = $pdo->query("SELECT gender, COUNT(*) as count FROM enrollments WHERE user_type = 'student' GROUP BY gender");
    $gender_stats = $stmt_gender->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $gender_stats = [];
}

// Fetch enrollment progress: new vs returning (assuming status column)
try {
    $stmt_progress = $pdo->query("SELECT status, COUNT(*) as count FROM enrollments GROUP BY status");
    $progress_stats = $stmt_progress->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $progress_stats = [];
}

// Fetch school metrics: teacher-to-student ratio (assuming teachers table exists; placeholder)
try {
    $stmt_teachers = $pdo->query("SELECT COUNT(*) as total_teachers FROM teachers");
    $total_teachers = $stmt_teachers->fetch()['total_teachers'];
} catch (PDOException $e) {
    $total_teachers = 0;
}
$teacher_ratio = $total_teachers > 0 ? round($total_students / $total_teachers, 2) : 0;

// Capacity utilization (placeholder value)
$capacity = 1000;
$utilization = round(($total_students / $capacity) * 100, 2);

// Fetch all users for table (view-only)
$stmt_users = $pdo->query("SELECT user_id, email, user_type, firstname, lastname FROM users ORDER BY user_id ASC");
$users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

// Error handling
$error = null;
try {
    // Queries above
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
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

    <title>WESSH - Admin Dashboard</title>

    <!-- Custom fonts for this template-->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <!-- Chart.js for visualizations -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <?php include 'sidebar.php'; ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

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
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">Admin User</span>
                                <img class="img-profile rounded-circle" src="../img/undraw_profile.svg">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Profile
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="../logout.php">
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
                        <h1 class="h3 mb-0 text-gray-800">Admin Dashboard</h1>
                    </div>

                    <!-- Pending Approvals Alert -->
                    <?php
                    $stmt_pending = $pdo->query("SELECT COUNT(*) as pending_count FROM users WHERE status = 'pending'");
                    $pending_count = $stmt_pending->fetch()['pending_count'];
                    if ($pending_count > 0): ?>
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <strong>Pending Approvals:</strong> There are <?php echo $pending_count; ?> user(s) waiting for
                            approval.
                            <a href="user_management.php" class="alert-link">Go to User Management</a> to review.
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <!-- Content Row -->
                    <div class="row">

                        <!-- Total Students Card -->
                        <div class="col-xl-3 col-md-6 col-12 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Students</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $total_students; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Enrollments Card -->
                        <div class="col-xl-3 col-md-6 col-12 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Total Enrollments</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $total_enrollments; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Enrollment Progress Card -->
                        <div class="col-xl-3 col-md-6 col-12 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Enrollment Progress</div>
                                            <div class="progress mb-2">
                                                <div class="progress-bar bg-success" role="progressbar"
                                                    style="width: <?php echo ($total_enrollments / $capacity) * 100; ?>%"
                                                    aria-valuenow="<?php echo $total_enrollments; ?>" aria-valuemin="0"
                                                    aria-valuemax="<?php echo $capacity; ?>"></div>
                                            </div>
                                            <div class="small text-gray-600">
                                                <?php echo $total_enrollments; ?>/<?php echo $capacity; ?> Enrolled
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- School Metrics Card -->
                        <div class="col-xl-3 col-md-6 col-12 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Capacity Utilization</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $utilization; ?>%
                                            </div>
                                            <div class="small text-gray-600">Teacher Ratio:
                                                <?php echo $teacher_ratio; ?>:1
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-school fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Enrollment Stats Row -->
                    <div class="row">

                        <!-- Strand Stats -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Enrollments by Strand</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="strandChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Grade Level Stats -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Enrollments by Grade Level</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="gradeChart"></canvas>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Demographics Row -->
                    <div class="row">

                        <!-- Age Groups -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Student Demographics: Age Groups</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="ageChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Gender Ratios -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Student Demographics: Gender Ratios
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="genderChart"></canvas>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Reports Section -->
                    <div class="row">
                        <div class="col-lg-12 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Enrollment Reports</h6>
                                    <button class="btn btn-primary btn-sm ml-2" onclick="exportReport('csv')">Export
                                        CSV</button>
                                    <button class="btn btn-secondary btn-sm" onclick="exportReport('pdf')">Export
                                        PDF</button>
                                </div>
                                <div class="card-body">
                                    <p>Customizable reports for enrollment trends, demographics, and metrics. Use export
                                        buttons to download.</p>
                                    <!-- Placeholder for report content -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Student Data Table -->
                    <div class="row">
                        <div class="col-lg-12 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">User Data</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Email</th>
                                                    <th>User Type</th>
                                                    <th>Name</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($users as $user): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                        <td><?php echo htmlspecialchars($user['user_type']); ?></td>
                                                        <td><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
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
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; WESSH 2025</span>
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

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <!-- Chart.js Scripts -->
    <script>
        // Strand Chart
        const strandCtx = document.getElementById('strandChart').getContext('2d');
        const strandChart = new Chart(strandCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($strand_stats, 'strand')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($strand_stats, 'count')); ?>,
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Enrollments by Strand'
                    }
                }
            }
        });

        // Grade Level Chart
        const gradeCtx = document.getElementById('gradeChart').getContext('2d');
        const gradeChart = new Chart(gradeCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($grade_stats, 'grade_level')); ?>,
                datasets: [{
                    label: 'Enrollments',
                    data: <?php echo json_encode(array_column($grade_stats, 'count')); ?>,
                    backgroundColor: '#4e73df',
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Enrollments by Grade Level'
                    }
                }
            }
        });

        // Age Groups Chart
        const ageCtx = document.getElementById('ageChart').getContext('2d');
        const ageChart = new Chart(ageCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($age_stats, 'age_group')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($age_stats, 'count')); ?>,
                    backgroundColor: ['#f6c23e', '#e74a3b', '#1cc88a'],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Student Demographics: Age Groups'
                    }
                }
            }
        });

        // Gender Ratios Chart
        const genderCtx = document.getElementById('genderChart').getContext('2d');
        const genderChart = new Chart(genderCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($gender_stats, 'gender')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($gender_stats, 'count')); ?>,
                    backgroundColor: ['#4e73df', '#1cc88a'],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Student Demographics: Gender Ratios'
                    }
                }
            }
        });

        // Export Report Function
        function exportReport(type) {
            if (type === 'csv') {
                // Simple CSV export placeholder
                let csv = 'Strand,Count\n';
                <?php foreach ($strand_stats as $stat): ?>
                    csv += '<?php echo $stat['strand']; ?>,<?php echo $stat['count']; ?>\n';
                <?php endforeach; ?>
                const blob = new Blob([csv], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'enrollment_report.csv';
                a.click();
                window.URL.revokeObjectURL(url);
            } else if (type === 'pdf') {
                alert('PDF export functionality to be implemented.');
            }
        }
    </script>

</body>

</html>