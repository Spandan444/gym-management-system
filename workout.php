<?php
/**
 * Workout Plan Management Page - GYM MANAGEMENT SYSTEM
 * Course: B.Tech DBMS Mini-Project
 * CRUD Operations: Create, Read, Update, Delete for Workout Plans
 */
$page_title = "Workout Plans";
require_once __DIR__ . '/header.php';

// ====================================================================
// 1. HANDLE POST REQUESTS (CREATE & UPDATE)
// ====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Action: ADD WORKOUT PLAN
    if ($action === 'add') {
        $member_id = intval($_POST['member_id'] ?? 0);
        $trainer_id = intval($_POST['trainer_id'] ?? 0);
        $workout_name = trim($_POST['workout_name'] ?? '');
        $workout_date = trim($_POST['workout_date'] ?? date('Y-m-d'));

        if ($member_id > 0 && $trainer_id > 0 && !empty($workout_name) && !empty($workout_date)) {
            $stmt = $conn->prepare("INSERT INTO `Workout_Plan` (`member_id`, `trainer_id`, `workout_name`, `workout_date`) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $member_id, $trainer_id, $workout_name, $workout_date);

            if ($stmt->execute()) {
                set_flash("success", "Workout plan '{$workout_name}' assigned successfully!");
            } else {
                set_flash("danger", "Failed to assign workout plan: " . $stmt->error);
            }
            $stmt->close();
        } else {
            set_flash("warning", "Please select a member, trainer, and enter a workout routine.");
        }
        header("Location: workout.php");
        exit();
    }

    // Action: EDIT WORKOUT PLAN
    if ($action === 'edit') {
        $workout_id = intval($_POST['workout_id'] ?? 0);
        $member_id = intval($_POST['member_id'] ?? 0);
        $trainer_id = intval($_POST['trainer_id'] ?? 0);
        $workout_name = trim($_POST['workout_name'] ?? '');
        $workout_date = trim($_POST['workout_date'] ?? '');

        if ($workout_id > 0 && $member_id > 0 && $trainer_id > 0 && !empty($workout_name)) {
            $stmt = $conn->prepare("UPDATE `Workout_Plan` SET `member_id` = ?, `trainer_id` = ?, `workout_name` = ?, `workout_date` = ? WHERE `workout_id` = ?");
            $stmt->bind_param("iissi", $member_id, $trainer_id, $workout_name, $workout_date, $workout_id);

            if ($stmt->execute()) {
                set_flash("success", "Workout plan #{$workout_id} updated successfully!");
            } else {
                set_flash("danger", "Failed to update workout plan: " . $stmt->error);
            }
            $stmt->close();
        } else {
            set_flash("warning", "Invalid data provided for workout plan update.");
        }
        header("Location: workout.php");
        exit();
    }
}

// ====================================================================
// 2. HANDLE DELETE REQUEST
// ====================================================================
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM `Workout_Plan` WHERE `workout_id` = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        set_flash("success", "Workout plan #{$delete_id} deleted.");
    } else {
        set_flash("danger", "Could not delete workout plan: " . $stmt->error);
    }
    $stmt->close();
    header("Location: workout.php");
    exit();
}

// Fetch members and trainers for dropdowns
$members_dropdown = $conn->query("SELECT `member_id`, `name` FROM `Member` ORDER BY `name` ASC");
$trainers_dropdown = $conn->query("SELECT `trainer_id`, `name`, `specialization` FROM `Trainer` ORDER BY `name` ASC");

// Fetch workout plans with member and trainer names using multiple SQL JOINs
$workout_query = "
    SELECT w.*, m.name AS member_name, m.phone AS member_phone, t.name AS trainer_name, t.specialization 
    FROM `Workout_Plan` w 
    JOIN `Member` m ON w.member_id = m.member_id 
    JOIN `Trainer` t ON w.trainer_id = t.trainer_id 
    ORDER BY w.workout_date DESC, w.workout_id DESC
";
$workout_list = $conn->query($workout_query);
?>

<!-- Page Header Bar -->
<div class="page-header-row">
    <div class="page-title-group">
        <h2>Workout Plans</h2>
        <p>Custom training programs prescribed by trainers to athletes</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openModal('addWorkoutModal')">
            <span>+</span> Assign Workout Plan
        </button>
    </div>
</div>

<!-- Main Table Card -->
<div class="content-card">
    <div class="table-filter-bar">
        <div class="table-search">
            <span>🔍</span>
            <input type="text" placeholder="Filter by routine, member, trainer...">
        </div>
        <div style="font-size: 13px; color: var(--text-secondary);">
            Assigned Plans: <strong><?php echo ($workout_list) ? $workout_list->num_rows : 0; ?></strong>
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Workout ID</th>
                    <th>Workout Routine</th>
                    <th>Member Name</th>
                    <th>Assigned Trainer</th>
                    <th>Scheduled Date</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($workout_list && $workout_list->num_rows > 0): ?>
                    <?php while ($row = $workout_list->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo $row['workout_id']; ?></strong></td>
                            <td style="font-weight: 600; color: var(--primary-navy);">
                                <?php echo htmlspecialchars($row['workout_name']); ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['member_name']); ?></strong>
                                <span style="font-size: 11px; color: var(--text-muted); display: block;">ID: #<?php echo $row['member_id']; ?></span>
                            </td>
                            <td>
                                <span><?php echo htmlspecialchars($row['trainer_name']); ?></span>
                                <span class="badge badge-orange" style="margin-left: 4px; font-size: 10px;">
                                    <?php echo htmlspecialchars($row['specialization']); ?>
                                </span>
                            </td>
                            <td><?php echo format_date($row['workout_date']); ?></td>
                            <td style="text-align: right;">
                                <div class="action-buttons" style="justify-content: flex-end;">
                                    <button type="button" class="btn-action btn-edit" title="Edit Plan" onclick="populateEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                        Edit
                                    </button>
                                    <a href="javascript:void(0);" class="btn-action btn-delete" title="Delete Plan" onclick="confirmDelete('workout.php?delete=<?php echo $row['workout_id']; ?>', 'Workout #<?php echo $row['workout_id']; ?>')">
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty-state">
                            <div class="empty-state-icon">📋</div>
                            <p>No workout plans assigned yet. Click 'Assign Workout Plan' to start.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ====================================================================
     MODAL: ASSIGN NEW WORKOUT PLAN
     ==================================================================== -->
<div id="addWorkoutModal" class="modal">
    <div class="modal-dialog">
        <form action="workout.php" method="POST">
            <input type="hidden" name="action" value="add">

            <div class="modal-header">
                <h3>Assign New Workout Plan</h3>
                <button type="button" class="modal-close" onclick="closeModal('addWorkoutModal')">&times;</button>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label for="add_w_member">Select Member <span class="required">*</span></label>
                    <select id="add_w_member" name="member_id" class="form-control" required>
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

                <div class="form-group">
                    <label for="add_w_trainer">Assigning Trainer <span class="required">*</span></label>
                    <select id="add_w_trainer" name="trainer_id" class="form-control" required>
                        <option value="">-- Choose Trainer --</option>
                        <?php 
                        if ($trainers_dropdown && $trainers_dropdown->num_rows > 0) {
                            $trainers_dropdown->data_seek(0);
                            while ($t = $trainers_dropdown->fetch_assoc()) {
                                echo "<option value='{$t['trainer_id']}'>" . htmlspecialchars($t['name']) . " (" . htmlspecialchars($t['specialization']) . ")</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="add_w_name">Workout Routine Name <span class="required">*</span></label>
                    <input type="text" id="add_w_name" name="workout_name" class="form-control" placeholder="e.g. Chest & Triceps Hypertrophy, Cardio HIIT" required>
                </div>

                <div class="form-group">
                    <label for="add_w_date">Scheduled Date <span class="required">*</span></label>
                    <input type="date" id="add_w_date" name="workout_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addWorkoutModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Workout Plan</button>
            </div>
        </form>
    </div>
</div>

<!-- ====================================================================
     MODAL: EDIT WORKOUT PLAN
     ==================================================================== -->
<div id="editWorkoutModal" class="modal">
    <div class="modal-dialog">
        <form action="workout.php" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="edit_workout_id" name="workout_id" value="">

            <div class="modal-header">
                <h3>Edit Workout Plan</h3>
                <button type="button" class="modal-close" onclick="closeModal('editWorkoutModal')">&times;</button>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_w_member">Member <span class="required">*</span></label>
                    <select id="edit_w_member" name="member_id" class="form-control" required>
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

                <div class="form-group">
                    <label for="edit_w_trainer">Trainer <span class="required">*</span></label>
                    <select id="edit_w_trainer" name="trainer_id" class="form-control" required>
                        <?php 
                        if ($trainers_dropdown && $trainers_dropdown->num_rows > 0) {
                            $trainers_dropdown->data_seek(0);
                            while ($t = $trainers_dropdown->fetch_assoc()) {
                                echo "<option value='{$t['trainer_id']}'>" . htmlspecialchars($t['name']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_w_name">Workout Routine Name <span class="required">*</span></label>
                    <input type="text" id="edit_w_name" name="workout_name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="edit_w_date">Scheduled Date <span class="required">*</span></label>
                    <input type="date" id="edit_w_date" name="workout_date" class="form-control" required>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editWorkoutModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Plan</button>
            </div>
        </form>
    </div>
</div>

<script>
function populateEditModal(data) {
    document.getElementById('edit_workout_id').value = data.workout_id;
    document.getElementById('edit_w_member').value = data.member_id;
    document.getElementById('edit_w_trainer').value = data.trainer_id;
    document.getElementById('edit_w_name').value = data.workout_name;
    document.getElementById('edit_w_date').value = data.workout_date;
    openModal('editWorkoutModal');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
