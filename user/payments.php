<?php
$page_title = "Payment History";
require_once __DIR__ . '/user_header.php';
$member_id = $_SESSION['member_id'];

// Get total amount
$tot_stmt = $conn->prepare("SELECT SUM(amount) as total_paid FROM Payment WHERE member_id = ?");
$tot_stmt->bind_param("i", $member_id);
$tot_stmt->execute();
$total_paid = $tot_stmt->get_result()->fetch_assoc()['total_paid'] ?? 0;

$stmt = $conn->prepare("SELECT * FROM Payment WHERE member_id = ? ORDER BY payment_date DESC");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="stats-grid" style="margin-bottom: 20px;">
    <div class="stat-card">
        <div class="stat-info">
            <h3>Total Amount Paid</h3>
            <div class="stat-number" style="color: #4CAF50;"><?php echo format_currency($total_paid); ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Payment Records</h2>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Payment Date</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $row['payment_id']; ?></td>
                    <td><?php echo format_date($row['payment_date']); ?></td>
                    <td><?php echo format_currency($row['amount']); ?></td>
                    <td>
                        <span class="badge badge-info">
                            <?php echo htmlspecialchars($row['payment_method']); ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if($result->num_rows == 0): ?>
                <tr><td colspan="4" class="text-center">No payment history found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../footer.php'; ?>
