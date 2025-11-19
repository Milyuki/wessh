<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Allow automatic dev login only from localhost (127.0.0.1 or ::1)
$local_ips = ['127.0.0.1', '::1'];
if (in_array($_SERVER['REMOTE_ADDR'] ?? '', $local_ips, true)) {
    // Set a safe default dev registrar session if not already present
    $_SESSION['user_id'] = $_SESSION['user_id'] ?? 1;
    $_SESSION['user_type'] = $_SESSION['user_type'] ?? 'registrar';
    $_SESSION['name'] = $_SESSION['name'] ?? 'Dev Registrar';
} else {
    // Normal production behavior: require registrar role
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'registrar') {
        header("Location: registrar_login.php");
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
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="Registrar_Dashboard.php">
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
        <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'Registrar_Dashboard.php' ? 'active' : ''; ?>">
            <a class="nav-link" href="Registrar_Dashboard.php">
                <i class="fas fa-fw fa-tachometer-alt"></i>
                <span>Dashboard</span></a>
        </li>

        <!-- Nav Item - Schedule Assignment -->
        <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'Schedule_Assignment.php' ? 'active' : ''; ?>">
            <a class="nav-link" href="Schedule_Assignment.php">
                <i class="fas fa-fw fa-calendar-alt"></i>
                <span>Schedule Assignment</span></a>
        </li>

        <!-- Nav Item - Advisory Assignment -->
        <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'Advisory_Assignment.php' ? 'active' : ''; ?>">
            <a class="nav-link" href="Advisory_Assignment.php">
                <i class="fas fa-fw fa-users"></i>
                <span>Advisory Assignment</span></a>
        </li>

        <!-- Nav Item - Subject Registration -->
        <li
            class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'Subject_Registration.php' ? 'active' : ''; ?>">
            <a class="nav-link" href="Subject_Registration.php">
                <i class="fas fa-fw fa-book"></i>
                <span>Subject Registration</span></a>
        </li>

        <!-- Nav Item - Enrollment Approval -->
        <li
            class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'Enrollments_Management.php' ? 'active' : ''; ?>">
            <a class="nav-link" href="Enrollments_Management.php">
                <i class="fas fa-fw fa-check-circle"></i>
                <span>Enrollment Approval</span></a>
        </li>

        <!-- Nav Item - Block Management -->
        <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'Block_Management.php' ? 'active' : ''; ?>">
            <a class="nav-link" href="Block_Management.php">
                <i class="fas fa-fw fa-cubes"></i>
                <span>Block Management</span></a>
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