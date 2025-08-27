<?php
session_start();

// Enable error reporting for development. Disable or log errors in production.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';

// Ensure database connection is established
if (!isset($conn) || $conn->connect_error) {
    error_log("Database connection failed: " . ($conn->connect_error ?? "Unknown error during connection."));
    header("Location: error.php"); // Redirect to a generic error page (create this file if it doesn't exist)
    exit();
}

// Check if user is logged in
// Ensure path to login.php is correct, assuming relative to the current script.
if (empty($_SESSION['user_id']) || empty($_SESSION['email'])) {
    header("Location: ../login.php"); // Adjust this path if necessary
    exit();
}

// Store session values for profile, using null coalescing to prevent undefined index notices
$adminName = $_SESSION['fullname'] ?? 'Guest';
$adminEmail = $_SESSION['email'] ?? '';
$adminId = $_SESSION['user_id'] ?? 0; // Default to 0 or an appropriate non-existent ID

// --- Helper Function for Prepared Statements ---
/**
 * Safely executes a prepared statement and returns the statement object or false on failure.
 *
 * @param mysqli $conn The database connection object.
 * @param string $sql The SQL query with placeholders.
 * @param array $params An array of parameters to bind.
 * @param string $types A string representing the types of the parameters (e.g., "isd").
 * @return mysqli_stmt|false The executed statement object on success, or false on failure.
 */
function executePreparedStatement($conn, $sql, $params = [], $types = "") {
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Failed to prepare statement: " . $conn->error . " | SQL: " . $sql);
        return false;
    }

    if (!empty($params) && !empty($types)) {
        // Dynamically bind parameters by reference
        $bind_names = [$types];
        for ($i = 0; $i < count($params); $i++) {
            $bind_names[] = &$params[$i];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_names);
    }

    if (!$stmt->execute()) {
        error_log("Failed to execute statement: " . $stmt->error . " | SQL: " . $sql);
        $stmt->close();
        return false;
    }
    return $stmt;
}

// Get unread notifications count
$unread_count = 0;
$stmt_notif_count = executePreparedStatement($conn, "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE", [$adminId], "i");
if ($stmt_notif_count) {
    $stmt_notif_count->bind_result($unread_count);
    $stmt_notif_count->fetch();
    $stmt_notif_count->close();
}

// Get recent notifications
$notifications = [];
$stmt_notifications = executePreparedStatement($conn, "SELECT id, message, created_at, is_read FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5", [$adminId], "i");
if ($stmt_notifications) {
    $result_notifications = $stmt_notifications->get_result();
    while ($row = $result_notifications->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt_notifications->close();
}

// Handle date filtering
$dateFilterSql = "";
$dateFilterParams = [];
$dateFilterTypes = "";

if (isset($_GET['date_range']) && !empty($_GET['date_range'])) {
    $range = $_GET['date_range'];
    switch ($range) {
        case 'today':
            $dateFilterSql = " AND DATE(b.bookingDate) = CURDATE()";
            break;
        case 'week':
            $dateFilterSql = " AND b.bookingDate >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $dateFilterSql = " AND b.bookingDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            break;
        case 'year':
            $dateFilterSql = " AND b.bookingDate >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
            break;
    }
}

// Base SQL for room type analytics (cottage and VIP)
$baseRoomAnalyticsQuery = "
    SELECT
        r.name AS room_name,
        COUNT(b.id) AS booking_count,
        SUM(CASE WHEN b.status = 'Approved' OR b.status = 'Checked Out' THEN b.totalPrice ELSE 0 END) AS total_sales,
        AVG(b.stayDuration) AS avg_stay_duration
    FROM
        bookingstatus b
    JOIN
        rooms r ON b.roomName = r.name
    WHERE
        r.type = ?
        {$dateFilterSql}
    GROUP BY
        r.name
    ORDER BY
        booking_count DESC
";

// Fetch Cottage Analytics
$cottageData = [];
$stmt_cottage = executePreparedStatement($conn, $baseRoomAnalyticsQuery, ['Cottage'], "s");
if ($stmt_cottage) {
    $cottageResult = $stmt_cottage->get_result();
    while ($row = $cottageResult->fetch_assoc()) {
        $cottageData[] = $row;
    }
    $stmt_cottage->close();
} else {
    error_log("Error fetching cottage analytics: " . $conn->error);
}

// Fetch VIP Room Analytics
$vipData = [];
$stmt_vip = executePreparedStatement($conn, $baseRoomAnalyticsQuery, ['VipRoom'], "s");
if ($stmt_vip) {
    $vipResult = $stmt_vip->get_result();
    while ($row = $vipResult->fetch_assoc()) {
        $vipData[] = $row;
    }
    $stmt_vip->close();
} else {
    error_log("Error fetching VIP room analytics: " . $conn->error);
}


// Overall statistics query
$statsQuery = "
    SELECT
        COUNT(*) AS total_bookings,
        SUM(CASE WHEN status = 'Approved' OR status = 'Checked Out' THEN totalPrice ELSE 0 END) AS total_sales,
        IFNULL(AVG(stayDuration), 0) AS avg_stay_duration, -- Use IFNULL for avg_stay_duration
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) AS approved_count,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) AS rejected_count,
        SUM(CASE WHEN status = 'Checked Out' THEN 1 ELSE 0 END) AS checked_out_count,
        SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled_count
    FROM
        bookingstatus
    WHERE 1=1
    {$dateFilterSql}
";

$stats = ['total_bookings' => 0, 'total_sales' => 0, 'avg_stay_duration' => 0,
          'approved_count' => 0, 'pending_count' => 0, 'rejected_count' => 0,
          'checked_out_count' => 0, 'cancelled_count' => 0];

$stmt_stats = executePreparedStatement($conn, $statsQuery, $dateFilterParams, $dateFilterTypes); // No specific params or types for 1=1 query
if ($stmt_stats) {
    $result_stats = $stmt_stats->get_result();
    $fetchedStats = $result_stats->fetch_assoc();
    if ($fetchedStats) {
        $stats = $fetchedStats;
    }
    $stmt_stats->close();
} else {
    error_log("Error fetching overall statistics: " . $conn->error);
}

// Get data for sales chart
$chartQuery = "
    SELECT
        r.type,
        r.name,
        SUM(CASE WHEN b.status = 'Approved' OR b.status = 'Checked Out' THEN b.totalPrice ELSE 0 END) AS sales
    FROM
        bookingstatus b
    JOIN
        rooms r ON b.roomName = r.name
    WHERE 1=1
    {$dateFilterSql}
    GROUP BY
        r.type, r.name
    ORDER BY
        sales DESC
";

$chartData = ['labels' => [], 'data' => [], 'colors' => []];
// A more appealing and consistent color palette
$colorPalette = [
    '#4285F4', '#EA4335', '#FBBC05', '#34A853', // Google colors
    '#9C27B0', '#FF5722', '#009688', '#E91E63', // Material Design
    '#2196F3', '#FFC107', '#4CAF50', '#795548'
];
$colorIndex = 0;

$stmt_chart = executePreparedStatement($conn, $chartQuery, $dateFilterParams, $dateFilterTypes); // No specific params or types for 1=1 query
if ($stmt_chart) {
    $chartResult = $stmt_chart->get_result();
    while ($row = $chartResult->fetch_assoc()) {
        $chartData['labels'][] = htmlspecialchars($row['name'] . ' (' . $row['type'] . ')');
        $chartData['data'][] = floatval($row['sales']); // Ensure data is float for Chart.js
        $chartData['colors'][] = $colorPalette[$colorIndex % count($colorPalette)];
        $colorIndex++;
    }
    $stmt_chart->close();
} else {
    error_log("Error fetching chart data: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Sales Analytics Dashboard</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #9b59b6;
            --dark-color: #2c3e50;
            --light-color: #f8f9fa;
        }
        
        body {
            display: flex;
            min-height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
        }
        .sidebar {
            width: 250px;
            background-color: var(--dark-color);
            color: white;
            height: 100vh;
            position: fixed;
            top: 56px;
            left: 0;
            overflow-y: auto;
            padding-top: 1rem;
            transition: all 0.3s;
            z-index: 1020; /* Below navbar but above content */
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            margin: 2px 0;
            border-radius: 4px;
            transition: all 0.2s;
            display: flex; /* Use flexbox for icon alignment */
            align-items: center;
        }
        .sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        .sidebar .nav-link.active {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
        }
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px; /* Fixed width for icons */
            text-align: center;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            background-color: var(--light-color);
            width: 100%;
            margin-top: 56px; /* Space for fixed navbar */
        }
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1030;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .notification-badge {
            background-color: var(--danger-color);
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            position: relative;
            top: -10px;
            right: -5px;
            line-height: 1;
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
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 20px;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0 !important;
            display: flex;
            align-items: center;
            color: var(--dark-color);
        }
        .card-header i {
            margin-right: 8px;
        }
        .card-body {
            padding: 20px;
        }
        .summary-card {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            color: white;
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 150px; /* Ensure consistent height */
        }
        .summary-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        .summary-card .value {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 10px 0 5px 0; /* Adjust margin */
        }
        .summary-card .label {
            font-size: 1rem;
            opacity: 0.9;
        }
        /* Summary Card Colors */
        .sales { background: linear-gradient(135deg, #2ecc71, #27ae60); }
        .bookings { background: linear-gradient(135deg, #3498db, #2980b9); }
        .duration { background: linear-gradient(135deg, #9b59b6, #8e44ad); }
        .approved { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .pending-status { background: linear-gradient(135deg, #5bc0de, #337ab7); }
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 15px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
        }
        tr:nth-child(even) { background-color: rgba(0, 0, 0, 0.02); }
        tr:hover { background-color: rgba(52, 152, 219, 0.1); }
        .sales-amount { color: #27ae60; font-weight: bold; }
        .count { color: #e67e22; }
        .duration-text { color: #9b59b6; } /* Consistent class name */
        h1, h2, h3 { color: var(--dark-color); }
        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 30px;
        }
        .filter-container {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .badge-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-flex; /* Align text and icon better */
            align-items: center;
        }
        .badge-status i {
            margin-right: 5px;
        }
        .badge-approved { background-color: #d4edda; color: #155724; }
        .badge-pending { background-color: #fff3cd; color: #856404; }
        .badge-rejected { background-color: #f8d7da; color: #721c24; }
        .badge-checkedout { background-color: #cce5ff; color: #004085; }
        .badge-cancelled { background-color: #e2e3e5; color: #383d41; }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                top: 0;
            }
            .main-content {
                margin-left: 0;
                margin-top: 0; /* Adjust for non-fixed sidebar */
            }
            .navbar {
                position: relative; /* Make navbar not fixed on small screens */
            }
            .d-flex.align-items-center {
                flex-wrap: wrap; /* Allow navbar items to wrap */
                justify-content: center;
            }
            .dropdown.me-3, .ms-auto strong, #profileBtn, #logoutBtn {
                margin-bottom: 10px;
                margin-left: 5px !important;
                margin-right: 5px !important;
            }
            .summary-card {
                padding: 15px;
                min-height: unset; /* Remove min-height */
            }
            .summary-card .value {
                font-size: 1.5rem;
            }
            .summary-card .label {
                font-size: 0.9rem;
            }
            .chart-container {
                height: 300px; /* Adjust chart height for smaller screens */
            }
            .table-responsive table {
                font-size: 0.9rem;
            }
            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php"><i class="fas fa-chart-line me-2"></i>DentoReserve</a>
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
                        <li><a class="dropdown-item text-muted" href="#">No new notifications</a></li>
                    <?php else: ?>
                        <?php foreach ($notifications as $notif): ?>
                            <li>
                                <a class="dropdown-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>" href="notification.php?id=<?php echo htmlspecialchars($notif['id']); ?>">
                                    <?php echo htmlspecialchars($notif['message']); ?>
                                    <small><?php echo date('M j, Y, g:i a', strtotime($notif['created_at'])); ?></small>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item view-all" href="notifications.php">View all notifications</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            Welcome, <strong class="ms-2 me-3"><?php echo htmlspecialchars($adminName); ?></strong>
            <button id="profileBtn" class="btn btn-outline-light btn-sm me-2"><i class="fas fa-user-circle me-1"></i>Profile</button>
            <button id="logoutBtn" class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt me-1"></i>Logout</button>
        </div>
    </div>
</nav>

<div class="sidebar" id="sidebar">
    <nav class="nav flex-column">
        <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="analytics.php" class="nav-link"><i class="fas fa-star"></i> Room Rating</a>
        <a href="bookinganaly.php" class="nav-link"><i class="fas fa-calendar-check"></i> Booking Analysis</a>
        <a href="sales-analytics.php" class="nav-link active"><i class="fas fa-money-bill-wave"></i> Sales Analytics</a>
        <a href="predictive.php" class="nav-link"><i class="fas fa-chart-bar"></i> Predict</a>
        <a href="add-room.php" class="nav-link"><i class="fas fa-plus-circle"></i> Add Room</a>
        <a href="room-bookings.php" class="nav-link"><i class="fas fa-bed"></i> Room Bookings</a>
        <a href="events.php" class="nav-link"><i class="fas fa-calendar-alt"></i> Events</a>
        <a href="admin_create_staff.php" class="nav-link"><i class="fas fa-user-plus"></i> Add Staff</a>
        <a href="admin_manage_staff.php" class="nav-link"><i class="fas fa-users-cog"></i> Manage Staff</a>
        <a href="calen.php" class="nav-link"><i class="fas fa-calendar-days"></i> Calendar</a>
    </nav>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <h1><i class="fas fa-chart-pie me-2"></i>Sales Analytics Dashboard</h1>
        <form method="GET" class="d-flex mt-2 mt-md-0">
            <select name="date_range" class="form-select me-2" onchange="this.form.submit()">
                <option value="" <?= !isset($_GET['date_range']) || $_GET['date_range'] == '' ? 'selected' : '' ?>>All Time</option>
                <option value="today" <?= isset($_GET['date_range']) && $_GET['date_range'] == 'today' ? 'selected' : '' ?>>Today</option>
                <option value="week" <?= isset($_GET['date_range']) && $_GET['date_range'] == 'week' ? 'selected' : '' ?>>Last 7 Days</option>
                <option value="month" <?= isset($_GET['date_range']) && $_GET['date_range'] == 'month' ? 'selected' : '' ?>>Last 30 Days</option>
                <option value="year" <?= isset($_GET['date_range']) && $_GET['date_range'] == 'year' ? 'selected' : '' ?>>Last Year</option>
            </select>
            <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='sales-analytics.php'">
                <i class="fas fa-sync-alt"></i> Reset Filter
            </button>
        </form>
    </div>
    
    <p class="text-muted mb-4"><i class="fas fa-info-circle me-1"></i>Note: Sales calculations only include Approved and Checked Out bookings.</p>

    <div class="row mb-4">
        <div class="col-6 col-md-2">
            <div class="summary-card sales">
                <i class="fas fa-money-bill-wave"></i>
                <div class="value">₱<?= number_format($stats['total_sales'], 2) ?></div>
                <div class="label">Total Sales</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="summary-card bookings">
                <i class="fas fa-calendar-check"></i>
                <div class="value"><?= number_format($stats['total_bookings']) ?></div>
                <div class="label">Total Bookings</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="summary-card duration">
                <i class="fas fa-clock"></i>
                <div class="value"><?= number_format($stats['avg_stay_duration'], 1) ?></div>
                <div class="label">Avg Stay (hrs)</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="summary-card approved">
                <i class="fas fa-check-circle"></i>
                <div class="value"><?= number_format($stats['approved_count']) ?></div>
                <div class="label">Approved</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="summary-card pending-status">
                <i class="fas fa-hourglass-half"></i>
                <div class="value"><?= number_format($stats['pending_count']) ?></div>
                <div class="label">Pending</div>
            </div>
        </div>
         <div class="col-6 col-md-2">
            <div class="summary-card rejected-status" style="background: linear-gradient(135deg, #e74a3b, #cc2a20);">
                <i class="fas fa-times-circle"></i>
                <div class="value"><?= number_format($stats['rejected_count']) ?></div>
                <div class="label">Rejected</div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-chart-bar me-2"></i>Sales by Room
        </div>
        <div class="card-body">
            <div class="chart-container">
                <?php if (empty($chartData['labels'])): ?>
                    <p class="text-center text-muted mt-5">No sales data available for the selected period.</p>
                <?php else: ?>
                    <canvas id="salesChart"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-home me-2"></i>Most Booked Cottages
                </div>
                <div class="card-body">
                    <?php if (!empty($cottageData)): ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Cottage Name</th>
                                        <th>Bookings</th>
                                        <th>Total Sales</th>
                                        <th>Avg Stay</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($cottageData as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['room_name']) ?></td>
                                        <td class="count"><?= number_format($row['booking_count']) ?></td>
                                        <td class="sales-amount">₱<?= number_format($row['total_sales'], 2) ?></td>
                                        <td class="duration-text"><?= number_format($row['avg_stay_duration'], 1) ?> hrs</td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center" role="alert">No cottage bookings found for the selected period.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-crown me-2"></i>Most Booked VIP Rooms
                </div>
                <div class="card-body">
                    <?php if (!empty($vipData)): ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>VIP Room Name</th>
                                        <th>Bookings</th>
                                        <th>Total Sales</th>
                                        <th>Avg Stay</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($vipData as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['room_name']) ?></td>
                                        <td class="count"><?= number_format($row['booking_count']) ?></td>
                                        <td class="sales-amount">₱<?= number_format($row['total_sales'], 2) ?></td>
                                        <td class="duration-text"><?= number_format($row['avg_stay_duration'], 1) ?> hrs</td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center" role="alert">No VIP room bookings found for the selected period.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <i class="fas fa-info-circle me-2"></i>Booking Status Breakdown
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless"> <tbody>
                            <tr>
                                <th>Total Bookings</th>
                                <td><?= number_format($stats['total_bookings']) ?></td>
                            </tr>
                            <tr>
                                <th>Total Sales (Approved & Checked Out)</th>
                                <td class="sales-amount">₱<?= number_format($stats['total_sales'], 2) ?></td>
                            </tr>
                            <tr>
                                <th>Average Stay Duration</th>
                                <td><?= number_format($stats['avg_stay_duration'], 1) ?> hours</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <th>Approved Bookings</th>
                                <td><span class="badge badge-status badge-approved"><i class="fas fa-check"></i><?= number_format($stats['approved_count']) ?></span></td>
                            </tr>
                            <tr>
                                <th>Pending Bookings</th>
                                <td><span class="badge badge-status badge-pending"><i class="fas fa-clock"></i><?= number_format($stats['pending_count']) ?></span></td>
                            </tr>
                            <tr>
                                <th>Rejected Bookings</th>
                                <td><span class="badge badge-status badge-rejected"><i class="fas fa-times"></i><?= number_format($stats['rejected_count']) ?></span></td>
                            </tr>
                            <tr>
                                <th>Checked Out Bookings</th>
                                <td><span class="badge badge-status badge-checkedout"><i class="fas fa-sign-out-alt"></i><?= number_format($stats['checked_out_count']) ?></span></td>
                            </tr>
                            <tr>
                                <th>Cancelled Bookings</th>
                                <td><span class="badge badge-status badge-cancelled"><i class="fas fa-ban"></i><?= number_format($stats['cancelled_count']) ?></span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profileModalLabel"><i class="fas fa-user-circle me-2"></i>My Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong><i class="fas fa-user me-2"></i>Name:</strong> <?php echo htmlspecialchars($adminName); ?></p>
                <p><strong><i class="fas fa-envelope me-2"></i>Email:</strong> <?php echo htmlspecialchars($adminEmail); ?></p>
                <p><strong><i class="fas fa-user-tag me-2"></i>Role:</strong> Administrator</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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

    // Sales Chart
    const ctx = document.getElementById('salesChart').getContext('2d');
    const chartDataExists = <?php echo json_encode(!empty($chartData['labels'])); ?>;

    if (chartDataExists) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartData['labels']) ?>,
                datasets: [{
                    label: 'Sales (₱)',
                    data: <?= json_encode($chartData['data']) ?>,
                    backgroundColor: <?= json_encode($chartData['colors']) ?>,
                    borderColor: 'rgba(255, 255, 255, 0.8)', // Unified border for consistency
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '₱' + context.raw.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Total Sales (₱)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString('en-PH', {minimumFractionDigits: 0, maximumFractionDigits: 0});
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Room Name (Type)'
                        },
                        ticks: {
                            autoSkip: false, // Prevent labels from being skipped
                            maxRotation: 45, // Rotate labels if they overlap
                            minRotation: 45
                        }
                    }
                }
            }
        });
    }
</script>
</body>
</html>

<?php
// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>