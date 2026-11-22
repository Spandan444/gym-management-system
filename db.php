<?php
/**
 * Database Configuration & Connection File
 * Course: B.Tech DBMS Mini-Project
 * Project: GYM MANAGEMENT SYSTEM
 * Tech Stack: PHP & MySQL (XAMPP)
 */

// Keep local credentials in config.local.php; Vercel credentials come from env vars.
$db_config = [
    'host' => '127.0.0.1',
    'user' => 'root',
    'pass' => '',
    'name' => 'gym_management',
    'port' => 3307,
    'ssl' => false,
    'ssl_ca' => null,
];
$local_config_file = __DIR__ . '/config.local.php';
if (is_file($local_config_file)) {
    $local_config = require $local_config_file;
    if (is_array($local_config)) {
        $db_config = array_merge($db_config, $local_config);
    }
}

$env_host = getenv('TIDB_HOST');
$env_user = getenv('TIDB_USER');
$env_pass = getenv('TIDB_PASSWORD');
$env_name = getenv('TIDB_DATABASE');
$env_port = getenv('TIDB_PORT');
$env_ssl = getenv('TIDB_SSL');
$env_ssl_ca = getenv('TIDB_SSL_CA');
if ($env_host !== false && $env_host !== '') $db_config['host'] = $env_host;
if ($env_user !== false && $env_user !== '') $db_config['user'] = $env_user;
if ($env_pass !== false) $db_config['pass'] = $env_pass;
if ($env_name !== false && $env_name !== '') $db_config['name'] = $env_name;
if ($env_port !== false && $env_port !== '') $db_config['port'] = $env_port;
if ($env_ssl !== false && $env_ssl !== '') {
    $db_config['ssl'] = filter_var($env_ssl, FILTER_VALIDATE_BOOLEAN);
} elseif ($env_host !== false && $env_host !== '') {
    $db_config['ssl'] = true;
}
if ($env_ssl_ca !== false && $env_ssl_ca !== '') $db_config['ssl_ca'] = $env_ssl_ca;

$db_host = (string)$db_config['host'];
$db_user = (string)$db_config['user'];
$db_pass = (string)$db_config['pass'];
$db_name = (string)$db_config['name'];
$db_port = (int)$db_config['port'];
$db_ssl = (bool)$db_config['ssl'];

// Establish MySQL Connection using mysqli
mysqli_report(MYSQLI_REPORT_OFF);
$connect_to_database = static function (int $port) use ($db_host, $db_user, $db_pass, $db_name, $db_ssl, $db_config) {
    $connection = mysqli_init();
    if ($db_ssl) {
        $ca_file = !empty($db_config['ssl_ca']) ? (string)$db_config['ssl_ca'] : null;
        $connection->ssl_set(null, null, $ca_file, null, null);
        if (defined('MYSQLI_OPT_SSL_VERIFY_SERVER_CERT')) {
            $connection->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);
        }
    }
    $flags = $db_ssl ? MYSQLI_CLIENT_SSL : 0;
    @$connection->real_connect($db_host, $db_user, $db_pass, $db_name, $port, null, $flags);
    return $connection;
};

// TiDB requires TLS; only local non-TLS MySQL connections use the port fallback.
$conn = $connect_to_database($db_port);
if ($conn->connect_errno && !$db_ssl && $db_port !== 3306) {
    $conn = $connect_to_database(3306);
}

if ($conn->connect_errno) {
    if (getenv('VERCEL')) {
        die("
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 60px auto; padding: 25px; border: 1px solid #fecaca; background: #fff1f2; border-radius: 8px; color: #991b1b;'>
            <h2 style='margin-top:0;'>Database Connection Notice</h2>
            <p>The app could not connect to TiDB. Check the TIDB_HOST, TIDB_USER, TIDB_PASSWORD, TIDB_DATABASE, and TIDB_PORT environment variables in Vercel.</p>
            <p>Also verify that TiDB allows connections from this deployment and that TLS is enabled.</p>
        </div>
        ");
    }

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

// Persist PHP sessions in TiDB/MySQL because Vercel instances have ephemeral storage.
$conn->query("CREATE TABLE IF NOT EXISTS `App_Sessions` (
    `session_id` VARCHAR(128) NOT NULL PRIMARY KEY,
    `session_data` MEDIUMBLOB NOT NULL,
    `last_activity` INT UNSIGNED NOT NULL,
    KEY `idx_app_sessions_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

final class MysqlSessionHandler implements SessionHandlerInterface {
    private mysqli $connection;

    public function __construct(mysqli $connection) {
        $this->connection = $connection;
    }

    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }

    public function read(string $id): string|false {
        $stmt = $this->connection->prepare(
            "SELECT `session_data` FROM `App_Sessions` WHERE `session_id` = ? AND `last_activity` >= ?"
        );
        if (!$stmt) return false;
        $active_since = time() - (int)ini_get('session.gc_maxlifetime');
        $stmt->bind_param('si', $id, $active_since);
        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }
        $stmt->bind_result($data);
        $found = $stmt->fetch();
        $stmt->close();
        return $found ? (string)$data : '';
    }

    public function write(string $id, string $data): bool {
        $stmt = $this->connection->prepare(
            "INSERT INTO `App_Sessions` (`session_id`, `session_data`, `last_activity`)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE `session_data` = VALUES(`session_data`), `last_activity` = VALUES(`last_activity`)"
        );
        if (!$stmt) return false;
        $last_activity = time();
        $stmt->bind_param('ssi', $id, $data, $last_activity);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function destroy(string $id): bool {
        $stmt = $this->connection->prepare("DELETE FROM `App_Sessions` WHERE `session_id` = ?");
        if (!$stmt) return false;
        $stmt->bind_param('s', $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function gc(int $max_lifetime): int|false {
        $stmt = $this->connection->prepare("DELETE FROM `App_Sessions` WHERE `last_activity` < ?");
        if (!$stmt) return false;
        $expired_before = time() - $max_lifetime;
        $stmt->bind_param('i', $expired_before);
        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }
        $deleted = $stmt->affected_rows;
        $stmt->close();
        return $deleted;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', '1440');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'),
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_set_save_handler(new MysqlSessionHandler($conn), true);
    session_start();
}

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
    $admin_initial_password = getenv('ADMIN_INITIAL_PASSWORD');
    if (getenv('VERCEL') && ($admin_initial_password === false || $admin_initial_password === '')) {
        die('Set ADMIN_INITIAL_PASSWORD in Vercel before initializing the database.');
    }
    if ($admin_initial_password === false || $admin_initial_password === '') {
        $admin_initial_password = 'admin123';
    }
    $admin_hash = password_hash($admin_initial_password, PASSWORD_DEFAULT);
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
