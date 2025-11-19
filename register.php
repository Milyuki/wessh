<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);  // Enable error reporting for debugging

session_start();
include 'includes/db.php';  // Ensure this file exists in the correct path

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = trim($_POST['password'] ?? '');
    $repeat_password = trim($_POST['repeat_password'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $middle_name = trim($_POST['middlename'] ?? '');
    $extension_name = trim($_POST['extensionname'] ?? '');
    $age = intval($_POST['age'] ?? 0);
    $gender = trim($_POST['gender'] ?? 'Select Gender');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $user_type = 'student';  // Hardcoded as per our earlier discussion

    // Validation
    $errors = [];
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }
    if ($password !== $repeat_password) {
        $errors[] = "Passwords do not match.";
    }
    if (empty($email) || empty($first_name) || empty($last_name) || empty($age) || empty($gender) || empty($contact_number)) {
        $errors[] = "All required fields must be filled.";
    }
    // Add email format validation for better security
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    // Validate contact number format (basic check for digits and length)
    if (!preg_match('/^\d{10,15}$/', $contact_number)) {
        $errors[] = "Contact number must be 10-15 digits.";
    }
    // Validate age range
    if ($age < 18 || $age > 25) {
        $errors[] = "Age must be between 18 and 25.";
    }

    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Start transaction
            $pdo->beginTransaction();

            // Insert into users table first
            $stmt_user = $pdo->prepare("INSERT INTO users (password, email, user_type, firstname, lastname, status) VALUES (:password, :email, 'student', :firstname, :lastname, 'pending')");
            $stmt_user->execute([
                ':password' => $hashed_password,
                ':email' => $email,
                ':firstname' => $first_name,
                ':lastname' => $last_name
            ]);

            // Get the generated user_id
            $user_id = $pdo->lastInsertId();

            // Insert into students table with student_id = user_id
            $stmt_student = $pdo->prepare("INSERT INTO students (student_id, email, password, contact_number, first_name, middle_name, last_name, extension_name, age, gender, date_registered) VALUES (:student_id, :email, :password, :contact_number, :first_name, :middle_name, :last_name, :extension_name, :age, :gender, NOW())");
            $stmt_student->execute([
                ':student_id' => $user_id,
                ':email' => $email,
                ':password' => $hashed_password,
                ':contact_number' => $contact_number,
                ':first_name' => $first_name,
                ':middle_name' => $middle_name,
                ':last_name' => $last_name,
                ':extension_name' => $extension_name,
                ':age' => $age,
                ':gender' => $gender
            ]);

            // Commit transaction
            $pdo->commit();

            // Redirect to login on success with pending message
            header("Location: login.php?message=pending");
            exit;
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            if ($e->getCode() == 23000) {  // Duplicate entry error
                $errors[] = "Email already exists.";
            } else {
                $errors[] = "Error: " . $e->getMessage();
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

    <title>WESSH - Register</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet"> <!-- Use relative path -->
</head>

<body class="bg-gradient-primary d-flex justify-content-center align-items-center min-vh-100">

    <div class="container">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="card o-hidden border-0 shadow-lg">
            <div class="card-body p-5">
                <div class="text-center">
                    <h1 class="h4 text-gray-900 mb-4">Create a Student Account for WESSH!</h1>
                </div>
                <form class="user" method="POST" action="register.php">
                    <div class="form-group row">
                        <div class="col-sm-6 mb-3 mb-sm-0">
                            <input type="text" class="form-control form-control-user" id="first_name" name="first_name"
                                placeholder="First Name" required>
                        </div>
                        <div class="col-sm-6">
                            <input type="text" class="form-control form-control-user" id="last_name" name="last_name"
                                placeholder="Last Name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <input type="text" class="form-control form-control-user" id="middle_name" name="middle_name"
                            placeholder="Middle Name">
                    </div>
                    <div class="form-group">
                        <input type="text" class="form-control form-control-user" id="extension_name"
                            name="extension_name" placeholder="Extension Name">
                    </div>
                    <div class="form-group">
                        <input type="email" class="form-control form-control-user" id="email" name="email"
                            placeholder="Email Address" required>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-6 mb-3 mb-sm-0">
                            <input type="password" class="form-control form-control-user" id="password" name="password"
                                placeholder="Password (min 8 characters)" required>
                        </div>
                        <div class="col-sm-6">
                            <input type="password" class="form-control form-control-user" id="repeat_password"
                                name="repeat_password" placeholder="Repeat Password">
                        </div>
                    </div>
                    <div class="form-group">
                        <input type="number" class="form-control form-control-user" id="age" name="age"
                            placeholder="Age" required>
                    </div>
                    <div class="form-group">
                        <select class="form-control form-control-user" id="gender" name="gender"
                            placeholder="Select Gender" required>
                            <option value="" disabled <?= empty($sex) ? 'selected' : '' ?>>Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <input type="text" class="form-control form-control-user" id="contact_number"
                            name="contact_number" placeholder="Contact Number" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-user btn-block">Register Account</button>
                    <hr>
                </form>
                <div class="text-center">
                    <a class="small" href="login.php">Already have an account? Login!</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <!-- Custom validation script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('.user');
            const password = document.getElementById('password');
            const repeatPassword = document.getElementById('repeat_password');

            form.addEventListener('submit', function (e) {
                if (password.value !== repeatPassword.value) {
                    e.preventDefault();
                    alert('Passwords do not match.');
                    return false;
                }
                if (password.value.length < 8) {
                    e.preventDefault();
                    alert('Password must be at least 8 characters.');
                    return false;
                }
            });
        });
    </script>

</body>

</html>