<?php
// WESSH Admin Enrollment List Page
// This page allows administrators to view all student enrollments with their status and documents
// Requires admin authentication

// session_start();

// // Check if admin is logged in (adjust based on your authentication system)
// if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
//     header("Location: login.php"); // Redirect to login if not authenticated
//     exit;
// }

include 'db_connect.php'; // Include MySQLi database connection

$message = ''; // Variable to store messages

// Handle status update if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $enrollment_id = intval($_POST['enrollment_id'] ?? 0);
    $new_status = trim($_POST['status'] ?? '');

    if ($enrollment_id > 0 && in_array($new_status, ['Pending', 'Approved', 'Rejected'])) {
        $stmt = $conn->prepare("UPDATE enrollments SET status = ? WHERE enrollment_id = ?");
        $stmt->bind_param("si", $new_status, $enrollment_id);

        if ($stmt->execute()) {
            $message = "Enrollment status updated successfully.";
        } else {
            $message = "Error updating status: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $message = "Invalid enrollment ID or status.";
    }
}

// Fetch all enrollments with student information
$query = "SELECT e.*, s.email, s.full_name as student_name
          FROM enrollments e
          JOIN students s ON e.student_id = s.student_id
          ORDER BY e.submission_date DESC";

$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}

$enrollments = $result->fetch_all(MYSQLI_ASSOC);

$conn->close(); // Close database connection
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WESSH - Admin Enrollment List</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS for table sorting and searching -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Custom styles -->
    <style>
        .status-badge {
            font-size: 0.8em;
        }

        .document-link {
            word-break: break-all;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="h3 mb-0">WESSH Enrollment Management</h1>
                    <a href="admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                </div>
            </div>
        </div>

        <!-- Message Display -->
        <?php if ($message): ?>
            <div class="row mb-3">
                <div class="col-12">
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Enrollment Table -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">All Enrollments</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="enrollmentTable" class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Student Name</th>
                                        <th>Email</th>
                                        <th>Grade Level</th>
                                        <th>Strand</th>
                                        <th>Status</th>
                                        <th>Submission Date</th>
                                        <th>Documents</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Reconnect to database for documents query
                                    include 'db_connect.php';
                                    foreach ($enrollments as $enrollment):
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($enrollment['enrollment_id']); ?></td>
                                            <td><?php echo htmlspecialchars($enrollment['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($enrollment['email']); ?></td>
                                            <td><?php echo htmlspecialchars($enrollment['grade_level']); ?></td>
                                            <td><?php echo htmlspecialchars($enrollment['strand'] ?: 'N/A'); ?></td>
                                            <td>
                                                <span class="badge status-badge
                                                    <?php
                                                    switch ($enrollment['status']) {
                                                        case 'Approved':
                                                            echo 'bg-success';
                                                            break;
                                                        case 'Rejected':
                                                            echo 'bg-danger';
                                                            break;
                                                        default:
                                                            echo 'bg-warning text-dark';
                                                    }
                                                    ?>">
                                                    <?php echo htmlspecialchars($enrollment['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y H:i', strtotime($enrollment['submission_date'])); ?>
                                            </td>
                                            <td>
                                                <?php
                                                // Fetch documents for this enrollment
                                                $doc_query = "SELECT * FROM documents WHERE enrollment_id = " . intval($enrollment['enrollment_id']);
                                                $doc_result = $conn->query($doc_query);
                                                if ($doc_result && $doc_result->num_rows > 0) {
                                                    while ($doc = $doc_result->fetch_assoc()) {
                                                        echo '<a href="' . htmlspecialchars($doc['file_path']) . '" target="_blank" class="document-link btn btn-sm btn-outline-primary me-1 mb-1">' . htmlspecialchars($doc['document_type']) . '</a>';
                                                    }
                                                } else {
                                                    echo '<span class="text-muted">No documents</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <!-- Status Update Form -->
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="enrollment_id"
                                                        value="<?php echo $enrollment['enrollment_id']; ?>">
                                                    <select name="status"
                                                        class="form-select form-select-sm d-inline-block w-auto me-2"
                                                        required>
                                                        <option value="Pending" <?php echo $enrollment['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="Approved" <?php echo $enrollment['status'] == 'Approved' ? 'selected' : ''; ?>>Approved
                                                        </option>
                                                        <option value="Rejected" <?php echo $enrollment['status'] == 'Rejected' ? 'selected' : ''; ?>>Rejected
                                                        </option>
                                                    </select>
                                                    <button type="submit" name="update_status"
                                                        class="btn btn-sm btn-primary">Update</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php $conn->close(); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enrollment Details Modal (for future expansion) -->
        <div class="modal fade" id="enrollmentModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Enrollment Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Details will be loaded here via AJAX in future implementation -->
                        <p>Full enrollment details will be displayed here.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables JS for table functionality -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <script>
        // Initialize DataTable
        $(document).ready(function () {
            $('#enrollmentTable').DataTable({
                "pageLength": 25,
                "order": [[0, "desc"]], // Sort by ID descending
                "responsive": true,
                "language": {
                    "search": "Search enrollments:",
                    "lengthMenu": "Show _MENU_ enrollments per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ enrollments"
                }
            });
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function () {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>

</html>