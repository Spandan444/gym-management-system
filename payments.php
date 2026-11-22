<?php
/**
 * Payment Management Page - GYM MANAGEMENT SYSTEM
 * Course: B.Tech DBMS Mini-Project
 * CRUD Operations: Create, Read, Update, Delete for Payments
 */
$page_title = "Payment Management";
require_once __DIR__ . '/header.php';

// ====================================================================
// 1. HANDLE POST REQUESTS (CREATE & UPDATE)
// ====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Action: RECORD PAYMENT
    if ($action === 'add') {
        $member_id = intval($_POST['member_id'] ?? 0);
        $payment_date = trim($_POST['payment_date'] ?? date('Y-m-d'));
        $amount = floatval($_POST['amount'] ?? 0);
        $payment_method = trim($_POST['payment_method'] ?? 'UPI');

        if ($member_id > 0 && !empty($payment_date) && $amount > 0 && !empty($payment_method)) {
            $stmt = $conn->prepare("INSERT INTO `Payment` (`member_id`, `payment_date`, `amount`, `payment_method`) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isds", $member_id, $payment_date, $amount, $payment_method);

            if ($stmt->execute()) {
                set_flash("success", "Payment of " . format_currency($amount) . " recorded successfully!");
            } else {
                set_flash("danger", "Failed to record payment: " . $stmt->error);
            }
            $stmt->close();
        } else {
            set_flash("warning", "Please provide a valid member, date, and amount.");
        }
        header("Location: payments.php");
        exit();
    }

    // Action: EDIT PAYMENT
    if ($action === 'edit') {
        $payment_id = intval($_POST['payment_id'] ?? 0);
        $member_id = intval($_POST['member_id'] ?? 0);
        $payment_date = trim($_POST['payment_date'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $payment_method = trim($_POST['payment_method'] ?? '');

        if ($payment_id > 0 && $member_id > 0 && $amount > 0) {
            $stmt = $conn->prepare("UPDATE `Payment` SET `member_id` = ?, `payment_date` = ?, `amount` = ?, `payment_method` = ? WHERE `payment_id` = ?");
            $stmt->bind_param("isdsi", $member_id, $payment_date, $amount, $payment_method, $payment_id);

            if ($stmt->execute()) {
                set_flash("success", "Payment #{$payment_id} updated successfully!");
            } else {
                set_flash("danger", "Failed to update payment: " . $stmt->error);
            }
            $stmt->close();
        } else {
            set_flash("warning", "Invalid data provided for payment update.");
        }
        header("Location: payments.php");
        exit();
    }
}

// ====================================================================
// 2. HANDLE DELETE REQUEST
// ====================================================================
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM `Payment` WHERE `payment_id` = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        set_flash("success", "Payment record #{$delete_id} deleted.");
    } else {
        set_flash("danger", "Could not delete payment: " . $stmt->error);
    }
    $stmt->close();
    header("Location: payments.php");
    exit();
}

// Fetch all members for dropdown
$members_dropdown = $conn->query("SELECT `member_id`, `name`, `phone` FROM `Member` ORDER BY `name` ASC");

// Fetch payment records with member names using SQL JOIN
$payments_query = "
    SELECT p.*, m.name AS member_name, m.phone AS member_phone, m.email AS member_email
    FROM `Payment` p 
    JOIN `Member` m ON p.member_id = m.member_id 
    ORDER BY p.payment_date DESC, p.payment_id DESC
";
$payments_list = $conn->query($payments_query);

// Check if viewing receipt for a specific payment
$receipt_payment = null;
if (isset($_GET['receipt'])) {
    $rec_id = intval($_GET['receipt']);
    $stmt = $conn->prepare("
        SELECT p.*, m.name AS member_name, m.phone AS member_phone, m.email AS member_email
        FROM `Payment` p 
        JOIN `Member` m ON p.member_id = m.member_id 
        WHERE p.payment_id = ?
    ");
    $stmt->bind_param("i", $rec_id);
    $stmt->execute();
    $receipt_res = $stmt->get_result();
    if ($receipt_res && $receipt_res->num_rows > 0) {
        $receipt_payment = $receipt_res->fetch_assoc();
    }
    $stmt->close();
}
?>

<!-- Page Header Bar -->
<div class="page-header-row">
    <div class="page-title-group">
        <h2>Payment Records</h2>
        <p>Track membership fee collections, payment methods, and invoices</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openModal('addPaymentModal')">
            <span>+</span> Record Payment
        </button>
    </div>
</div>

<!-- Main Table Card -->
<div class="content-card">
    <div class="table-filter-bar">
        <div class="table-search">
            <span>🔍</span>
            <input type="text" placeholder="Filter by member, method, amount...">
        </div>
        <div style="font-size: 13px; color: var(--text-secondary);">
            Transactions: <strong><?php echo ($payments_list) ? $payments_list->num_rows : 0; ?></strong>
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Member Name</th>
                    <th>Payment Date</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($payments_list && $payments_list->num_rows > 0): ?>
                    <?php while ($row = $payments_list->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo $row['payment_id']; ?></strong></td>
                            <td style="font-weight: 600; color: var(--primary-navy);">
                                <?php echo htmlspecialchars($row['member_name']); ?>
                                <span style="font-size: 11px; color: var(--text-muted); display: block;">Phone: <?php echo htmlspecialchars($row['member_phone']); ?></span>
                            </td>
                            <td><?php echo format_date($row['payment_date']); ?></td>
                            <td style="font-weight: 700; color: #047857; font-size: 14px;">
                                <?php echo format_currency($row['amount']); ?>
                            </td>
                            <td>
                                <span class="badge <?php 
                                    echo ($row['payment_method'] == 'UPI') ? 'badge-orange' : 
                                        (($row['payment_method'] == 'Card') ? 'badge-navy' : 'badge-success'); 
                                ?>">
                                    <?php echo htmlspecialchars($row['payment_method']); ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div class="action-buttons" style="justify-content: flex-end;">
                                    <!-- Print Receipt -->
                                    <a href="payments.php?receipt=<?php echo $row['payment_id']; ?>" class="btn-action btn-view" title="View Receipt">
                                        Receipt
                                    </a>
                                    <!-- Edit -->
                                    <button type="button" class="btn-action btn-edit" title="Edit Payment" onclick="populateEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                        Edit
                                    </button>
                                    <!-- Delete -->
                                    <a href="javascript:void(0);" class="btn-action btn-delete" title="Delete Payment" onclick="confirmDelete('payments.php?delete=<?php echo $row['payment_id']; ?>', 'Payment #<?php echo $row['payment_id']; ?>')">
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty-state">
                            <div class="empty-state-icon">💰</div>
                            <p>No payments recorded yet. Click 'Record Payment' to add transactions.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ====================================================================
     MODAL: RECORD NEW PAYMENT
     ==================================================================== -->
<div id="addPaymentModal" class="modal">
    <div class="modal-dialog">
        <form action="payments.php" method="POST">
            <input type="hidden" name="action" value="add">

            <div class="modal-header">
                <h3>Record New Payment</h3>
                <button type="button" class="modal-close" onclick="closeModal('addPaymentModal')">&times;</button>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label for="add_p_member_id">Member <span class="required">*</span></label>
                    <select id="add_p_member_id" name="member_id" class="form-control" required>
                        <option value="">-- Choose Member --</option>
                        <?php 
                        if ($members_dropdown && $members_dropdown->num_rows > 0) {
                            $members_dropdown->data_seek(0);
                            while ($m = $members_dropdown->fetch_assoc()) {
                                echo "<option value='{$m['member_id']}'>" . htmlspecialchars($m['name']) . " (ID: #{$m['member_id']})</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-col form-group">
                        <label for="add_payment_date">Payment Date <span class="required">*</span></label>
                        <input type="date" id="add_payment_date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-col form-group">
                        <label for="add_amount">Amount (₹) <span class="required">*</span></label>
                        <input type="number" id="add_amount" name="amount" class="form-control" placeholder="e.g. 4800" min="1" step="50" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="add_payment_method">Payment Method <span class="required">*</span></label>
                    <select id="add_payment_method" name="payment_method" class="form-control" required>
                        <option value="UPI">UPI (Google Pay, PhonePe, Paytm)</option>
                        <option value="Cash">Cash</option>
                        <option value="Card">Credit / Debit Card</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addPaymentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Payment</button>
            </div>
        </form>
    </div>
</div>

<!-- ====================================================================
     MODAL: EDIT PAYMENT
     ==================================================================== -->
<div id="editPaymentModal" class="modal">
    <div class="modal-dialog">
        <form action="payments.php" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="edit_payment_id" name="payment_id" value="">

            <div class="modal-header">
                <h3>Edit Payment Record</h3>
                <button type="button" class="modal-close" onclick="closeModal('editPaymentModal')">&times;</button>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_p_member_id">Member <span class="required">*</span></label>
                    <select id="edit_p_member_id" name="member_id" class="form-control" required>
                        <?php 
                        if ($members_dropdown && $members_dropdown->num_rows > 0) {
                            $members_dropdown->data_seek(0);
                            while ($m = $members_dropdown->fetch_assoc()) {
                                echo "<option value='{$m['member_id']}'>" . htmlspecialchars($m['name']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-col form-group">
                        <label for="edit_payment_date">Payment Date <span class="required">*</span></label>
                        <input type="date" id="edit_payment_date" name="payment_date" class="form-control" required>
                    </div>
                    <div class="form-col form-group">
                        <label for="edit_p_amount">Amount (₹) <span class="required">*</span></label>
                        <input type="number" id="edit_p_amount" name="amount" class="form-control" min="1" step="50" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_payment_method">Payment Method <span class="required">*</span></label>
                    <select id="edit_payment_method" name="payment_method" class="form-control" required>
                        <option value="UPI">UPI</option>
                        <option value="Cash">Cash</option>
                        <option value="Card">Card</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editPaymentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Payment</button>
            </div>
        </form>
    </div>
</div>

<!-- ====================================================================
     MODAL: VIEW & PRINT PAYMENT RECEIPT
     ==================================================================== -->
<?php if ($receipt_payment): ?>
<div id="receiptModal" class="modal active">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Payment Receipt #<?php echo $receipt_payment['payment_id']; ?></h3>
            <button type="button" class="modal-close" onclick="window.location.href='payments.php'">&times;</button>
        </div>

        <div class="modal-body" id="receiptPrintArea">
            <div class="receipt-box" style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px;">
                <div class="receipt-title" style="text-align: center; border-bottom: 2px solid #ff6b35; padding-bottom: 12px; margin-bottom: 16px;">
                    <h2 style="font-size: 18px; color: #0f172a; margin: 0;">GYM MANAGEMENT SYSTEM</h2>
                    <p style="font-size: 12px; color: #64748b; margin-top: 3px;">Official Payment Confirmation Receipt</p>
                </div>

                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr>
                        <td style="padding: 6px 0; color: #64748b;">Receipt No:</td>
                        <td style="padding: 6px 0; font-weight: 600; text-align: right;">REC-<?php echo str_pad($receipt_payment['payment_id'], 5, '0', STR_PAD_LEFT); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b;">Payment Date:</td>
                        <td style="padding: 6px 0; text-align: right;"><?php echo format_date($receipt_payment['payment_date']); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b;">Member Name:</td>
                        <td style="padding: 6px 0; font-weight: 600; text-align: right;"><?php echo htmlspecialchars($receipt_payment['member_name']); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b;">Member Phone:</td>
                        <td style="padding: 6px 0; text-align: right;"><?php echo htmlspecialchars($receipt_payment['member_phone']); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b;">Payment Mode:</td>
                        <td style="padding: 6px 0; text-align: right; font-weight: 600;"><?php echo htmlspecialchars($receipt_payment['payment_method']); ?></td>
                    </tr>
                    <tr style="border-top: 2px solid #e2e8f0;">
                        <td style="padding: 12px 0; font-size: 15px; font-weight: 700; color: #0f172a;">Amount Paid:</td>
                        <td style="padding: 12px 0; font-size: 16px; font-weight: 700; color: #ff6b35; text-align: right;">
                            <?php echo format_currency($receipt_payment['amount']); ?>
                        </td>
                    </tr>
                </table>

                <div style="margin-top: 15px; padding-top: 10px; border-top: 1px dashed #cbd5e1; text-align: center; font-size: 11px; color: #94a3b8;">
                    Thank you for training with us! • Computer Generated Slip
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="window.location.href='payments.php'">Close</button>
            <button type="button" class="btn btn-primary" onclick="printReceipt('receiptPrintArea')">
                <span>🖨️</span> Print Receipt
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function populateEditModal(data) {
    document.getElementById('edit_payment_id').value = data.payment_id;
    document.getElementById('edit_p_member_id').value = data.member_id;
    document.getElementById('edit_payment_date').value = data.payment_date;
    document.getElementById('edit_p_amount').value = parseFloat(data.amount);
    document.getElementById('edit_payment_method').value = data.payment_method;
    openModal('editPaymentModal');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
