<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    header("Location: login.php?error=not_logged_in");
    exit();
}

// Simple check for staff email domain
if (strpos($_SESSION['email'], '@staff.dentofarm.com') === false) {
    header("Location: login.php?error=access_denied");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Staff Dashboard - DentoFarm Resort</title>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #eef2f7;
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: white;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: fixed;
        }
        .sidebar-header {
            padding: 1rem 0;
            border-bottom: 1px solid #34495e;
            margin-bottom: 2rem;
        }
        .sidebar-header h2 {
            margin: 0;
            color: white;
            text-align: center;
        }
        .sidebar-menu {
            flex-grow: 1;
        }
        .sidebar-menu a {
            display: block;
            color: white;
            padding: 0.75rem;
            text-decoration: none;
            transition: all 0.3s;
            text-align: center;
            margin-bottom: 1rem;
            background-color: #3498db;
            border-radius: 5px;
        }
        .sidebar-menu a:hover {
            background-color: #2980b9;
        }
        .sidebar-footer {
            padding: 1rem 0;
            border-top: 1px solid #34495e;
        }
        .logout-btn {
            display: block;
            width: 100%;
            padding: 0.75rem;
            background-color: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            border: none;
        }
        .logout-btn:hover {
            background-color: #c0392b;
        }
        .main-content {
            margin-left: 250px;
            flex-grow: 1;
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .welcome-container {
            text-align: center;
            max-width: 600px;
        }
        .welcome-msg {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .welcome-msg h1 {
            color: #2c3e50;
            margin-top: 0;
            font-size: 2.5rem;
        }
        .welcome-msg p {
            color: #7f8c8d;
            font-size: 1.2rem;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>DentoFarm Staff</h2>
        </div>
        <div class="sidebar-menu">
            <a href="manage_bookings.php">Manage Bookings</a>
        </div>
        <div class="sidebar-footer">
            <button id="logoutBtn" class="logout-btn">Logout</button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="welcome-container">
            <div class="welcome-msg">
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?></h1>
                <p>Staff Dashboard - Click "Manage Bookings" to get started</p>
            </div>
        </div>
    </div>

    <script>
        // Logout confirmation
        document.getElementById('logoutBtn').addEventListener('click', function () {
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
                    window.location.href = '../logout.php';
                }
            });
        });

        // Show alert if redirected due to error
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('error') === 'access_denied') {
            Swal.fire({
                icon: 'error',
                title: 'Access Denied',
                text: 'You must be a staff member to access the dashboard.'
            });
        } else if (urlParams.get('error') === 'not_logged_in') {
            Swal.fire({
                icon: 'warning',
                title: 'Login Required',
                text: 'Please login first to access that page.'
            });
        }
    </script>
</body>
</html>