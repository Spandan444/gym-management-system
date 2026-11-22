<?php
$page_title = "My Profile";
require_once __DIR__ . '/user_header.php';
$member_id = $_SESSION['member_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = sanitize($conn, $_POST['phone']);
    $email = sanitize($conn, $_POST['email']);
    
    $upd_stmt = $conn->prepare("UPDATE Member SET phone = ?, email = ? WHERE member_id = ?");
    $upd_stmt->bind_param("ssi", $phone, $email, $member_id);
    if ($upd_stmt->execute()) {
        set_flash("success", "Profile updated successfully!");
    } else {
        set_flash("danger", "Failed to update profile.");
    }
    header("Location: profile.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM Member WHERE member_id = ?");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();
?>

<div class="card">
    <div class="card-header">
        <h2>My Information</h2>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="form-group">
                <label>Member ID</label>
                <input type="text" class="form-control" value="<?php echo $member['member_id']; ?>" disabled>
            </div>
            <div class="form-group">
                <label>Name</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($member['name']); ?>" disabled>
            </div>
            <div class="form-group">
                <label>Gender</label>
                <input type="text" class="form-control" value="<?php echo $member['gender']; ?>" disabled>
            </div>
            <div class="form-group">
                <label>Age</label>
                <input type="text" class="form-control" value="<?php echo $member['age']; ?>" disabled>
            </div>
            <div class="form-group">
                <label>Join Date</label>
                <input type="text" class="form-control" value="<?php echo format_date($member['join_date']); ?>" disabled>
            </div>
            
            <hr style="margin: 20px 0;">
            <p><strong>Editable Information:</strong></p>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($member['phone']); ?>" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($member['email']); ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>
    </div>
</div>

<?php require_once '../footer.php'; ?>
