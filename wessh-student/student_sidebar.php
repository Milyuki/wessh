<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/* ---------- DEV AUTO-LOGIN (localhost only) ---------- */
$local_ips = ['127.0.0.1', '::1'];
if (in_array($_SERVER['REMOTE_ADDR'] ?? '', $local_ips, true)) {
    $_SESSION['user_id'] = $_SESSION['user_id'] ?? 1;
    $_SESSION['user_type'] = $_SESSION['user_type'] ?? 'student';
    $_SESSION['name'] = $_SESSION['name'] ?? 'Dev Student';
} else {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
        header("Location: ../login.php");
        exit;
    }
}

/* ---------- DETECT CURRENT PAGE ---------- */
$current_file = basename($_SERVER['PHP_SELF']);

// Map filename → menu key
$page_map = [
    'dashboard.php' => 'dashboard',
    'enroll.php' => 'enrollment',
    'notifications.php' => 'notifications',
    'profile_schedule.php' => 'profile',
];

$active_key = $page_map[$current_file] ?? 'dashboard';
?>

<!-- ====================== STUDENT SIDEBAR ====================== -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
        <div class="sidebar-brand-icon">
            <img src="../img/official logo.png" alt="WESSH Logo" style="width:190px;height:60px;max-width:100%;">
        </div>
    </a>

    <hr class="sidebar-divider my-0">

    <!-- Dashboard -->
    <li class="nav-item <?php echo $active_key === 'dashboard' ? 'active' : ''; ?>">
        <a class="nav-link" href="dashboard.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <!-- Enrollment Form -->
    <li class="nav-item <?php echo $active_key === 'enrollment' ? 'active' : ''; ?>">
        <a class="nav-link" href="enroll.php">
            <i class="fas fa-fw fa-edit"></i>
            <span>Enrollment Form</span>
        </a>
    </li>

    <hr class="sidebar-divider">

    <div class="sidebar-heading">Student Portal</div>

    <!-- Notifications -->
    <li class="nav-item <?php echo $active_key === 'notifications' ? 'active' : ''; ?>">
        <a class="nav-link" href="notifications.php">
            <i class="fas fa-fw fa-bell"></i>
            <span>Notifications</span>
        </a>
    </li>

    <!-- Profile & Schedule -->
    <li class="nav-item <?php echo $active_key === 'profile' ? 'active' : ''; ?>">
        <a class="nav-link" href="profile_schedule.php">
            <i class="fas fa-fw fa-user"></i>
            <span>Profile & Schedule</span>
        </a>
    </li>

    <hr class="sidebar-divider d-none d-md-block">

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>
</ul>
<!-- ====================== END OF SIDEBAR ====================== -->