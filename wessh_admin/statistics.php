<?php
// session_start();
// if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
//     header("Location: login.php");
//     exit;
// }
include '../includes/db.php';

// Create grades table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS grades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        grade DECIMAL(5,2),
        final_average DECIMAL(5,2),
        FOREIGN KEY (student_id) REFERENCES enrollments(id)
    )");
} catch (PDOException $e) {
    // Handle error silently or log it
}

// Default filters
$selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$selected_strand = isset($_GET['strand']) ? $_GET['strand'] : '';
$selected_block = isset($_GET['block']) ? $_GET['block'] : '';
$is_ajax = isset($_GET['ajax']);

// Fetch students per block (assuming block is section or similar in enrollments)
try {
    $stmt_blocks = $pdo->prepare("SELECT section AS block, COUNT(*) AS count FROM enrollments WHERE YEAR(created_at) = ? GROUP BY section ORDER BY section");
    $stmt_blocks->execute([$selected_year]);
    $blocks_stats = $stmt_blocks->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $blocks_stats = [];
}

// Fetch students per year
try {
    $stmt_years = $pdo->query("SELECT YEAR(created_at) AS year, COUNT(*) AS count FROM enrollments GROUP BY YEAR(created_at) ORDER BY year DESC");
    $years_stats = $stmt_years->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $years_stats = [];
}

// Fetch students per strand
try {
    $query_strand = "SELECT strand, COUNT(*) AS count FROM enrollments WHERE YEAR(created_at) = ?";
    if (!empty($selected_strand)) {
        $query_strand .= " AND strand = ?";
    }
    $query_strand .= " GROUP BY strand ORDER BY strand";
    $stmt_strand = $pdo->prepare($query_strand);
    $params = [$selected_year];
    if (!empty($selected_strand)) {
        $params[] = $selected_strand;
    }
    $stmt_strand->execute($params);
    $strand_stats = $stmt_strand->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $strand_stats = [];
}

// Total students enrolled for selected year
try {
    $stmt_total = $pdo->prepare("SELECT COUNT(*) AS total FROM enrollments WHERE YEAR(created_at) = ?");
    $stmt_total->execute([$selected_year]);
    $total_enrolled = $stmt_total->fetch()['total'];
} catch (PDOException $e) {
    $total_enrolled = 0;
}

// Fetch enrollment trend data (monthly for the past year)
try {
    $stmt_trend = $pdo->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS count FROM enrollments WHERE YEAR(created_at) = ? GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY month");
    $stmt_trend->execute([$selected_year]);
    $trend_data = $stmt_trend->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $trend_data = [];
}

// Prepare data for Chart.js
$trend_labels = array_column($trend_data, 'month');
$trend_counts = array_column($trend_data, 'count');

// Get unique years for filter
$years = array_unique(array_column($years_stats, 'year'));

// Get unique strands for filter
try {
    $stmt_unique_strands = $pdo->query("SELECT DISTINCT strand FROM enrollments ORDER BY strand");
    $unique_strands = $stmt_unique_strands->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $unique_strands = [];
}

// Get unique blocks for filter
try {
    $stmt_unique_blocks = $pdo->query("SELECT DISTINCT section AS block FROM enrollments ORDER BY section");
    $unique_blocks = $stmt_unique_blocks->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $unique_blocks = [];
}

// Fetch detailed student info per block
try {
    $query_students = "SELECT id, first_name, last_name, section AS block, strand, grade_level, status, created_at FROM enrollments WHERE YEAR(created_at) = ?";
    $params_students = [$selected_year];
    if (!empty($selected_strand)) {
        $query_students .= " AND strand = ?";
        $params_students[] = $selected_strand;
    }
    if (!empty($selected_block)) {
        $query_students .= " AND section = ?";
        $params_students[] = $selected_block;
    }
    $query_students .= " ORDER BY section, last_name, first_name";
    $stmt_students = $pdo->prepare($query_students);
    $stmt_students->execute($params_students);
    $students_data = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $students_data = [];
}

// Fetch grades data
try {
    $query_grades = "SELECT e.first_name, e.last_name, g.subject, g.grade, g.final_average FROM grades g JOIN enrollments e ON g.student_id = e.id ORDER BY e.last_name, e.first_name, g.subject";
    $stmt_grades = $pdo->query($query_grades);
    $grades_data = $stmt_grades->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $grades_data = [];
}
?>

<?php
if ($is_ajax) {
    // Output only the detailed reports content for AJAX
    $current_block = '';
    foreach ($students_data as $student) {
        if ($student['block'] !== $current_block) {
            if ($current_block !== '') {
                echo '</tbody></table></div></div>';
            }
            $current_block = $student['block'];
            echo '<div class="mb-4"><h5>Block: ' . htmlspecialchars($current_block) . '</h5>';
            echo '<div class="table-responsive"><table class="table table-bordered" id="blockTable' . htmlspecialchars($current_block) . '">';
            echo '<thead><tr><th>Name</th><th>Strand</th><th>Grade Level</th><th>Status</th><th>Enrollment Date</th></tr></thead><tbody>';
        }
        echo '<tr>';
        echo '<td>' . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . '</td>';
        echo '<td>' . htmlspecialchars($student['strand']) . '</td>';
        echo '<td>' . htmlspecialchars($student['grade_level']) . '</td>';
        echo '<td>' . htmlspecialchars($student['status']) . '</td>';
        echo '<td>' . htmlspecialchars(date('Y-m-d', strtotime($student['created_at']))) . '</td>';
        echo '</tr>';
    }
    if ($current_block !== '') {
        echo '</tbody></table></div></div>';
    }
    exit;
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

    <title>WESSH - Statistics</title>

    <!-- Custom fonts for this template-->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
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
                        <h1 class="h3 mb-0 text-gray-800">Statistics Dashboard</h1>
                        <button class="btn btn-primary" onclick="exportExcel()">Export Excel</button>
                    </div>

                    <!-- Filters -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <form method="GET" id="filterForm">
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="year">Year</label>
                                        <select class="form-control" id="year" name="year"
                                            onchange="document.getElementById('filterForm').submit();">
                                            <?php foreach ($years as $year): ?>
                                                <option value="<?php echo $year; ?>" <?php echo $year == $selected_year ? 'selected' : ''; ?>><?php echo $year . '-' . ($year + 1); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="strand">Strand</label>
                                        <select class="form-control" id="strand" name="strand"
                                            onchange="document.getElementById('filterForm').submit();">
                                            <option value="">All Strands</option>
                                            <?php foreach ($unique_strands as $strand): ?>
                                                <option value="<?php echo $strand; ?>" <?php echo $strand == $selected_strand ? 'selected' : ''; ?>><?php echo $strand; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="block">Block</label>
                                        <select class="form-control" id="block" name="block"
                                            onchange="document.getElementById('filterForm').submit();">
                                            <option value="">All Blocks</option>
                                            <?php foreach ($unique_blocks as $block): ?>
                                                <option value="<?php echo $block; ?>" <?php echo $block == $selected_block ? 'selected' : ''; ?>><?php echo $block; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Total Enrolled Card -->
                    <div class="row mb-4">
                        <div class="col-xl-12 col-md-12 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Students Enrolled
                                                (<?php echo $selected_year . '-' . ($selected_year + 1); ?>)</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $total_enrolled; ?>
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

                    <!-- Stats Tables -->
                    <div class="row">
                        <!-- Students per Block -->
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Students per Block</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Block</th>
                                                    <th>Count</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($blocks_stats as $stat): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($stat['block']); ?></td>
                                                        <td><?php echo htmlspecialchars($stat['count']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Students per Year -->
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Students per Year</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Year</th>
                                                    <th>Count</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($years_stats as $stat): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($stat['year']); ?></td>
                                                        <td><?php echo htmlspecialchars($stat['count']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Students per Strand -->
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Students per Strand</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Strand</th>
                                                    <th>Count</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($strand_stats as $stat): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($stat['strand']); ?></td>
                                                        <td><?php echo htmlspecialchars($stat['count']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Block Reports -->
                    <div class="row mb-4">
                        <div class="col-lg-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">Detailed Student Reports per Block
                                    </h6>
                                    <div class="form-group mb-0">
                                        <label for="report_year" class="sr-only">Select Year for Reports</label>
                                        <select class="form-control form-control-sm" id="report_year"
                                            name="report_year">
                                            <?php foreach ($years as $year): ?>
                                                <option value="<?php echo $year; ?>" <?php echo $year == $selected_year ? 'selected' : ''; ?>><?php echo $year . '-' . ($year + 1); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="detailed-reports-content">
                                        <?php
                                        $current_block = '';
                                        foreach ($students_data as $student) {
                                            if ($student['block'] !== $current_block) {
                                                if ($current_block !== '') {
                                                    echo '</tbody></table></div></div>';
                                                }
                                                $current_block = $student['block'];
                                                echo '<div class="mb-4"><h5>Block: ' . htmlspecialchars($current_block) . '</h5>';
                                                echo '<div class="table-responsive"><table class="table table-bordered" id="blockTable' . htmlspecialchars($current_block) . '">';
                                                echo '<thead><tr><th>Name</th><th>Strand</th><th>Grade Level</th><th>Status</th><th>Enrollment Date</th></tr></thead><tbody>';
                                            }
                                            echo '<tr>';
                                            echo '<td>' . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . '</td>';
                                            echo '<td>' . htmlspecialchars($student['strand']) . '</td>';
                                            echo '<td>' . htmlspecialchars($student['grade_level']) . '</td>';
                                            echo '<td>' . htmlspecialchars($student['status']) . '</td>';
                                            echo '<td>' . htmlspecialchars(date('Y-m-d', strtotime($student['created_at']))) . '</td>';
                                            echo '</tr>';
                                        }
                                        if ($current_block !== '') {
                                            echo '</tbody></table></div></div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Grades Table -->
                    <div class="row mb-4">
                        <div class="col-lg-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Student Grades and Final Averages</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered datatable" id="gradesTable">
                                            <thead>
                                                <tr>
                                                    <th>Student Name</th>
                                                    <th>Subject</th>
                                                    <th>Grade</th>
                                                    <th>Final Average</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($grades_data as $grade): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($grade['first_name'] . ' ' . $grade['last_name']); ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($grade['subject']); ?></td>
                                                        <td><?php echo htmlspecialchars($grade['grade']); ?></td>
                                                        <td><?php echo htmlspecialchars($grade['final_average']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Enrollment Trend Chart -->
                    <div class="row">
                        <div class="col-lg-12 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Enrollment Trends
                                        (<?php echo $selected_year . '-' . ($selected_year + 1); ?>)</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="enrollmentTrendChart"></canvas>
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

    <!-- Bootstrap core JavaScript-->
    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="../js/sb-admin-2.min.js"></script>

    <!-- Page level plugins -->
    <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <!-- Chart.js Script -->
    <script>
        const ctx = document.getElementById('enrollmentTrendChart').getContext('2d');
        const enrollmentTrendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($trend_labels); ?>,
                datasets: [{
                    label: 'Enrollments',
                    data: <?php echo json_encode($trend_counts); ?>,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
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
                        text: 'Monthly Enrollment Trends'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Export Excel Function
        function exportExcel() {
            window.location.href = 'export_excel.php?year=<?php echo $selected_year; ?>';
        }

        // Update detailed reports based on selected year
        $('#report_year').on('change', function () {
            const selectedYear = $(this).val();
            $.ajax({
                url: 'statistics.php',
                type: 'GET',
                data: { year: selectedYear, ajax: 1 },
                success: function (response) {
                    // Parse the response to extract the detailed reports content
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(response, 'text/html');
                    const newContent = doc.getElementById('detailed-reports-content').innerHTML;
                    $('#detailed-reports-content').html(newContent);
                }
            });
        });

        // AJAX for real-time updates (placeholder, can be expanded)
        function refreshData() {
            // Implement AJAX call to fetch updated data and update chart/tables
            // For now, just reload the page
            location.reload();
        }

        // Optional: Auto-refresh every 5 minutes
        setInterval(refreshData, 300000);

        // Initialize DataTable
        $(document).ready(function () {
            $('.datatable').DataTable();
        });
    </script>

</body>

</html>