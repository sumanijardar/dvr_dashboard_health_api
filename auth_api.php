<?php
require_once __DIR__ . '/config.php';

// ==============================================================================
// Authentication APIs (Login, Check Session/Verify, Logout)
// Supports JSON POST, Form-data, and Query Parameters
// ==============================================================================

// Ensure users table exists
$table_init_query = "
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `plain_password` VARCHAR(255) NULL,
    `name` VARCHAR(150) DEFAULT 'Admin User',
    `email` VARCHAR(150) DEFAULT 'admin@sentinel.local',
    `role` VARCHAR(50) DEFAULT 'Administrator',
    `status` TINYINT(1) DEFAULT 1,
    `token` VARCHAR(255) NULL,
    `last_login` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
mysqli_query($con, $table_init_query);

// Also ensure column plain_password exists if table was created previously without it
$col_check = mysqli_query($con, "SHOW COLUMNS FROM `users` LIKE 'plain_password'");
if (mysqli_num_rows($col_check) == 0) {
    mysqli_query($con, "ALTER TABLE `users` ADD COLUMN `plain_password` VARCHAR(255) NULL AFTER `password`");
}

// Seed default admin user if table is empty
$count_check = mysqli_query($con, "SELECT COUNT(*) as cnt FROM `users`");
$row_cnt = mysqli_fetch_assoc($count_check);
if ((int)($row_cnt['cnt'] ?? 0) === 0) {
    $default_user = 'admin';
    $default_plain_pass = 'admin123';
    $default_pass = password_hash($default_plain_pass, PASSWORD_BCRYPT);
    $default_name = 'Security Administrator';
    $default_email = 'admin@sentinel.com';
    $default_role = 'Super Admin';

    $stmt_seed = mysqli_prepare($con, "INSERT INTO `users` (`username`, `password`, `plain_password`, `name`, `email`, `role`, `status`) VALUES (?, ?, ?, ?, ?, ?, 1)");
    mysqli_stmt_bind_param($stmt_seed, 'ssssss', $default_user, $default_pass, $default_plain_pass, $default_name, $default_email, $default_role);
    mysqli_stmt_execute($stmt_seed);
    mysqli_stmt_close($stmt_seed);
}

// Helper to extract JSON or POST payload
function getRequestPayload() {
    $input = file_get_contents('php://input');
    $json = json_decode($input, true);
    if (is_array($json)) {
        return $json;
    }
    return $_POST;
}

// Helper to extract Bearer or custom token from headers / params
function getAuthToken() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
        return trim($matches[1]);
    }
    if (!empty($_GET['token'])) {
        return trim($_GET['token']);
    }
    $payload = getRequestPayload();
    if (!empty($payload['token'])) {
        return trim($payload['token']);
    }
    return '';
}

$action = $_GET['action'] ?? '';
$payload = getRequestPayload();
if (empty($action) && isset($payload['action'])) {
    $action = $payload['action'];
}

// ==============================================================================
// 1. LOGIN API
// URL: POST api/auth_api.php?action=login (or POST with action in body)
// Body: { "username": "admin", "password": "..." }
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'login' || empty($action))) {
    $username = trim($payload['username'] ?? '');
    $password = trim($payload['password'] ?? '');

    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode([
            'Code' => 400,
            'status' => 'error',
            'message' => 'Username and password are required.'
        ]);
        exit();
    }

    $stmt = mysqli_prepare($con, "SELECT id, username, password, plain_password, name, email, role, status FROM `users` WHERE username = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$user) {
        http_response_code(401);
        echo json_encode([
            'Code' => 401,
            'status' => 'error',
            'message' => 'Invalid username or password.'
        ]);
        exit();
    }

    if ((int)$user['status'] !== 1) {
        http_response_code(403);
        echo json_encode([
            'Code' => 403,
            'status' => 'error',
            'message' => 'Account is suspended or inactive. Please contact system admin.'
        ]);
        exit();
    }

    // Support bcrypt hash, plain_password column match, and password column match
    $is_valid = password_verify($password, $user['password'])
        || ($password === ($user['plain_password'] ?? ''))
        || ($password === $user['password']);

    if (!$is_valid) {
        http_response_code(401);
        echo json_encode([
            'Code' => 401,
            'status' => 'error',
            'message' => 'Invalid username or password.'
        ]);
        exit();
    }

    // Generate secure session token
    $token = bin2hex(random_bytes(32));
    $user_id = (int)$user['id'];

    $update_stmt = mysqli_prepare($con, "UPDATE `users` SET token = ?, last_login = NOW() WHERE id = ?");
    mysqli_stmt_bind_param($update_stmt, 'si', $token, $user_id);
    mysqli_stmt_execute($update_stmt);
    mysqli_stmt_close($update_stmt);

    http_response_code(200);
    echo json_encode([
        'Code' => 200,
        'status' => 'success',
        'message' => 'Login successful.',
        'data' => [
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ]
        ]
    ]);
    exit();
}

// ==============================================================================
// 2. CHECK / VERIFY SESSION API
// URL: GET/POST api/auth_api.php?action=verify
// Headers: Authorization: Bearer <token>
// ==============================================================================
if ($action === 'verify' || $action === 'check') {
    $token = getAuthToken();

    if (empty($token)) {
        http_response_code(401);
        echo json_encode([
            'Code' => 401,
            'status' => 'error',
            'message' => 'No active authentication token provided.'
        ]);
        exit();
    }

    $stmt = mysqli_prepare($con, "SELECT id, username, name, email, role, status, last_login FROM `users` WHERE token = ? AND token IS NOT NULL AND status = 1 LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$user) {
        http_response_code(401);
        echo json_encode([
            'Code' => 401,
            'status' => 'error',
            'message' => 'Session expired or invalid token.'
        ]);
        exit();
    }

    http_response_code(200);
    echo json_encode([
        'Code' => 200,
        'status' => 'success',
        'message' => 'Token verified.',
        'data' => [
            'user' => $user
        ]
    ]);
    exit();
}

// ==============================================================================
// 3. LOGOUT API
// URL: POST/GET api/auth_api.php?action=logout
// Headers: Authorization: Bearer <token>
// ==============================================================================
if ($action === 'logout') {
    $token = getAuthToken();

    if (!empty($token)) {
        $stmt = mysqli_prepare($con, "UPDATE `users` SET token = NULL WHERE token = ?");
        mysqli_stmt_bind_param($stmt, 's', $token);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    http_response_code(200);
    echo json_encode([
        'Code' => 200,
        'status' => 'success',
        'message' => 'Logged out successfully.'
    ]);
    exit();
}

// ==============================================================================
// Default: 400 Invalid Request
// ==============================================================================
http_response_code(400);
echo json_encode([
    'Code' => 400,
    'status' => 'error',
    'message' => 'Invalid authentication request. Available actions: login, verify, logout'
]);
exit();

