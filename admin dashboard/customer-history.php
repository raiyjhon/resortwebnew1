<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Customer History</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body {
      display: flex;
      min-height: 100vh;
      margin: 0;
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
      min-height: 100vh;
    }
    .navbar {
      position: fixed;
      top: 0;
      width: 100%;
      z-index: 1030;
    }
    /* Floating table container with dark blue background */
    .table-container {
      background-color: #0d3b66; /* Dark blue */
      color: white;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(13, 59, 102, 0.6);
      max-width: 100%;
      overflow-x: auto;
    }
    /* Override table styles to fit dark background */
    .table-container table {
      color: white;
    }
    .table-container thead th {
      border-bottom: 2px solid #145da0;
    }
    .table-container tbody td,
    .table-container thead th {
      vertical-align: middle;
    }
    /* Style Delete button for better contrast */
    .btn-danger {
      background-color: #e63946;
      border: none;
    }
    .btn-danger:hover {
      background-color: #d62828;
    }
  </style>
</head>
<body>

  <!-- Top Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
      <a class="navbar-brand" href="#">DentoReserve</a>
      <div class="ms-auto text-white d-flex align-items-center">
        Welcome, <strong class="ms-2 me-3">Admin</strong>
        <button id="profileBtn" class="btn btn-outline-light btn-sm">My Profile</button>
      </div>
    </div>
  </nav>

  <!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <nav class="nav flex-column">
    <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="analytics.php" class="nav-link"><i class="fas fa-star"></i> Room Rating</a>
    <a href="sales-analytics.php" class="nav-link active"><i class="fas fa-chart-line"></i> Revenue Report</a>
    <a href="bookinganaly.php" class="nav-link"><i class="fas fa-calendar-check"></i> Booking Analysis</a>
    <a href="predictive.php" class="nav-link"><i class="fas fa-chart-bar"></i> Predict</a>
    <a href="add-room.php" class="nav-link"><i class="fas fa-plus-circle"></i> Add Room</a>
    <a href="cottages.php" class="nav-link"><i class="fas fa-money-bill-wave"></i> Revenue analytics</a>
    <a href="room-bookings.php" class="nav-link"><i class="fas fa-bed"></i> Room Bookings</a>
    <a href="events.php" class="nav-link"><i class="fas fa-calendar-alt"></i> Events</a>
    <a href="admin_create_staff.php" class="nav-link"><i class="fas fa-user-plus"></i> Add Staff</a>
    <a href="admin_manage_staff.php" class="nav-link"><i class="fas fa-users-cog"></i> Manage Staff</a>
  </nav>
</div>

  <!-- Main Content -->
  <div class="main-content">
    <h2>Customer History</h2>
    <div class="table-container">
      <table class="table table-hover table-bordered">
        <thead>
          <tr>
            <th>Customer ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Room</th>
            <th>Check-In Date</th>
            <th>Check-In Time</th>
            <th>Check-Out Date</th>
            <th>Check-Out Time</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <!-- Example row -->
          <tr>
            <td>501</td>
            <td>Jane Smith</td>
            <td>jane@example.com</td>
            <td>Room Deluxe</td>
            <td>2025-04-01</td>
            <td>14:00</td>
            <td>2025-04-03</td>
            <td>11:00</td>
            <td>
              <button class="btn btn-sm btn-danger">Delete</button>
            </td>
          </tr>
          <!-- Add more rows dynamically -->
        </tbody>
      </table>
    </div>
  </div>

  <!-- Profile Popup -->
  <div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">My Profile</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p><strong>Name:</strong> Wasabi</p>
          <p><strong>Email:</strong> admin@resort.com</p>
          <p><strong>Role:</strong> Administrator</p>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.getElementById('profileBtn').addEventListener('click', () => {
      const profileModal = new bootstrap.Modal(document.getElementById('profileModal'));
      profileModal.show();
    });
  </script>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
