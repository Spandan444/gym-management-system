<?php
/**
 * Membership Management Page - GYM MANAGEMENT SYSTEM
 * Course: B.Tech DBMS Mini-Project
 * CRUD Operations: Create, Read, Update, Delete for Memberships
 */
$page_title = "Membership Management";
require_once __DIR__ . '/header.php';

// ====================================================================
// 1. HANDLE POST REQUESTS (CREATE & UPDATE)
// ====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Action: ADD MEMBERSHIP
    if ($action === 'add') {
        $member_id = intval($_POST['member_id'] ?? 0);
        $plan_name = trim($_POST['plan_name'] ?? '');
        $start_date = trim($_POST['start_date'] ?? date('Y-m-d'));
        $end_date = trim($_POST['end_date'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);

        if ($member_id > 0 && !empty($plan_name) && !empty($start_date) && !empty($end_date) && $amount >= 0) {
            $stmt = $conn->prepare("INSERT INTO `Membership` (`member_id`, `plan_name`, `start_date`, `end_date`, `amount`) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssd", $member_id, $plan_name, $start_date, $end_date, $amount);

            if ($stmt->execute()) {
                set_flash("success", "New {$plan_name} membership plan assigned successfully!");
            } else {
                set_flash("danger", "Failed to add membership: " . $stmt->error);
            }
            $stmt->close();
        } else {
            set_flash("warning", "Please select a valid member and plan duration.");
        }
        header("Location: membership.php");
        exit();
    }

    // Action: EDIT MEMBERSHIP
    if ($action === 'edit') {
        $membership_id = intval($_POST['membership_id'] ?? 0);
        $member_id = intval($_POST['member_id'] ?? 0);
        $plan_name = trim($_POST['plan_name'] ?? '');
        $start_date = trim($_POST['start_date'] ?? '');
        $end_date = trim($_POST['end_date'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);

        if ($membership_id > 0 && $member_id > 0 && !empty($plan_name) && !empty($start_date) && !empty($end_date)) {
            $stmt = $conn->prepare("UPDATE `Membership` SET `member_id` = ?, `plan_name` = ?, `start_date` = ?, `end_date` = ?, `amount` = ? WHERE `membership_id` = ?");
            $stmt->bind_param("isssdi", $member_id, $plan_name, $start_date, $end_date, $amount, $membership_id);

            if ($stmt->execute()) {
                set_flash("success", "Membership #{$membership_id} updated successfully!");
            } else {
                set_flash("danger", "Failed to update membership: " . $stmt->error);
            }
            $stmt->close();
        } else {
            set_flash("warning", "Invalid data provided for membership update.");
        }
        header("Location: membership.php");
        exit();
    }
}

// ====================================================================
// 2. HANDLE DELETE REQUEST
// ====================================================================
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM `Membership` WHERE `membership_id` = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        set_flash("success", "Membership record #{$delete_id} deleted.");
    } else {
        set_flash("danger", "Could not delete membership: " . $stmt->error);
    }
    $stmt->close();
    header("Location: membership.php");
    exit();
}

// Fetch all members for dropdown selection
$all_members = $conn->query("SELECT `member_id`, `name`, `phone` FROM `Member` ORDER BY `name` ASC");

// Fetch all memberships with member details using SQL JOIN
$memberships_query = "
    SELECT ms.*, m.name AS member_name, m.phone AS member_phone 
    FROM `Membership` ms 
    JOIN `Member` m ON ms.member_id = m.member_id 
    ORDER BY ms.membership_id DESC
";
$memberships_list = $conn->query($memberships_query);
?>

<!-- Page Header Bar -->
<div class="page-header-row">
    <div class="page-title-group">
        <h2>Memberships</h2>
        <p>Manage subscription plans, durations, and renewal dates</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openModal('addMembershipModal')">
            <span>+</span> Assign Membership
        </button>
    </div>
</div>

<!-- Main Table Card -->
<div class="content-card">
    <div class="table-filter-bar">
        <div class="table-search">
            <span>🔍</span>
            <input type="text" placeholder="Filter by member name, plan, dates...">
        </div>
        <div style="font-size: 13px; color: var(--text-secondary);">
            Total Subscriptions: <strong><?php echo ($memberships_list) ? $memberships_list->num_rows : 0; ?></strong>
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Membership ID</th>
                    <th>Member Name</th>
                    <th>Plan Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($memberships_list && $memberships_list->num_rows > 0): ?>
                    <?php while ($row = $memberships_list->fetch_assoc()): 
                        $is_active = (strtotime($row['end_date']) >= strtotime(date('Y-m-d')));
                    ?>
                        <tr>
                            <td><strong>#<?php echo $row['membership_id']; ?></strong></td>
                            <td style="font-weight: 600; color: var(--primary-navy);">
                                <?php echo htmlspecialchars($row['member_name']); ?>
                                <span style="font-size: 11px; color: var(--text-muted); display: block;">ID: #<?php echo $row['member_id']; ?></span>
                            </td>
                            <td>
                                <span class="badge badge-orange">
                                    <?php echo htmlspecialchars($row['plan_name']); ?>
                                </span>
                            </td>
                            <td><?php echo format_date($row['start_date']); ?></td>
                            <td><?php echo format_date($row['end_date']); ?></td>
                            <td style="font-weight: 600; color: #0f172a;">
                                <?php echo format_currency($row['amount']); ?>
                            </td>
                            <td>
                                <?php if ($is_active): ?>
                                    <span class="badge badge-success">✓ Active</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">&times; Expired</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <div class="action-buttons" style="justify-content: flex-end;">
                                    <button type="button" class="btn-action btn-edit" title="Edit Plan" onclick="populateEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                        Edit
                                    </button>
                                    <a href="javascript:void(0);" class="btn-action btn-delete" title="Delete Plan" onclick="confirmDelete('membership.php?delete=<?php echo $row['membership_id']; ?>', 'Membership #<?php echo $row['membership_id']; ?>')">
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            <div class="empty-state-icon">💳</div>
                            <p>No membership plans found. Click 'Assign Membership' to create one.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ====================================================================
     MODAL: ASSIGN NEW MEMBERSHIP
     ==================================================================== -->
<div id="addMembershipModal" class="modal">
    <div class="modal-dialog">
        <form action="membership.php" method="POST">
            <input type="hidden" name="action" value="add">

            <div class="modal-header">
                <h3>Assign New Membership Plan</h3>
                <button type="button" class="modal-close" onclick="closeModal('addMembershipModal')">&times;</button>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label for="add_member_id">Select Member <span class="required">*</span></label>
                    <select id="add_member_id" name="member_id" class="form-control" required>
                        <option value="">-- Choose Member --</option>
                        <?php 
                        if ($all_members && $all_members->num_rows > 0) {
                            $all_members->data_seek(0);
                            while ($m = $all_members->fetch_assoc()) {
                                echo "<option value='{$m['member_id']}'>" . htmlspecialchars($m['name']) . " (ID: #{$m['member_id']} - " . htmlspecialchars($m['phone']) . ")</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="plan_name">Subscription Plan <span class="required">*</span></label>
                    <select id="plan_name" name="plan_name" class="form-control" required>
                        <option value="">-- Select Plan --</option>
                        <option value="Monthly">Monthly (1 Month)</option>
                        <option value="Quarterly">Quarterly (3 Months)</option>
                        <option value="Half Yearly">Half Yearly (6 Months)</option>
                        <option value="Yearly">Yearly (12 Months)</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-col form-group">
                        <label for="start_date">Start Date <span class="required">*</span></label>
                        <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-col form-group">
                        <label for="end_date">End Date <span class="required">*</span></label>
                        <input type="date" id="end_date" name="end_date" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="amount">Plan Amount (₹) <span class="required">*</span></label>
                    <input type="number" id="amount" name="amount" class="form-control" placeholder="Plan fee" min="0" step="100" required>
                    <small style="color: var(--text-muted); font-size: 11px;">Auto-calculated based on plan selection (can be manually adjusted).</small>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addMembershipModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Membership</button>
            </div>
        </form>
    </div>
</div>

<!-- ====================================================================
     MODAL: EDIT MEMBERSHIP
     ==================================================================== -->
<div id="editMembershipModal" class="modal">
    <div class="modal-dialog">
        <form action="membership.php" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="edit_membership_id" name="membership_id" value="">

            <div class="modal-header">
                <h3>Edit Membership Subscription</h3>
                <button type="button" class="modal-close" onclick="closeModal('editMembershipModal')">&times;</button>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_member_id">Member <span class="required">*</span></label>
                    <select id="edit_member_id" name="member_id" class="form-control" required>
                        <?php 
                        if ($all_members && $all_members->num_rows > 0) {
                            $all_members->data_seek(0);
                            while ($m = $all_members->fetch_assoc()) {
                                echo "<option value='{$m['member_id']}'>" . htmlspecialchars($m['name']) . " (ID: #{$m['member_id']})</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_plan_name">Subscription Plan <span class="required">*</span></label>
                    <select id="edit_plan_name" name="plan_name" class="form-control" required>
                        <option value="Monthly">Monthly</option>
                        <option value="Quarterly">Quarterly</option>
                        <option value="Half Yearly">Half Yearly</option>
                        <option value="Yearly">Yearly</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-col form-group">
                        <label for="edit_start_date">Start Date <span class="required">*</span></label>
                        <input type="date" id="edit_start_date" name="start_date" class="form-control" required>
                    </div>
                    <div class="form-col form-group">
                        <label for="edit_end_date">End Date <span class="required">*</span></label>
                        <input type="date" id="edit_end_date" name="end_date" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_amount">Amount (₹) <span class="required">*</span></label>
                    <input type="number" id="edit_amount" name="amount" class="form-control" min="0" step="100" required>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editMembershipModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Membership</button>
            </div>
        </form>
    </div>
</div>

<script>
function populateEditModal(data) {
    document.getElementById('edit_membership_id').value = data.membership_id;
    document.getElementById('edit_member_id').value = data.member_id;
    document.getElementById('edit_plan_name').value = data.plan_name;
    document.getElementById('edit_start_date').value = data.start_date;
    document.getElementById('edit_end_date').value = data.end_date;
    document.getElementById('edit_amount').value = parseFloat(data.amount);
    openModal('editMembershipModal');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
