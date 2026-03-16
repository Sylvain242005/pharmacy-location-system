<?php
// admin/manage_users.php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: " . SITE_URL . "/login.html");
    exit();
}

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    
    if ($_GET['action'] == 'delete') {
        $conn->query("DELETE FROM users WHERE user_id = $user_id");
    } elseif ($_GET['action'] == 'verify') {
        $conn->query("UPDATE users SET verified = 1 WHERE user_id = $user_id");
    } elseif ($_GET['action'] == 'unverify') {
        $conn->query("UPDATE users SET verified = 0 WHERE user_id = $user_id");
    } elseif ($_GET['action'] == 'suspend') {
        $conn->query("UPDATE users SET is_active = 0 WHERE user_id = $user_id");
    } elseif ($_GET['action'] == 'activate') {
        $conn->query("UPDATE users SET is_active = 1 WHERE user_id = $user_id");
    }
    
    header("Location: manage_users.php");
    exit();
}

$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - PharmaLocator</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* ===== GLOBAL STYLES ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ===== HEADER DESIGN ===== */
        .new-header {
            background: white;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-section {
            display: flex;
            align-items: center;
        }

        .logo-link {
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-icon {
            font-size: 24px;
            background: linear-gradient(135deg, #2c7da0, #2a9d8f);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            color: white;
        }

        .logo-text {
            font-weight: 700;
            font-size: 18px;
            color: #1e293b;
        }

        .logo-text span {
            color: #64748b;
            font-weight: 400;
            font-size: 12px;
            display: block;
            line-height: 1.2;
        }

        /* Navigation */
        .nav-center {
            display: flex;
            gap: 32px;
        }

        .nav-center a {
            text-decoration: none;
            color: #475569;
            font-weight: 500;
            font-size: 15px;
            padding: 8px 0;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .nav-center a:hover {
            color: #2c7da0;
            border-bottom-color: #2c7da0;
        }

        .nav-center a.active {
            color: #2c7da0;
            border-bottom-color: #2c7da0;
            font-weight: 600;
        }

        /* User Menu */
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-name {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f1f5f9;
            padding: 8px 16px;
            border-radius: 40px;
            color: #1e293b;
            font-weight: 500;
            font-size: 14px;
        }

        .user-name i {
            color: #2c7da0;
        }

        .logout-btn {
            text-decoration: none;
            padding: 8px 16px;
            background: #fee2e2;
            color: #dc2626;
            border-radius: 40px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #fecaca;
            transform: translateY(-2px);
        }

        /* ===== MAIN CONTAINER ===== */
        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 30px;
            flex: 1;
            width: 100%;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
            background: white;
            padding: 20px 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: 1px solid #f0f0f0;
        }

        .page-header h1 {
            font-size: 24px;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header h1 i {
            color: #2c7da0;
        }

        .user-count {
            background: #f1f5f9;
            padding: 8px 16px;
            border-radius: 40px;
            color: #475569;
            font-size: 14px;
            font-weight: 500;
        }

        /* Table Container */
        .table-container {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: 1px solid #f0f0f0;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th {
            background: #f8fafc;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            font-size: 14px;
            white-space: nowrap;
        }

        table td {
            padding: 15px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            white-space: nowrap;
        }

        table tbody tr:hover {
            background: #f8fafc;
        }

        /* Badges */
        .badge {
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .badge.customer {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge.business_owner {
            background: #d1fae5;
            color: #065f46;
        }

        .badge.admin {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge.verified {
            background: #d1fae5;
            color: #065f46;
        }

        .badge.unverified {
            background: #fff3cd;
            color: #856404;
        }

        .badge.active {
            background: #d1fae5;
            color: #065f46;
        }

        .badge.suspended {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .btn-small {
            padding: 6px 10px;
            border: none;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-verify {
            background: #d1fae5;
            color: #065f46;
        }

        .btn-unverify {
            background: #fff3cd;
            color: #856404;
        }

        .btn-suspend {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-activate {
            background: #d1fae5;
            color: #065f46;
        }

        .btn-delete {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-view {
            background: #dbeafe;
            color: #1e40af;
        }

        .btn-small:hover {
            transform: translateY(-2px);
            filter: brightness(0.95);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }

            .nav-center {
                flex-wrap: wrap;
                justify-content: center;
                gap: 15px;
            }

            .user-menu {
                width: 100%;
                justify-content: center;
            }

            .page-header {
                flex-direction: column;
                text-align: center;
            }

            table {
                font-size: 13px;
            }

            td, th {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <!-- ===== HEADER ===== -->
    <header class="new-header">
        <div class="header-container">
            <div class="logo-section">
                <a href="../home.html" class="logo-link">
                    <div class="logo-icon">💊</div>
                    <div class="logo-text">
                        PharmaLocator
                        <span>Admin Portal</span>
                    </div>
                </a>
            </div>

            <nav class="nav-center">
                <a href="dashboard.php">Dashboard</a>
                <a href="manage_users.php" class="active">Users</a>
                <a href="verify_businesses.php">Verifications</a>
                <a href="reports.php">Reports</a>
            </nav>

            <div class="user-menu">
                <span class="user-name">
                    <i class="fas fa-user-shield"></i>
                    <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Admin'; ?>
                </span>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <i class="fas fa-users-cog"></i>
                Manage Users
            </h1>
            <div class="user-count">
                <i class="fas fa-user"></i> Total: <?php echo $users ? $users->num_rows : 0; ?> users
            </div>
        </div>

        <!-- Users Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Verified</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users && $users->num_rows > 0): ?>
                        <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo $user['user_id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo $user['email']; ?></td>
                            <td><?php echo $user['phone']; ?></td>
                            <td>
                                <span class="badge <?php echo $user['role']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $user['verified'] ? 'verified' : 'unverified'; ?>">
                                    <?php echo $user['verified'] ? 'Verified' : 'Unverified'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $user['is_active'] ? 'active' : 'suspended'; ?>">
                                    <?php echo $user['is_active'] ? 'Active' : 'Suspended'; ?>
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <!-- Verification Actions -->
                                    <?php if (!$user['verified']): ?>
                                        <a href="?action=verify&id=<?php echo $user['user_id']; ?>" class="btn-small btn-verify" title="Verify">
                                            <i class="fas fa-check-circle"></i> Verify
                                        </a>
                                    <?php else: ?>
                                        <a href="?action=unverify&id=<?php echo $user['user_id']; ?>" class="btn-small btn-unverify" title="Unverify">
                                            <i class="fas fa-times-circle"></i> Unverify
                                        </a>
                                    <?php endif; ?>

                                    <!-- Status Actions -->
                                    <?php if ($user['is_active']): ?>
                                        <a href="?action=suspend&id=<?php echo $user['user_id']; ?>" class="btn-small btn-suspend" title="Suspend">
                                            <i class="fas fa-ban"></i> Suspend
                                        </a>
                                    <?php else: ?>
                                        <a href="?action=activate&id=<?php echo $user['user_id']; ?>" class="btn-small btn-activate" title="Activate">
                                            <i class="fas fa-play"></i> Activate
                                        </a>
                                    <?php endif; ?>

                                    <!-- View Details -->
                                    <a href="user_details.php?id=<?php echo $user['user_id']; ?>" class="btn-small btn-view" title="View Details">
                                        <i class="fas fa-eye"></i> View
                                    </a>

                                    <!-- Delete -->
                                    <a href="?action=delete&id=<?php echo $user['user_id']; ?>" class="btn-small btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px; color: #94a3b8;">
                                <i class="fas fa-users" style="font-size: 48px; margin-bottom: 10px;"></i>
                                <p>No users found</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===== FOOTER ===== -->
    <footer>
        <div class="footer-container">
            <div class="footer-copyright">
                &copy; 2026 PharmaLocator - Admin Portal. All rights reserved.
            </div>
            <div class="footer-social">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-linkedin-in"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>
</body>
</html>