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
$_SESSION['user_type'] = $_SESSION['user_type'] ?? 'teacher';


// Check if user is logged in and is a teacher
// if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
//     header('Location: login.php');
//     exit();
// }

include dirname(__DIR__) . '/includes/db.php';

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


$enrollment_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$enrollment = null;
$message = '';

// Fetch enrollment details first
try {
    $stmt = $pdo->prepare("
        SELECT e.*, u.user_id as student_id, CONCAT(u.firstname, ' ', u.lastname) as student_name, u.email as student_email
        FROM enrollments e
        JOIN users u ON e.student_id = u.user_id
        WHERE e.enrollment_id = ?
    ");
    $stmt->execute([$enrollment_id]);
    $enrollment = $stmt->fetch();
} catch (PDOException $e) {
    // If database error, set to null
    $enrollment = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$enrollment || !is_array($enrollment) || !isset($enrollment['student_id'])) {
        $message = 'Enrollment not found or invalid.';
    } else {
        $checked_placeholders = isset($_POST['placeholders']) ? $_POST['placeholders'] : [];
        $feedback_message = trim($_POST['message']);

        $all_placeholders = ['personal_info', 'contact_info', 'family_info', 'special_needs', 'educational_bg', 'enrollment_details', 'id_photo', 'birth_cert', 'medical_cert', 'transcript'];
        $unchecked_placeholders = array_diff($all_placeholders, $checked_placeholders);

        try {
            if (empty($unchecked_placeholders)) {
                // All documents approved - send to registrar for final approval
                $status = 'For Checking';
                $notification_message = 'Your documents have been reviewed and approved by the teacher. Your enrollment is now under review by the registrar for final approval.';
            } else {
                // Some documents need revision
                $status = 'Rejected';
                $revision_list = implode(', ', array_map(function ($p) {
                    $labels = [
                        'personal_info' => 'Personal Information (Name, Email, Birth Details)',
                        'contact_info' => 'Contact Information (Address, Phone)',
                        'family_info' => 'Family Information (Parents/Guardians)',
                        'special_needs' => 'Special Needs (SPED, Diagnosis)',
                        'educational_bg' => 'Educational Background (Previous School, Track/Strand)',
                        'enrollment_details' => 'Enrollment Details (Semester, Modality)',
                        'id_photo' => 'ID Photo',
                        'birth_cert' => 'Birth Certificate',
                        'medical_cert' => 'Medical Certificate',
                        'transcript' => 'Academic Transcript'
                    ];
                    return $labels[$p] ?? $p;
                }, $unchecked_placeholders));
                $notification_message = "The following documents need revision: $revision_list. " . ($feedback_message ? $feedback_message : 'Please update and resubmit.');
            }

            // Update enrollment status
            $stmt = $pdo->prepare("UPDATE enrollments SET status = ? WHERE enrollment_id = ?");
            $stmt->execute([$status, $enrollment_id]);

            // Insert notification
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$enrollment['student_id'], $notification_message]);

            $message = 'Review submitted successfully!';
            // Redirect back to reports page after update
            header('Location: reports.php?updated=1');
            exit();
        } catch (PDOException $e) {
            $message = 'Error updating enrollment: ' . $e->getMessage();
        }
    }
}

// Fetch documents for this enrollment from database
$documents = [];
if ($enrollment) {
    try {
        $stmt = $pdo->prepare("SELECT document_type, file_name, file_path as file_data FROM documents WHERE enrollment_id = ?");
        $stmt->execute([$enrollment_id]);
        $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($docs as $doc) {
            $documents[$doc['document_type']] = [
                'file_name' => $doc['file_name'],
                'file_data' => $doc['file_data']
            ];
        }
    } catch (PDOException $e) {
        echo "Error fetching documents: " . $e->getMessage();
    }
}

// Debug output
if (isset($_GET['debug'])) {
    echo "<pre>Debug Info:\n";
    echo "Enrollment ID: {$enrollment_id}\n";
    echo "Student ID: {$enrollment['student_id']}\n";
    echo "Documents loaded from database: " . count($documents) . "\n";
    foreach ($documents as $type => $doc) {
        echo "- $type: {$doc['file_name']} (" . strlen($doc['file_data']) . " bytes)\n";
    }
    echo "</pre>";
}

if (!$enrollment) {
    $message = 'Enrollment not found.';
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
    <title>Review Enrollment - WESSH Teacher Dashboard</title>
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
        <?php include 'teacher_sidebar.php'; ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar (same as dashboard) -->
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
                                <span
                                    class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Teacher'); ?></span>
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
                        <h1 class="h3 mb-0 text-gray-800">Review Enrollment</h1>
                        <a href="dashboard.php" class="btn btn-secondary btn-sm">Back to Dashboard</a>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>

                    <?php if ($enrollment): ?>
                        <form method="POST">
                            <!-- Enrollment Details -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Filled Enrollment Form</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="d-flex align-items-center">
                                                    <input type="checkbox" name="placeholders[]" value="personal_info"
                                                        id="personal_info_check" class="mr-2">
                                                    <label for="personal_info_check" class="mb-0 font-weight-bold">Personal
                                                        Information (Name, Email, Birth Details)</label>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="first_name">First Name:</label>
                                                <input type="text" class="form-control" id="first_name"
                                                    value="<?php echo htmlspecialchars($enrollment['first_name']); ?>"
                                                    readonly>
                                            </div>
                                            <div class="form-group">
                                                <label for="middle_name">Middle Name:</label>
                                                <input type="text" class="form-control" id="middle_name"
                                                    value="<?php echo htmlspecialchars($enrollment['middle_name']); ?>"
                                                    readonly>
                                            </div>
                                            <div class="form-group">
                                                <label for="last_name">Last Name:</label>
                                                <input type="text" class="form-control" id="last_name"
                                                    value="<?php echo htmlspecialchars($enrollment['last_name']); ?>"
                                                    readonly>
                                            </div>
                                            <div class="form-group">
                                                <label for="extension_name">Extension Name:</label>
                                                <input type="text" class="form-control" id="extension_name"
                                                    value="<?php echo htmlspecialchars($enrollment['extension_name'] ?? ''); ?>"
                                                    readonly>
                                            </div>
                                            <div class="form-group">
                                                <label for="student_email">Email:</label>
                                                <input type="email" class="form-control" id="student_email"
                                                    value="<?php echo htmlspecialchars($enrollment['student_email']); ?>"
                                                    readonly>
                                            </div>
                                            <div class="form-group">
                                                <label for="birth_date">Birth Date:</label>
                                                <input type="date" class="form-control" id="birth_date"
                                                    value="<?php echo htmlspecialchars($enrollment['birth_date']); ?>"
                                                    readonly>
                                            </div>
                                            <div class="form-group">
                                                <label for="age">Age:</label>
                                                <input type="number" class="form-control" id="age"
                                                    value="<?php echo htmlspecialchars($enrollment['age']); ?>" readonly>
                                            </div>
                                            <div class="form-group">
                                                <label for="sex">Sex:</label>
                                                <select class="form-control" id="sex" disabled>
                                                    <option value="Male" <?php echo ($enrollment['sex'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                                    <option value="Female" <?php echo ($enrollment['sex'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="birth_place">Birth Place:</label>
                                                <input type="text" class="form-control" id="birth_place"
                                                    value="<?php echo htmlspecialchars($enrollment['birth_place']); ?>"
                                                    readonly>
                                            </div>
                                            <div class="form-group">
                                                <label for="religion">Religion:</label>
                                                <input type="text" class="form-control" id="religion"
                                                    value="<?php echo htmlspecialchars($enrollment['religion'] ?? ''); ?>"
                                                    readonly>
                                            </div>
                                            <div class="form-group">
                                                <label for="mother_tongue">Mother Tongue:</label>
                                                <input type="text" class="form-control" id="mother_tongue"
                                                    value="<?php echo htmlspecialchars($enrollment['mother_tongue'] ?? ''); ?>"
                                                    readonly>
                                            </div>
                                            <div class="form-group">
                                                <label for="ip_community">IP Community:</label>
                                                <input type="text" class="form-control" id="ip_community"
                                                    value="<?php echo htmlspecialchars($enrollment['ip_community'] ?? ''); ?>"
                                                    readonly>
                                            </div>
                                            <div class="form-group">
                                                <label for="four_ps">4Ps Beneficiary:</label>
                                                <select class="form-control" id="four_ps" disabled>
                                                    <option value="Yes" <?php echo ($enrollment['four_ps'] == 'Yes') ? 'selected' : ''; ?>>Yes</option>
                                                    <option value="No" <?php echo ($enrollment['four_ps'] == 'No') ? 'selected' : ''; ?>>No</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <div class="d-flex align-items-center">
                                                    <input type="checkbox" name="placeholders[]" value="contact_info"
                                                        id="contact_info_check" class="mr-2">
                                                    <label for="contact_info_check" class="mb-0 font-weight-bold">Contact
                                                        Information (Address, Phone)</label>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="address">Address:</label>
                                                <textarea class="form-control" id="address" rows="2"
                                                    readonly><?php echo htmlspecialchars($enrollment['address']); ?></textarea>
                                            </div>
                                            <div class="form-group">
                                                <div class="d-flex align-items-center">
                                                    <input type="checkbox" name="placeholders[]" value="family_info"
                                                        id="family_info_check" class="mr-2">
                                                    <label for="family_info_check" class="mb-0 font-weight-bold">Family
                                                        Information (Parents/Guardians)</label>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="father_name">Father's Name:</label>
                                                <input type="text" class="form-control" id="father_name"
                                                    value="<?php echo htmlspecialchars($enrollment['father_name'] ?? ''); ?>"
                                                    readonly>
                                            </div>
                                            <div class="form-group">
                                                <label for="mother_name">Mother's Name:</label>
                                                <input type="text" class="form-control" id="mother_name"
                                                    value="<?php echo htmlspecialchars($enrollment['mother_name'] ?? ''); ?>"
                                                    readonly>
                                            </div>
                                            <div class="form-group">
                                                <label for="guardian_name">Guardian's Name:</label>
                                                <input type="text" class="form-control" id="guardian_name"
                                                    value="<?php echo htmlspecialchars($enrollment['guardian_name'] ?? ''); ?>"
                                                    readonly>
                                            </div>
                                            <div class="form-group">
                                                <label for="guardian_contact">Guardian's Contact:</label>
                                                <input type="text" class="form-control" id="guardian_contact"
                                                    value="<?php echo htmlspecialchars($enrollment['guardian_contact'] ?? ''); ?>"
                                                    readonly>
                                            </div>
                                            <div class="form-group">
                                                <div class="d-flex align-items-center">
                                                    <input type="checkbox" name="placeholders[]" value="special_needs"
                                                        id="special_needs_check" class="mr-2">
                                                    <label for="special_needs_check" class="mb-0 font-weight-bold">Special
                                                        Needs (SPED, Diagnosis)</label>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="sped_needs">SPED Needs:</label>
                                                <select class="form-control" id="sped_needs" disabled>
                                                    <option value="Yes" <?php echo ($enrollment['sped_needs'] == 'Yes') ? 'selected' : ''; ?>>Yes</option>
                                                    <option value="No" <?php echo ($enrollment['sped_needs'] == 'No') ? 'selected' : ''; ?>>No</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="diagnosis">Diagnosis:</label>
                                                <input type="text" class="form-control" id="diagnosis"
                                                    value="<?php echo htmlspecialchars($enrollment['diagnosis'] ?? ''); ?>"
                                                    readonly>
                                            </div>
                                            <div class="form-group">
                                                <label for="manifestations">Manifestations:</label>
                                                <textarea class="form-control" id="manifestations" rows="2"
                                                    readonly><?php echo htmlspecialchars($enrollment['manifestations'] ?? ''); ?></textarea>
                                            </div>
                                            <div class="form-group">
                                                <div class="d-flex align-items-center">
                                                    <input type="checkbox" name="placeholders[]" value="educational_bg"
                                                        id="educational_bg_check" class="mr-2">
                                                    <label for="educational_bg_check"
                                                        class="mb-0 font-weight-bold">Educational Background (Previous
                                                        School, Track/Strand)</label>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="returning_learner">Returning Learner:</label>
                                                <select class="form-control" id="returning_learner" disabled>
                                                    <option value="Yes" <?php echo ($enrollment['returning_learner'] == 'Yes') ? 'selected' : ''; ?>>Yes</option>
                                                    <option value="No" <?php echo ($enrollment['returning_learner'] == 'No') ? 'selected' : ''; ?>>No</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="last_school_attended">Last School Attended:</label>
                                                <input type="text" class="form-control" id="last_school_attended"
                                                    value="<?php echo htmlspecialchars($enrollment['last_school_attended'] ?? ''); ?>"
                                                    readonly>
                                            </div>
                                            <div class="form-group">
                                                <label for="last_school_id">Last School ID:</label>
                                                <input type="text" class="form-control" id="last_school_id"
                                                    value="<?php echo htmlspecialchars($enrollment['last_school_id'] ?? ''); ?>"
                                                    readonly>
                                            </div>
                                            <div class="form-group">
                                                <div class="d-flex align-items-center">
                                                    <input type="checkbox" name="placeholders[]" value="enrollment_details"
                                                        id="enrollment_details_check" class="mr-2">
                                                    <label for="enrollment_details_check"
                                                        class="mb-0 font-weight-bold">Enrollment Details (Semester,
                                                        Modality)</label>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="semester">Semester:</label>
                                                <select class="form-control" id="semester" disabled>
                                                    <option value="1st Semester" <?php echo ($enrollment['semester'] == '1st Semester') ? 'selected' : ''; ?>>1st Semester</option>
                                                    <option value="2nd Semester" <?php echo ($enrollment['semester'] == '2nd Semester') ? 'selected' : ''; ?>>2nd Semester</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="track">Track:</label>
                                                <select class="form-control" id="track" disabled>
                                                    <option value="Academic" <?php echo ($enrollment['track'] == 'Academic') ? 'selected' : ''; ?>>Academic</option>
                                                    <option value="TVL" <?php echo ($enrollment['track'] == 'TVL') ? 'selected' : ''; ?>>TVL</option>
                                                    <option value="Sports" <?php echo ($enrollment['track'] == 'Sports') ? 'selected' : ''; ?>>Sports</option>
                                                    <option value="Arts and Design" <?php echo ($enrollment['track'] == 'Arts and Design') ? 'selected' : ''; ?>>Arts and Design</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="strand">Strand:</label>
                                                <input type="text" class="form-control" id="strand"
                                                    value="<?php echo htmlspecialchars($enrollment['strand'] ?? ''); ?>"
                                                    readonly>
                                            </div>
                                            <div class="form-group">
                                                <label for="learning_modality">Learning Modality:</label>
                                                <select class="form-control" id="learning_modality" disabled>
                                                    <option value="Face-to-Face" <?php echo ($enrollment['learning_modality'] == 'Face-to-Face') ? 'selected' : ''; ?>>Face-to-Face</option>
                                                    <option value="Online" <?php echo ($enrollment['learning_modality'] == 'Online') ? 'selected' : ''; ?>>
                                                        Online</option>
                                                    <option value="Blended" <?php echo ($enrollment['learning_modality'] == 'Blended') ? 'selected' : ''; ?>>
                                                        Blended</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="submission_date">Submission Date:</label>
                                                <input type="text" class="form-control" id="submission_date"
                                                    value="<?php echo htmlspecialchars($enrollment['submission_date']); ?>"
                                                    readonly>
                                            </div>
                                            <div class="form-group">
                                                <label for="status">Current Status:</label>
                                                <input type="text" class="form-control" id="status"
                                                    value="<?php echo htmlspecialchars($enrollment['status']); ?>" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Document Placeholders -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Document Placeholders</h6>
                                </div>
                                <div class="card-body">
                                    <!-- Note: 2x2 ID is not saved to DB as per task requirements, so no display here -->
                                    <div class="form-group">
                                        <div class="d-flex align-items-center">
                                            <input type="checkbox" name="placeholders[]" value="birth_cert"
                                                id="birth_cert_check" class="mr-2">
                                            <label for="birth_cert_check" class="mb-0 font-weight-bold">Birth
                                                Certificate</label>
                                        </div>
                                        <div class="mt-2">
                                            <?php if (!empty($documents['PSA Birth Certificate'])): ?>
                                                <?php
                                                $file_name = $documents['PSA Birth Certificate']['file_name'];
                                                $file_data = $documents['PSA Birth Certificate']['file_data'];
                                                $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                                                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])):
                                                    $mime = 'image/' . ($ext == 'jpg' ? 'jpeg' : $ext);
                                                    $base64 = base64_encode($file_data);
                                                    ?>
                                                    <a href="#" data-toggle="modal" data-target="#birthCertModal">
                                                        <img src="data:<?php echo $mime; ?>;base64,<?php echo $base64; ?>"
                                                            alt="Birth Certificate" class="img-fluid"
                                                            style="max-width: 100%; max-height: 200px; cursor: pointer;">
                                                    </a>
                                                <?php elseif ($ext == 'pdf'): ?>
                                                    <a href="#" data-toggle="modal" data-target="#birthCertModal"
                                                        class="btn btn-primary">View Birth Certificate</a>
                                                <?php else: ?>
                                                    <a href="data:application/octet-stream;base64,<?php echo base64_encode($file_data); ?>"
                                                        download="<?php echo htmlspecialchars($file_name); ?>"
                                                        class="btn btn-primary">Download Birth Certificate</a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div class="border p-3 text-center" style="height: 200px;">Birth Certificate
                                                    Placeholder</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="d-flex align-items-center">
                                            <input type="checkbox" name="placeholders[]" value="medical_cert"
                                                id="medical_cert_check" class="mr-2">
                                            <label for="medical_cert_check" class="mb-0 font-weight-bold">Good Moral
                                                Certificate</label>
                                        </div>
                                        <div class="mt-2">
                                            <?php if (!empty($documents['GOOD_MORAL'])): ?>
                                                <?php
                                                $file_name = $documents['Good Moral']['file_name'];
                                                $file_data = $documents['Good Moral']['file_data'];
                                                $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                                                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])):
                                                    $mime = 'image/' . ($ext == 'jpg' ? 'jpeg' : $ext);
                                                    $base64 = base64_encode($file_data);
                                                    ?>
                                                    <a href="#" data-toggle="modal" data-target="#medicalCertModal">
                                                        <img src="data:<?php echo $mime; ?>;base64,<?php echo $base64; ?>"
                                                            alt="Medical Certificate" class="img-fluid"
                                                            style="max-width: 100%; max-height: 200px; cursor: pointer;">
                                                    </a>
                                                <?php elseif ($ext == 'pdf'): ?>
                                                    <a href="#" data-toggle="modal" data-target="#medicalCertModal"
                                                        class="btn btn-primary">View Medical Certificate</a>
                                                <?php else: ?>
                                                    <a href="data:application/octet-stream;base64,<?php echo base64_encode($file_data); ?>"
                                                        download="<?php echo htmlspecialchars($file_name); ?>"
                                                        class="btn btn-primary">Download Medical Certificate</a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div class="border p-3 text-center" style="height: 200px;">Medical Certificate
                                                    Placeholder</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="d-flex align-items-center">
                                            <input type="checkbox" name="placeholders[]" value="transcript"
                                                id="transcript_check" class="mr-2">
                                            <label for="transcript_check" class="mb-0 font-weight-bold">Academic
                                                Transcript</label>
                                        </div>
                                        <div class="mt-2">
                                            <?php if (!empty($documents['SCHOOL_FORM_9'])): ?>
                                                <a href="#" data-toggle="modal" data-target="#transcriptModal">
                                                    <i class="fas fa-file-alt fa-2x"></i> Academic Transcript
                                                </a>
                                            <?php else: ?>
                                                <div class="border p-3 text-center" style="height: 200px;">Academic Transcript
                                                    Placeholder</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Review Form -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Feedback Message:</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <textarea name="message" id="message" class="form-control" rows="4"
                                            placeholder="Provide feedback or additional instructions for the student"></textarea>
                                        <small class="form-text text-muted">This message will be sent to the student if
                                            any
                                            sections or documents need revision.</small>
                                    </div>
                                    <button type="submit" class="btn btn-primary"
                                        onclick="return confirm('Are you sure you want to submit this review?');">Submit
                                        Review</button>
                                    <button type="button" class="btn btn-success ml-2" id="sendToRegistrarBtn"
                                        onclick="sendToRegistrar()">Send to Registrar</button>
                                    <a href="reports.php" class="btn btn-secondary ml-2">Cancel</a>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">Enrollment not found.</div>
            <?php endif; ?>
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

    <!-- ID Photo Modal -->
    <div class="modal fade" id="idPhotoModal" tabindex="-1" role="dialog" aria-labelledby="idPhotoModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="idPhotoModalLabel">ID Photo</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($documents['2x2 ID'])): ?>
                        <?php
                        $file_name = $documents['2x2 ID']['file_name'];
                        $file_data = $documents['2x2 ID']['file_data'];
                        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])):
                            $mime = 'image/' . ($ext == 'jpg' ? 'jpeg' : $ext);
                            $base64 = base64_encode($file_data);
                            ?>
                            <img src="data:<?php echo $mime; ?>;base64,<?php echo $base64; ?>" alt="ID Photo" class="img-fluid">
                        <?php elseif ($ext == 'pdf'): ?>
                            <embed src="data:application/pdf;base64,<?php echo base64_encode($file_data); ?>"
                                type="application/pdf" width="100%" height="600px">
                        <?php else: ?>
                            <p>Unsupported file type.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>No document available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Birth Certificate Modal -->
    <div class="modal fade" id="birthCertModal" tabindex="-1" role="dialog" aria-labelledby="birthCertModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="birthCertModalLabel">Birth Certificate</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($documents['PSA Birth Certificate'])): ?>
                        <?php
                        $file_name = $documents['PSA Birth Certificate']['file_name'];
                        $file_data = $documents['PSA Birth Certificate']['file_data'];
                        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])):
                            $mime = 'image/' . ($ext == 'jpg' ? 'jpeg' : $ext);
                            $base64 = base64_encode($file_data);
                            ?>
                            <img src="data:<?php echo $mime; ?>;base64,<?php echo $base64; ?>" alt="Birth Certificate"
                                class="img-fluid">
                        <?php elseif ($ext == 'pdf'): ?>
                            <embed src="data:application/pdf;base64,<?php echo base64_encode($file_data); ?>"
                                type="application/pdf" width="100%" height="600px">
                        <?php else: ?>
                            <p>Unsupported file type.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>No document available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Medical Certificate Modal -->
    <div class="modal fade" id="medicalCertModal" tabindex="-1" role="dialog" aria-labelledby="medicalCertModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="medicalCertModalLabel">Medical Certificate</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($documents['Good Moral'])): ?>
                        <?php
                        $file_name = $documents['Good Moral']['file_name'];
                        $file_data = $documents['Good Moral']['file_data'];
                        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])):
                            $mime = 'image/' . ($ext == 'jpg' ? 'jpeg' : $ext);
                            $base64 = base64_encode($file_data);
                            ?>
                            <img src="data:<?php echo $mime; ?>;base64,<?php echo $base64; ?>" alt="Medical Certificate"
                                class="img-fluid">
                        <?php elseif ($ext == 'pdf'): ?>
                            <embed src="data:application/pdf;base64,<?php echo base64_encode($file_data); ?>"
                                type="application/pdf" width="100%" height="600px">
                        <?php else: ?>
                            <p>Unsupported file type.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>No document available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Form 137 Modal -->
    <div class="modal fade" id="transcriptModal" tabindex="-1" role="dialog" aria-labelledby="transcriptModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="transcriptModalLabel">Form 137</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($documents['SCHOOL_FORM_9'])): ?>
                        <?php
                        $file_name = $documents['SCHOOL_FORM_9']['file_name'];
                        $file_data = $documents['SCHOOL_FORM_9']['file_data'];
                        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])):
                            $mime = 'image/' . ($ext == 'jpg' ? 'jpeg' : $ext);
                            $base64 = base64_encode($file_data);
                            ?>
                            <img src="data:<?php echo $mime; ?>;base64,<?php echo $base64; ?>" alt="Form 137" class="img-fluid">
                        <?php elseif ($ext == 'pdf'): ?>
                            <embed src="data:application/pdf;base64,<?php echo base64_encode($file_data); ?>"
                                type="application/pdf" width="100%" height="600px">
                        <?php else: ?>
                            <p>Unsupported file type.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>No document available.</p>
                    <?php endif; ?>
                </div>
            </div>
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

    <!-- Bootstrap core JavaScript-->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="../js/sb-admin-2.min.js"></script>

    <script>
        function sendToRegistrar() {
            if (confirm('Are you sure you want to send this enrollment to the registrar for final approval?')) {
                // Submit the form with all placeholders checked (simulating approval)
                $('#personal_info_check').prop('checked', true);
                $('#contact_info_check').prop('checked', true);
                $('#family_info_check').prop('checked', true);
                $('#special_needs_check').prop('checked', true);
                $('#educational_bg_check').prop('checked', true);
                $('#enrollment_details_check').prop('checked', true);
                $('#id_photo_check').prop('checked', true);
                $('#birth_cert_check').prop('checked', true);
                $('#medical_cert_check').prop('checked', true);
                $('#transcript_check').prop('checked', true);

                // Submit the form
                $('form').submit();
            }
        }
    </script>
</body>

</html>