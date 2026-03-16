<?php
// Start session
session_start();

// Include database connection
require_once 'includes/db.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic validation
    if (empty($email) || empty($password)) {
        header('Location: login.html?error=missing');
        exit();
    }

    // Prepare statement to fetch user by email
    $stmt = $conn->prepare("SELECT user_id, full_name, email, password_hash, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password_hash'])) {
            // Password correct – set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['user_role'] = $user['role']; // keep in sync with helper functions and other pages

            // Redirect based on role
            if ($user['role'] === 'business_owner') {
                header('Location: business/dashboard.php');
            } elseif ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: customer/dashboard.php');
            }
            exit();
        } else {
            // Wrong password
            header('Location: login.html?error=invalid');
            exit();
        }
    } else {
        // Email not found
        header('Location: login.html?error=invalid');
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    // If someone tries to access login.php directly without POST, redirect to login page
    header('Location: login.html');
    exit();
}
?>