<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'registrar') {
    header("Location: registrar_login.php");
    exit;
}
include dirname(__DIR__) . '/includes/db.php';

// Handle form submission for creating block
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_block'])) {
    $block_name = $_POST['block_name'];
    $grade_level = $_POST['grade_level'];
    $strand_code = $_POST['strand'];
    $max_students = $_POST['max_students'];

    // Get strand_id from strand_code
    try {
        $stmt = $pdo->prepare("SELECT strand_id FROM strands WHERE strand_code = ?");
        $stmt->execute([$strand_code]);
        $strand_id = $stmt->fetch()['strand_id'];

        if (!$strand_id) {
            $error = "Invalid strand selected.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO blocks (block_name, grade_level, strand, strand_id, max_students) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$block_name, $grade_level, $strand_code, $strand_id, $max_students]);
            $success = "Block created successfully.";
        }
    } catch (PDOException $e) {
        $error = "Error creating block: " . $e->getMessage();
    }
}

// Handle form submission for updating block limit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_limit'])) {
    $block_id = $_POST['block_id'];
    $new_limit = $_POST['new_limit'];

    try {
        $stmt = $pdo->prepare("UPDATE blocks SET max_students = ? WHERE block_id = ?");
        $stmt->execute([$new_limit, $block_id]);
        $success = "Block limit updated successfully.";
    } catch (PDOException $e) {
        $error = "Error updating block limit: " . $e->getMessage();
    }
}

// Handle form submission for deleting block
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_block'])) {
    $block_id = $_POST['block_id'];

    // Check if block has enrolled students
    try {
        $stmt = $pdo->prepare("SELECT COUNT(e.enrollment_id) as enrolled FROM enrollments e WHERE e.block_id = ? AND e.status = 'Approved'");
        $stmt->execute([$block_id]);
        $enrolled = $stmt->fetch()['enrolled'];

        if ($enrolled > 0) {
            $error = "Cannot delete block with enrolled students.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM blocks WHERE block_id = ?");
            $stmt->execute([$block_id]);
            $success = "Block deleted successfully.";
        }
    } catch (PDOException $e) {
        $error = "Error deleting block: " . $e->getMessage();
    }
}

// Fetch strands
try {
    $stmt = $pdo->prepare("SELECT * FROM strands ORDER BY strand_name");
    $stmt->execute();
    $strands = $stmt->fetchAll();
} catch (PDOException $e) {
    $strands = [];
}

// Fetch existing blocks with enrolled students count and strand name
try {
    $stmt = $pdo->prepare("
        SELECT b.*, COUNT(e.enrollment_id) as enrolled_students, s.strand_name
        FROM blocks b
        LEFT JOIN enrollments e ON b.block_id = e.block_id AND e.status = 'Approved'
        LEFT JOIN strands s ON b.strand_id = s.strand_id
        GROUP BY b.block_id
        ORDER BY b.block_name
    ");
    $stmt->execute();
    $blocks = $stmt->fetchAll();
} catch (PDOException $e) {
    $blocks = [];
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
    <title>Block Management - Registrar</title>
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
                    <h1 class="h3 mb-0 text-gray-800">Block Management</h1>

                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Create New Block</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="block_name">Block Name</label>
                                        <input type="text" class="form-control" id="block_name" name="block_name"
                                            required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="grade_level">Grade Level</label>
                                        <select class="form-control" id="grade_level" name="grade_level" required>
                                            <option value="">Select Grade Level</option>
                                            <option value="Grade 7">Grade 7</option>
                                            <option value="Grade 8">Grade 8</option>
                                            <option value="Grade 9">Grade 9</option>
                                            <option value="Grade 10">Grade 10</option>
                                            <option value="Grade 11">Grade 11</option>
                                            <option value="Grade 12">Grade 12</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="strand">Strand</label>
                                        <select class="form-control" id="strand" name="strand" required>
                                            <option value="">Select Strand</option>
                                            <?php foreach ($strands as $strand): ?>
                                                <option value="<?php echo htmlspecialchars($strand['strand_code']); ?>">
                                                    <?php echo htmlspecialchars($strand['strand_name']); ?>
                                                    (<?php echo htmlspecialchars($strand['strand_code']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="max_students">Max Students</label>
                                        <input type="number" class="form-control" id="max_students" name="max_students"
                                            min="1" required>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <button type="submit" name="create_block" class="btn btn-primary">Create
                                            Block</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Existing Blocks</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="blocksTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Block Name</th>
                                            <th>Grade Level</th>
                                            <th>Strand</th>
                                            <th>Enrolled Students</th>
                                            <th>Max Students</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($blocks)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No blocks created yet.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($blocks as $block): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($block['block_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($block['grade_level']); ?></td>
                                                    <td><?php echo htmlspecialchars($block['strand_name'] ?? $block['strand']); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($block['enrolled_students']); ?></td>
                                                    <td><?php echo htmlspecialchars($block['max_students']); ?></td>
                                                    <td>
                                                        <?php
                                                        $enrolled = $block['enrolled_students'];
                                                        $max = $block['max_students'];
                                                        if ($enrolled < $max) {
                                                            echo '<span class="badge badge-success">Available</span>';
                                                        } elseif ($enrolled == $max) {
                                                            echo '<span class="badge badge-warning">Full</span>';
                                                        } else {
                                                            echo '<span class="badge badge-danger">Over Capacity</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-warning"
                                                            onclick="editLimit(<?php echo $block['block_id']; ?>, <?php echo $block['max_students']; ?>)">Edit
                                                            Limit</button>
                                                        <button class="btn btn-sm btn-info"
                                                            onclick="viewStudents(<?php echo $block['block_id']; ?>)">View
                                                            Students</button>
                                                        <form method="POST" style="display:inline;"
                                                            onsubmit="return confirm('Are you sure you want to delete the block \'<?php echo htmlspecialchars($block['block_name']); ?>\'? This action cannot be undone.');">
                                                            <input type="hidden" name="block_id"
                                                                value="<?php echo $block['block_id']; ?>">
                                                            <button type="submit" name="delete_block"
                                                                class="btn btn-sm btn-danger">Delete</button>
                                                        </form>
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

    <!-- Edit Limit Modal -->
    <div class="modal fade" id="editLimitModal" tabindex="-1" role="dialog" aria-labelledby="editLimitModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editLimitModalLabel">Edit Block Limit</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="edit_block_id" name="block_id">
                        <div class="form-group">
                            <label for="new_limit">New Max Students</label>
                            <input type="number" class="form-control" id="new_limit" name="new_limit" min="1" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_limit" class="btn btn-primary">Update Limit</button>
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
        function editLimit(blockId, currentLimit) {
            $('#edit_block_id').val(blockId);
            $('#new_limit').val(currentLimit);
            $('#editLimitModal').modal('show');
        }

        function viewStudents(blockId) {
            // Redirect to a page showing enrolled students for this block
            window.location.href = 'Block_Students.php?block_id=' + blockId;
        }


    </script>
</body>

</html>