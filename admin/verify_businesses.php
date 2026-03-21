<?php
// admin/verify_businesses.php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Only admin can access
if (!isAdmin()) {
    header('Location: ' . SITE_URL . '/login.php');
    exit();
}

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['owner_id'])) {
    $owner_id = (int)$_POST['owner_id'];
    $action = $_POST['action']; // 'approve' or 'reject'

    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE business_owners SET verification_status = 'approved', verified_at = NOW() WHERE owner_id = ?");
        $stmt->bind_param('i', $owner_id);
        $stmt->execute();
        $stmt->close();

        // Optionally approve all facilities of this business owner
        $updateFacilities = $conn->prepare("UPDATE facilities SET verified = 1 WHERE owner_id = ?");
        $updateFacilities->bind_param('i', $owner_id);
        $updateFacilities->execute();
        $updateFacilities->close();

        $_SESSION['success_msg'] = "Business approved successfully.";
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE business_owners SET verification_status = 'rejected' WHERE owner_id = ?");
        $stmt->bind_param('i', $owner_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['success_msg'] = "Business rejected.";
    }

    // ✅ Redirect to the same file (verify_businesses.php)
    header('Location: verify_businesses.php');
    exit();
}

// Fetch all pending businesses
$query = "
    SELECT bo.*, u.full_name, u.email, u.phone
    FROM business_owners bo
    JOIN users u ON bo.user_id = u.user_id
    WHERE bo.verification_status = 'pending'
    ORDER BY bo.created_at DESC
";
$pending = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Verifications - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Same styles as your redesigned admin pages (unchanged) */
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

        /* Header styles – reuse from previous admin design */
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

        /* Main container */
        .admin-container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 30px;
            flex: 1;
            width: 100%;
        }

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
        }

        .page-header h1 {
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header h1 i {
            color: #2c7da0;
        }

        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .table-responsive {
            overflow-x: auto;
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8fafc;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #475569;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f1f5f9;
        }

        tr:hover {
            background: #f8fafc;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge.approved {
            background: #d1fae5;
            color: #065f46;
        }

        .badge.rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-small {
            padding: 6px 12px;
            border: none;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-approve {
            background: #d1fae5;
            color: #065f46;
        }

        .btn-approve:hover {
            background: #a7f3d0;
            transform: translateY(-2px);
        }

        .btn-reject {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-reject:hover {
            background: #fecaca;
            transform: translateY(-2px);
        }

        .btn-view {
            background: #dbeafe;
            color: #1e40af;
        }

        .btn-view:hover {
            background: #bfdbfe;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 60px;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 15px;
        }

        footer {
            background: white;
            padding: 20px 30px;
            margin-top: 40px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        }

        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .footer-copyright {
            color: #64748b;
            font-size: 14px;
        }

        .footer-social {
            display: flex;
            gap: 20px;
        }

        .footer-social a {
            color: #94a3b8;
            font-size: 18px;
            transition: all 0.3s;
        }

        .footer-social a:hover {
            color: #2c7da0;
            transform: translateY(-3px);
        }

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
            }
            .action-buttons {
                flex-direction: column;
            }
            .btn-small {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
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
                <a href="manage_users.php">Users</a>
                <a href="verify_businesses.php" class="active">Verifications</a>
                <a href="reports.php">Reports</a>
            </nav>

            <div class="user-menu">
                <span class="user-name">
                    <i class="fas fa-user-shield"></i>
                    <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>
                </span>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <div class="admin-container">
        <div class="page-header">
            <h1><i class="fas fa-check-double"></i> Business Verifications</h1>
            <div class="stats">
                <span class="badge pending">Pending: <?php echo $pending ? $pending->num_rows : 0; ?></span>
            </div>
        </div>

        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php
                echo htmlspecialchars($_SESSION['success_msg']);
                unset($_SESSION['success_msg']);
                ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <?php if ($pending && $pending->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Business Name</th>
                            <th>Type</th>
                            <th>Owner</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $pending->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['business_name']); ?></strong></td>
                                <td><span class="badge"><?php echo ucfirst($row['business_type']); ?></span></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td><span class="badge pending">Pending</span></td>
                                <td class="action-buttons">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="owner_id" value="<?php echo $row['owner_id']; ?>">
                                        <button type="submit" name="action" value="approve" class="btn-small btn-approve" onclick="return confirm('Approve this business?')">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="owner_id" value="<?php echo $row['owner_id']; ?>">
                                        <button type="submit" name="action" value="reject" class="btn-small btn-reject" onclick="return confirm('Reject this business?')">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                    <a href="business_details.php?id=<?php echo $row['owner_id']; ?>" class="btn-small btn-view">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <p>No pending business verifications.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

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