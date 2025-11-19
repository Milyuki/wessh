<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'registrar') {
    header("Location: registrar_login.php");
    exit;
}
include dirname(__DIR__) . '/includes/db.php';

// Generate form token to prevent duplicate submissions
if (!isset($_SESSION['form_token'])) {
    $_SESSION['form_token'] = bin2hex(random_bytes(32));
}

// Handle delete schedule
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_schedule'])) {
    $schedule_id = $_POST['schedule_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM schedules WHERE schedule_id = ?");
        $stmt->execute([$schedule_id]);
        $_SESSION['success_message'] = "Schedule deleted successfully.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } catch (PDOException $e) {
        $error = "Error deleting schedule: " . $e->getMessage();
    }
}

// Check for edit mode
$edit_schedule = null;
if (isset($_GET['edit'])) {
    $schedule_id = $_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT s.*, b.strand_id FROM schedules s JOIN blocks b ON s.block_id = b.block_id WHERE s.schedule_id = ?");
        $stmt->execute([$schedule_id]);
        $edit_schedule = $stmt->fetch();
    } catch (PDOException $e) {
        $error = "Error fetching schedule for edit.";
    }
}

// Handle form submission for assigning or updating schedule
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_schedule']) && isset($_POST['token']) && $_POST['token'] === $_SESSION['form_token'] && !isset($_SESSION['form_processed'])) {
    $subject_id = $_POST['subject_id'];
    $teacher_id = $_POST['teacher_id'];
    $block_id = $_POST['block_id'];
    $day = $_POST['day'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $room = $_POST['room'];

    try {
        if (isset($_POST['schedule_id']) && !empty($_POST['schedule_id'])) {
            $schedule_id = $_POST['schedule_id'];
            $stmt = $pdo->prepare("UPDATE schedules SET subject_id = ?, teacher_id = ?, block_id = ?, day = ?, start_time = ?, end_time = ?, room = ? WHERE schedule_id = ?");
            $stmt->execute([$subject_id, $teacher_id, $block_id, $day, $start_time, $end_time, $room, $schedule_id]);
            $_SESSION['success_message'] = "Schedule updated successfully.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO schedules (subject_id, teacher_id, block_id, day, start_time, end_time, room) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$subject_id, $teacher_id, $block_id, $day, $start_time, $end_time, $room]);
            $_SESSION['success_message'] = "Schedule assigned successfully.";
        }
        $_SESSION['form_processed'] = true;
        unset($_SESSION['form_token']);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } catch (PDOException $e) {
        $error = "Error saving schedule: " . $e->getMessage();
    }
}

// Success message
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
    unset($_SESSION['form_processed']);
}

// Fetch dropdown data
try {
    $subjects = $pdo->query("SELECT subject_id, subject_name FROM subjects ORDER BY subject_name")->fetchAll();
    $teachers = $pdo->query("SELECT user_id, firstname, lastname FROM users WHERE user_type = 'teacher' ORDER BY lastname")->fetchAll();
    $blocks = $pdo->query("SELECT b.block_id, b.block_name, b.strand_id, s.strand_name FROM blocks b JOIN strands s ON b.strand_id = s.strand_id ORDER BY s.strand_name, b.block_name")->fetchAll();
    $strands = $pdo->query("SELECT strand_id, strand_name FROM strands ORDER BY strand_name")->fetchAll();
} catch (PDOException $e) {
    $subjects = $teachers = $blocks = $strands = [];
}

// Build block data grouped by strand for JavaScript
$blockData = [];
foreach ($blocks as $b) {
    $blockData[$b['strand_id']][] = ['id' => $b['block_id'], 'name' => $b['block_name']];
}

// Fetch schedules with strand info
try {
    $sql = "
        SELECT s.schedule_id, sub.subject_name, u.firstname, u.lastname, 
               b.block_name, b.block_id, str.strand_id, str.strand_name,
               s.day, s.start_time, s.end_time, s.room
        FROM schedules s
        JOIN subjects sub ON s.subject_id = sub.subject_id
        JOIN users u ON s.teacher_id = u.user_id
        JOIN blocks b ON s.block_id = b.block_id
        JOIN strands str ON b.strand_id = str.strand_id
        ORDER BY str.strand_name, b.block_name, s.day, s.start_time
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $schedules = $stmt->fetchAll();
} catch (PDOException $e) {
    $schedules = [];
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Schedule Assignment - Registrar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        .badge-day {
            font-size: 0.9em;
        }

        th {
            background-color: #f8f9fa;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper" class="d-flex">
        <?php include 'sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php
                $topbar = dirname(__DIR__) . '/includes/topbar.php';
                if (file_exists($topbar)) {
                    include $topbar;
                } else {
                    // topbar not found — keep silent or add a fallback
                    // error_log("Topbar include missing: $topbar");
                }
                ?>

                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Schedule Assignment</h1>

                    <?php if (isset($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?= $success ?> <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= $error ?> <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>

                    <!-- Assign / Edit Form -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <?= isset($edit_schedule) ? 'Edit Schedule' : 'Assign New Schedule' ?>
                            </h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="token" value="<?= $_SESSION['form_token'] ?? '' ?>">
                                <?php if (isset($edit_schedule)): ?>
                                    <input type="hidden" name="schedule_id" value="<?= $edit_schedule['schedule_id'] ?>">
                                <?php endif; ?>

                                <div class="row">
                                    <div class="col-md-2 mb-3">
                                        <label>Subject <span class="text-danger">*</span></label>
                                        <select class="form-control" name="subject_id" required>
                                            <option value="">Select Subject</option>
                                            <?php foreach ($subjects as $s): ?>
                                                <option value="<?= $s['subject_id'] ?>" <?= (isset($edit_schedule) && $edit_schedule['subject_id'] == $s['subject_id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($s['subject_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label>Teacher <span class="text-danger">*</span></label>
                                        <select class="form-control" name="teacher_id" required>
                                            <option value="">Select Teacher</option>
                                            <?php foreach ($teachers as $t): ?>
                                                <option value="<?= $t['user_id'] ?>" <?= (isset($edit_schedule) && $edit_schedule['teacher_id'] == $t['user_id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($t['lastname'] . ', ' . $t['firstname']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label>Strand <span class="text-danger">*</span></label>
                                        <select class="form-control" id="form_strand" name="strand_id" required>
                                            <option value="">Select Strand</option>
                                            <?php foreach ($strands as $str): ?>
                                                <option value="<?= $str['strand_id'] ?>" <?= (isset($edit_schedule) && $edit_schedule['strand_id'] == $str['strand_id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($str['strand_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label>Block <span class="text-danger">*</span></label>
                                        <select class="form-control" id="form_block" name="block_id" required>
                                            <option value="">Select Block</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label>Day <span class="text-danger">*</span></label>
                                        <select class="form-control" name="day" required>
                                            <?php
                                            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                            foreach ($days as $d): ?>
                                                <option value="<?= $d ?>" <?= (isset($edit_schedule) && $edit_schedule['day'] == $d) ? 'selected' : '' ?>><?= $d ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-2 mb-3">
                                        <label>Start Time <span class="text-danger">*</span></label>
                                        <input type="time" class="form-control" name="start_time"
                                            value="<?= $edit_schedule['start_time'] ?? '' ?>" required>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label>End Time <span class="text-danger">*</span></label>
                                        <input type="time" class="form-control" name="end_time"
                                            value="<?= $edit_schedule['end_time'] ?? '' ?>" required>
                                    </div>
                                    <div class="col-md-5 mb-3">
                                        <label>Room <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="room"
                                            value="<?= $edit_schedule['room'] ?? '' ?>" placeholder="e.g., Room 301"
                                            required>
                                    </div>
                                    <div class="col-md-3 mb-3 d-flex align-items-end">
                                        <button type="submit" name="assign_schedule" class="btn btn-primary mr-2">
                                            <?= isset($edit_schedule) ? 'Save Schedule' : 'Assign Schedule' ?>
                                        </button>
                                        <?php if (isset($edit_schedule)): ?>
                                            <a href="Schedule_Assignment.php" class="btn btn-secondary">Cancel</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Existing Schedules with Filters -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Existing Schedules</h6>
                        </div>
                        <div class="card-body">
                            <!-- Filters -->
                            <div class="row mb-4">
                                <div class="col-md-5">
                                    <label><strong>Filter by Strand</strong></label>
                                    <select id="filter_strand" class="form-control">
                                        <option value="">All Strands</option>
                                        <?php foreach ($strands as $strand): ?>
                                            <option value="<?= $strand['strand_id'] ?>">
                                                <?= htmlspecialchars($strand['strand_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label><strong>Filter by Block</strong></label>
                                    <select id="filter_block" class="form-control">
                                        <option value="">All Blocks</option>
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button id="clear_filters" class="btn btn-outline-danger btn-block">Clear
                                        Filters</button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="schedulesTable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Strand</th>
                                            <th>Block</th>
                                            <th>Subject</th>
                                            <th>Teacher</th>
                                            <th>Day</th>
                                            <th>Time</th>
                                            <th>Room</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($schedules)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-5">No schedules assigned
                                                    yet.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($schedules as $s): ?>
                                                <tr data-strand="<?= $s['strand_id'] ?>" data-block="<?= $s['block_id'] ?>">
                                                    <td><strong><?= htmlspecialchars($s['strand_name']) ?></strong></td>
                                                    <td><?= htmlspecialchars($s['block_name']) ?></td>
                                                    <td><?= htmlspecialchars($s['subject_name']) ?></td>
                                                    <td><?= htmlspecialchars($s['firstname'] . ' ' . $s['lastname']) ?></td>
                                                    <td><span class="badge badge-info badge-day"><?= $s['day'] ?></span></td>
                                                    <td><?= $s['start_time'] ?> - <?= $s['end_time'] ?></td>
                                                    <td><strong><?= htmlspecialchars($s['room']) ?></strong></td>
                                                    <td>
                                                        <a href="?edit=<?= $s['schedule_id'] ?>" class="btn btn-sm btn-warning">
                                                            Edit
                                                        </a>
                                                        <form method="POST" style="display:inline;"
                                                            onsubmit="return confirm('Delete this schedule permanently?');">
                                                            <input type="hidden" name="schedule_id"
                                                                value="<?= $s['schedule_id'] ?>">
                                                            <button type="submit" name="delete_schedule"
                                                                class="btn btn-sm btn-danger">
                                                                Delete
                                                            </button>
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
                        <span>Copyright © WESSH 2025</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

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

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/sb-admin-2.min.js"></script>

    <script>
        const blockData = <?= json_encode($blockData) ?>;

        const strandSelect = document.getElementById('filter_strand');
        const blockSelect = document.getElementById('filter_block');
        const formStrandSelect = document.getElementById('form_strand');
        const formBlockSelect = document.getElementById('form_block');
        const rows = document.querySelectorAll('#schedulesTable tbody tr[data-strand]');
        const clearBtn = document.getElementById('clear_filters');

        function populateBlocks(strandId = '', targetSelect = blockSelect) {
            targetSelect.innerHTML = targetSelect === blockSelect ? '<option value="">All Blocks</option>' : '<option value="">Select Block</option>';
            if (!strandId || !blockData[strandId]) return;

            blockData[strandId].forEach(b => {
                const opt = new Option(b.name, b.id);
                targetSelect.appendChild(opt);
            });
        }

        function filterTable() {
            const strand = strandSelect.value;
            const block = blockSelect.value;

            rows.forEach(row => {
                const rowStrand = row.dataset.strand;
                const rowBlock = row.dataset.block;

                const matchStrand = !strand || rowStrand === strand;
                const matchBlock = !block || rowBlock === block;

                row.style.display = (matchStrand && matchBlock) ? '' : 'none';
            });
        }

        strandSelect.addEventListener('change', () => {
            populateBlocks(strandSelect.value);
            filterTable();
        });

        blockSelect.addEventListener('change', filterTable);

        formStrandSelect.addEventListener('change', () => {
            populateBlocks(formStrandSelect.value, formBlockSelect);
            formBlockSelect.value = '';
        });

        clearBtn.addEventListener('click', () => {
            strandSelect.value = '';
            populateBlocks();
            blockSelect.innerHTML = '<option value="">All Blocks</option>';
            filterTable();
        });

        // Initialize
        populateBlocks(strandSelect.value);
        if (formStrandSelect.value) {
            populateBlocks(formStrandSelect.value, formBlockSelect);
        }
        <?php if (isset($edit_schedule)): ?>
            formBlockSelect.value = '<?= $edit_schedule['block_id'] ?>';
        <?php endif; ?>
    </script>
</body>

</html>