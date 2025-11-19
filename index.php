<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WESSH - Student Enrollment System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Using Bootstrap for now; replace with your ADMIN-02 version later -->
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Web-Based Enrollment System for Senior High School</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Register</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero py-5"
        style="background-image: url('img/school.jpg'); background-size: cover; background-position: center;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-12 text-center">
                    <div class="d-flex justify-content-center mb-4 flex-wrap">
                        <img src="img/official logo.png" alt="WESSH Logo" class="img-fluid me-3 mb-2"
                            style="max-width: 250px; height: auto;">
                        <img src="img/looc logo.png" alt="School Logo" class="img-fluid mb-2"
                            style="max-width: 150px; height: auto;">
                    </div>
                    <style>
                        @media (max-width: 768px) {
                            .hero img {
                                max-width: 120px !important;
                            }

                            .hero h1 {
                                font-size: 2rem !important;
                            }

                            .hero .lead {
                                font-size: 1rem !important;
                            }
                        }
                    </style>
                    <h1 class="display-4 fw-bold text-primary">Welcome to WESSH</h1>
                    <p class="lead text-white">The ultimate Student Enrollment System for seamless registration,
                        management, and
                        communication between students, teachers, and administrators.</p>
                    <p class="nextlead text-white">Streamline your enrollment process with our user-friendly platform
                        designed
                        for efficiency and
                        ease.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features py-5">
        <div class="container">
            <h2 class="text-center mb-5">Why Choose WESSH?</h2>
            <div class="row">
                <div class="col-12 col-md-4 text-center mb-4">
                    <i class="bi bi-person-check-fill fs-1 text-primary mb-3"></i>
                    <h5>Easy Enrollment</h5>
                    <p>Register and manage student enrollments with a simple, intuitive interface.</p>
                </div>
                <div class="col-12 col-md-4 text-center mb-4">
                    <i class="bi bi-bell-fill fs-1 text-primary mb-3"></i>
                    <h5>Real-time Notifications</h5>
                    <p>Stay updated with instant notifications for schedules, updates, and important announcements.</p>
                </div>
                <div class="col-12 col-md-4 text-center mb-4">
                    <i class="bi bi-bar-chart-line-fill fs-1 text-primary mb-3"></i>
                    <h5>Comprehensive Dashboards</h5>
                    <p>Access detailed dashboards for students, teachers, and administrators to track progress and
                        manage data.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="cta bg-primary text-white py-5">
        <div class="container text-center">
            <h2>Get Started Today</h2>
            <p>Join thousands of users who trust WESSH for their enrollment needs.</p>
            <div class="d-flex justify-content-center flex-wrap">
                <a href="login.php" class="btn btn-outline-light me-3 mb-2">Login</a>
                <a href="register.php" class="btn btn-outline-light me-3 mb-2">Register</a>
            </div>
        </div>
    </section>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>