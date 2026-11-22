<?php
$page_title = "My Workout Plan";
require_once __DIR__ . '/user_header.php';
$member_id = $_SESSION['member_id'];

$stmt = $conn->prepare("SELECT w.workout_id, w.workout_name, w.workout_date, t.name as trainer_name FROM Workout_Plan w JOIN Trainer t ON w.trainer_id = t.trainer_id WHERE w.member_id = ? ORDER BY w.workout_date DESC");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="card">
    <div class="card-header">
        <h2>Assigned Workout Plans</h2>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Workout ID</th>
                    <th>Trainer</th>
                    <th>Workout Name</th>
                    <th>Workout Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $row['workout_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['trainer_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['workout_name']); ?></td>
                    <td><?php echo format_date($row['workout_date']); ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if($result->num_rows == 0): ?>
                <tr><td colspan="4" class="text-center">No workout plans assigned yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../footer.php'; ?>
