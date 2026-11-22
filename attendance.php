<?php
/**
 * Attendance Management Page - GYM MANAGEMENT SYSTEM
 * Course: B.Tech DBMS Mini-Project
 * CRUD Operations: Mark, View, Filter, and Delete Daily Attendance
 */
$page_title = "Attendance Management";
require_once __DIR__ . '/header.php';

// Filter date (defaults to today or selected date)
$filter_date = $_GET['date'] ?? date('Y-m-d');
$show_all = isset($_GET['all']) && $_GET['all'] == '1';

// ====================================================================
// 1. HANDLE POST REQUEST (MARK / UPDATE ATTENDANCE)
// ====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Action: MARK ATTENDANCE
    if ($action === 'mark') {
        $member_id = intval($_POST['member_id'] ?? 0);
        $attendance_date = trim($_POST['attendance_date'] ?? date('Y-m-d'));
        $status = trim($_POST['status'] ?? 'Present');

        if ($member_id > 0 && !empty($attendance_date) && in_array($status, ['Present', 'Absent'])) {
            // Check if record already exists for this member on this date
            $check_stmt = $conn->prepare("SELECT `attendance_id` FROM `Attendance` WHERE `member_id` = ? AND `attendance_date` = ?");
            $check_stmt->bind_param("is", $member_id, $attendance_date);
            $check_stmt->execute();
            $check_res = $check_stmt->get_result();

            if ($check_res && $check_res->num_rows > 0) {
                // Update existing record
                $existing = $check_res->fetch_assoc();
                $att_id = $existing['attendance_id'];
                $update_stmt = $conn->prepare("UPDATE `Attendance` SET `status` = ? WHERE `attendance_id` = ?");
                $update_stmt->bind_param("si", $status, $att_id);
                $update_stmt->execute();
                $update_stmt->close();
                set_flash("info", "Attendance record updated to '{$status}' for Member #{$member_id}.");
            } else {
                // Insert new record
                $insert_stmt = $conn->prepare("INSERT INTO `Attendance` (`member_id`, `attendance_date`, `status`) VALUES (?, ?, ?)");
                $insert_stmt->bind_param("iss", $member_id, $attendance_date, $status);
                if ($insert_stmt->execute()) {
                    set_flash("success", "Marked Member #{$member_id} as {$status} on " . format_date($attendance_date) . ".");
                } else {
                    set_flash("danger", "Failed to mark attendance: " . $insert_stmt->error);
                }
                $insert_stmt->close();
            }
            $check_stmt->close();
        } else {
            set_flash("warning", "Please select a member, date, and status.");
        }
        header("Location: attendance.php?date=" . urlencode($attendance_date));
        exit();
    }
}

// ====================================================================
// 2. HANDLE DELETE REQUEST
// ====================================================================
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM `Attendance` WHERE `attendance_id` = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        set_flash("success", "Attendance record #{$delete_id} deleted.");
    } else {
        set_flash("danger", "Could not delete attendance record: " . $stmt->error);
    }
    $stmt->close();
    header("Location: attendance.php" . ($show_all ? "?all=1" : "?date=" . urlencode($filter_date)));
    exit();
}

// Fetch all members for the dropdown
$members_dropdown = $conn->query("SELECT `member_id`, `name`, `phone` FROM `Member` ORDER BY `name` ASC");

// Fetch attendance records with member names using SQL JOIN
if ($show_all) {
    $attendance_query = "
        SELECT a.*, m.name AS member_name, m.phone AS member_phone 
        FROM `Attendance` a 
        JOIN `Member` m ON a.member_id = m.member_id 
        ORDER BY a.attendance_date DESC, a.attendance_id DESC
    ";
    $attendance_stmt = $conn->prepare($attendance_query);
} else {
    $attendance_query = "
        SELECT a.*, m.name AS member_name, m.phone AS member_phone 
        FROM `Attendance` a 
        JOIN `Member` m ON a.member_id = m.member_id 
        WHERE a.attendance_date = ? 
        ORDER BY a.attendance_id DESC
    ";
    $attendance_stmt = $conn->prepare($attendance_query);
    $attendance_stmt->bind_param("s", $filter_date);
}

$attendance_stmt->execute();
$attendance_list = $attendance_stmt->get_result();

// Count present vs absent for the current view
$present_count = 0;
$absent_count = 0;
?>

<!-- Page Header Bar -->
<div class="page-header-row">
    <div class="page-title-group">
        <h2>Attendance Management</h2>
        <p>Log daily gym visits, track member discipline, and view attendance logs</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openModal('markAttendanceModal')">
            <span>+</span> Mark Attendance
        </button>
    </div>
</div>

<!-- Attendance Form & Filter Bar -->
<div class="content-card" style="margin-bottom: 24px;">
    <div class="card-body-padded">
        <form action="attendance.php" method="GET" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <label for="date_filter" style="font-weight: 600; color: var(--primary-navy); font-size: 13.5px;">Filter by Date:</label>
                <input type="date" id="date_filter" name="date" class="form-control" style="width: 180px;" value="<?php echo htmlspecialchars($filter_date); ?>" onchange="this.form.submit()">
                <button type="submit" class="btn btn-navy btn-sm">Filter</button>
                <a href="attendance.php?date=<?php echo date('Y-m-d'); ?>" class="btn btn-outline btn-sm">Today</a>
                <a href="attendance.php?all=1" class="btn btn-outline btn-sm <?php echo ($show_all) ? 'btn-primary' : ''; ?>">Show All Dates</a>
            </div>

            <div style="font-size: 13px; color: var(--text-secondary);">
                Active View: <strong><?php echo $show_all ? "All Dates History" : format_date($filter_date); ?></strong>
            </div>
        </form>
    </div>
</div>

<!-- Attendance Table Card -->
<div class="content-card">
    <div class="table-filter-bar">
        <div class="table-search">
            <span>🔍</span>
            <input type="text" placeholder="Filter by member name, phone...">
        </div>
        <div style="font-size: 13px; color: var(--text-secondary);">
            Records on Display: <strong><?php echo ($attendance_list) ? $attendance_list->num_rows : 0; ?></strong>
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Attendance ID</th>
                    <th>Member ID</th>
                    <th>Member Name</th>
                    <th>Attendance Date</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($attendance_list && $attendance_list->num_rows > 0): ?>
                    <?php while ($row = $attendance_list->fetch_assoc()): 
                        if ($row['status'] === 'Present') $present_count++; else $absent_count++;
                    ?>
                        <tr>
                            <td><strong>#<?php echo $row['attendance_id']; ?></strong></td>
                            <td>#<?php echo $row['member_id']; ?></td>
                            <td style="font-weight: 600; color: var(--primary-navy);">
                                <?php echo htmlspecialchars($row['member_name']); ?>
                                <span style="font-size: 11px; color: var(--text-muted); display: block;">Phone: <?php echo htmlspecialchars($row['member_phone']); ?></span>
                            </td>
                            <td><?php echo format_date($row['attendance_date']); ?></td>
                            <td>
                                <?php if ($row['status'] === 'Present'): ?>
                                    <span class="badge badge-success">✓ Present</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">&times; Absent</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <div class="action-buttons" style="justify-content: flex-end;">
                                    <a href="javascript:void(0);" class="btn-action btn-delete" title="Delete Record" onclick="confirmDelete('attendance.php?delete=<?php echo $row['attendance_id']; ?>&date=<?php echo urlencode($filter_date); ?><?php echo $show_all ? '&all=1' : ''; ?>', 'Attendance #<?php echo $row['attendance_id']; ?>')">
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="empty-state">
                            <div class="empty-state-icon">📅</div>
                            <p>No attendance records found for <?php echo $show_all ? "the system" : format_date($filter_date); ?>.</p>
                            <button type="button" class="btn btn-primary btn-sm" style="margin-top: 10px;" onclick="openModal('markAttendanceModal')">
                                + Mark Attendance Now
                            </button>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ====================================================================
     MODAL: MARK ATTENDANCE
     ==================================================================== -->
<div id="markAttendanceModal" class="modal">
    <div class="modal-dialog">
        <form action="attendance.php" method="POST">
            <input type="hidden" name="action" value="mark">

            <div class="modal-header">
                <h3>Mark Member Attendance</h3>
                <button type="button" class="modal-close" onclick="closeModal('markAttendanceModal')">&times;</button>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label for="att_member_id">Select Member <span class="required">*</span></label>
                    <select id="att_member_id" name="member_id" class="form-control" required>
                        <option value="">-- Choose Member --</option>
                        <?php 
                        if ($members_dropdown && $members_dropdown->num_rows > 0) {
                            $members_dropdown->data_seek(0);
                            while ($m = $members_dropdown->fetch_assoc()) {
                                echo "<option value='{$m['member_id']}'>" . htmlspecialchars($m['name']) . " (ID: #{$m['member_id']} - " . htmlspecialchars($m['phone']) . ")</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="att_date">Attendance Date <span class="required">*</span></label>
                    <input type="date" id="att_date" name="attendance_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label>Attendance Status <span class="required">*</span></label>
                    <div style="display: flex; gap: 20px; margin-top: 8px;">
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-weight: 500;">
                            <input type="radio" name="status" value="Present" checked>
                            <span class="badge badge-success">✓ Present</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-weight: 500;">
                            <input type="radio" name="status" value="Absent">
                            <span class="badge badge-danger">&times; Absent</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('markAttendanceModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Attendance</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
