<?php
/**
 * Dashboard Page - GYM MANAGEMENT SYSTEM
 * Course: B.Tech DBMS Mini-Project
 */
$page_title = "Gym Dashboard";
require_once __DIR__ . '/header.php';

// Fetch Dashboard Metrics using SQL Aggregate Functions
// 1. Total Members
$q_members = $conn->query("SELECT COUNT(*) AS total FROM `Member`");
$total_members = ($q_members) ? $q_members->fetch_assoc()['total'] : 0;

// 2. Active Memberships (end_date >= current date)
$q_active_sub = $conn->query("SELECT COUNT(*) AS total FROM `Membership` WHERE `end_date` >= CURDATE()");
$active_memberships = ($q_active_sub) ? $q_active_sub->fetch_assoc()['total'] : 0;

// 3. Total Trainers
$q_trainers = $conn->query("SELECT COUNT(*) AS total FROM `Trainer`");
$total_trainers = ($q_trainers) ? $q_trainers->fetch_assoc()['total'] : 0;

// 4. Today's Attendance (Present members today)
$q_attendance = $conn->query("SELECT COUNT(*) AS total FROM `Attendance` WHERE `attendance_date` = CURDATE() AND `status` = 'Present'");
$today_attendance = ($q_attendance) ? $q_attendance->fetch_assoc()['total'] : 0;

// 5. Total Payments Collected
$q_payments = $conn->query("SELECT COALESCE(SUM(`amount`), 0) AS total FROM `Payment`");
$total_payments = ($q_payments) ? $q_payments->fetch_assoc()['total'] : 0;

// Fetch Recent Members (Limit 5)
$recent_members = $conn->query("SELECT * FROM `Member` ORDER BY `join_date` DESC, `member_id` DESC LIMIT 5");

// Fetch Recent Payments (Limit 5) using JOIN with Member table
$recent_payments = $conn->query("
    SELECT p.payment_id, m.name AS member_name, p.amount, p.payment_date, p.payment_method 
    FROM `Payment` p 
    JOIN `Member` m ON p.member_id = m.member_id 
    ORDER BY p.payment_date DESC, p.payment_id DESC 
    LIMIT 5
");

// Fetch Today's Attendance (or most recent attendance records)
$today_records = $conn->query("
    SELECT a.attendance_id, m.name AS member_name, m.phone, a.attendance_date, a.status 
    FROM `Attendance` a 
    JOIN `Member` m ON a.member_id = m.member_id 
    WHERE a.attendance_date = CURDATE() 
    ORDER BY a.attendance_id DESC
");

// Fallback to recent attendance if none marked for today yet
if ($today_records && $today_records->num_rows === 0) {
    $today_records = $conn->query("
        SELECT a.attendance_id, m.name AS member_name, m.phone, a.attendance_date, a.status 
        FROM `Attendance` a 
        JOIN `Member` m ON a.member_id = m.member_id 
        ORDER BY a.attendance_date DESC, a.attendance_id DESC 
        LIMIT 6
    ");
    $is_recent_fallback = true;
} else {
    $is_recent_fallback = false;
}
?>

<!-- Top Stats Grid -->
<div class="stats-grid">
    <!-- Stat 1: Total Members -->
    <div class="stat-card">
        <div class="stat-details">
            <span class="stat-title">Total Members</span>
            <span class="stat-value"><?php echo number_format($total_members); ?></span>
            <span class="stat-subtext">Registered athletes</span>
        </div>
        <div class="stat-icon orange">👥</div>
    </div>

    <!-- Stat 2: Active Memberships -->
    <div class="stat-card">
        <div class="stat-details">
            <span class="stat-title">Active Memberships</span>
            <span class="stat-value"><?php echo number_format($active_memberships); ?></span>
            <span class="stat-subtext">Valid active subscriptions</span>
        </div>
        <div class="stat-icon green">💳</div>
    </div>

    <!-- Stat 3: Total Trainers -->
    <div class="stat-card">
        <div class="stat-details">
            <span class="stat-title">Total Trainers</span>
            <span class="stat-value"><?php echo number_format($total_trainers); ?></span>
            <span class="stat-subtext">Certified gym instructors</span>
        </div>
        <div class="stat-icon navy">💪</div>
    </div>

    <!-- Stat 4: Today's Attendance -->
    <div class="stat-card">
        <div class="stat-details">
            <span class="stat-title">Today's Attendance</span>
            <span class="stat-value"><?php echo number_format($today_attendance); ?></span>
            <span class="stat-subtext">Present check-ins today</span>
        </div>
        <div class="stat-icon blue">📅</div>
    </div>

    <!-- Stat 5: Total Revenue -->
    <div class="stat-card">
        <div class="stat-details">
            <span class="stat-title">Total Payments</span>
            <span class="stat-value"><?php echo format_currency($total_payments); ?></span>
            <span class="stat-subtext">All time revenue</span>
        </div>
        <div class="stat-icon yellow">💰</div>
    </div>
</div>

<!-- Main Dashboard Grid (Recent Members & Recent Payments) -->
<div class="dashboard-grid">
    <!-- 1. Recent Members Table -->
    <div class="content-card">
        <div class="card-header">
            <h3><span>👥</span> Recent Members</h3>
            <a href="members.php">View All &rarr;</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>Phone</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_members && $recent_members->num_rows > 0): ?>
                        <?php while ($member = $recent_members->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?php echo $member['member_id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($member['name']); ?></td>
                                <td>
                                    <span class="badge <?php echo ($member['gender'] == 'Male') ? 'badge-navy' : 'badge-orange'; ?>">
                                        <?php echo htmlspecialchars($member['gender']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($member['phone']); ?></td>
                                <td><?php echo format_date($member['join_date']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="empty-state">No members found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. Recent Payments Table -->
    <div class="content-card">
        <div class="card-header">
            <h3><span>💰</span> Recent Payments</h3>
            <a href="payments.php">View All &rarr;</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Member</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Method</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_payments && $recent_payments->num_rows > 0): ?>
                        <?php while ($payment = $recent_payments->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?php echo $payment['payment_id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($payment['member_name']); ?></td>
                                <td style="font-weight: 600; color: #0f172a;"><?php echo format_currency($payment['amount']); ?></td>
                                <td><?php echo format_date($payment['payment_date']); ?></td>
                                <td>
                                    <span class="badge <?php 
                                        echo ($payment['payment_method'] == 'UPI') ? 'badge-orange' : 
                                            (($payment['payment_method'] == 'Card') ? 'badge-navy' : 'badge-success'); 
                                    ?>">
                                        <?php echo htmlspecialchars($payment['payment_method']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="empty-state">No payments recorded.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 3. Today's Attendance Table -->
<div class="dashboard-full">
    <div class="content-card">
        <div class="card-header">
            <h3>
                <span>📅</span> 
                <?php echo ($is_recent_fallback) ? "Recent Attendance Records" : "Today's Attendance (" . date("d M Y") . ")"; ?>
            </h3>
            <a href="attendance.php">Mark Attendance &rarr;</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Attendance ID</th>
                        <th>Member Name</th>
                        <th>Phone</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($today_records && $today_records->num_rows > 0): ?>
                        <?php while ($att = $today_records->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $att['attendance_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($att['member_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($att['phone']); ?></td>
                                <td><?php echo format_date($att['attendance_date']); ?></td>
                                <td>
                                    <?php if ($att['status'] === 'Present'): ?>
                                        <span class="badge badge-success">✓ Present</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">&times; Absent</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="empty-state">No attendance records for today.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
