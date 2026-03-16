<?php
// create_admin_secure.php
include 'db.php';

// Configuration
$admin_email = "admin@pharmacylocator.com";
$admin_password = "Admin@123"; // Change this to your desired password
$admin_name = "System Administrator";
$admin_phone = "677000000";

// Generate password hash
$password_hash = password_hash($admin_password, PASSWORD_DEFAULT);

// Check if admin exists
$check = $conn->query("SELECT user_id FROM users WHERE email = '$admin_email'");

if ($check->num_rows > 0) {
    // Update existing admin
    $sql = "UPDATE users SET 
            password_hash = '$password_hash',
            full_name = '$admin_name',
            phone = '$admin_phone',
            verified = 1,
            is_active = 1,
            role = 'admin'
            WHERE email = '$admin_email'";
    
    if ($conn->query($sql)) {
        echo "<h2 style='color: green;'>✅ Admin Updated Successfully!</h2>";
    }
} else {
    // Insert new admin
    $sql = "INSERT INTO users (full_name, email, password_hash, phone, role, verified, is_active) 
            VALUES ('$admin_name', '$admin_email', '$password_hash', '$admin_phone', 'admin', 1, 1)";
    
    if ($conn->query($sql)) {
        echo "<h2 style='color: green;'>✅ Admin Created Successfully!</h2>";
    }
}

if ($conn->error) {
    echo "<h2 style='color: red;'>❌ Error: " . $conn->error . "</h2>";
} else {
    echo "<div style='background: #f0f0f0; padding: 20px; margin: 20px; border-radius: 5px;'>";
    echo "<h3>Admin Login Credentials:</h3>";
    echo "<p><strong>Email:</strong> $admin_email</p>";
    echo "<p><strong>Password:</strong> $admin_password</p>";
    echo "<p><strong>Name:</strong> $admin_name</p>";
    echo "<p><strong>⚠️ IMPORTANT:</strong> Save these credentials and delete this file!</p>";
    echo "</div>";
    
    // Verify the hash works
    if (password_verify($admin_password, $password_hash)) {
        echo "<p style='color: green;'>✅ Password hash verification successful!</p>";
    }
}

echo "<p><a href='login.html' style='padding: 10px 20px; background: #007BFF; color: white; text-decoration: none; border-radius: 5px;'>Go to Login</a></p>";

$conn->close();
?>