<?php
/********************************************************************
 * WESSH – Student Enrollment Form (100% COMPLETE)
 ********************************************************************/

ini_set('display_errors', 0);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE)
    session_start();

/* ---------- DEV AUTO-LOGIN (REMOVE IN PRODUCTION) ---------- */
$local_ips = ['127.0.0.1', '::1'];
if (in_array($_SERVER['REMOTE_ADDR'] ?? '', $local_ips, true)) {
    $_SESSION['user_id'] = $_SESSION['user_id'] ?? 15;
    $_SESSION['user_type'] = $_SESSION['user_type'] ?? 'student';
    $_SESSION['name'] = $_SESSION['name'] ?? 'Test Student';
}

/* --------------------- AUTH --------------------- */
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../login.php');
    exit;
}
$user_id = (int) $_SESSION['user_id'];
include '../db_connect.php';

/* ------------------- FETCH STUDENT ------------------- */
$student_id = null;
$display_name = 'Student';
$stmt = $conn->prepare(
    "SELECT s.student_id, CONCAT(s.first_name, ' ', s.last_name) AS full_name
     FROM students s JOIN users u ON s.email = u.email WHERE u.user_id = ?"
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $student_id = $row['student_id'];
    $display_name = $row['full_name'];
    $_SESSION['name'] = $display_name;
}
$stmt->close();
if (!$student_id)
    die("Student not found.");

/* ------------------------ CSRF ------------------------ */
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

/* ------------------- TRACK → STRAND MAP ------------------- */
$track_strands = [
    'ACADEMIC' => [
        'STEM' => 'SCIENCE, TECHNOLOGY, ENGINEERING, AND MATHEMATICS',
        'ABM' => 'ACCOUNTANCY, BUSINESS, AND MANAGEMENT',
        'HUMSS' => 'HUMANITIES AND SOCIAL SCIENCES'
    ],
    'TVL' => [
        'HE-COOKERY' => 'HOME ECONOMICS - COOKERY NCII',
        'HE-BREAD' => 'HOME ECONOMICS - BREAD AND PASTERY PRODUCTION NCII',
        'HE-FOOD' => 'HOME ECONOMICS - FOOD AND BEVERAGE SERVICES NCII'
    ],
    'INDUSTRIAL ARTS' => [
        'IA-CARPENTRY' => 'INDUSTRIAL ARTS - CARPENTRY NCII',
        'IA-EIM' => 'INDUSTRIAL ARTS - ELECTRICAL INSTALLATION AND MAINTENANCE NCII'
    ],
    'ICT' => [
        'ICT-CSS' => 'INFORMATION AND COMMUNICATION TECHNOLOGY - COMPUTER SYSTEM SERVICING NCII'
    ],
    'AGRICULTURAL AND FISHERY ARTS' => [
        'AFA-FOOD' => 'AGRICULTURAL AND FISHERY ART - FOOD PROCESSING NCII'
    ],
    'ARTS & DESIGN' => [
        'AD' => 'ARTS & DESIGN'
    ]
];

/* ------------------- DOCUMENT MAP ------------------- */
// Mapping of document keys to database enum values for documents table.
// Enum values: 'PSA_BIRTH_CERTIFICATE', 'CERTIFICATE_OF_COMPLETION', 'GOOD_MORAL', 'SCHOOL_FORM_9'
// Note: 2x2 ID is not saved to DB as per task requirements.
$required_docs = [
    'psa_birth_certificate' => ['label' => 'PSA Birth Certificate', 'type' => 'PSA_BIRTH_CERTIFICATE'],
    'good_moral' => ['label' => 'Good Moral Certificate', 'type' => 'GOOD_MORAL'],
    'form_137' => ['label' => 'Form 137', 'type' => 'SCHOOL_FORM_9'],
    'certificate_completion' => ['label' => 'Certificate of Completion', 'type' => 'CERTIFICATE_OF_COMPLETION']
];

/* ------------------- CHECK ENROLLMENT ------------------- */
$enrollment_exists = false;
$enrollment = null;
$stmt = $conn->prepare(
    "SELECT * FROM enrollments WHERE student_id = ? ORDER BY enrollment_id DESC LIMIT 1"
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($enrollment = $res->fetch_assoc()) {
    $enrollment_exists = true;
}
$stmt->close();

/* ------------------- DEFAULT VALUES ------------------- */
$defaults = [
    'school_year' => '',
    'grade_level' => '',
    'lrn' => '',
    'semester' => '',
    'track' => '',
    'strand' => '',
    'first_name' => '',
    'middle_name' => '',
    'last_name' => '',
    'extension_name' => '',
    'birth_date' => '',
    'age' => '',
    'sex' => '',
    'birth_place' => '',
    'religion' => '',
    'mother_tongue' => '',
    'ip_community' => '',
    'four_ps' => 'No',
    'four_ps_number' => '',
    'forgot_4ps_id' => '',
    'current_house_num' => '',
    'current_street' => '',
    'current_brgy' => '',
    'current_municipality' => '',
    'current_province' => '',
    'current_country' => 'Philippines',
    'current_zip' => '',
    'permanent_house_num' => '',
    'permanent_street' => '',
    'permanent_brgy' => '',
    'permanent_municipality' => '',
    'permanent_province' => '',
    'permanent_country' => 'Philippines',
    'permanent_zip' => '',
    'father_first_name' => '',
    'father_middle_name' => '',
    'father_last_name' => '',
    'father_contact' => '',
    'mother_first_name' => '',
    'mother_middle_name' => '',
    'mother_last_name' => '',
    'mother_contact' => '',
    'guardian_first_name' => '',
    'guardian_middle_name' => '',
    'guardian_last_name' => '',
    'guardian_contact' => '',
    'sped_needs' => 'No',
    'diagnosis' => '',
    'manifestations' => '',
    'returning_learner' => 'No',
    'last_school_attended' => '',
    'last_school_id' => '',
    'learning_modality' => '',
    'modality_online' => '',
    'modality_modular' => '',
    'modality_blended' => '',
    'modality_face_to_face' => ''
];

/* Load saved data */
if ($enrollment_exists) {
    foreach ($defaults as $k => $v) {
        $$k = $enrollment[$k] ?? $v;
    }
    $mods = ['online' => 'Online', 'modular' => 'Modular', 'blended' => 'Blended', 'face_to_face' => 'Face-to-Face'];
    foreach ($mods as $k => $label) {
        ${"modality_$k"} = strpos($learning_modality, $label) !== false ? '1' : '';
    }
} else {
    foreach ($defaults as $k => $v)
        $$k = $v;
}

/* ----------------------- PROCESS POST ---------------------- */
$errors = [];
if (!$enrollment_exists && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $errors[] = 'Security error. Please refresh and try again.';
    } else {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));

        $school_year = trim($_POST['school_year'] ?? '');
        $grade_level = trim($_POST['grade_level'] ?? '');
        $lrn = trim($_POST['lrn'] ?? '');
        $semester = trim($_POST['semester'] ?? '');
        $track = trim($_POST['track'] ?? '');
        $strand = trim($_POST['strand'] ?? '');

        $first_name = trim($_POST['first_name'] ?? '');
        $middle_name = trim($_POST['middle_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $extension_name = trim($_POST['extension_name'] ?? '');

        $birth_date = trim($_POST['birth_date'] ?? '');
        $age = (int) ($_POST['age'] ?? 0);
        $sex = trim($_POST['sex'] ?? '');
        $birth_place = trim($_POST['birth_place'] ?? '');
        $religion = trim($_POST['religion'] ?? '');
        $mother_tongue = trim($_POST['mother_tongue'] ?? '');
        $ip_community = trim($_POST['ip_community'] ?? '');

        $four_ps = (!empty($_POST['four_ps']) && $_POST['four_ps'] === 'Yes') ? 'Yes' : 'No';
        $forgot_4ps_id = !empty($_POST['forgot_4ps_id']) ? '1' : '';
        $four_ps_number = ($four_ps === 'Yes' && empty($forgot_4ps_id)) ? trim($_POST['four_ps_number'] ?? '') : '';

        $current_house_num = trim($_POST['current_house_num'] ?? '');
        $current_street = trim($_POST['current_street'] ?? '');
        $current_brgy = trim($_POST['current_brgy'] ?? '');
        $current_municipality = trim($_POST['current_municipality'] ?? '');
        $current_province = trim($_POST['current_province'] ?? '');
        $current_country = trim($_POST['current_country'] ?? '');
        $current_zip = trim($_POST['current_zip'] ?? '');

        $permanent_house_num = trim($_POST['permanent_house_num'] ?? '');
        $permanent_street = trim($_POST['permanent_street'] ?? '');
        $permanent_brgy = trim($_POST['permanent_brgy'] ?? '');
        $permanent_municipality = trim($_POST['permanent_municipality'] ?? '');
        $permanent_province = trim($_POST['permanent_province'] ?? '');
        $permanent_country = trim($_POST['permanent_country'] ?? '');
        $permanent_zip = trim($_POST['permanent_zip'] ?? '');

        $father_first_name = trim($_POST['father_first_name'] ?? '');
        $father_middle_name = trim($_POST['father_middle_name'] ?? '');
        $father_last_name = trim($_POST['father_last_name'] ?? '');
        $father_contact = trim($_POST['father_contact'] ?? '');

        $mother_first_name = trim($_POST['mother_first_name'] ?? '');
        $mother_middle_name = trim($_POST['mother_middle_name'] ?? '');
        $mother_last_name = trim($_POST['mother_last_name'] ?? '');
        $mother_contact = trim($_POST['mother_contact'] ?? '');

        $guardian_first_name = trim($_POST['guardian_first_name'] ?? '');
        $guardian_middle_name = trim($_POST['guardian_middle_name'] ?? '');
        $guardian_last_name = trim($_POST['guardian_last_name'] ?? '');
        $guardian_contact = trim($_POST['guardian_contact'] ?? '');

        $sped_needs = (!empty($_POST['sped_needs']) && $_POST['sped_needs'] === 'Yes') ? 'Yes' : 'No';
        $diagnosis = trim($_POST['diagnosis'] ?? '');
        $manifestations = trim($_POST['manifestations'] ?? '');

        $returning_learner = (!empty($_POST['returning_learner']) && $_POST['returning_learner'] === 'Yes') ? 'Yes' : 'No';
        $last_school_attended = trim($_POST['last_school_attended'] ?? '');
        $last_school_id = trim($_POST['last_school_id'] ?? '');

        $mods = ['online', 'modular', 'blended', 'face_to_face'];
        $options = [];
        foreach ($mods as $m) {
            $key = "modality_$m";
            if (!empty($_POST[$key])) {
                $label = ucfirst(str_replace('_', '-', $m));
                $options[] = $label;
                ${"modality_$m"} = '1';
            }
        }
        $learning_modality = implode(', ', $options);

        /* ----- VALIDATION ----- */
        $required = [
            $school_year,
            $grade_level,
            $first_name,
            $last_name,
            $birth_date,
            $sex,
            $birth_place,
            $current_brgy,
            $current_municipality,
            $current_province,
            $semester,
            $track,
            $strand,
            $learning_modality
        ];
        if (in_array('', $required, true))
            $errors[] = 'Please fill all required fields.';
        if ($age < 10 || $age > 25)
            $errors[] = 'Age must be between 10 and 25.';
        if (!in_array($sex, ['Male', 'Female']))
            $errors[] = 'Invalid sex.';
        if ($four_ps === 'Yes' && empty($four_ps_number) && empty($forgot_4ps_id))
            $errors[] = '4Ps ID required or check "Forgot ID".';

        /* ----- FILE VALIDATION ----- */
        $allowed = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        $max = 2 * 1024 * 1024;
        $uploaded = [];
        foreach ($required_docs as $k => $doc) {
            if (empty($_FILES[$k]) || $_FILES[$k]['error'] !== 0) {
                $errors[] = "Missing: {$doc['label']}";
                continue;
            }
            $f = $_FILES[$k];
            if (!in_array($f['type'], $allowed))
                $errors[] = "{$doc['label']}: Invalid file type.";
            if ($f['size'] > $max)
                $errors[] = "{$doc['label']}: File too large (max 2MB).";
            $uploaded[$k] = $f;
        }

        /* ----- INSERT INTO DB ----- */
        if (empty($errors)) {
            // Prepare concatenated fields for original schema
            $address = trim($current_house_num . ' ' . $current_street . ' ' . $current_brgy . ' ' . $current_municipality . ' ' . $current_province . ' ' . $current_country . ' ' . $current_zip);
            $father_name = trim($father_first_name . ' ' . $father_middle_name . ' ' . $father_last_name);
            $mother_name = trim($mother_first_name . ' ' . $mother_middle_name . ' ' . $mother_last_name);
            $guardian_name = trim($guardian_first_name . ' ' . $guardian_middle_name . ' ' . $guardian_last_name);

            $conn->autocommit(false);
            try {
                $sql = "INSERT INTO enrollments (
                    student_id, school_year, grade_level, lrn, first_name, middle_name, last_name,
                    birth_date, age, sex, birth_place, religion, mother_tongue, ip_community, four_ps, address,
                    father_name, mother_name, guardian_name, guardian_contact,
                    sped_needs, diagnosis, manifestations,
                    returning_learner, last_school_attended, last_school_id,
                    semester, track, strand, learning_modality, status
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, ?, ?
                )";

                $stmt = $conn->prepare($sql);
                $status = 'Pending';

                $stmt->bind_param(
                    'issssssssssssssssssssssssssssss',
                    $student_id,
                    $school_year,
                    $grade_level,
                    $lrn,
                    $first_name,
                    $middle_name,
                    $last_name,
                    $birth_date,
                    $age,
                    $sex,
                    $birth_place,
                    $religion,
                    $mother_tongue,
                    $ip_community,
                    $four_ps,
                    $address,
                    $father_name,
                    $mother_name,
                    $guardian_name,
                    $guardian_contact,
                    $sped_needs,
                    $diagnosis,
                    $manifestations,
                    $returning_learner,
                    $last_school_attended,
                    $last_school_id,
                    $semester,
                    $track,
                    $strand,
                    $learning_modality,
                    $status
                );

                $stmt->execute();
                $enrollment_id = $conn->insert_id;
                $stmt->close();

                /* Upload files */
                $dir = "uploads/documents/{$student_id}/";
                if (!is_dir($dir))
                    mkdir($dir, 0755, true);

                $doc_sql = "INSERT INTO documents (enrollment_id, student_id, document_type, file_name, file_path, upload_date, status, is_placeholder)
                            VALUES (?, ?, ?, ?, ?, NOW(), 'PENDING', 0)";
                $doc_stmt = $conn->prepare($doc_sql);

                foreach ($uploaded as $k => $f) {
                    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                    $name = $required_docs[$k]['label'] . "_{$enrollment_id}.{$ext}";
                    $path = $dir . $name;
                    move_uploaded_file($f['tmp_name'], $path);

                    $doc_type = $required_docs[$k]['type'];
                    $doc_stmt->bind_param('iisss', $enrollment_id, $student_id, $doc_type, $name, $path);
                    $doc_stmt->execute();
                }
                $doc_stmt->close();

                $conn->commit();
                header("Location: enroll.php");
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $errors[] = 'Database Error: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WESSH - Enrollment</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        .readonly-input {
            background: #f8f9fa;
            cursor: not-allowed;
        }

        .form-check-input:disabled~.form-check-label {
            opacity: 0.7;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include 'student_sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">

                <!-- TOPBAR -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fas fa-bars"></i>
                    </button>
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?= htmlspecialchars($display_name) ?>
                                </span>
                                <img class="img-profile rounded-circle" src="../img/undraw_profile.svg">
                            </a>
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
                <!-- END TOPBAR -->

                <div class="container-fluid">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 bg-primary">
                            <h4 class="m-0 font-weight-bold text-white">Enrollment Form</h4>
                        </div>
                        <div class="card-body">

                            <?php if ($enrollment_exists): ?>
                                <div class="alert alert-success mb-4">
                                    Enrollment complete. <a href="dashboard.php" class="alert-link">Go to Dashboard</a>
                                </div>
                            <?php endif; ?>

                            <?php if ($errors): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $e): ?>
                                            <li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <form method="POST" enctype="multipart/form-data" id="enrollmentForm">
                                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">

                                <!-- ACADEMIC DETAILS -->
                                <h5 class="mt-4 text-primary">Academic Details</h5>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label>School Year <span class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                            name="school_year" value="<?= htmlspecialchars($school_year) ?>"
                                            placeholder="2025-2026" <?= $enrollment_exists ? 'readonly' : '' ?> required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label>Grade Level <span class="text-danger">*</span></label>
                                        <select class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                            name="grade_level" <?= $enrollment_exists ? 'disabled' : '' ?> required>
                                            <option value="" disabled <?= empty($grade_level) ? 'selected' : '' ?>>Select
                                            </option>
                                            <option value="Grade 11" <?= $grade_level === 'Grade 11' ? 'selected' : '' ?>>Grade
                                                11</option>
                                            <option value="Grade 12" <?= $grade_level === 'Grade 12' ? 'selected' : '' ?>>Grade
                                                12</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-3 mt-2">
                                    <div class="col-12 col-md-6">
                                        <label>LRN</label>
                                        <input type="text"
                                            class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                            name="lrn" value="<?= htmlspecialchars($lrn) ?>"
                                            <?= $enrollment_exists ? 'readonly' : '' ?>>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label>Semester <span class="text-danger">*</span></label>
                                        <select class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                            name="semester" <?= $enrollment_exists ? 'disabled' : '' ?> required>
                                            <option value="" disabled <?= empty($semester) ? 'selected' : '' ?>>Select
                                            </option>
                                            <option value="1st" <?= $semester === '1st' ? 'selected' : '' ?>>1st</option>
                                            <option value="2nd" <?= $semester === '2nd' ? 'selected' : '' ?>>2nd</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-3 mt-2">
                                    <div class="col-12 col-md-6">
                                        <label>Track <span class="text-danger">*</span></label>
                                        <select class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                            name="track" id="track" <?= $enrollment_exists ? 'disabled' : '' ?> required>
                                            <option value="" disabled <?= empty($track) ? 'selected' : '' ?>>Select Track
                                            </option>
                                            <?php foreach (array_keys($track_strands) as $t): ?>
                                                <option value="<?= htmlspecialchars($t) ?>" <?= $track === $t ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($t) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label>Strand <span class="text-danger">*</span></label>
                                        <select class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                            name="strand" id="strand" <?= $enrollment_exists ? 'disabled' : '' ?> required>
                                            <option value="" disabled selected>Select Strand</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- PERSONAL INFORMATION -->
                                <h5 class="mt-4 text-primary">Personal Information</h5>
                                <div class="row g-3">
                                    <div class="col-12 col-md-3">
                                        <label>First Name <span class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                            name="first_name" value="<?= htmlspecialchars($first_name) ?>"
                                            <?= $enrollment_exists ? 'readonly' : '' ?> required>
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <label>Middle Name</label>
                                        <input type="text"
                                            class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                            name="middle_name" value="<?= htmlspecialchars($middle_name) ?>"
                                            <?= $enrollment_exists ? 'readonly' : '' ?>>
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <label>Last Name <span class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                            name="last_name" value="<?= htmlspecialchars($last_name) ?>"
                                            <?= $enrollment_exists ? 'readonly' : '' ?> required>
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <label>Extension Name</label>
                                        <input type="text"
                                            class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                            name="extension_name" value="<?= htmlspecialchars($extension_name) ?>"
                                            <?= $enrollment_exists ? 'readonly' : '' ?>>
                                    </div>
                                </div>
                                <div class="row g-3 mt-2">
                                    <div class="col-12 col-md-4">
                                        <label>Birth Date <span class="text-danger">*</span></label>
                                        <input type="date"
                                            class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                            id="birth_date" name="birth_date" value="<?= htmlspecialchars($birth_date) ?>"
                                            <?= $enrollment_exists ? 'readonly' : '' ?> required>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label>Age <span class="text-danger">*</span></label>
                                        <input type="number"
                                            class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>" id="age"
                                            name="age" min="10" max="25" value="<?= $age ?>"
                                            <?= $enrollment_exists ? 'readonly' : '' ?> required>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label>Sex <span class="text-danger">*</span></label>
                                        <select class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                            name="sex" <?= $enrollment_exists ? 'disabled' : '' ?> required>
                                            <option value="" disabled <?= empty($sex) ? 'selected' : '' ?>>Select</option>
                                            <option value="Male" <?= $sex === 'Male' ? 'selected' : '' ?>>Male</option>
                                            <option value="Female" <?= $sex === 'Female' ? 'selected' : '' ?>>Female</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-3 mt-2">
                                    <div class="col-12 col-md-6">
                                        <label>Birth Place <span class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                            name="birth_place" value="<?= htmlspecialchars($birth_place) ?>"
                                            <?= $enrollment_exists ? 'readonly' : '' ?> required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label>Religion</label>
                                        <input type="text"
                                            class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                            name="religion" value="<?= htmlspecialchars($religion) ?>"
                                            <?= $enrollment_exists ? 'readonly' : '' ?>>
                                    </div>
                                </div>
                                <div class="row g-3 mt-2">
                                    <div class="col-12 col-md-6">
                                        <label>Mother Tongue</label>
                                        <input type="text"
                                            class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                            name="mother_tongue" value="<?= htmlspecialchars($mother_tongue) ?>"
                                            <?= $enrollment_exists ? 'readonly' : '' ?>>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label>Indigenous Community</label>
                                        <input type="text"
                                            class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                            name="ip_community" value="<?= htmlspecialchars($ip_community) ?>"
                                            <?= $enrollment_exists ? 'readonly' : '' ?>>
                                    </div>
                                </div>

                                <!-- 4PS -->
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="four_ps" name="four_ps"
                                                value="Yes" <?= $four_ps === 'Yes' ? 'checked' : '' ?>
                                                <?= $enrollment_exists ? 'disabled' : '' ?>>
                                            <label class="form-check-label" for="four_ps">4Ps Beneficiary</label>
                                        </div>
                                        <div class="mt-2" id="four_ps_container"
                                            style="<?= $four_ps === 'Yes' ? '' : 'display:none;' ?>">
                                            <label>4Ps Household ID Number <span id="four_ps_required"
                                                    class="text-danger"
                                                    style="<?= $four_ps === 'Yes' && empty($forgot_4ps_id) ? '' : 'display:none;' ?>">*</span></label>
                                            <input type="text"
                                                class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                id="four_ps_number" name="four_ps_number"
                                                value="<?= htmlspecialchars($four_ps_number) ?>" <?= $enrollment_exists ? 'readonly' : '' ?>>
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="checkbox" id="forgot_4ps_id"
                                                    name="forgot_4ps_id" value="1" <?= $forgot_4ps_id === '1' ? 'checked' : '' ?> <?= $enrollment_exists ? 'disabled' : '' ?>>
                                                <label class="form-check-label" for="forgot_4ps_id">Forgot ID
                                                    Number</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ADDRESS -->
                                <h5 class="mt-4 text-primary">Address Information</h5>
                                <div class="ms-3">
                                    <h6 class="text-success mb-3">Current Address</h6>
                                    <div class="row g-3" id="current_addr">
                                        <div class="col-12 col-md-3">
                                            <label>House No.</label>
                                            <input type="text"
                                                class="form-control cur-field <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                name="current_house_num" id="cur_house"
                                                value="<?= htmlspecialchars($current_house_num) ?>"
                                                <?= $enrollment_exists ? 'readonly' : '' ?>>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label>Street</label>
                                            <input type="text"
                                                class="form-control cur-field <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                name="current_street" id="cur_street"
                                                value="<?= htmlspecialchars($current_street) ?>" <?= $enrollment_exists ? 'readonly' : '' ?>>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label>Barangay <span class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control cur-field <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                name="current_brgy" id="cur_brgy"
                                                value="<?= htmlspecialchars($current_brgy) ?>" <?= $enrollment_exists ? 'readonly' : '' ?> required>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label>Municipality <span class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control cur-field <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                name="current_municipality" id="cur_mun"
                                                value="<?= htmlspecialchars($current_municipality) ?>"
                                                <?= $enrollment_exists ? 'readonly' : '' ?> required>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label>Province <span class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control cur-field <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                name="current_province" id="cur_prov"
                                                value="<?= htmlspecialchars($current_province) ?>" <?= $enrollment_exists ? 'readonly' : '' ?> required>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label>Country <span class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control cur-field <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                name="current_country" id="cur_country"
                                                value="<?= htmlspecialchars($current_country ?: 'Philippines') ?>"
                                                <?= $enrollment_exists ? 'readonly' : '' ?> required>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label>ZIP Code <span class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control cur-field <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                name="current_zip" id="cur_zip"
                                                value="<?= htmlspecialchars($current_zip) ?>" <?= $enrollment_exists ? 'readonly' : '' ?> required>
                                        </div>
                                    </div>
                                    <div class="form-check mt-3">
                                        <input class="form-check-input" type="checkbox" id="same_as_current"
                                            <?= $enrollment_exists ? 'disabled' : '' ?>>
                                        <label class="form-check-label" for="same_as_current">Permanent address is the
                                            same as current address</label>
                                    </div>
                                    <h6 class="text-success mb-3 mt-4">Permanent Address</h6>
                                    <div class="row g-3" id="perm_addr">
                                        <div class="col-12 col-md-3">
                                            <label>House No.</label>
                                            <input type="text"
                                                class="form-control perm-field <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                name="permanent_house_num" id="perm_house"
                                                value="<?= htmlspecialchars($permanent_house_num) ?>"
                                                <?= $enrollment_exists ? 'readonly' : '' ?>>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label>Street</label>
                                            <input type="text"
                                                class="form-control perm-field <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                name="permanent_street" id="perm_street"
                                                value="<?= htmlspecialchars($permanent_street) ?>" <?= $enrollment_exists ? 'readonly' : '' ?>>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label>Barangay <span class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control perm-field <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                name="permanent_brgy" id="perm_brgy"
                                                value="<?= htmlspecialchars($permanent_brgy) ?>" <?= $enrollment_exists ? 'readonly' : '' ?> required>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label>Municipality <span class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control perm-field <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                name="permanent_municipality" id="perm_mun"
                                                value="<?= htmlspecialchars($permanent_municipality) ?>"
                                                <?= $enrollment_exists ? 'readonly' : '' ?> required>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label>Province <span class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control perm-field <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                name="permanent_province" id="perm_prov"
                                                value="<?= htmlspecialchars($permanent_province) ?>"
                                                <?= $enrollment_exists ? 'readonly' : '' ?> required>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label>Country <span class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control perm-field <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                name="permanent_country" id="perm_country"
                                                value="<?= htmlspecialchars($permanent_country ?: 'Philippines') ?>"
                                                <?= $enrollment_exists ? 'readonly' : '' ?> required>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label>ZIP Code <span class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control perm-field <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                name="permanent_zip" id="perm_zip"
                                                value="<?= htmlspecialchars($permanent_zip) ?>" <?= $enrollment_exists ? 'readonly' : '' ?> required>
                                        </div>
                                    </div>
                                </div>

                                <!-- GUARDIAN INFO -->
                                <h5 class="mt-4 text-primary">Legal Guardian Information</h5>
                                <div class="ms-3">
                                    <h6 class="text-success mb-3">Father</h6>
                                    <div class="row g-3">
                                        <div class="col-12 col-md-3">
                                            <label>First Name</label>
                                            <input type="text"
                                                class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                name="father_first_name"
                                                value="<?= htmlspecialchars($father_first_name) ?>"
                                                <?= $enrollment_exists ? 'readonly' : '' ?>>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label>Middle Name</label>
                                            <input type="text"
                                                class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                name="father_middle_name"
                                                value="<?= htmlspecialchars($father_middle_name) ?>"
                                                <?= $enrollment_exists ? 'readonly' : '' ?>>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label>Last Name</label>
                                            <input type="text"
                                                class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                name="father_last_name"
                                                value="<?= htmlspecialchars($father_last_name) ?>" <?= $enrollment_exists ? 'readonly' : '' ?>>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label>Contact</label>
                                            <input type="text"
                                                class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                name="father_contact" value="<?= htmlspecialchars($father_contact) ?>"
                                                <?= $enrollment_exists ? 'readonly' : '' ?>>
                                        </div>
                                    </div>
                                    <h6 class="text-success mb-3 mt-4">Mother</h6>
                                    <div class="row g-3">
                                        <div class="col-12 col-md-3">
                                            <label>First Name</label>
                                            <input type="text"
                                                class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                name="mother_first_name"
                                                value="<?= htmlspecialchars($mother_first_name) ?>"
                                                <?= $enrollment_exists ? 'readonly' : '' ?>>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label>Middle Name</label>
                                            <input type="text"
                                                class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                name="mother_middle_name"
                                                value="<?= htmlspecialchars($mother_middle_name) ?>"
                                                <?= $enrollment_exists ? 'readonly' : '' ?>>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label>Last Name</label>
                                            <input type="text"
                                                class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                name="mother_last_name"
                                                value="<?= htmlspecialchars($mother_last_name) ?>" <?= $enrollment_exists ? 'readonly' : '' ?>>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label>Contact</label>
                                            <input type="text"
                                                class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                name="mother_contact" value="<?= htmlspecialchars($mother_contact) ?>"
                                                <?= $enrollment_exists ? 'readonly' : '' ?>>
                                        </div>
                                    </div>
                                    <h6 class="text-success mb-3 mt-4">Guardian</h6>
                                    <div class="row g-3">
                                        <div class="col-12 col-md-3">
                                            <label>First Name</label>
                                            <input type="text"
                                                class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                name="guardian_first_name"
                                                value="<?= htmlspecialchars($guardian_first_name) ?>"
                                                <?= $enrollment_exists ? 'readonly' : '' ?>>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label>Middle Name</label>
                                            <input type="text"
                                                class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                name="guardian_middle_name"
                                                value="<?= htmlspecialchars($guardian_middle_name) ?>"
                                                <?= $enrollment_exists ? 'readonly' : '' ?>>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label>Last Name</label>
                                            <input type="text"
                                                class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                name="guardian_last_name"
                                                value="<?= htmlspecialchars($guardian_last_name) ?>"
                                                <?= $enrollment_exists ? 'readonly' : '' ?>>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label>Contact</label>
                                            <input type="text"
                                                class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                name="guardian_contact"
                                                value="<?= htmlspecialchars($guardian_contact) ?>" <?= $enrollment_exists ? 'readonly' : '' ?>>
                                        </div>
                                    </div>
                                </div>

                                <!-- SPED & RETURNING -->
                                <h5 class="mt-4 text-primary">Special Needs & Previous School</h5>
                                <div class="ms-3">
                                    <div class="row mt-3 align-items-start">
                                        <div class="col-md-6">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" id="sped_needs"
                                                    name="sped_needs" value="Yes" <?= $sped_needs === 'Yes' ? 'checked' : '' ?> <?= $enrollment_exists ? 'disabled' : '' ?>>
                                                <label class="form-check-label fw-bold" for="sped_needs">SPED
                                                    Needs</label>
                                            </div>
                                            <div id="diagnosis_container"
                                                style="<?= $sped_needs === 'Yes' ? '' : 'display:none;' ?>">
                                                <label>Diagnosis</label>
                                                <input type="text"
                                                    class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                    id="diagnosis" name="diagnosis"
                                                    value="<?= htmlspecialchars($diagnosis) ?>" <?= $enrollment_exists ? 'readonly' : '' ?>>
                                            </div>
                                            <div id="manifestations_container"
                                                style="<?= $sped_needs === 'Yes' ? '' : 'display:none;' ?>"
                                                class="mt-2">
                                                <label>Manifestations</label>
                                                <input type="text"
                                                    class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                    id="manifestations" name="manifestations"
                                                    value="<?= htmlspecialchars($manifestations) ?>"
                                                    <?= $enrollment_exists ? 'readonly' : '' ?>>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" id="returning_learner"
                                                    name="returning_learner" value="Yes" <?= $returning_learner === 'Yes' ? 'checked' : '' ?> <?= $enrollment_exists ? 'disabled' : '' ?>>
                                                <label class="form-check-label fw-bold"
                                                    for="returning_learner">Returning Learner</label>
                                            </div>
                                            <div id="last_school_container"
                                                style="<?= $returning_learner === 'Yes' ? '' : 'display:none;' ?>">
                                                <label>Last School Attended</label>
                                                <input type="text"
                                                    class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                    id="last_school_attended" name="last_school_attended"
                                                    value="<?= htmlspecialchars($last_school_attended) ?>"
                                                    <?= $enrollment_exists ? 'readonly' : '' ?>>
                                            </div>
                                            <div id="last_school_id_container"
                                                style="<?= $returning_learner === 'Yes' ? '' : 'display:none;' ?>"
                                                class="mt-2">
                                                <label>Last School ID</label>
                                                <input type="text"
                                                    class="form-control <?= $enrollment_exists ? 'readonly-input' : '' ?>"
                                                    id="last_school_id" name="last_school_id"
                                                    value="<?= htmlspecialchars($last_school_id) ?>"
                                                    <?= $enrollment_exists ? 'readonly' : '' ?>>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- LEARNING MODALITY -->
                                <h5 class="mt-4 text-primary">Learning Modality <span class="text-danger">*</span></h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="modality_online"
                                                name="modality_online" value="1" <?= $modality_online ? 'checked' : '' ?>
                                                <?= $enrollment_exists ? 'disabled' : '' ?>>
                                            <label class="form-check-label" for="modality_online">Online</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="modality_modular"
                                                name="modality_modular" value="1" <?= $modality_modular ? 'checked' : '' ?> <?= $enrollment_exists ? 'disabled' : '' ?>>
                                            <label class="form-check-label" for="modality_modular">Modular</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="modality_blended"
                                                name="modality_blended" value="1" <?= $modality_blended ? 'checked' : '' ?> <?= $enrollment_exists ? 'disabled' : '' ?>>
                                            <label class="form-check-label" for="modality_blended">Blended</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="modality_face_to_face"
                                                name="modality_face_to_face" value="1" <?= $modality_face_to_face ? 'checked' : '' ?> <?= $enrollment_exists ? 'disabled' : '' ?>>
                                            <label class="form-check-label"
                                                for="modality_face_to_face">Face-to-Face</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- DOCUMENTS -->
                                <h5 class="mt-4 text-primary">Required Documents <span class="text-danger">*</span></h5>
                                <div class="row g-3">
                                    <?php foreach ($required_docs as $k => $doc): ?>
                                        <div class="col-12 col-md-6">
                                            <label><?= $doc['label'] ?></label>
                                            <?php if ($enrollment_exists):
                                                $pattern = "uploads/documents/{$student_id}/" . preg_quote($doc['label'], '/') . "_{$enrollment['enrollment_id']}.*";
                                                $files = glob($pattern);
                                                if ($files): ?>
                                                    <div class="text-success">Uploaded</div>
                                                <?php else: ?>
                                                    <div class="text-muted">Not found</div>
                                                <?php endif;
                                            else: ?>
                                                <input type="file" class="form-control" name="<?= $k ?>"
                                                    accept=".pdf,.jpg,.jpeg,.png" required>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- SUBMIT -->
                                <div class="mt-4" id="submitSection">
                                    <?php if (!$enrollment_exists): ?>
                                        <button type="submit" id="submitBtn" class="btn btn-primary btn-lg">Submit
                                            Enrollment</button>
                                        <div id="submitStatus" class="mt-3"></div>
                                    <?php else: ?>
                                        <a href="dashboard.php" class="btn btn-success btn-lg">Go to Dashboard</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../includes/footer.php'; ?>
        </div>
    </div>

    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../js/sb-admin-2.min.js"></script>
    <script>
        // Track → Strand
        document.addEventListener('DOMContentLoaded', function () {
            const track = document.getElementById('track');
            const strand = document.getElementById('strand');
            const saved = <?= json_encode($strand) ?>;
            const map = <?= json_encode($track_strands) ?>;

            function update() {
                strand.innerHTML = '<option value="" disabled selected>Select Strand</option>';
                if (track.value && map[track.value]) {
                    Object.keys(map[track.value]).forEach(code => {
                        const opt = new Option(map[track.value][code], code, false, code === saved);
                        strand.add(opt);
                    });
                }
                strand.disabled = <?= $enrollment_exists ? 'true' : 'false' ?>;
            }
            track && track.addEventListener('change', update);
            track && track.value && update();
        });

        // Age from birth date
        document.getElementById('birth_date')?.addEventListener('change', function () {
            const b = new Date(this.value), t = new Date();
            let a = t.getFullYear() - b.getFullYear();
            if (t.getMonth() < b.getMonth() || (t.getMonth() === b.getMonth() && t.getDate() < b.getDate())) a--;
            document.getElementById('age').value = a > 0 ? a : '';
        });

        // 4Ps
        const fourPsChk = document.getElementById('four_ps');
        const fourPsCont = document.getElementById('four_ps_container');
        const fourPsNum = document.getElementById('four_ps_number');
        const fourPsStar = document.getElementById('four_ps_required');
        const forgotChk = document.getElementById('forgot_4ps_id');
        function toggle4Ps() {
            fourPsCont.style.display = fourPsChk.checked ? 'block' : 'none';
            toggleForgot();
        }
        function toggleForgot() {
            const req = fourPsChk.checked && !forgotChk.checked;
            fourPsNum.required = req;
            fourPsStar.style.display = req ? '' : 'none';
        }
        fourPsChk && fourPsChk.addEventListener('change', toggle4Ps);
        forgotChk && forgotChk.addEventListener('change', toggleForgot);
        toggle4Ps();

        // Address sync - FINAL: Sync + Disable Permanent Fields When Checked
        const sameChk = document.getElementById('same_as_current');
        const curFields = document.querySelectorAll('.cur-field');
        const permFields = document.querySelectorAll('.perm-field');
        const map = {
            cur_house: 'perm_house',
            cur_street: 'perm_street',
            cur_brgy: 'perm_brgy',
            cur_mun: 'perm_mun',
            cur_prov: 'perm_prov',
            cur_country: 'perm_country',
            cur_zip: 'perm_zip'
        };

        function copyAndLock() {
            if (!sameChk.checked) return;
            curFields.forEach(f => {
                const target = document.getElementById(map[f.id]);
                if (target) {
                    target.value = f.value;
                    target.disabled = true;
                    target.classList.add('readonly-input');
                }
            });
        }

        function unlockPermanent() {
            permFields.forEach(f => {
                f.disabled = false;
                f.classList.remove('readonly-input');
            });
        }

        function isSameAsCurrent() {
            for (const c in map) {
                const cf = document.getElementById(c);
                const pf = document.getElementById(map[c]);
                if (cf && pf && cf.value !== pf.value) return false;
            }
            return true;
        }

        if (isSameAsCurrent()) {
            sameChk.checked = true;
            copyAndLock();
        } else {
            unlockPermanent();
        }

        sameChk?.addEventListener('change', function () {
            if (this.checked) copyAndLock();
            else unlockPermanent();
        });

        curFields.forEach(f => {
            f.addEventListener('input', () => {
                if (sameChk.checked) {
                    const target = document.getElementById(map[f.id]);
                    if (target) target.value = f.value;
                }
            });
        });

        // SPED & Returning
        ['sped_needs', 'returning_learner'].forEach(id => {
            const chk = document.getElementById(id);
            if (chk) chk.addEventListener('change', () => {
                const show = chk.checked;
                const conts = id === 'sped_needs'
                    ? ['diagnosis_container', 'manifestations_container']
                    : ['last_school_container', 'last_school_id_container'];
                conts.forEach(c => {
                    const el = document.getElementById(c);
                    if (el) el.style.display = show ? 'block' : 'none';
                });
            });
            if (chk && chk.checked) chk.dispatchEvent(new Event('change'));
        });

        // Modality validation
        document.getElementById('enrollmentForm').addEventListener('submit', e => {
            if (!document.querySelectorAll('input[name^="modality_"]:checked').length) {
                e.preventDefault();
                alert('Select at least one learning modality.');
            }
        });

        // Submit lock
        const form = document.getElementById('enrollmentForm');
        const submitBtn = document.getElementById('submitBtn');
        const statusDiv = document.getElementById('submitStatus');
        if (submitBtn) {
            let submitting = false;
            form.addEventListener('submit', function (e) {
                if (submitting) { e.preventDefault(); return; }
                submitting = true;
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Submitting...';
                statusDiv.innerHTML = '<div class="alert alert-info mt-3">Please wait while we process your enrollment...</div>';
            });
        }
    </script>
</body>

</html>
<?php if (isset($conn))
    $conn->close(); ?>