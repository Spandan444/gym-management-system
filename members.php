<?php
/**
 * Member Management Page - GYM MANAGEMENT SYSTEM
 * Course: B.Tech DBMS Mini-Project
 * CRUD Operations: Create, Read, Update, Delete for Members
 */
$page_title = "Member Management";
require_once __DIR__ . '/header.php';

// ====================================================================
// 1. HANDLE POST REQUESTS (CREATE & UPDATE)
// ====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Action: ADD MEMBER
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $age = intval($_POST['age'] ?? 0);
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $join_date = trim($_POST['join_date'] ?? date('Y-m-d'));

        if (!empty($name) && !empty($gender) && $age > 0 && !empty($phone) && !empty($email)) {
            $stmt = $conn->prepare("INSERT INTO `Member` (`name`, `gender`, `age`, `phone`, `email`, `join_date`) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssisss", $name, $gender, $age, $phone, $email, $join_date);
            
            if ($stmt->execute()) {
                set_flash("success", "Member '{$name}' was successfully added!");
            } else {
                set_flash("danger", "Failed to add member: " . $stmt->error);
            }
            $stmt->close();
        } else {
            set_flash("warning", "Please fill in all required fields properly.");
        }
        header("Location: members.php");
        exit();
    }

    // Action: EDIT MEMBER
    if ($action === 'edit') {
        $member_id = intval($_POST['member_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $age = intval($_POST['age'] ?? 0);
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $join_date = trim($_POST['join_date'] ?? '');

        if ($member_id > 0 && !empty($name) && !empty($gender) && $age > 0 && !empty($phone)) {
            $stmt = $conn->prepare("UPDATE `Member` SET `name` = ?, `gender` = ?, `age` = ?, `phone` = ?, `email` = ?, `join_date` = ? WHERE `member_id` = ?");
            $stmt->bind_param("ssisssi", $name, $gender, $age, $phone, $email, $join_date, $member_id);

            if ($stmt->execute()) {
                set_flash("success", "Member #{$member_id} details updated successfully!");
            } else {
                set_flash("danger", "Failed to update member: " . $stmt->error);
            }
            $stmt->close();
        } else {
            set_flash("warning", "Invalid data provided for update.");
        }
        header("Location: members.php");
        exit();
    }
}

// ====================================================================
// 2. HANDLE GET REQUESTS (DELETE & VIEW)
// ====================================================================
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM `Member` WHERE `member_id` = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        set_flash("success", "Member #{$delete_id} and related records have been deleted.");
    } else {
        set_flash("danger", "Could not delete member: " . $stmt->error);
    }
    $stmt->close();
    header("Location: members.php");
    exit();
}

// Check if viewing a specific member's details
$view_member = null;
$view_memberships = null;
$view_payments = null;
if (isset($_GET['view'])) {
    $view_id = intval($_GET['view']);
    $stmt = $conn->prepare("SELECT * FROM `Member` WHERE `member_id` = ?");
    $stmt->bind_param("i", $view_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $view_member = $res->fetch_assoc();

        // Get memberships
        $m_stmt = $conn->prepare("SELECT * FROM `Membership` WHERE `member_id` = ? ORDER BY `start_date` DESC");
        $m_stmt->bind_param("i", $view_id);
        $m_stmt->execute();
        $view_memberships = $m_stmt->get_result();

        // Get payments
        $p_stmt = $conn->prepare("SELECT * FROM `Payment` WHERE `member_id` = ? ORDER BY `payment_date` DESC");
        $p_stmt->bind_param("i", $view_id);
        $p_stmt->execute();
        $view_payments = $p_stmt->get_result();
    }
}

// Fetch all members with optional server search
$search_query = trim($_GET['search'] ?? '');
if (!empty($search_query)) {
    $stmt = $conn->prepare("SELECT * FROM `Member` WHERE `name` LIKE ? OR `phone` LIKE ? OR `email` LIKE ? ORDER BY `member_id` DESC");
    $term = "%{$search_query}%";
    $stmt->bind_param("sss", $term, $term, $term);
    $stmt->execute();
    $members_list = $stmt->get_result();
} else {
    $members_list = $conn->query("SELECT * FROM `Member` ORDER BY `member_id` DESC");
}
?>

<!-- Page Header Bar -->
<div class="page-header-row">
    <div class="page-title-group">
        <h2>Members Directory</h2>
        <p>Manage gym member profiles, registrations, and contact records</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openModal('addMemberModal')">
            <span>+</span> Add Member
        </button>
    </div>
</div>

<!-- Main Table Card -->
<div class="content-card">
    <!-- Filter and Search Bar -->
    <div class="table-filter-bar">
        <div class="table-search">
            <span>🔍</span>
            <input type="text" placeholder="Filter by name, phone, email...">
        </div>
        <div style="font-size: 13px; color: var(--text-secondary);">
            Total Members: <strong><?php echo ($members_list) ? $members_list->num_rows : 0; ?></strong>
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Member ID</th>
                    <th>Name</th>
                    <th>Gender</th>
                    <th>Age</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Join Date</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($members_list && $members_list->num_rows > 0): ?>
                    <?php while ($row = $members_list->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo $row['member_id']; ?></strong></td>
                            <td style="font-weight: 600; color: var(--primary-navy);">
                                <?php echo htmlspecialchars($row['name']); ?>
                            </td>
                            <td>
                                <span class="badge <?php echo ($row['gender'] == 'Male') ? 'badge-navy' : 'badge-orange'; ?>">
                                    <?php echo htmlspecialchars($row['gender']); ?>
                                </span>
                            </td>
                            <td><?php echo $row['age']; ?> yrs</td>
                            <td><?php echo htmlspecialchars($row['phone']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo format_date($row['join_date']); ?></td>
                            <td style="text-align: right;">
                                <div class="action-buttons" style="justify-content: flex-end;">
                                    <!-- View Action -->
                                    <a href="members.php?view=<?php echo $row['member_id']; ?>" class="btn-action btn-view" title="View Details">
                                        View
                                    </a>
                                    <!-- Edit Action -->
                                    <button type="button" class="btn-action btn-edit" title="Edit Member" onclick="populateEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                        Edit
                                    </button>
                                    <!-- Delete Action -->
                                    <a href="javascript:void(0);" class="btn-action btn-delete" title="Delete Member" onclick="confirmDelete('members.php?delete=<?php echo $row['member_id']; ?>', '<?php echo addslashes($row['name']); ?>')">
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            <div class="empty-state-icon">👥</div>
                            <p>No members found in database.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ====================================================================
     MODAL: ADD NEW MEMBER
     ==================================================================== -->
<div id="addMemberModal" class="modal">
    <div class="modal-dialog">
        <form action="members.php" method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="modal-header">
                <h3>Add New Member</h3>
                <button type="button" class="modal-close" onclick="closeModal('addMemberModal')">&times;</button>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label for="add_name">Full Name <span class="required">*</span></label>
                    <input type="text" id="add_name" name="name" class="form-control" placeholder="e.g. Rahul Sharma" required>
                </div>

                <div class="form-row">
                    <div class="form-col form-group">
                        <label for="add_gender">Gender <span class="required">*</span></label>
                        <select id="add_gender" name="gender" class="form-control" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-col form-group">
                        <label for="add_age">Age <span class="required">*</span></label>
                        <input type="number" id="add_age" name="age" class="form-control" placeholder="e.g. 24" min="12" max="100" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col form-group">
                        <label for="add_phone">Phone Number <span class="required">*</span></label>
                        <input type="text" id="add_phone" name="phone" class="form-control" placeholder="e.g. 9876543210" required>
                    </div>
                    <div class="form-col form-group">
                        <label for="add_email">Email Address <span class="required">*</span></label>
                        <input type="email" id="add_email" name="email" class="form-control" placeholder="e.g. rahul@example.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="add_join_date">Join Date <span class="required">*</span></label>
                    <input type="date" id="add_join_date" name="join_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addMemberModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Member</button>
            </div>
        </form>
    </div>
</div>

<!-- ====================================================================
     MODAL: EDIT MEMBER
     ==================================================================== -->
<div id="editMemberModal" class="modal">
    <div class="modal-dialog">
        <form action="members.php" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="edit_id" name="member_id" value="">

            <div class="modal-header">
                <h3>Edit Member Details</h3>
                <button type="button" class="modal-close" onclick="closeModal('editMemberModal')">&times;</button>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_name">Full Name <span class="required">*</span></label>
                    <input type="text" id="edit_name" name="name" class="form-control" required>
                </div>

                <div class="form-row">
                    <div class="form-col form-group">
                        <label for="edit_gender">Gender <span class="required">*</span></label>
                        <select id="edit_gender" name="gender" class="form-control" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-col form-group">
                        <label for="edit_age">Age <span class="required">*</span></label>
                        <input type="number" id="edit_age" name="age" class="form-control" min="12" max="100" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col form-group">
                        <label for="edit_phone">Phone Number <span class="required">*</span></label>
                        <input type="text" id="edit_phone" name="phone" class="form-control" required>
                    </div>
                    <div class="form-col form-group">
                        <label for="edit_email">Email Address <span class="required">*</span></label>
                        <input type="email" id="edit_email" name="email" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_join_date">Join Date <span class="required">*</span></label>
                    <input type="date" id="edit_join_date" name="join_date" class="form-control" required>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editMemberModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- ====================================================================
     MODAL: VIEW MEMBER DETAILS
     ==================================================================== -->
<?php if ($view_member): ?>
<div id="viewMemberModal" class="modal active">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3>Member Profile: <?php echo htmlspecialchars($view_member['name']); ?> (#<?php echo $view_member['member_id']; ?>)</h3>
            <button type="button" class="modal-close" onclick="window.location.href='members.php'">&times;</button>
        </div>

        <div class="modal-body">
            <!-- Member Bio -->
            <div class="detail-list">
                <div class="detail-item">
                    <span class="label">Full Name</span>
                    <span class="val"><?php echo htmlspecialchars($view_member['name']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Gender / Age</span>
                    <span class="val"><?php echo htmlspecialchars($view_member['gender']); ?>, <?php echo $view_member['age']; ?> yrs</span>
                </div>
                <div class="detail-item">
                    <span class="label">Phone</span>
                    <span class="val"><?php echo htmlspecialchars($view_member['phone']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Email</span>
                    <span class="val"><?php echo htmlspecialchars($view_member['email']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Date Joined</span>
                    <span class="val"><?php echo format_date($view_member['join_date']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Member ID</span>
                    <span class="val">#<?php echo $view_member['member_id']; ?></span>
                </div>
            </div>

            <!-- Memberships History -->
            <h4 style="margin: 18px 0 8px 0; font-size: 14px; color: var(--primary-navy);">Membership Subscriptions</h4>
            <div class="table-responsive">
                <table class="data-table" style="font-size: 12.5px;">
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($view_memberships && $view_memberships->num_rows > 0): ?>
                            <?php while ($m = $view_memberships->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($m['plan_name']); ?></strong></td>
                                    <td><?php echo format_date($m['start_date']); ?></td>
                                    <td><?php echo format_date($m['end_date']); ?></td>
                                    <td><?php echo format_currency($m['amount']); ?></td>
                                    <td>
                                        <?php if (strtotime($m['end_date']) >= strtotime(date('Y-m-d'))): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Expired</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="empty-state" style="padding:15px;">No membership records found for this member.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Payments History -->
            <h4 style="margin: 18px 0 8px 0; font-size: 14px; color: var(--primary-navy);">Payment History</h4>
            <div class="table-responsive">
                <table class="data-table" style="font-size: 12.5px;">
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($view_payments && $view_payments->num_rows > 0): ?>
                            <?php while ($p = $view_payments->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $p['payment_id']; ?></td>
                                    <td><?php echo format_date($p['payment_date']); ?></td>
                                    <td style="font-weight:600;"><?php echo format_currency($p['amount']); ?></td>
                                    <td><span class="badge badge-navy"><?php echo htmlspecialchars($p['payment_method']); ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="empty-state" style="padding:15px;">No payments recorded.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-navy" onclick="window.location.href='members.php'">Close</button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function populateEditModal(data) {
    document.getElementById('edit_id').value = data.member_id;
    document.getElementById('edit_name').value = data.name;
    document.getElementById('edit_gender').value = data.gender;
    document.getElementById('edit_age').value = data.age;
    document.getElementById('edit_phone').value = data.phone;
    document.getElementById('edit_email').value = data.email;
    document.getElementById('edit_join_date').value = data.join_date;
    openModal('editMemberModal');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
