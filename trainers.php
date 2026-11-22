<?php
/**
 * Trainer Management Page - GYM MANAGEMENT SYSTEM
 * Course: B.Tech DBMS Mini-Project
 * CRUD Operations: Create, Read, Update, Delete for Trainers
 */
$page_title = "Trainer Management";
require_once __DIR__ . '/header.php';

// ====================================================================
// 1. HANDLE POST REQUESTS (CREATE & UPDATE)
// ====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Action: ADD TRAINER
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $specialization = trim($_POST['specialization'] ?? '');
        $salary = floatval($_POST['salary'] ?? 0);

        if (!empty($name) && !empty($phone) && !empty($specialization) && $salary >= 0) {
            $stmt = $conn->prepare("INSERT INTO `Trainer` (`name`, `phone`, `specialization`, `salary`) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssd", $name, $phone, $specialization, $salary);

            if ($stmt->execute()) {
                set_flash("success", "Trainer '{$name}' was successfully registered!");
            } else {
                set_flash("danger", "Failed to add trainer: " . $stmt->error);
            }
            $stmt->close();
        } else {
            set_flash("warning", "Please provide all required trainer information.");
        }
        header("Location: trainers.php");
        exit();
    }

    // Action: EDIT TRAINER
    if ($action === 'edit') {
        $trainer_id = intval($_POST['trainer_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $specialization = trim($_POST['specialization'] ?? '');
        $salary = floatval($_POST['salary'] ?? 0);

        if ($trainer_id > 0 && !empty($name) && !empty($phone) && !empty($specialization)) {
            $stmt = $conn->prepare("UPDATE `Trainer` SET `name` = ?, `phone` = ?, `specialization` = ?, `salary` = ? WHERE `trainer_id` = ?");
            $stmt->bind_param("sssdi", $name, $phone, $specialization, $salary, $trainer_id);

            if ($stmt->execute()) {
                set_flash("success", "Trainer #{$trainer_id} details updated successfully!");
            } else {
                set_flash("danger", "Failed to update trainer: " . $stmt->error);
            }
            $stmt->close();
        } else {
            set_flash("warning", "Invalid data provided for trainer update.");
        }
        header("Location: trainers.php");
        exit();
    }
}

// ====================================================================
// 2. HANDLE DELETE REQUEST
// ====================================================================
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM `Trainer` WHERE `trainer_id` = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        set_flash("success", "Trainer #{$delete_id} was removed from the system.");
    } else {
        set_flash("danger", "Could not delete trainer: " . $stmt->error);
    }
    $stmt->close();
    header("Location: trainers.php");
    exit();
}

// Fetch all trainers
$trainers_list = $conn->query("SELECT * FROM `Trainer` ORDER BY `trainer_id` DESC");
?>

<!-- Page Header Bar -->
<div class="page-header-row">
    <div class="page-title-group">
        <h2>Trainers Directory</h2>
        <p>Manage gym fitness instructors, coaching specializations, and payroll</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openModal('addTrainerModal')">
            <span>+</span> Add Trainer
        </button>
    </div>
</div>

<!-- Main Table Card -->
<div class="content-card">
    <!-- Filter and Search Bar -->
    <div class="table-filter-bar">
        <div class="table-search">
            <span>🔍</span>
            <input type="text" placeholder="Filter by trainer name, skill, phone...">
        </div>
        <div style="font-size: 13px; color: var(--text-secondary);">
            Total Staff: <strong><?php echo ($trainers_list) ? $trainers_list->num_rows : 0; ?></strong>
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Trainer ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Specialization</th>
                    <th>Monthly Salary</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($trainers_list && $trainers_list->num_rows > 0): ?>
                    <?php while ($row = $trainers_list->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo $row['trainer_id']; ?></strong></td>
                            <td style="font-weight: 600; color: var(--primary-navy);">
                                <?php echo htmlspecialchars($row['name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['phone']); ?></td>
                            <td>
                                <span class="badge badge-orange">
                                    <?php echo htmlspecialchars($row['specialization']); ?>
                                </span>
                            </td>
                            <td style="font-weight: 600; color: #047857;">
                                <?php echo format_currency($row['salary']); ?>
                            </td>
                            <td style="text-align: right;">
                                <div class="action-buttons" style="justify-content: flex-end;">
                                    <!-- Edit Action -->
                                    <button type="button" class="btn-action btn-edit" title="Edit Trainer" onclick="populateEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                        Edit
                                    </button>
                                    <!-- Delete Action -->
                                    <a href="javascript:void(0);" class="btn-action btn-delete" title="Delete Trainer" onclick="confirmDelete('trainers.php?delete=<?php echo $row['trainer_id']; ?>', '<?php echo addslashes($row['name']); ?>')">
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty-state">
                            <div class="empty-state-icon">💪</div>
                            <p>No trainers found. Click 'Add Trainer' to register instructors.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ====================================================================
     MODAL: ADD NEW TRAINER
     ==================================================================== -->
<div id="addTrainerModal" class="modal">
    <div class="modal-dialog">
        <form action="trainers.php" method="POST">
            <input type="hidden" name="action" value="add">

            <div class="modal-header">
                <h3>Register New Trainer</h3>
                <button type="button" class="modal-close" onclick="closeModal('addTrainerModal')">&times;</button>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label for="add_name">Trainer Name <span class="required">*</span></label>
                    <input type="text" id="add_name" name="name" class="form-control" placeholder="e.g. Marcus Vance" required>
                </div>

                <div class="form-row">
                    <div class="form-col form-group">
                        <label for="add_phone">Phone Number <span class="required">*</span></label>
                        <input type="text" id="add_phone" name="phone" class="form-control" placeholder="e.g. 9812300001" required>
                    </div>
                    <div class="form-col form-group">
                        <label for="add_salary">Salary (Monthly ₹) <span class="required">*</span></label>
                        <input type="number" id="add_salary" name="salary" class="form-control" placeholder="e.g. 40000" min="0" step="500" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="add_specialization">Fitness Specialization <span class="required">*</span></label>
                    <input type="text" id="add_specialization" name="specialization" class="form-control" placeholder="e.g. Strength & Conditioning, CrossFit, HIIT" required>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addTrainerModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Trainer</button>
            </div>
        </form>
    </div>
</div>

<!-- ====================================================================
     MODAL: EDIT TRAINER
     ==================================================================== -->
<div id="editTrainerModal" class="modal">
    <div class="modal-dialog">
        <form action="trainers.php" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="edit_trainer_id" name="trainer_id" value="">

            <div class="modal-header">
                <h3>Edit Trainer Details</h3>
                <button type="button" class="modal-close" onclick="closeModal('editTrainerModal')">&times;</button>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_name">Trainer Name <span class="required">*</span></label>
                    <input type="text" id="edit_name" name="name" class="form-control" required>
                </div>

                <div class="form-row">
                    <div class="form-col form-group">
                        <label for="edit_phone">Phone Number <span class="required">*</span></label>
                        <input type="text" id="edit_phone" name="phone" class="form-control" required>
                    </div>
                    <div class="form-col form-group">
                        <label for="edit_salary">Monthly Salary (₹) <span class="required">*</span></label>
                        <input type="number" id="edit_salary" name="salary" class="form-control" min="0" step="500" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_specialization">Specialization <span class="required">*</span></label>
                    <input type="text" id="edit_specialization" name="specialization" class="form-control" required>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editTrainerModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Trainer</button>
            </div>
        </form>
    </div>
</div>

<script>
function populateEditModal(data) {
    document.getElementById('edit_trainer_id').value = data.trainer_id;
    document.getElementById('edit_name').value = data.name;
    document.getElementById('edit_phone').value = data.phone;
    document.getElementById('edit_specialization').value = data.specialization;
    document.getElementById('edit_salary').value = parseFloat(data.salary);
    openModal('editTrainerModal');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
