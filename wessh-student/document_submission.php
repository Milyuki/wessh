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
$_SESSION['user_type'] = $_SESSION['user_type'] ?? 'student';
// session_start();
// if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
//     header("Location: ../login.php");
//     exit;
// }
include dirname(__DIR__) . '/includes/db.php';

$user_id = $_SESSION['user_id'];

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $document = $_FILES['document'];
    $document_type = $_POST['document_type'];
    $allowed_types = ['application/pdf', 'image/jpeg', 'image/jpg'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if (in_array($document['type'], $allowed_types) && $document['size'] <= $max_size) {
        $file_name = basename($document['name']);
        $file_data = file_get_contents($document['tmp_name']);
        // Get enrollment_id for the user (pending enrollment)
        $stmt = $pdo->prepare("SELECT enrollment_id FROM enrollments WHERE student_id = ? AND status = 'Pending' ORDER BY enrollment_id DESC LIMIT 1");
        $stmt->execute([$user_id]);
        $enrollment = $stmt->fetch();
        if ($enrollment) {
            $enrollment_id = $enrollment['enrollment_id'];
            // Insert into documents table with BLOB
            $stmt = $pdo->prepare("INSERT INTO documents (enrollment_id, document_type, file_name, file_data, upload_date) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$enrollment_id, $document_type, $file_name, $file_data]);
            $message = 'Document submitted successfully!';
            // TODO: Notify teacher/registrar (insert into notifications table)
        } else {
            $message = 'No enrollment found for this user.';
        }
    } else {
        $message = 'Invalid file type or size.';
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

    <title>WESSH - Document Submission</title>

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
    <div id="wrapper">

        <?php include 'student_sidebar.php'; ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fas fa-bars"></i>
                    </button>

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">User</span>
                                <img class="img-profile rounded-circle" src="../img/undraw_profile.svg">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="profile_schedule.php">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Profile & Schedule
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
                        <h1 class="h3 mb-0 text-gray-800">Document Submission</h1>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-lg-8 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Upload Document</h6>
                                </div>
                                <div class="card-body">
                                    <form method="post" enctype="multipart/form-data" onsubmit="return validateForm()">
                                        <div class="form-group">
                                            <label for="document_type">Document Type</label>
                                            <select class="form-control" id="document_type" name="document_type"
                                                required>
                                                <option value="">Select Document Type</option>
                                                <option value="2x2 ID">ID Photo (2x2)</option>
                                                <option value="PSA Birth Certificate">Birth Certificate</option>
                                                <option value="Good Moral">Medical Certificate</option>
                                                <option value="Form 137">Academic Transcript</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="document">Select Document (PDF or JPEG, max 5MB)</label>
                                            <input type="file" class="form-control-file" id="document" name="document"
                                                required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </form>
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

    <script>
        function validateForm() {
            const file = document.getElementById('document').files[0];
            if (!file) return false;
            const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg'];
            if (!allowedTypes.includes(file.type)) {
                alert('Invalid file type. Only PDF and JPEG are allowed.');
                return false;
            }
            if (file.size > 5 * 1024 * 1024) {
                alert('File size exceeds 5MB.');
                return false;
            }
            return true;
        }
    </script>

</body>

</html>