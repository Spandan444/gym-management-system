<?php
$page_title = "My Attendance";
require_once __DIR__ . '/user_header.php';
$member_id = $_SESSION['member_id'];

// Get summary
$att_stmt = $conn->prepare("SELECT COUNT(*) AS total_days, SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) AS present_days, SUM(CASE WHEN status='Absent' THEN 1 ELSE 0 END) AS absent_days FROM Attendance WHERE member_id = ?");
$att_stmt->bind_param("i", $member_id);
$att_stmt->execute();
$attendance = $att_stmt->get_result()->fetch_assoc();

$total = $attendance['total_days'] ?? 0;
$present = $attendance['present_days'] ?? 0;
$absent = $attendance['absent_days'] ?? 0;
$percentage = $total > 0 ? round(($present / $total) * 100, 2) : 0;

$stmt = $conn->prepare("SELECT * FROM Attendance WHERE member_id = ? ORDER BY attendance_date DESC");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="stats-grid" style="margin-bottom: 20px;">
    <div class="stat-card">
        <div class="stat-info">
            <h3>Total Days</h3>
            <div class="stat-number"><?php echo (int)$total; ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <h3>Present Days</h3>
            <div class="stat-number" style="color: green;"><?php echo (int)$present; ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <h3>Absent Days</h3>
            <div class="stat-number" style="color: red;"><?php echo (int)$absent; ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <h3>Attendance %</h3>
            <div class="stat-number"><?php echo $percentage; ?>%</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Attendance History</h2>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Attendance ID</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $row['attendance_id']; ?></td>
                    <td><?php echo format_date($row['attendance_date']); ?></td>
                    <td>
                        <span class="badge <?php echo $row['status'] == 'Present' ? 'badge-success' : 'badge-danger'; ?>">
                            <?php echo $row['status']; ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if($result->num_rows == 0): ?>
                <tr><td colspan="3" class="text-center">No attendance records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../footer.php'; ?>
