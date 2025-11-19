<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'registrar') {
    header("Location: registrar_login.php");
    exit;
}
include dirname(__DIR__) . '/includes/db.php';

$enrollment_id = isset($_GET['enrollment_id']) ? (int) $_GET['enrollment_id'] : 0;
$enrollment = null;

// Fetch enrollment details
try {
    $stmt = $pdo->prepare("
        SELECT e.*, u.user_id, CONCAT(u.firstname, ' ', u.lastname) as student_name, u.email as student_email
        FROM enrollments e
        JOIN students s ON e.student_id = s.student_id
        JOIN users u ON s.email = u.email
        WHERE e.enrollment_id = ?
    ");
    $stmt->execute([$enrollment_id]);
    $enrollment = $stmt->fetch();
} catch (PDOException $e) {
    $enrollment = null;
}

// Fetch documents for this enrollment from filesystem
$documents = [];
if ($enrollment) {
    $student_id = $enrollment['student_id'];
    $dir = "../uploads/documents/{$student_id}/";
    if (is_dir($dir)) {
        $files = scandir($dir);
        $labels_to_types = [
            '2x2 ID' => '2x2 ID',
            'PSA Birth Certificate' => 'PSA Birth Certificate',
            'Good Moral' => 'Good Moral',
            'Form 137' => 'Form 137',
            'Certificate of Completion' => 'Certificate of Completion'
        ];
        foreach ($files as $file) {
            if ($file == '.' || $file == '..')
                continue;
            foreach ($labels_to_types as $label => $type) {
                if (strpos($file, $label . '_') === 0) {
                    $file_path = $dir . $file;
                    if (file_exists($file_path)) {
                        $documents[$type] = $file_path;
                    }
                }
            }
        }
    }
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
    <title>Enrollment Details - Registrar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper" class="d-flex">
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
                                <span
                                    class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Registrar'); ?></span>
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

                <div class="container-fluid">
                    <h1 class="h3 mb-0 text-gray-800">Enrollment Details</h1>

                    <?php if (!$enrollment): ?>
                        <div class="alert alert-danger">Enrollment not found.</div>
                    <?php else: ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Student Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Student Name:</strong>
                                            <?php echo htmlspecialchars($enrollment['student_name']); ?></p>
                                        <p><strong>Email:</strong>
                                            <?php echo htmlspecialchars($enrollment['student_email']); ?></p>
                                        <p><strong>Grade Level:</strong>
                                            <?php echo htmlspecialchars($enrollment['grade_level']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Strand:</strong> <?php echo htmlspecialchars($enrollment['strand']); ?>
                                        </p>
                                        <p><strong>Semester:</strong>
                                            <?php echo htmlspecialchars($enrollment['semester']); ?> Semester</p>
                                        <p><strong>Submission Date:</strong>
                                            <?php echo htmlspecialchars($enrollment['submission_date']); ?></p>
                                        <p><strong>Status:</strong>
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
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Submitted Documents</h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($documents)): ?>
                                    <p>No documents found.</p>
                                <?php else: ?>
                                    <ul class="list-group">
                                        <?php foreach ($documents as $type => $path): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <?php echo htmlspecialchars($type); ?>
                                                <a href="<?php echo htmlspecialchars($path); ?>" target="_blank"
                                                    class="btn btn-sm btn-primary">View/Download</a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
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

    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
    <script src="../js/sb-admin-2.min.js"></script>
</body>

</html>