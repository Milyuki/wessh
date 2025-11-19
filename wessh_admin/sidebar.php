<?php
// // Session check: Ensure user is logged in
// if (session_status() == PHP_SESSION_NONE) {
//     session_start();
// }
// if (!isset($_SESSION['user_type'])) {
//     header("Location: login.php");
//     exit;
// }

// Determine current page for active state
$current_page = basename($_SERVER['PHP_SELF']);

// Determine brand and dashboard link based on user type
$user_type = $_SESSION['user_type'] ?? 'admin';
if ($user_type === 'admin') {
    $brand_text = 'WESSH Admin';
    $dashboard_href = 'admin_dashboard.php';
} else {
    $brand_text = 'WESSH';
    $dashboard_href = 'dashboard.php';
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
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?php echo $dashboard_href; ?>">
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
    <li
        class="nav-item <?php echo ($current_page == 'admin_dashboard.php' || $current_page == 'dashboard.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo $dashboard_href; ?>">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span></a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Management
    </div>

    <!-- Nav Item - User Management -->
    <li class="nav-item <?php echo ($current_page == 'user_management.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="user_management.php">
            <i class="fas fa-fw fa-users"></i>
            <span>User Management</span></a>
    </li>

    <!-- Nav Item - Statistics -->
    <li class="nav-item <?php echo ($current_page == 'statistics.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="statistics.php">
            <i class="fas fa-fw fa-chart-line"></i>
            <span>Statistics</span></a>
    </li>

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
<!-- End of Sidebar -->