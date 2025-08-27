<?php
session_start();
require 'db.php';

// Only allow admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['email']) || $_SESSION['email'] !== 'admin@dentofarm.com') {
    header("Location: login.php?error=access_denied");
    exit();
}

// Get admin info for navbar
$adminName = $_SESSION['fullname'];
$adminEmail = $_SESSION['email'];
$adminId = $_SESSION['user_id'];

// Get unread notifications count
$unread_count = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE")) {
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $stmt->bind_result($unread_count);
    $stmt->fetch();
    $stmt->close();
}

// Get recent notifications
$notifications = [];
if ($stmt = $conn->prepare("SELECT id, message, created_at, is_read FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5")) {
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($fullname) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@staff\.dentofarm\.com$/', $email)) {
        $message = "Email must end with @staff.dentofarm.com";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $message = "Email is already registered.";
        } else {
            // Insert staff user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'staff'; // Keep if you plan to use role later

            $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $fullname, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                $message = "Staff account created successfully.";
            } else {
                $message = "Error creating staff account: " . $conn->error;
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Create Staff Account - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        body {
            display: flex;
            min-height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
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
            margin-top: 56px;
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
        .form-container {
            max-width: 450px;
            margin: auto;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #2c3e50;
        }
        form {
            margin-top: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.3rem;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .message {
            margin-bottom: 1rem;
            padding: 0.7rem;
            background-color: #e3f2fd;
            border: 1px solid #90caf9;
            color: #0d47a1;
            border-radius: 4px;
        }
        button[type="submit"] {
            width: 100%;
            padding: 0.7rem;
            background-color: #2980b9;
            color: white;
            font-size: 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button[type="submit"]:hover {
            background-color: #1f6391;
        }
        .back-link {
            margin-top: 1rem;
            display: block;
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

<!-- Top Navbar -->
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
      Welcome, <strong class="ms-2 me-3"><?php echo htmlspecialchars($adminName); ?></strong>
      <button id="profileBtn" class="btn btn-outline-light btn-sm me-2">My Profile</button>
      <button id="logoutBtn" class="btn btn-danger btn-sm">Logout</button>
    </div>
  </div>
</nav>

<!-- Sidebar -->
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
    <a href="admin_manage_staff.php" class="nav-link"><i class="fas fa-users-cog"></i> Manage Staff</a>
  </nav>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="form-container">
        <h1>Create Staff Account</h1>
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form method="post" action="admin_create_staff.php">
            <label for="fullname">Full Name</label>
            <input type="text" id="fullname" name="fullname" required />

            <label for="email">Email</label>
            <input 
                type="email" 
                id="email" 
                name="email" 
                required 
                placeholder="example@staff.dentofarm.com"
                pattern="^[a-zA-Z0-9._%+-]+@staff\.dentofarm\.com$" 
                title="Email must end with @staff.dentofarm.com" 
            />

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required />

            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required />

            <button type="submit">Create Staff</button>
        </form>
        <a href="dashboard.php" class="back-link">Back to Admin Dashboard</a>
    </div>
</div>

<!-- Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">My Profile</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p><strong>Name:</strong> <?php echo htmlspecialchars($adminName); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($adminEmail); ?></p>
        <p><strong>Role:</strong> Administrator</p>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
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