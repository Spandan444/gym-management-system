<?php
/**
 * Shared Header & Sidebar Navigation
 * Course: B.Tech DBMS Mini-Project
 */
require_once __DIR__ . '/db.php';
check_login();

// Determine active page for highlighting navigation items
$current_page = basename($_SERVER['PHP_SELF']);
$admin_name = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Administrator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . " - GYM MANAGEMENT" : "GYM MANAGEMENT SYSTEM"; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="app-container">
    <!-- Left Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">🏋️</div>
            <div class="brand-text">
                <h2>GYM MANAGEMENT</h2>
                <span>DBMS Mini Project</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <span class="nav-icon">📊</span>
                <span>Dashboard</span>
            </a>
            <a href="members.php" class="nav-link <?php echo ($current_page == 'members.php') ? 'active' : ''; ?>">
                <span class="nav-icon">👥</span>
                <span>Members</span>
            </a>
            <a href="trainers.php" class="nav-link <?php echo ($current_page == 'trainers.php') ? 'active' : ''; ?>">
                <span class="nav-icon">💪</span>
                <span>Trainers</span>
            </a>
            <a href="membership.php" class="nav-link <?php echo ($current_page == 'membership.php') ? 'active' : ''; ?>">
                <span class="nav-icon">💳</span>
                <span>Memberships</span>
            </a>
            <a href="payments.php" class="nav-link <?php echo ($current_page == 'payments.php') ? 'active' : ''; ?>">
                <span class="nav-icon">💰</span>
                <span>Payments</span>
            </a>
            <a href="attendance.php" class="nav-link <?php echo ($current_page == 'attendance.php') ? 'active' : ''; ?>">
                <span class="nav-icon">📅</span>
                <span>Attendance</span>
            </a>
            <a href="workout.php" class="nav-link <?php echo ($current_page == 'workout.php') ? 'active' : ''; ?>">
                <span class="nav-icon">📋</span>
                <span>Workout Plans</span>
            </a>
            <a href="equipment.php" class="nav-link <?php echo ($current_page == 'equipment.php') ? 'active' : ''; ?>">
                <span class="nav-icon">⚙️</span>
                <span>Equipment</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="logout.php" class="nav-link logout-link" onclick="return confirm('Are you sure you want to log out?');">
                <span class="nav-icon">🚪</span>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="main-content">
        <!-- Top Sticky Header -->
        <header class="top-header">
            <div class="header-left">
                <h1><?php echo isset($page_title) ? htmlspecialchars($page_title) : "Gym Dashboard"; ?></h1>
            </div>

            <div class="header-right">
                <div class="header-search">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="globalSearchInput" placeholder="Quick search anything...">
                </div>

                <div class="admin-profile">
                    <div class="admin-avatar">
                        <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                    </div>
                    <div class="admin-info">
                        <span class="admin-name"><?php echo htmlspecialchars($admin_name); ?></span>
                        <span class="admin-role">System Admin</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Container -->
        <div class="page-container">
            <?php display_flash(); ?>
