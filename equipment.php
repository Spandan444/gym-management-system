<?php
/**
 * Equipment Management Page - GYM MANAGEMENT SYSTEM
 * Course: B.Tech DBMS Mini-Project
 * CRUD Operations: Create, Read, Update, Delete for Gym Equipment
 */
$page_title = "Equipment Management";
require_once __DIR__ . '/header.php';

// ====================================================================
// 1. HANDLE POST REQUESTS (CREATE & UPDATE)
// ====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Action: ADD EQUIPMENT
    if ($action === 'add') {
        $equipment_name = trim($_POST['equipment_name'] ?? '');
        $quantity = intval($_POST['quantity'] ?? 1);
        $purchase_date = trim($_POST['purchase_date'] ?? date('Y-m-d'));
        $condition_status = trim($_POST['condition_status'] ?? 'Working');

        if (!empty($equipment_name) && $quantity > 0 && !empty($purchase_date)) {
            $stmt = $conn->prepare("INSERT INTO `Equipment` (`equipment_name`, `quantity`, `purchase_date`, `condition_status`) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("siss", $equipment_name, $quantity, $purchase_date, $condition_status);

            if ($stmt->execute()) {
                set_flash("success", "Equipment '{$equipment_name}' added to inventory!");
            } else {
                set_flash("danger", "Failed to add equipment: " . $stmt->error);
            }
            $stmt->close();
        } else {
            set_flash("warning", "Please provide a valid equipment name, quantity, and date.");
        }
        header("Location: equipment.php");
        exit();
    }

    // Action: EDIT EQUIPMENT
    if ($action === 'edit') {
        $equipment_id = intval($_POST['equipment_id'] ?? 0);
        $equipment_name = trim($_POST['equipment_name'] ?? '');
        $quantity = intval($_POST['quantity'] ?? 1);
        $purchase_date = trim($_POST['purchase_date'] ?? '');
        $condition_status = trim($_POST['condition_status'] ?? 'Working');

        if ($equipment_id > 0 && !empty($equipment_name) && $quantity > 0) {
            $stmt = $conn->prepare("UPDATE `Equipment` SET `equipment_name` = ?, `quantity` = ?, `purchase_date` = ?, `condition_status` = ? WHERE `equipment_id` = ?");
            $stmt->bind_param("sissi", $equipment_name, $quantity, $purchase_date, $condition_status, $equipment_id);

            if ($stmt->execute()) {
                set_flash("success", "Equipment #{$equipment_id} updated successfully!");
            } else {
                set_flash("danger", "Failed to update equipment: " . $stmt->error);
            }
            $stmt->close();
        } else {
            set_flash("warning", "Invalid data provided for equipment update.");
        }
        header("Location: equipment.php");
        exit();
    }
}

// ====================================================================
// 2. HANDLE DELETE REQUEST
// ====================================================================
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM `Equipment` WHERE `equipment_id` = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        set_flash("success", "Equipment record #{$delete_id} deleted.");
    } else {
        set_flash("danger", "Could not delete equipment: " . $stmt->error);
    }
    $stmt->close();
    header("Location: equipment.php");
    exit();
}

// Fetch all equipment records
$equipment_list = $conn->query("SELECT * FROM `Equipment` ORDER BY `equipment_id` DESC");
?>

<!-- Page Header Bar -->
<div class="page-header-row">
    <div class="page-title-group">
        <h2>Gym Equipment Inventory</h2>
        <p>Monitor gym machines, weights, maintenance conditions, and asset inventory</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openModal('addEquipmentModal')">
            <span>+</span> Add Equipment
        </button>
    </div>
</div>

<!-- Main Table Card -->
<div class="content-card">
    <div class="table-filter-bar">
        <div class="table-search">
            <span>🔍</span>
            <input type="text" placeholder="Filter by equipment name, condition...">
        </div>
        <div style="font-size: 13px; color: var(--text-secondary);">
            Items: <strong><?php echo ($equipment_list) ? $equipment_list->num_rows : 0; ?></strong>
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Equipment ID</th>
                    <th>Equipment Name</th>
                    <th>Quantity</th>
                    <th>Purchase Date</th>
                    <th>Condition Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($equipment_list && $equipment_list->num_rows > 0): ?>
                    <?php while ($row = $equipment_list->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo $row['equipment_id']; ?></strong></td>
                            <td style="font-weight: 600; color: var(--primary-navy);">
                                <?php echo htmlspecialchars($row['equipment_name']); ?>
                            </td>
                            <td>
                                <span class="badge badge-navy" style="font-size: 12px; padding: 4px 10px;">
                                    <?php echo $row['quantity']; ?> units
                                </span>
                            </td>
                            <td><?php echo format_date($row['purchase_date']); ?></td>
                            <td>
                                <?php 
                                $status = $row['condition_status'];
                                if ($status === 'Working') {
                                    echo '<span class="badge badge-success">✓ Working</span>';
                                } elseif ($status === 'Under Maintenance') {
                                    echo '<span class="badge badge-warning">⚠️ Under Maintenance</span>';
                                } else {
                                    echo '<span class="badge badge-danger">⚡ Needs Repair</span>';
                                }
                                ?>
                            </td>
                            <td style="text-align: right;">
                                <div class="action-buttons" style="justify-content: flex-end;">
                                    <button type="button" class="btn-action btn-edit" title="Edit Item" onclick="populateEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                        Edit
                                    </button>
                                    <a href="javascript:void(0);" class="btn-action btn-delete" title="Delete Item" onclick="confirmDelete('equipment.php?delete=<?php echo $row['equipment_id']; ?>', '<?php echo addslashes($row['equipment_name']); ?>')">
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty-state">
                            <div class="empty-state-icon">⚙️</div>
                            <p>No equipment recorded. Click 'Add Equipment' to record inventory.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ====================================================================
     MODAL: ADD NEW EQUIPMENT
     ==================================================================== -->
<div id="addEquipmentModal" class="modal">
    <div class="modal-dialog">
        <form action="equipment.php" method="POST">
            <input type="hidden" name="action" value="add">

            <div class="modal-header">
                <h3>Add New Equipment</h3>
                <button type="button" class="modal-close" onclick="closeModal('addEquipmentModal')">&times;</button>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label for="add_eq_name">Equipment Name <span class="required">*</span></label>
                    <input type="text" id="add_eq_name" name="equipment_name" class="form-control" placeholder="e.g. Commercial Treadmill T-900, Olympic Barbell" required>
                </div>

                <div class="form-row">
                    <div class="form-col form-group">
                        <label for="add_eq_qty">Quantity (Units) <span class="required">*</span></label>
                        <input type="number" id="add_eq_qty" name="quantity" class="form-control" value="1" min="1" max="1000" required>
                    </div>
                    <div class="form-col form-group">
                        <label for="add_eq_date">Purchase Date <span class="required">*</span></label>
                        <input type="date" id="add_eq_date" name="purchase_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="add_eq_status">Condition Status <span class="required">*</span></label>
                    <select id="add_eq_status" name="condition_status" class="form-control" required>
                        <option value="Working">Working (Operational)</option>
                        <option value="Under Maintenance">Under Maintenance</option>
                        <option value="Needs Repair">Needs Repair</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addEquipmentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Equipment</button>
            </div>
        </form>
    </div>
</div>

<!-- ====================================================================
     MODAL: EDIT EQUIPMENT
     ==================================================================== -->
<div id="editEquipmentModal" class="modal">
    <div class="modal-dialog">
        <form action="equipment.php" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="edit_equipment_id" name="equipment_id" value="">

            <div class="modal-header">
                <h3>Edit Equipment Details</h3>
                <button type="button" class="modal-close" onclick="closeModal('editEquipmentModal')">&times;</button>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_eq_name">Equipment Name <span class="required">*</span></label>
                    <input type="text" id="edit_eq_name" name="equipment_name" class="form-control" required>
                </div>

                <div class="form-row">
                    <div class="form-col form-group">
                        <label for="edit_eq_qty">Quantity <span class="required">*</span></label>
                        <input type="number" id="edit_eq_qty" name="quantity" class="form-control" min="1" max="1000" required>
                    </div>
                    <div class="form-col form-group">
                        <label for="edit_eq_date">Purchase Date <span class="required">*</span></label>
                        <input type="date" id="edit_eq_date" name="purchase_date" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_eq_status">Condition Status <span class="required">*</span></label>
                    <select id="edit_eq_status" name="condition_status" class="form-control" required>
                        <option value="Working">Working</option>
                        <option value="Under Maintenance">Under Maintenance</option>
                        <option value="Needs Repair">Needs Repair</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editEquipmentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Equipment</button>
            </div>
        </form>
    </div>
</div>

<script>
function populateEditModal(data) {
    document.getElementById('edit_equipment_id').value = data.equipment_id;
    document.getElementById('edit_eq_name').value = data.equipment_name;
    document.getElementById('edit_eq_qty').value = data.quantity;
    document.getElementById('edit_eq_date').value = data.purchase_date;
    document.getElementById('edit_eq_status').value = data.condition_status;
    openModal('editEquipmentModal');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
