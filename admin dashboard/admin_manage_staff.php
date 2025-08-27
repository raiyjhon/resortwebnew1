<?php
session_start();
require 'db.php';

// Only allow admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['email']) || $_SESSION['email'] !== 'admin@dentofarm.com') {
    header("Location: login.php?error=access_denied");
    exit();
}

// Get unread notifications count
$unread_count = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE")) {
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($unread_count);
    $stmt->fetch();
    $stmt->close();
}

// Get recent notifications
$notifications = [];
if ($stmt = $conn->prepare("SELECT id, message, created_at, is_read FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5")) {
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
}

// Fetch all staff users by email domain
$staff_sql = "SELECT id, fullname, email FROM users WHERE email LIKE '%@staff.dentofarm.com' ORDER BY fullname ASC";
$staff_result = $conn->query($staff_sql);

// Fetch all other users (customers), excluding admin and staff emails
$customer_sql = "SELECT id, fullname, email, phone FROM users WHERE email NOT LIKE '%@staff.dentofarm.com' AND email != 'admin@dentofarm.com' ORDER BY fullname ASC";
$customer_result = $conn->query($customer_sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Manage Users - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        body {
            display: flex;
            min-height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7f9;
        }
        .sidebar {
            width: 250px;
            background-color: #343a40;
            color: white;
            height: 100vh;
            position: fixed;
            top: 56px;
            left: 0;
            overflow-y: auto;
            padding-top: 1rem;
        }
        .sidebar .nav-link {
            color: white;
        }
        .sidebar .nav-link.active {
            background-color: #495057;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            background-color: #f8f9fa;
            width: 100%;
            padding-top: 76px;
        }
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1030;
        }
        .notification-badge {
            background-color: #ff4757;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            position: relative;
            top: -10px;
            right: -5px;
        }
        .notification-dropdown {
            width: 300px;
            max-height: 400px;
            overflow-y: auto;
        }
        .notification-dropdown a {
            padding: 10px;
            border-bottom: 1px solid #eee;
            display: block;
            position: relative;
        }
        .notification-dropdown a.unread {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .notification-dropdown a small {
            display: block;
            color: #666;
            font-size: 0.8em;
            margin-top: 3px;
            font-weight: normal;
        }
        .notification-dropdown .view-all {
            text-align: center;
            background-color: #f1f1f1;
            font-weight: bold;
        }
        .container {
            max-width: 100%;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px; /* Added for spacing between sections */
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 1.5rem;
        }
        h2 {
            color: #2c3e50;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 0.75rem;
            text-align: left;
        }
        th {
            background-color: #2980b9;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9fbfd;
        }
        a {
            color: #2980b9;
            text-decoration: none;
            margin-right: 1rem;
        }
        a:hover {
            text-decoration: underline;
        }
        .back-link {
            display: block;
            margin-top: 1.5rem;
            text-align: center;
            color: #2980b9;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">DentoReserve</a>
        <div class="ms-auto text-white d-flex align-items-center">
            <div class="dropdown me-3">
                <a href="#" class="text-white dropdown-toggle" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell"></i>
                    <?php if ($unread_count > 0): ?>
                        <span class="notification-badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
                    <?php if (empty($notifications)): ?>
                        <li><a class="dropdown-item" href="#">No notifications</a></li>
                    <?php else: ?>
                        <?php foreach ($notifications as $notif): ?>
                            <li>
                                <a class="dropdown-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>" href="notification.php?id=<?php echo $notif['id']; ?>">
                                    <?php echo htmlspecialchars($notif['message']); ?>
                                    <small><?php echo date('M j, g:i a', strtotime($notif['created_at'])); ?></small>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item view-all" href="notifications.php">View all notifications</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            Welcome, <strong class="ms-2 me-3"><?php echo htmlspecialchars($_SESSION['fullname']); ?></strong>
            <button id="profileBtn" class="btn btn-outline-light btn-sm me-2">My Profile</button>
            <button id="logoutBtn" class="btn btn-danger btn-sm">Logout</button>
        </div>
    </div>
</nav>

<div class="sidebar" id="sidebar">
    <nav class="nav flex-column">
        <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="analytics.php" class="nav-link"><i class="fas fa-star"></i> Room Rating</a>
        <a href="bookinganaly.php" class="nav-link"><i class="fas fa-calendar-check"></i> Booking Analysis</a>
        <a href="cottages.php" class="nav-link"><i class="fas fa-money-bill-wave"></i> Sales Analytics</a>
        <a href="predictive.php" class="nav-link"><i class="fas fa-chart-bar"></i> Predict</a>
        <a href="add-room.php" class="nav-link"><i class="fas fa-plus-circle"></i> Add Room</a>
        <a href="room-bookings.php" class="nav-link"><i class="fas fa-bed"></i> Room Bookings</a>
        <a href="events.php" class="nav-link"><i class="fas fa-calendar-alt"></i> Events</a>
        <a href="admin_create_staff.php" class="nav-link"><i class="fas fa-user-plus"></i> Add Staff</a>
        <a href="admin_manage_staff.php" class="nav-link active"><i class="fas fa-users-cog"></i> Manage Staff</a>
        <a href="admin_manage_users.php" class="nav-link"><i class="fas fa-users"></i> Manage Users</a>
    </nav>
</div>

<div class="main-content">
    <div class="container">
        <h1>User Management</h1>

        <h2>Staff Accounts</h2>
        <?php if ($staff_result && $staff_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($staff = $staff_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($staff['id']); ?></td>
                            <td><?php echo htmlspecialchars($staff['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($staff['email']); ?></td>
                            <td>
                                <a href="admin_delete_staff.php?id=<?php echo $staff['id']; ?>" onclick="return confirm('Are you sure you want to delete this staff?');">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No staff users found.</p>
        <?php endif; ?>

        <h2>Customer Accounts</h2>
        <?php if ($customer_result && $customer_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($customer = $customer_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($customer['id']); ?></td>
                            <td><?php echo htmlspecialchars($customer['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($customer['email']); ?></td>
                            <td><?php echo htmlspecialchars($customer['phone'] ?: 'N/A'); ?></td>
                            <td>
                                <a href="admin_delete_user.php?id=<?php echo $customer['id']; ?>" onclick="return confirm('Are you sure you want to delete this customer account?');">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No customer accounts found.</p>
        <?php endif; ?>

        <a href="dashboard.php" class="back-link">Back to Admin Dashboard</a>
    </div>
</div>

<div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">My Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['fullname']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email']); ?></p>
                <p><strong>Role:</strong> Administrator</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Profile modal trigger
    document.getElementById('profileBtn').addEventListener('click', () => {
        const profileModal = new bootstrap.Modal(document.getElementById('profileModal'));
        profileModal.show();
    });

    // Logout confirmation
    document.getElementById('logoutBtn').addEventListener('click', function() {
        Swal.fire({
            title: 'Logout Confirmation',
            text: "Are you sure you want to logout?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, logout',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'logout.php';
            }
        });
    });
</script>
</body>
</html>