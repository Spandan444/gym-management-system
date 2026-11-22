<?php
/**
 * Login Page - GYM MANAGEMENT SYSTEM
 * Course: B.Tech DBMS Mini-Project
 */
require_once __DIR__ . '/db.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit();
}
if (isset($_SESSION['member_logged_in']) && $_SESSION['member_logged_in'] === true) {
    header("Location: user/dashboard.php");
    exit();
}

$error = "";

// Handle Login Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        // Query Users table using prepared statement
        $stmt = $conn->prepare("SELECT * FROM `Users` WHERE `username` = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Use password_verify since passwords are now hashed
                if (password_verify($password, $user['password'])) {
                    if ($user['role'] === 'admin') {
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['admin_id'] = $user['user_id'];
                        $_SESSION['admin_username'] = $user['username'];
                        // Admin name fallback
                        $_SESSION['admin_name'] = 'Gym Administrator';

                        set_flash("success", "Welcome back, Admin!");
                        header("Location: dashboard.php");
                        exit();
                    } else if ($user['role'] === 'member') {
                        $_SESSION['member_logged_in'] = true;
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['member_id'] = $user['member_id'];
                        $_SESSION['username'] = $user['username'];
                        
                        // Fetch member name
                        $m_stmt = $conn->prepare("SELECT name FROM Member WHERE member_id = ?");
                        $m_stmt->bind_param("i", $user['member_id']);
                        $m_stmt->execute();
                        $m_res = $m_stmt->get_result();
                        if ($m_res && $m_res->num_rows === 1) {
                            $m_data = $m_res->fetch_assoc();
                            $_SESSION['member_name'] = $m_data['name'];
                        } else {
                            $_SESSION['member_name'] = 'Member';
                        }
                        
                        set_flash("success", "Welcome, " . htmlspecialchars($_SESSION['member_name']) . "!");
                        header("Location: user/dashboard.php");
                        exit();
                    }
                } else {
                    $error = "Incorrect password. Please try again.";
                }
            } else {
                $error = "Invalid username or password.";
            }
            $stmt->close();
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GYM MANAGEMENT SYSTEM</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-body">

<div class="login-card">
    <div class="login-header">
        <div class="login-brand-icon">🏋️</div>
        <h2>GYM MANAGEMENT</h2>
        <p>B.Tech DBMS Mini-Project Portal</p>
    </div>

    <div class="login-content">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="margin-bottom: 16px;">
                <span>❌ <?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php display_flash(); ?>

        <?php if (!getenv('VERCEL')): ?>
            <div class="login-hint">
                <strong>🔑 Demo Admin Credentials:</strong><br>
                Username: <code>admin</code> &nbsp;|&nbsp; Password: <code>admin123</code>
            </div>
        <?php endif; ?>

        <form action="index.php" method="POST">
            <div class="form-group">
                <label for="username">Username <span class="required">*</span></label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" required autofocus value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : (!getenv('VERCEL') ? 'admin' : ''); ?>">
            </div>

            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required value="<?php echo !getenv('VERCEL') ? 'admin123' : ''; ?>">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; margin-top: 10px; font-size: 14px;">
                Login to Dashboard &rarr;
            </button>
        </form>
    </div>
</div>

<script src="js/script.js"></script>
</body>
</html>
