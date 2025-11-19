c
<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'registrar') {
    header("Location: registrar_login.php");
    exit;
}
include dirname(__DIR__) . '/includes/db.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $enrollment_id = $_POST['enrollment_id'];
    $new_status = $_POST['new_status'];

    try {
        $stmt = $pdo->prepare("UPDATE enrollments SET status = ? WHERE enrollment_id = ?");
        $stmt->execute([$new_status, $enrollment_id]);
        $success = "Enrollment status updated successfully.";
    } catch (PDOException $e) {
        $error = "Error updating status: " . $e->getMessage();
    }
}

// Handle approval with automatic block and subject assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['approve_enrollment'])) {
    $enrollment_id = $_POST['enrollment_id'];

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Get enrollment details to find matching blocks
        $stmt = $pdo->prepare("SELECT grade_level, strand FROM enrollments WHERE enrollment_id = ?");
        $stmt->execute([$enrollment_id]);
        $enrollment = $stmt->fetch();

        if (!$enrollment) {
            throw new Exception("Enrollment not found.");
        }

        // Find first available block for this grade_level and strand
        $stmt = $pdo->prepare("
            SELECT b.block_id, b.block_name, b.max_students,
                   COUNT(e.enrollment_id) as enrolled_students
            FROM blocks b
            LEFT JOIN enrollments e ON b.block_id = e.block_id AND e.status = 'Approved'
            WHERE b.grade_level = ? AND b.strand = ?
            GROUP BY b.block_id
            HAVING enrolled_students < max_students
            ORDER BY b.block_name
            LIMIT 1
        ");
        $stmt->execute([$enrollment['grade_level'], $enrollment['strand']]);
        $available_block = $stmt->fetch();

        if (!$available_block) {
            throw new Exception("No available blocks for this grade level and strand.");
        }

        $block_id = $available_block['block_id'];

        // Update enrollment status to Approved and assign block
        $stmt = $pdo->prepare("UPDATE enrollments SET status = 'Approved', block_id = ? WHERE enrollment_id = ?");
        $stmt->execute([$block_id, $enrollment_id]);

        // Delete existing subject assignments for this enrollment
        $stmt = $pdo->prepare("DELETE FROM student_subjects WHERE enrollment_id = ?");
        $stmt->execute([$enrollment_id]);

        // Get all subjects for this block and assign them
        $stmt = $pdo->prepare("
            SELECT DISTINCT s.subject_id
            FROM subjects s
            JOIN schedules sch ON s.subject_id = sch.subject_id
            WHERE sch.block_id = ?
        ");
        $stmt->execute([$block_id]);
        $subjects = $stmt->fetchAll();

        // Insert subject assignments
        if (!empty($subjects)) {
            $stmt = $pdo->prepare("INSERT INTO student_subjects (enrollment_id, subject_id) VALUES (?, ?)");
            foreach ($subjects as $subject) {
                $stmt->execute([$enrollment_id, $subject['subject_id']]);
            }
        }

        $pdo->commit();
        $success = "Enrollment approved and automatically assigned to " . htmlspecialchars($available_block['block_name']) . " with " . count($subjects) . " subjects.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error approving enrollment: " . $e->getMessage();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Fetch enrollments for approval
try {
    $stmt = $pdo->prepare("
        SELECT e.enrollment_id, e.first_name, e.last_name, e.grade_level, e.strand, e.semester, e.submission_date, e.status, u.email
        FROM enrollments e
        JOIN students s ON e.student_id = s.student_id
        JOIN users u ON s.email = u.email
        ORDER BY
            CASE
                WHEN e.status = 'Pending' THEN 1
                WHEN e.status = 'For Checking' THEN 2
                WHEN e.status = 'Approved' THEN 3
                WHEN e.status = 'Rejected' THEN 4
                WHEN e.status = 'Partially Enrolled' THEN 5
                ELSE 6
            END ASC,
            e.submission_date DESC
    ");
    $stmt->execute();
    $enrollments = $stmt->fetchAll();
} catch (PDOException $e) {
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
    <title>Enrollment Management - Registrar</title>
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
                    <h1 class="h3 mb-0 text-gray-800">Enrollment Management</h1>

                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Student Enrollments</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="enrollmentsTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Student Name</th>
                                            <th>Email</th>
                                            <th>Grade Level</th>
                                            <th>Strand</th>
                                            <th>Semester</th>
                                            <th>Submission Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($enrollments)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center">No enrollments found.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($enrollments as $enrollment): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($enrollment['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($enrollment['grade_level']); ?></td>
                                                    <td><?php echo htmlspecialchars($enrollment['strand']); ?></td>
                                                    <td><?php echo htmlspecialchars($enrollment['semester']); ?> Semester</td>
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
                                                        <button class="btn btn-sm btn-primary"
                                                            onclick="viewDetails(<?php echo $enrollment['enrollment_id']; ?>)">View
                                                            Details</button>
                                                        <?php if ($enrollment['status'] !== 'Approved'): ?>
                                                            <button class="btn btn-sm btn-success"
                                                                onclick="approveEnrollment(<?php echo $enrollment['enrollment_id']; ?>)">Approve</button>
                                                        <?php else: ?>
                                                            <button class="btn btn-sm btn-warning"
                                                                onclick="editEnrollment(<?php echo $enrollment['enrollment_id']; ?>)">Edit</button>
                                                        <?php endif; ?>
                                                        <button class="btn btn-sm btn-danger"
                                                            onclick="updateStatus(<?php echo $enrollment['enrollment_id']; ?>, 'Rejected')">Reject</button>
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

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1" role="dialog" aria-labelledby="updateStatusModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">Update Enrollment Status</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="update_enrollment_id" name="enrollment_id">
                        <div class="form-group">
                            <label for="new_status">New Status</label>
                            <select class="form-control" id="new_status" name="new_status" required>
                                <option value="Pending">Pending</option>
                                <option value="For Checking">For Checking</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                                <option value="Partially Enrolled">Partially Enrolled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Approve Enrollment Modal -->
    <div class="modal fade" id="approveEnrollmentModal" tabindex="-1" role="dialog"
        aria-labelledby="approveEnrollmentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="approveEnrollmentModalLabel">Approve Enrollment</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="approve_enrollment_id" name="enrollment_id">
                        <p>Are you sure you want to approve this enrollment? The student will be automatically assigned
                            to the first available block for their grade level and strand, and all subjects for that
                            block will be assigned.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="approve_enrollment" class="btn btn-primary">Approve</button>
                    </div>
                </form>
            </div>
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

    <script>
        function viewDetails(enrollmentId) {
            // Redirect to enrollment details page
            window.location.href = 'Enrollment_Details.php?enrollment_id=' + enrollmentId;
        }

        function updateStatus(enrollmentId, status) {
            $('#update_enrollment_id').val(enrollmentId);
            $('#new_status').val(status);
            $('#updateStatusModal').modal('show');
        }

        function approveEnrollment(enrollmentId) {
            $('#approve_enrollment_id').val(enrollmentId);
            $('#approveEnrollmentModal').modal('show');
        }

        function editEnrollment(enrollmentId) {
            // For now, redirect to enrollment details page for editing
            // In the future, this could open an edit modal
            window.location.href = 'Enrollment_Details.php?enrollment_id=' + enrollmentId;
        }
    </script>
</body>

</html>