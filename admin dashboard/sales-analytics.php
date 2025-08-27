<?php
require 'db.php'; // your DB connection
session_start();

// Check if the database connection is successful
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$adminName = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin';

// --- Placeholder for Notification Data ---
// In a real application, you would fetch these from your database.
// For demonstration, let's assume some dummy data or an empty state.
$unread_count = 0; // Example: number of unread notifications
$notifications = []; // Example: an array of notification messages

// Example of how you might fetch notifications (replace with your actual logic)
// $notificationQuery = "SELECT id, message, is_read, created_at FROM notifications ORDER BY created_at DESC LIMIT 5";
// $notificationResult = $conn->query($notificationQuery);
// if ($notificationResult) {
//     while ($row = $notificationResult->fetch_assoc()) {
//         $notifications[] = $row;
//         if (!$row['is_read']) {
//             $unread_count++;
//         }
//     }
// }

// Weekly sales: sum totalPrice grouped by day (last 7 days) excluding cancelled bookings
$weeklyQuery = "
    SELECT DATE(created_at) AS sale_date,
           SUM(totalPrice) AS total_sale
    FROM bookingstatus
    WHERE created_at >= CURDATE() - INTERVAL 6 DAY
      AND status != 'Cancelled'
    GROUP BY sale_date
    ORDER BY sale_date ASC
";
$weeklyResult = $conn->query($weeklyQuery);
$weeklySale = [];
$weeklyTotal = 0;
if ($weeklyResult) {
    while ($row = $weeklyResult->fetch_assoc()) {
        $weeklySale[$row['sale_date']] = (float)$row['total_sale'];
        $weeklyTotal += (float)$row['total_sale'];
    }
} else {
    // Handle query error, e.g., log it or display a user-friendly message
    error_log("Weekly sale query failed: " . $conn->error);
}


// Prepare last 7 days labels including days without sales
$weekLabels = [];
$weekData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $weekLabels[] = date('M d', strtotime($date));
    $weekData[] = $weeklySale[$date] ?? 0;
}

// Monthly sales: sum totalPrice grouped by month (last 12 months) excluding cancelled bookings
$monthlyQuery = "
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS sale_month,
           SUM(totalPrice) AS total_sale
    FROM bookingstatus
    WHERE created_at >= DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH, '%Y-%m-01')
      AND status != 'Cancelled'
    GROUP BY sale_month
    ORDER BY sale_month ASC
";
$monthlyResult = $conn->query($monthlyQuery);
$monthlySale = [];
$monthlyTotal = 0;
if ($monthlyResult) {
    while ($row = $monthlyResult->fetch_assoc()) {
        $monthlySale[$row['sale_month']] = (float)$row['total_sale'];
        $monthlyTotal += (float)$row['total_sale'];
    }
} else {
    // Handle query error
    error_log("Monthly sale query failed: " . $conn->error);
}


// Prepare last 12 months labels including months without sales
$monthLabels = [];
$monthData = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthLabels[] = date('M Y', strtotime($month . '-01'));
    $monthData[] = $monthlySale[$month] ?? 0;
}

// Close the database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Sales Analytics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --sidebar-width: 250px;
            --topbar-height: 60px;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        
        /* Top Navbar */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1030;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: red;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Sidebar */
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
            color: rgba(255,255,255,0.8);
            padding: 8px 16px;
            border-radius: 0;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.2);
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--topbar-height);
            padding: 20px;
            transition: all 0.3s;
        }
        
        /* Chart styling */
        .chart-container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        h1, h2 {
            margin-bottom: 15px;
        }
        
        .total-sale { /* Changed from total-revenue */
            font-weight: bold;
            font-size: 1.2em;
            margin-bottom: 20px;
            color: #333;
        }
        
        .time-display {
            margin-bottom: 20px;
            font-style: italic;
            color: #6c757d;
        }
        
        .export-btn {
            margin-bottom: 20px;
        }
        
        /* Notification dropdown */
        .notification-dropdown {
            max-height: 400px;
            overflow-y: auto;
            width: 300px;
        }
        
        .notification-dropdown .unread {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        
        .notification-dropdown small {
            display: block;
            color: #6c757d;
            font-size: 0.8em;
        }

        .notification-dropdown .dropdown-item {
            white-space: normal; /* Allow text to wrap */
            text-decoration: none; /* Remove underline from links */
        }
        
        .view-all {
            text-align: center;
            background-color: #f8f9fa;
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
                        <?php if (isset($unread_count) && $unread_count > 0): ?>
                            <span class="notification-badge"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
                        <?php if (empty($notifications)): ?>
                            <li><a class="dropdown-item" href="#">No new notifications</a></li>
                        <?php else: ?>
                            <?php foreach ($notifications as $notif): ?>
                                <li>
                                    <a class="dropdown-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>" href="notification.php?id=<?php echo htmlspecialchars($notif['id']); ?>">
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

<div class="sidebar" id="sidebar">
    <nav class="nav flex-column">
        <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="analytics.php" class="nav-link"><i class="fas fa-star"></i> Room Rating</a>
        <a href="sales-analytics.php" class="nav-link active"><i class="fas fa-chart-line"></i> Sales Report</a>
        <a href="bookinganaly.php" class="nav-link"><i class="fas fa-calendar-check"></i> Booking Analysis</a>
        <a href="predictive.php" class="nav-link"><i class="fas fa-chart-bar"></i> Predict</a>
        <a href="add-room.php" class="nav-link"><i class="fas fa-plus-circle"></i> Add Room</a>
        <a href="cottages.php" class="nav-link"><i class="fas fa-money-bill-wave"></i> Sales Analytics</a>
        <a href="room-bookings.php" class="nav-link"><i class="fas fa-bed"></i> Room Bookings</a>
        <a href="events.php" class="nav-link"><i class="fas fa-calendar-alt"></i> Events</a>
        <a href="admin_create_staff.php" class="nav-link"><i class="fas fa-user-plus"></i> Add Staff</a>
        <a href="admin_manage_staff.php" class="nav-link"><i class="fas fa-users-cog"></i> Manage Staff</a>
    </nav>
</div>

    <div class="main-content">
        <h1>Sales Analytics</h1> <div class="time-display">
            <p>Today's Date: <?php echo date("F j, Y - l"); ?></p>
            <p id="live-time"></p>
        </div>

        <form method="post" action="export_revenue_excel.php" target="_blank">
            <button type="submit" class="btn btn-primary export-btn">
                <i class="fas fa-file-excel me-2"></i> Export to Excel
            </button>
        </form>

        <div class="chart-container">
            <h2>Weekly Sales (Last 7 Days)</h2> <div class="total-sale">Total Sale: ₱<?php echo number_format($weeklyTotal, 2); ?></div> <canvas id="weeklyRevenueChart"></canvas>
        </div>

        <div class="chart-container">
            <h2>Monthly Sales (Last 12 Months)</h2> <div class="total-sale">Total Sale: ₱<?php echo number_format($monthlyTotal, 2); ?></div> <canvas id="monthlyRevenueChart"></canvas>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateTime() {
            const now = new Date();
            const options = { hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('live-time').innerText = "Current Time: " + now.toLocaleTimeString([], options);
        }
        setInterval(updateTime, 1000);
        updateTime();

        // Logout button functionality
        document.getElementById('logoutBtn').addEventListener('click', function() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        });

        // Profile button functionality
        document.getElementById('profileBtn').addEventListener('click', function() {
            window.location.href = 'admin_profile.php';
        });

        const weeklyCtx = document.getElementById('weeklyRevenueChart').getContext('2d');
        const monthlyCtx = document.getElementById('monthlyRevenueChart').getContext('2d');

        const weeklyChart = new Chart(weeklyCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($weekLabels); ?>,
                datasets: [{
                    label: 'Total Sale (PHP)', // Changed from Total Revenue
                    data: <?php echo json_encode($weekData); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    borderRadius: 5,
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: ctx => '₱' + ctx.parsed.y.toLocaleString()
                        }
                    }
                }
            }
        });

        const monthlyChart = new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($monthLabels); ?>,
                datasets: [{
                    label: 'Total Sale (PHP)', // Changed from Total Revenue
                    data: <?php echo json_encode($monthData); ?>,
                    backgroundColor: 'rgba(255, 159, 64, 0.7)',
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 1,
                    borderRadius: 5,
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: ctx => '₱' + ctx.parsed.y.toLocaleString()
                        }
                    }
                }
            }
        });
    </script>

</body>
</html>