<?php
// admin/messages.php
require_once '../db.php';
require_once 'admin_auth.php'; // Your admin authentication check

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.html');
    exit();
}

// Handle message status update
if (isset($_POST['action']) && isset($_POST['message_id'])) {
    $message_id = intval($_POST['message_id']);
    
    if ($_POST['action'] === 'mark_read') {
        $stmt = $conn->prepare("UPDATE contact_messages SET status = 'read' WHERE message_id = ?");
        $stmt->bind_param("i", $message_id);
        $stmt->execute();
    } elseif ($_POST['action'] === 'mark_unread') {
        $stmt = $conn->prepare("UPDATE contact_messages SET status = 'unread' WHERE message_id = ?");
        $stmt->bind_param("i", $message_id);
        $stmt->execute();
    } elseif ($_POST['action'] === 'delete') {
        $stmt = $conn->prepare("DELETE FROM contact_messages WHERE message_id = ?");
        $stmt->bind_param("i", $message_id);
        $stmt->execute();
    }
    
    header('Location: messages.php');
    exit();
}

// Handle reply submission
if (isset($_POST['send_reply']) && isset($_POST['message_id'])) {
    $message_id = intval($_POST['message_id']);
    $reply = trim($_POST['reply_message']);
    $admin_id = $_SESSION['user_id'];
    
    if (!empty($reply)) {
        $stmt = $conn->prepare("INSERT INTO message_replies (message_id, admin_id, reply_message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $message_id, $admin_id, $reply);
        $stmt->execute();
        
        // Update message status to replied
        $stmt = $conn->prepare("UPDATE contact_messages SET status = 'replied' WHERE message_id = ?");
        $stmt->bind_param("i", $message_id);
        $stmt->execute();
    }
    
    header('Location: messages.php?view=' . $message_id);
    exit();
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$query = "SELECT m.*, 
          (SELECT COUNT(*) FROM message_replies WHERE message_id = m.message_id) as reply_count 
          FROM contact_messages m WHERE 1=1";
$params = [];
$types = "";

if ($status_filter !== 'all') {
    $query .= " AND m.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search)) {
    $query .= " AND (m.first_name LIKE ? OR m.last_name LIKE ? OR m.email LIKE ? OR m.message LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
    $types .= "ssss";
}

if (!empty($date_from)) {
    $query .= " AND DATE(m.created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $query .= " AND DATE(m.created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$query .= " ORDER BY m.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$messages = $stmt->get_result();

// Get single message for view
$view_message = null;
if (isset($_GET['view'])) {
    $view_id = intval($_GET['view']);
    $stmt = $conn->prepare("SELECT * FROM contact_messages WHERE message_id = ?");
    $stmt->bind_param("i", $view_id);
    $stmt->execute();
    $view_message = $stmt->get_result()->fetch_assoc();
    
    // Get replies
    $stmt = $conn->prepare("SELECT r.*, u.full_name as admin_name 
                           FROM message_replies r 
                           JOIN users u ON r.admin_id = u.user_id 
                           WHERE r.message_id = ? 
                           ORDER BY r.created_at ASC");
    $stmt->bind_param("i", $view_id);
    $stmt->execute();
    $replies = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages - Admin Panel</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .admin-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .filter-group {
            margin-bottom: 0;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .filter-actions button,
        .filter-actions a {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
        }
        
        .btn-primary {
            background: #007BFF;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #007BFF;
        }
        
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        
        .messages-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .messages-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .messages-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }
        
        .messages-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }
        
        .messages-table tr:hover {
            background: #f8f9fa;
        }
        
        .messages-table tr.unread {
            background: #fff3cd;
            font-weight: bold;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-unread {
            background: #dc3545;
            color: white;
        }
        
        .status-read {
            background: #28a745;
            color: white;
        }
        
        .status-replied {
            background: #17a2b8;
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .action-btn {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            color: white;
        }
        
        .btn-view {
            background: #007BFF;
        }
        
        .btn-read {
            background: #28a745;
        }
        
        .btn-unread {
            background: #ffc107;
            color: #333;
        }
        
        .btn-delete {
            background: #dc3545;
        }
        
        .message-view {
            background: white;
            border-radius: 8px;
            padding: 30px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .message-meta {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .reply-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
        }
        
        .reply-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .reply-meta {
            display: flex;
            justify-content: space-between;
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .messages-table {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1><i class="fas fa-envelope"></i> Contact Messages</h1>
            <div>
                <a href="dashboard.php" class="btn-secondary" style="padding: 8px 16px; margin-right: 10px;">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="logout.php" style="color: #dc3545;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <?php
        $stats = [];
        $stats['total'] = $conn->query("SELECT COUNT(*) as count FROM contact_messages")->fetch_assoc()['count'];
        $stats['unread'] = $conn->query("SELECT COUNT(*) as count FROM contact_messages WHERE status='unread'")->fetch_assoc()['count'];
        $stats['replied'] = $conn->query("SELECT COUNT(*) as count FROM contact_messages WHERE status='replied'")->fetch_assoc()['count'];
        $stats['today'] = $conn->query("SELECT COUNT(*) as count FROM contact_messages WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
        ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Messages</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #dc3545;"><?php echo $stats['unread']; ?></div>
                <div class="stat-label">Unread</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #17a2b8;"><?php echo $stats['replied']; ?></div>
                <div class="stat-label">Replied</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['today']; ?></div>
                <div class="stat-label">Today</div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Messages</option>
                        <option value="unread" <?php echo $status_filter == 'unread' ? 'selected' : ''; ?>>Unread</option>
                        <option value="read" <?php echo $status_filter == 'read' ? 'selected' : ''; ?>>Read</option>
                        <option value="replied" <?php echo $status_filter == 'replied' ? 'selected' : ''; ?>>Replied</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" name="search" placeholder="Name, email, message..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="filter-group">
                    <label>From Date</label>
                    <input type="date" name="date_from" value="<?php echo $date_from; ?>">
                </div>
                
                <div class="filter-group">
                    <label>To Date</label>
                    <input type="date" name="date_to" value="<?php echo $date_to; ?>">
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="messages.php" class="btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Messages Table -->
        <div class="messages-table">
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Replies</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($messages->num_rows > 0): ?>
                        <?php while ($msg = $messages->fetch_assoc()): ?>
                            <tr class="<?php echo $msg['status'] == 'unread' ? 'unread' : ''; ?>">
                                <td>
                                    <span class="status-badge status-<?php echo $msg['status']; ?>">
                                        <?php echo ucfirst($msg['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($msg['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']); ?></td>
                                <td>
                                    <a href="mailto:<?php echo $msg['email']; ?>">
                                        <?php echo htmlspecialchars($msg['email']); ?>
                                    </a>
                                </td>
                                <td><?php echo ucfirst($msg['subject_type']); ?></td>
                                <td>
                                    <?php echo substr(htmlspecialchars($msg['message']), 0, 50); ?>...
                                </td>
                                <td style="text-align: center;">
                                    <?php echo $msg['reply_count']; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?view=<?php echo $msg['message_id']; ?>" class="action-btn btn-view" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="message_id" value="<?php echo $msg['message_id']; ?>">
                                            <?php if ($msg['status'] == 'unread'): ?>
                                                <button type="submit" name="action" value="mark_read" class="action-btn btn-read" title="Mark as Read">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" name="action" value="mark_unread" class="action-btn btn-unread" title="Mark as Unread">
                                                    <i class="fas fa-envelope"></i>
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                        
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this message?');">
                                            <input type="hidden" name="message_id" value="<?php echo $msg['message_id']; ?>">
                                            <button type="submit" name="action" value="delete" class="action-btn btn-delete" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 30px;">
                                <i class="fas fa-inbox fa-3x" style="color: #ccc; margin-bottom: 10px;"></i>
                                <p>No messages found</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- View Single Message -->
        <?php if ($view_message): ?>
            <div class="message-view" id="view-message">
                <div class="message-header">
                    <h2>Message Details</h2>
                    <a href="messages.php" class="btn-secondary" style="padding: 8px 16px;">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
                
                <div class="message-meta">
                    <div><strong>From:</strong> <?php echo htmlspecialchars($view_message['first_name'] . ' ' . $view_message['last_name']); ?></div>
                    <div><strong>Email:</strong> <a href="mailto:<?php echo $view_message['email']; ?>"><?php echo $view_message['email']; ?></a></div>
                    <div><strong>Phone:</strong> <?php echo $view_message['phone'] ?: 'Not provided'; ?></div>
                    <div><strong>Subject:</strong> <?php echo ucfirst($view_message['subject_type']); ?></div>
                    <div><strong>Received:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($view_message['created_at'])); ?></div>
                    <div><strong>Status:</strong> 
                        <span class="status-badge status-<?php echo $view_message['status']; ?>">
                            <?php echo ucfirst($view_message['status']); ?>
                        </span>
                    </div>
                    <div><strong>IP Address:</strong> <?php echo $view_message['ip_address'] ?: 'Unknown'; ?></div>
                </div>
                
                <div style="margin: 20px 0;">
                    <h3>Message:</h3>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; white-space: pre-wrap;">
                        <?php echo nl2br(htmlspecialchars($view_message['message'])); ?>
                    </div>
                </div>
                
                <!-- Replies Section -->
                <?php if (isset($replies) && $replies->num_rows > 0): ?>
                    <div class="reply-section">
                        <h3>Replies:</h3>
                        <?php while ($reply = $replies->fetch_assoc()): ?>
                            <div class="reply-card">
                                <div class="reply-meta">
                                    <span><strong><?php echo htmlspecialchars($reply['admin_name']); ?></strong> (Admin)</span>
                                    <span><?php echo date('M d, Y H:i', strtotime($reply['created_at'])); ?></span>
                                </div>
                                <div><?php echo nl2br(htmlspecialchars($reply['reply_message'])); ?></div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Reply Form -->
                <div class="reply-section">
                    <h3>Send Reply:</h3>
                    <form method="POST" onsubmit="return confirm('Send this reply?');">
                        <input type="hidden" name="message_id" value="<?php echo $view_message['message_id']; ?>">
                        <div class="form-group">
                            <textarea name="reply_message" rows="5" required placeholder="Type your reply here..." style="width: 100%; padding: 12px; border: 2px solid #e1e1e1; border-radius: 6px;"></textarea>
                        </div>
                        <button type="submit" name="send_reply" class="btn-primary" style="padding: 10px 20px;">
                            <i class="fas fa-reply"></i> Send Reply
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>