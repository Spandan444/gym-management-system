<?php
$page_title = "Dashboard";
require_once __DIR__ . '/user_header.php';
$member_id = $_SESSION['member_id'];

// Get membership info
$mem_stmt = $conn->prepare("SELECT plan_name, end_date, IF(end_date >= CURDATE(), 'Active', 'Expired') AS status FROM Membership WHERE member_id = ? ORDER BY end_date DESC LIMIT 1");
$mem_stmt->bind_param("i", $member_id);
$mem_stmt->execute();
$membership = $mem_stmt->get_result()->fetch_assoc();

// Get attendance summary
$att_stmt = $conn->prepare("SELECT COUNT(*) AS total_days, SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) AS present_days FROM Attendance WHERE member_id = ?");
$att_stmt->bind_param("i", $member_id);
$att_stmt->execute();
$attendance = $att_stmt->get_result()->fetch_assoc();

// Get active workout plan
$work_stmt = $conn->prepare("SELECT w.workout_name, t.name as trainer_name FROM Workout_Plan w JOIN Trainer t ON w.trainer_id = t.trainer_id WHERE w.member_id = ? ORDER BY w.workout_date DESC LIMIT 1");
$work_stmt->bind_param("i", $member_id);
$work_stmt->execute();
$workout = $work_stmt->get_result()->fetch_assoc();
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">💳</div>
        <div class="stat-info">
            <h3>Current Membership</h3>
            <div class="stat-number"><?php echo $membership ? htmlspecialchars($membership['plan_name']) : 'None'; ?></div>
            <div class="stat-label">Status: <?php echo $membership ? $membership['status'] : 'N/A'; ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">📅</div>
        <div class="stat-info">
            <h3>Expiry Date</h3>
            <div class="stat-number"><?php echo $membership ? format_date($membership['end_date']) : '-'; ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">✔️</div>
        <div class="stat-info">
            <h3>Attendance</h3>
            <div class="stat-number"><?php echo (int)($attendance['present_days'] ?? 0); ?> / <?php echo (int)($attendance['total_days'] ?? 0); ?></div>
            <div class="stat-label">Present Days</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">📋</div>
        <div class="stat-info">
            <h3>Assigned Trainer</h3>
            <div class="stat-number" style="font-size:1.2rem;"><?php echo $workout ? htmlspecialchars($workout['trainer_name']) : 'None'; ?></div>
            <div class="stat-label"><?php echo $workout ? htmlspecialchars($workout['workout_name']) : 'No active plan'; ?></div>
        </div>
    </div>
</div>

<?php require_once '../footer.php'; ?>
