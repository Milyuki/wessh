<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>WESSH - Login</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

</head>

<body class="bg-gradient-primary">

    <div class="container">

        <!-- Outer Row -->
        <div class="row justify-content-center">

            <div class="col-xl-10 col-lg-12 col-md-9">

                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <!-- Nested Row within Card Body -->
                        <div class="row">
                            <div class="col-lg-6 d-none d-lg-block bg-login-image"></div>
                            <div class="col-lg-6 col-12">
                                <div class="p-5">
                                    <?php if (isset($_GET['message']) && $_GET['message'] == 'pending'): ?>
                                        <div class="alert alert-info">
                                            <p>Your account has been created successfully. Please wait for admin approval to
                                                access the system.</p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($_GET['error'])): ?>
                                        <div class="alert alert-danger">
                                            <?php if ($_GET['error'] == 'facebook_not_configured'): ?>
                                                <p>Facebook login is not configured yet. Please contact the administrator to set
                                                    up Facebook OAuth credentials.</p>
                                            <?php elseif ($_GET['error'] == 'facebook_error'): ?>
                                                <p>Facebook login error:
                                                    <?php echo htmlspecialchars($_GET['message'] ?? 'Unknown error'); ?>
                                                </p>
                                            <?php elseif ($_GET['error'] == 'google_not_configured'): ?>
                                                <p>Google login is not configured yet. Please contact the administrator to set
                                                    up Google OAuth credentials.</p>
                                            <?php elseif ($_GET['error'] == 'google_error'): ?>
                                                <p>Google login error:
                                                    <?php echo htmlspecialchars($_GET['message'] ?? 'Unknown error'); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">Welcome Back!</h1>
                                    </div>
                                    <form class="user" method="POST" action="login_process.php">
                                        <input type="hidden" name="source" value="main">
                                        <div class="form-group">
                                            <input type="email" class="form-control form-control-user" id="email"
                                                name="email" aria-describedby="emailHelp"
                                                placeholder="Enter Email Address..." required>
                                        </div>
                                        <div class="form-group">
                                            <input type="password" class="form-control form-control-user" id="password"
                                                name="password" placeholder="Password" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-user btn-block">
                                            Login
                                        </button>
                                    </form>
                                    <hr>
                                    <a href="google_login.php" class="btn btn-google btn-user btn-block">
                                        <i class="fab fa-google fa-fw"></i> Login with Google
                                    </a>
                                    <a href="#" class="btn btn-facebook btn-user btn-block"
                                        onclick="showUnavailablePopup()">
                                        <i class="fab fa-facebook-f fa-fw"></i> Login with Facebook
                                    </a>
                                    <hr>
                                    <div class="text-center">
                                        <a class="small" href="forgot-password.html">Forgot Password?</a>
                                    </div>
                                    <div class="text-center">
                                        <a class="small" href="register.php">Create an Account!</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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

    <!-- Custom popup script -->
    <script>
        function showUnavailablePopup() {
            alert('This feature is not available for now. Please use email and password login.');
        }
    </script>

</body>

</html>