<?php
session_start();
include 'db.php'; // Your database connection file

// Check if user is logged in
if (empty($_SESSION['user_id']) || empty($_SESSION['email'])) {
    header("Location: ../login.php");
    exit();
}

// Store session values for profile
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Panel - DentoReserve</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://cdn.jsdelivr.net/npm/remixicon@3.4.0/fonts/remixicon.css" rel="stylesheet" />
  <style>
    body {
      display: flex;
      min-height: 100vh;
      margin: 0;
      font-family: Arial, sans-serif;
      background: #f8f9fa;
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
    .container {
      padding: 20px;
      max-width: 1000px;
      margin: auto;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    h2 {
      margin-top: 40px;
      color: #333;
    }
    form {
      margin-top: 10px;
      margin-bottom: 30px;
    }
    input, textarea {
      width: 100%;
      padding: 10px;
      margin: 5px 0 15px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    button {
      background: #3498db;
      color: white;
      padding: 10px 20px;
      border: none;
      cursor: pointer;
      border-radius: 4px;
    }
    button:hover {
      background: #2980b9;
    }
    input[type="file"] {
      padding: 3px;
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
    <a href="add_video.php" class="nav-link active"><i class="fas fa-video"></i> Add Video</a>
  </nav>
</div>

<!-- Main Content -->
<div class="main-content">
  <div class="container">
    <h2>🎥 Add New Video</h2>
    <form action="process_video_upload.php" method="POST" enctype="multipart/form-data">
      <div class="mb-3">
        <label for="videoFile" class="form-label">Video File:</label>
        <input type="file" class="form-control" id="videoFile" name="video" accept="video/*" required>
      </div>
      <div class="mb-3">
        <label for="videoDescription" class="form-label">Video Description:</label>
        <textarea class="form-control" id="videoDescription" name="description" rows="3" placeholder="Enter a description for the video" required></textarea>
      </div>
      <input type="hidden" name="uploader_id" value="<?php echo htmlspecialchars($adminId); ?>">
      <button type="submit" class="btn btn-primary">Upload Video</button>
    </form>
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