<?php
require_once __DIR__ . '/../db.php';
check_member_login();

$current_page = basename($_SERVER['PHP_SELF']);
$member_name = isset($_SESSION['member_name']) ? $_SESSION['member_name'] : 'Member';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . " - GYM MANAGEMENT" : "Member Panel"; ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">🏃</div>
            <div class="brand-text">
                <h2>MY GYM</h2>
                <span>Member Portal</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <span class="nav-icon">📊</span>
                <span>Dashboard</span>
            </a>
            <a href="profile.php" class="nav-link <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
                <span class="nav-icon">👤</span>
                <span>My Profile</span>
            </a>
            <a href="membership.php" class="nav-link <?php echo ($current_page == 'membership.php') ? 'active' : ''; ?>">
                <span class="nav-icon">💳</span>
                <span>My Membership</span>
            </a>
            <a href="attendance.php" class="nav-link <?php echo ($current_page == 'attendance.php') ? 'active' : ''; ?>">
                <span class="nav-icon">📅</span>
                <span>My Attendance</span>
            </a>
            <a href="workout.php" class="nav-link <?php echo ($current_page == 'workout.php') ? 'active' : ''; ?>">
                <span class="nav-icon">📋</span>
                <span>My Workout Plan</span>
            </a>
            <a href="payments.php" class="nav-link <?php echo ($current_page == 'payments.php') ? 'active' : ''; ?>">
                <span class="nav-icon">💰</span>
                <span>Payment History</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="../logout.php" class="nav-link logout-link" onclick="return confirm('Are you sure you want to log out?');">
                <span class="nav-icon">🚪</span>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <h1><?php echo isset($page_title) ? htmlspecialchars($page_title) : "Member Dashboard"; ?></h1>
            </div>
            <div class="header-right">
                <div class="admin-profile">
                    <div class="admin-avatar">
                        <?php echo strtoupper(substr($member_name, 0, 1)); ?>
                    </div>
                    <div class="admin-info">
                        <span class="admin-name"><?php echo htmlspecialchars($member_name); ?></span>
                        <span class="admin-role">Gym Member</span>
                    </div>
                </div>
            </div>
        </header>
        <div class="page-container">
            <?php display_flash(); ?>
