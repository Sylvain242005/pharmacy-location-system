<?php
// Start output buffering to catch any accidental output
ob_start();

// Start session (required for $_SESSION)
session_start();

header('Content-Type: application/json');
require_once 'db.php'; // ensure this file does not output anything

// Helper to return a clean JSON error
function sendError($msg) {
    ob_clean(); // discard any buffered output
    echo json_encode(['success' => false, 'message' => $msg]);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['email']) || !isset($input['password'])) {
    sendError('Email and password required');
}

$email = trim($input['email']);
$password = $input['password'];

// Prepare statement
$stmt = $conn->prepare("SELECT user_id, full_name, email, password_hash, role FROM users WHERE email = ? AND is_active = 1");
if (!$stmt) {
    sendError('Database error: ' . $conn->error);
}
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    sendError('Invalid email or password');
}

$user = $result->fetch_assoc();

// Verify password
if (!password_verify($password, $user['password_hash'])) {
    sendError('Invalid email or password');
}

// Update last login (only if the column exists in your table)
$update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
if ($update) {
    $update->bind_param('i', $user['user_id']);
    $update->execute();
    $update->close();
}

// Set session
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['user_name'] = $user['full_name'];
$_SESSION['user_role'] = $user['role'];

// Clear buffer and return success JSON
ob_clean();
echo json_encode([
    'success' => true,
    'message' => 'Login successful',
    'user' => [
        'id' => $user['user_id'],
        'name' => $user['full_name'],
        'email' => $user['email'],
        'role' => $user['role']
    ]
]);
exit();
?>