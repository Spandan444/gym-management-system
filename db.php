<?php
/**
 * Database Configuration & Connection File
 * Course: B.Tech DBMS Mini-Project
 * Project: GYM MANAGEMENT SYSTEM
 * Tech Stack: PHP & MySQL (XAMPP)
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Credentials  (EDIT THESE WHEN DEPLOYING TO A LIVE HOST)
// Local XAMPP: host 127.0.0.1, user root, empty password, port 3307 (or 3306)
// Live host (e.g. InfinityFree): replace with the credentials shown in your hosting panel.
$db_host = "127.0.0.1";
$db_user = "root";
$db_pass = "";
$db_name = "gym_management";
$db_port = 3307;

// Establish MySQL Connection using mysqli
mysqli_report(MYSQLI_REPORT_OFF);

// Connect on the configured port, then fall back to the standard 3306 if needed
$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($conn->connect_errno && (int)$db_port !== 3306) {
    $conn = @new mysqli($db_host, $db_user, $db_pass, $db_name, 3306);
}

if ($conn->connect_errno) {
    // If database does not exist or MySQL is not running, show friendly guide
    $error_msg = $conn->connect_error ?: "Target machine actively refused connection";
    die("
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 60px auto; padding: 25px; border: 1px solid #fecaca; background: #fff1f2; border-radius: 8px; color: #991b1b;'>
        <h2 style='margin-top:0;'>⚠️ Database Connection Notice</h2>
        <p><strong>Could not connect to the MySQL database:</strong> " . htmlspecialchars($error_msg) . "</p>
        <hr style='border: 0; border-top: 1px solid #fca5a5; margin: 15px 0;'>
        <p><strong>Quick Setup Instructions for XAMPP:</strong></p>
        <ol style='line-height: 1.6;'>
            <li>Open the <strong>XAMPP Control Panel</strong> and ensure <strong>Apache</strong> and <strong>MySQL</strong> are started.</li>
            <li>Verify your MySQL port (Usually 3307 or 3306). Update <code>db.php</code> if it differs.</li>
            <li>Open your browser and navigate to <a href='http://localhost/phpmyadmin' target='_blank' style='color:#b91c1c; font-weight:bold;'>http://localhost/phpmyadmin</a></li>
            <li>Click <strong>New</strong> and create a database named <code>gym_management</code>.</li>
            <li>Select the SQL file at: <code>sql/gym_management.sql</code> and click <strong>Go / Import</strong>.</li>
            <li>Once imported, <a href='index.php' style='color:#1d4ed8; text-decoration:underline;'>refresh this page</a> to access the system.</li>
        </ol>
    </div>
    ");
}

// Set charset to utf8mb4 for unicode compatibility
$conn->set_charset("utf8mb4");

// Compute the app's base URL path so redirects work both locally
// (e.g. "/GymManagementSystem") and on a live host at the root (e.g. "")
if (!defined('APP_BASE')) {
    $__app_dir  = str_replace('\\', '/', __DIR__);
    $__doc_root = str_replace('\\', '/', rtrim((string)realpath($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\'));
    $__base = ($__doc_root !== '' && strpos($__app_dir, $__doc_root) === 0)
        ? substr($__app_dir, strlen($__doc_root))
        : '';
    define('APP_BASE', $__base);
}

/**
 * Authentication Check Helper (Admin)
 * Ensures user is authenticated before viewing protected pages
 */
function check_login() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header("Location: " . APP_BASE . "/index.php");
        exit();
    }
}

/**
 * Authentication Check Helper (Member)
 */
function check_member_login() {
    if (!isset($_SESSION['member_logged_in']) || $_SESSION['member_logged_in'] !== true) {
        header("Location: " . APP_BASE . "/index.php");
        exit();
    }
}

// Auto-create Users table and migrate data if not exists
$conn->query("CREATE TABLE IF NOT EXISTS `Users` (
    `user_id` INT AUTO_INCREMENT PRIMARY KEY,
    `member_id` INT NULL,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'member') NOT NULL DEFAULT 'member',
    CONSTRAINT `fk_user_member` FOREIGN KEY (`member_id`) REFERENCES `Member`(`member_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$user_check = $conn->query("SELECT * FROM `Users` LIMIT 1");
if ($user_check && $user_check->num_rows == 0) {
    // Insert admin
    $admin_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO `Users` (`username`, `password`, `role`) VALUES ('admin', '$admin_hash', 'admin')");
    
    // Insert members
    $members = $conn->query("SELECT * FROM `Member`");
    if ($members) {
        $member_hash = password_hash('member123', PASSWORD_DEFAULT);
        while($m = $members->fetch_assoc()) {
            $m_id = $m['member_id'];
            $username = strtolower(explode(' ', trim($m['name']))[0]) . $m_id; // e.g. aarav1
            $conn->query("INSERT INTO `Users` (`member_id`, `username`, `password`, `role`) VALUES ($m_id, '$username', '$member_hash', 'member')");
        }
    }
}


/**
 * Input sanitization function
 */
function sanitize($conn, $data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

/**
 * Set flash alert message for user feedback
 * @param string $type 'success', 'danger', 'warning', 'info'
 * @param string $message Text message
 */
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Display flash message banner if set
 */
function display_flash() {
    if (isset($_SESSION['flash'])) {
        $type = $_SESSION['flash']['type'];
        $message = $_SESSION['flash']['message'];
        unset($_SESSION['flash']);
        
        $icon = "ℹ️";
        if ($type === 'success') $icon = "✅";
        if ($type === 'danger') $icon = "❌";
        if ($type === 'warning') $icon = "⚠️";
        
        echo "<div class='alert alert-{$type}'>
                <span>{$icon} {$message}</span>
                <button type='button' class='close-alert' onclick='this.parentElement.remove();'>&times;</button>
              </div>";
    }
}

/**
 * Currency Formatter
 */
function format_currency($amount) {
    return "₹" . number_format((float)$amount, 2);
}

/**
 * Date Formatter (e.g. 15 Jan 2024)
 */
function format_date($date_str) {
    if (!$date_str || $date_str == '0000-00-00') return 'N/A';
    return date("d M Y", strtotime($date_str));
}
?>
