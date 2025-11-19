<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Allow automatic dev login only from localhost (127.0.0.1 or ::1)
$local_ips = ['127.0.0.1', '::1'];
if (in_array($_SERVER['REMOTE_ADDR'] ?? '', $local_ips, true)) {
    // Set a safe default dev teacher session if not already present
    $_SESSION['user_id'] = $_SESSION['user_id'] ?? 1;
    $_SESSION['user_type'] = $_SESSION['user_type'] ?? 'teacher';
    $_SESSION['name'] = $_SESSION['name'] ?? 'Dev Teacher';
} else {
    // Normal production behavior: require teacher role
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'teacher') {
        header("Location: ../login.php");
        exit;
    }
}
?>

<!-- Sidebar -->
<style>
    /* Logo switching when sidebar is toggled */
    .sidebar .sidebar-logo-full {
        display: block;
    }

    .sidebar .sidebar-logo-small {
        display: none;
    }

    .sidebar.toggled .sidebar-logo-full {
        display: none;
    }

    .sidebar.toggled .sidebar-logo-small {
        display: block;
    }
</style>
<div class="sidebar-wrapper" style="width: 250px; flex-shrink: 0;">
    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar"
        style="position: relative;">

        <!-- Sidebar - Brand -->
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
            <div class="sidebar-brand-icon">
                <img src="../img/official logo.png" alt="WESSH Logo" class="sidebar-logo-full"
                    style="width: 190px; height: 60px; max-width: 100%;">
                <img src="../img/small logo.png" alt="WESSH Logo" class="sidebar-logo-small"
                    style="width: 40px; height: 40px; display: none;">
            </div>
        </a>

        <!-- Divider -->
        <hr class="sidebar-divider my-0">

        <!-- Nav Item - Dashboard -->
        <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-fw fa-tachometer-alt"></i>
                <span>Dashboard</span></a>
        </li>

        <!-- Nav Item - Reviews -->
        <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'review.php' ? 'active' : ''; ?>">
            <a class="nav-link" href="review.php">
                <i class="fas fa-fw fa-file-alt"></i>
                <span>Pending Reviews</span></a>
        </li>

        <!-- Nav Item - Notifications -->
        <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>">
            <a class="nav-link" href="notifications.php">
                <i class="fas fa-fw fa-bell"></i>
                <span>Notifications</span></a>
        </li>

        <!-- Nav Item - Reports -->
        <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
            <a class="nav-link" href="reports.php">
                <i class="fas fa-fw fa-chart-bar"></i>
                <span>Reports</span></a>
        </li>

        <!-- Divider -->
        <hr class="sidebar-divider d-none d-md-block">

        <!-- Sidebar Toggler (Sidebar) -->
        <div class="text-center d-none d-md-inline">
            <button class="rounded-circle border-0" id="sidebarToggle"></button>
        </div>



    </ul>
</div>
<!-- End of Sidebar -->