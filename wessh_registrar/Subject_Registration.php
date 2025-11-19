<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'registrar') {
    header("Location: registrar_login.php");
    exit;
}
include dirname(__DIR__) . '/includes/db.php';

// Handle form submission for registering subject
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_subject'])) {
    $subject_name = $_POST['subject_name'];
    $subject_code = $_POST['subject_code'];
    $description = $_POST['description'];
    $grade_level = $_POST['grade_level'];
    $semester = $_POST['semester'];

    try {
        $stmt = $pdo->prepare("INSERT INTO subjects (subject_name, subject_code, description, grade_level, semester) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$subject_name, $subject_code, $description, $grade_level, $semester]);
        $success = "Subject registered successfully.";
    } catch (PDOException $e) {
        $error = "Error registering subject: " . $e->getMessage();
    }
}

// Handle delete subject
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_subject'])) {
    $subject_id = $_POST['subject_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM subjects WHERE subject_id = ?");
        $stmt->execute([$subject_id]);
        $success = "Subject deleted successfully.";
    } catch (PDOException $e) {
        $error = "Error deleting subject: " . $e->getMessage();
    }
}

// Handle update subject
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_subject'])) {
    $subject_id = $_POST['subject_id'];
    $subject_name = $_POST['subject_name'];
    $subject_code = $_POST['subject_code'];
    $description = $_POST['description'];
    $grade_level = $_POST['grade_level'];
    $semester = $_POST['semester'];

    try {
        $stmt = $pdo->prepare("UPDATE subjects SET subject_name = ?, subject_code = ?, description = ?, grade_level = ?, semester = ? WHERE subject_id = ?");
        $stmt->execute([$subject_name, $subject_code, $description, $grade_level, $semester, $subject_id]);
        $success = "Subject updated successfully.";
    } catch (PDOException $e) {
        $error = "Error updating subject: " . $e->getMessage();
    }
}

// Fetch existing subjects
try {
    $stmt = $pdo->query("SELECT * FROM subjects ORDER BY subject_name");
    $subjects = $stmt->fetchAll();
} catch (PDOException $e) {
    $subjects = [];
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
    <title>Subject Registration - Registrar</title>
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
                    <h1 class="h3 mb-0 text-gray-800">Subject Registration</h1>

                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Register New Subject</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="subject_name">Subject Name</label>
                                        <input type="text" class="form-control" id="subject_name" name="subject_name"
                                            required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="subject_code">Subject Code</label>
                                        <input type="text" class="form-control" id="subject_code" name="subject_code"
                                            required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="grade_level">Grade Level</label>
                                        <select class="form-control" id="grade_level" name="grade_level" required>
                                            <option value="Grade 11">Grade 11</option>
                                            <option value="Grade 12">Grade 12</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <label for="semester">Semester</label>
                                        <select class="form-control" id="semester" name="semester" required>
                                            <option value="1st">1st Semester</option>
                                            <option value="2nd">2nd Semester</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="description">Description</label>
                                        <textarea class="form-control" id="description" name="description"
                                            rows="3"></textarea>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" name="register_subject"
                                            class="btn btn-primary mt-4">Register Subject</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Registered Subjects</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="subjectsTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Subject Name</th>
                                            <th>Subject Code</th>
                                            <th>Grade Level</th>
                                            <th>Semester</th>
                                            <th>Description</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($subjects)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No subjects registered yet.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($subjects as $subject): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                                    <td><?php echo htmlspecialchars($subject['grade_level']); ?></td>
                                                    <td><?php echo htmlspecialchars($subject['semester']); ?> Semester</td>
                                                    <td><?php echo htmlspecialchars($subject['description']); ?></td>
                                                    <td>
                                                        <?php
                                                        echo '<button class="btn btn-sm btn-warning mr-2" data-id="' . htmlspecialchars(json_encode($subject['subject_id'])) . '" data-name="' . htmlspecialchars(json_encode($subject['subject_name'])) . '" data-code="' . htmlspecialchars(json_encode($subject['subject_code'])) . '" data-description="' . htmlspecialchars(json_encode($subject['description'])) . '" data-grade="' . htmlspecialchars(json_encode($subject['grade_level'])) . '" data-semester="' . htmlspecialchars(json_encode($subject['semester'])) . '" onclick="editSubject(this)">Edit</button>';
                                                        echo '<form method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to delete this subject?\');"><input type="hidden" name="subject_id" value="' . $subject['subject_id'] . '"><button type="submit" name="delete_subject" class="btn btn-sm btn-danger">Delete</button></form>';
                                                        ?>
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

    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Edit Subject Modal -->
    <div class="modal fade" id="editSubjectModal" tabindex="-1" role="dialog" aria-labelledby="editSubjectModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSubjectModalLabel">Edit Subject</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="edit_subject_id" name="subject_id">
                        <div class="form-group">
                            <label for="edit_subject_name">Subject Name</label>
                            <input type="text" class="form-control" id="edit_subject_name" name="subject_name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_subject_code">Subject Code</label>
                            <input type="text" class="form-control" id="edit_subject_code" name="subject_code" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_grade_level">Grade Level</label>
                            <select class="form-control" id="edit_grade_level" name="grade_level" required>
                                <option value="Grade 11">Grade 11</option>
                                <option value="Grade 12">Grade 12</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_semester">Semester</label>
                            <select class="form-control" id="edit_semester" name="semester" required>
                                <option value="1st">1st Semester</option>
                                <option value="2nd">2nd Semester</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_description">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_subject" class="btn btn-primary">Update Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
        function editSubject(button) {
            const id = JSON.parse(button.getAttribute('data-id'));
            const name = JSON.parse(button.getAttribute('data-name'));
            const code = JSON.parse(button.getAttribute('data-code'));
            const description = JSON.parse(button.getAttribute('data-description'));
            const grade = JSON.parse(button.getAttribute('data-grade'));
            const semester = JSON.parse(button.getAttribute('data-semester'));

            document.getElementById('edit_subject_id').value = id;
            document.getElementById('edit_subject_name').value = name;
            document.getElementById('edit_subject_code').value = code;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_grade_level').value = grade;
            document.getElementById('edit_semester').value = semester;
            $('#editSubjectModal').modal('show');
        }
    </script>
</body>

</html>