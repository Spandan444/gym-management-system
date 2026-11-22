<?php
$page_title = "My Membership";
require_once __DIR__ . '/user_header.php';
$member_id = $_SESSION['member_id'];

$stmt = $conn->prepare("SELECT *, IF(end_date >= CURDATE(), 'Active', 'Expired') AS status FROM Membership WHERE member_id = ? ORDER BY end_date DESC");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="card">
    <div class="card-header">
        <h2>Membership Details</h2>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Plan Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Amount Paid</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['plan_name']); ?></td>
                    <td><?php echo format_date($row['start_date']); ?></td>
                    <td><?php echo format_date($row['end_date']); ?></td>
                    <td><?php echo format_currency($row['amount']); ?></td>
                    <td>
                        <span class="badge <?php echo $row['status'] == 'Active' ? 'badge-success' : 'badge-danger'; ?>">
                            <?php echo $row['status']; ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if($result->num_rows == 0): ?>
                <tr><td colspan="5" class="text-center">No membership records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../footer.php'; ?>
