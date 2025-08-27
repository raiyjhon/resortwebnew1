<?php
session_start();

// Error reporting for development (remove or set to 0 in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php'; // Your database connection file

// Ensure $conn is available from db.php and handle connection errors
if (!isset($conn) || $conn->connect_error) {
    error_log("Database connection failed: " . ($conn->connect_error ?? "Unknown error"));
    header("Location: error.php"); // Redirect to a user-friendly error page
    exit();
}

// Check if user is logged in
if (empty($_SESSION['user_id']) || empty($_SESSION['email'])) {
    header("Location: ../login.php");
    exit();
}

// Store session values
$adminName = htmlspecialchars($_SESSION['fullname'] ?? 'Guest');
$adminEmail = htmlspecialchars($_SESSION['email'] ?? '');
$adminId = $_SESSION['user_id'] ?? 0;

// Function to safely execute prepared statements
function executeStatement($conn, $sql, $params = [], $types = "") {
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }

    if (!empty($params)) {
        if (!$stmt->bind_param($types, ...$params)) {
            error_log("Bind failed: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }

    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        $stmt->close();
        return false;
    }
    return $stmt;
}

// --- Dynamic Dashboard Data Fetching ---

// 1. Total Bookings
$totalBookings = 0;
$stmt = executeStatement($conn, "SELECT COUNT(*) FROM bookingstatus");
if ($stmt) {
    $stmt->bind_result($totalBookings);
    $stmt->fetch();
    $stmt->close();
}

// 2. Available Rooms
$availableRooms = 0;
// We should check for 'available' status rooms
$stmt = executeStatement($conn, "SELECT COUNT(*) FROM rooms WHERE status = 'available'");
if ($stmt) {
    $stmt->bind_result($availableRooms);
    $stmt->fetch();
    $stmt->close();
}

// 3. Pending Bookings (Corrected Logic: 'Pending' status)
$pendingBookings = 0;
$stmt = executeStatement($conn, "SELECT COUNT(*) FROM bookingstatus WHERE status = 'Pending'");
if ($stmt) {
    $stmt->bind_result($pendingBookings);
    $stmt->fetch();
    $stmt->close();
}

// 4. Total Sales for the current month
$totalSales = 0;
$currentMonth = date('Y-m');
$stmt = executeStatement($conn, "SELECT SUM(totalPrice) FROM bookingstatus WHERE (status = 'Approved' OR status = 'Checked Out') AND DATE_FORMAT(checkinDate, '%Y-%m') = ?", [$currentMonth], "s");
if ($stmt) {
    $stmt->bind_result($totalSales);
    $stmt->fetch();
    $stmt->close();
    $totalSales = $totalSales ?? 0;
}

// 5. Monthly Sales Data for Chart (last 6 months)
$monthlySalesLabels = [];
$monthlySalesValues = [];
$temp_monthly_sales = [];

for ($i = 5; $i >= 0; $i--) {
    $date = (new DateTime())->modify("-$i months");
    $month_year_format = $date->format('Y-m');
    $monthlySalesLabels[] = $date->format('M Y');
    $temp_monthly_sales[$month_year_format] = 0;
}

$sql_monthly_sales = "SELECT DATE_FORMAT(checkinDate, '%Y-%m') as month_year, SUM(totalPrice) as sales
                      FROM bookingstatus
                      WHERE (status = 'Approved' OR status = 'Checked Out')
                      AND checkinDate >= DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH, '%Y-%m-01')
                      GROUP BY month_year
                      ORDER BY month_year ASC";

$stmt = executeStatement($conn, $sql_monthly_sales);
if ($stmt) {
    $result_monthly_sales = $stmt->get_result();
    while ($row = $result_monthly_sales->fetch_assoc()) {
        $temp_monthly_sales[$row['month_year']] = $row['sales'];
    }
    $stmt->close();
}

foreach ($monthlySalesLabels as $label) {
    $month_year = DateTime::createFromFormat('M Y', $label)->format('Y-m');
    $monthlySalesValues[] = $temp_monthly_sales[$month_year] ?? 0;
}

// 6. Booking Status Data for Chart (Consolidated logic)
$bookingStatusLabels = ['Approved', 'Pending', 'Rejected'];
$bookingStatusValues = [0, 0, 0];

$stmt = executeStatement($conn, "SELECT status, COUNT(*) as count FROM bookingstatus GROUP BY status");
if ($stmt) {
    $result_status = $stmt->get_result();
    while ($row = $result_status->fetch_assoc()) {
        $status_lower = strtolower($row['status']);
        if ($status_lower === 'approved' || $status_lower === 'checked out') {
            $bookingStatusValues[0] += $row['count'];
        } elseif ($status_lower === 'pending') {
            $bookingStatusValues[1] += $row['count'];
        } elseif ($status_lower === 'rejected') {
            $bookingStatusValues[2] += $row['count'];
        }
    }
    $stmt->close();
}

// 7. Recent Bookings for Table
$recentBookings = [];
$stmt = executeStatement($conn, "SELECT id, guestName, roomName, checkinDate, checkoutTime, status, payment_status, created_at FROM bookingstatus ORDER BY created_at DESC LIMIT 5");
if ($stmt) {
    $result_recent_bookings = $stmt->get_result();
    while ($row = $result_recent_bookings->fetch_assoc()) {
        $recentBookings[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Resort Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
    <style>
        body {
            display: flex;
            min-height: 100vh;
            margin: 0;
            padding-top: 56px;
            background-color: #f8f9fa;
        }
        .sidebar {
            width: 250px;
            background-color: #343a40;
            color: white;
            height: calc(100vh - 56px);
            position: fixed;
            top: 56px;
            left: 0;
            overflow-y: auto;
            padding-top: 1rem;
        }
        .sidebar .nav-link {
            color: white;
            padding: 10px 15px;
            display: block;
            transition: background-color 0.3s ease;
        }
        .sidebar .nav-link:hover {
            background-color: #495057;
        }
        .sidebar .nav-link.active {
            background-color: #007bff; /* Highlight active link */
        }
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
        }
        .chart-container {
            margin-bottom: 30px;
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
            position: absolute;
            top: 0px;
            right: -8px;
        }
        .notification-dropdown {
            width: 350px;
            max-height: 450px;
            overflow-y: auto;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-radius: 8px;
            padding: 0;
        }
        .notification-dropdown .reservation-header,
        .notification-dropdown .system-header {
            background-color: #e9ecef;
            font-weight: bold;
            padding: 10px 15px;
            border-bottom: 1px solid #dee2e6;
            color: #343a40;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .notification-dropdown .notification-item-wrapper {
            border-bottom: 1px solid #e9ecef;
        }
        .notification-dropdown .notification-item-wrapper:last-of-type {
            border-bottom: none;
        }
        .notification-dropdown .notification-item {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            color: #212529;
            text-decoration: none;
            transition: background-color 0.2s ease;
        }
        .notification-dropdown .notification-item:hover {
            background-color: #f1f1f1;
        }
        .notification-dropdown .notification-item.unread {
            background-color: #e7f3ff;
            font-weight: 500;
        }
        .notification-dropdown .notification-type-icon {
            margin-right: 10px;
            font-size: 1.1em;
            color: #6c757d;
        }
        .notification-dropdown .notification-content {
            flex-grow: 1;
        }
        .notification-dropdown .notification-message {
            margin-bottom: 3px;
            line-height: 1.3;
        }
        .notification-dropdown .notification-time {
            display: block;
            color: #999;
            font-size: 0.75em;
            font-weight: normal;
        }
        .notification-dropdown .status-pill {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 12px;
            font-size: 0.7em;
            font-weight: bold;
            margin-left: 8px;
            white-space: nowrap;
        }
        .notification-dropdown .status-approved, .notification-dropdown .status-checked-out { background-color: #d4edda; color: #155724; }
        .notification-dropdown .status-pending { background-color: #fff3cd; color: #856404; }
        .notification-dropdown .status-rejected { background-color: #f8d7da; color: #721c24; }
        .notification-dropdown .no-notifications {
            padding: 15px;
            text-align: center;
            color: #6c757d;
        }
        .notification-dropdown .view-all {
            text-align: center;
            background-color: #f8f9fa;
            font-weight: bold;
            padding: 10px;
            border-top: 1px solid #dee2e6;
            color: #007bff;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
        }
        .card-stat {
            height: 100%;
        }
        .card-stat .stat-value {
            font-size: 2rem;
            font-weight: bold;
        }
        .booking-status {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8em;
            font-weight: bold;
            display: inline-block;
            color: white;
        }
        .booking-status.pending { background-color: #ffc107; }
        .booking-status.approved, .booking-status.paid { background-color: #28a745; }
        .booking-status.rejected, .booking-status.unpaid { background-color: #dc3545; }
        .booking-status.checked-out { background-color: #17a2b8; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">DentoReserve</a>
        <div class="ms-auto text-white d-flex align-items-center">
            <?php
            // Function to fetch reservations with proper formatting
            function fetchReservations($conn, $today, $type, $adminId) {
                $reservations = [];
                $sql = "";
                $params = [$today];
                $types = "s";
                $header = "";

                if ($type === 'today') {
                    $sql = "SELECT id, guestName, roomName, checkinDate, checkinTime, status, payment_status FROM bookingstatus WHERE DATE(checkinDate) = ? ORDER BY checkinTime ASC";
                    $header = "Today's Reservations";
                } elseif ($type === 'upcoming') {
                    $sql = "SELECT id, guestName, roomName, checkinDate, checkinTime, status, payment_status FROM bookingstatus WHERE DATE(checkinDate) > ? ORDER BY checkinDate ASC, checkinTime ASC";
                    $header = "Upcoming Reservations";
                } elseif ($type === 'system') {
                    $sql = "SELECT id, message, created_at, is_read FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
                    $params = [$adminId];
                    $types = "i";
                    $header = "System Notifications";
                }

                $stmt = executeStatement($conn, $sql, $params, $types);
                if ($stmt) {
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        $reservations[] = $row;
                    }
                    $stmt->close();
                }
                return ['header' => $header, 'items' => $reservations, 'type' => $type];
            }

            $unread_count = 0;
            $stmt = executeStatement($conn, "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE", [$adminId], "i");
            if ($stmt) {
                $stmt->bind_result($unread_count);
                $stmt->fetch();
                $stmt->close();
            }

            $today = date('Y-m-d');
            $notifications_data = [
                fetchReservations($conn, $today, 'today', $adminId),
                fetchReservations($conn, $today, 'upcoming', $adminId),
                fetchReservations($conn, $today, 'system', $adminId)
            ];

            $has_notifications = false;
            foreach ($notifications_data as $data) {
                if (!empty($data['items'])) {
                    $has_notifications = true;
                    break;
                }
            }
            ?>
            <div class="dropdown me-3">
                <a href="#" class="text-white dropdown-toggle position-relative" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell"></i>
                    <?php if ($unread_count > 0): ?>
                        <span class="notification-badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
                    <?php if (!$has_notifications): ?>
                        <li><div class="no-notifications">No new notifications</div></li>
                    <?php else: ?>
                        <?php foreach ($notifications_data as $data): ?>
                            <?php if (!empty($data['items'])): ?>
                                <li><div class="reservation-header"><?php echo htmlspecialchars($data['header']); ?></div></li>
                                <?php foreach ($data['items'] as $notif): ?>
                                    <li>
                                        <div class="notification-item-wrapper">
                                            <?php
                                            $link = "#";
                                            $icon = "fas fa-info-circle";
                                            $message = htmlspecialchars($notif['message'] ?? '');
                                            $time = htmlspecialchars(date('M j, g:i a', strtotime($notif['created_at'] ?? 'now')));
                                            $status_pill = '';
                                            $is_read_class = $notif['is_read'] ?? true ? '' : 'unread';

                                            if ($data['type'] === 'today' || $data['type'] === 'upcoming') {
                                                $bookingId = htmlspecialchars($notif['id'] ?? '');
                                                $link = "room-bookings.php?search=" . $bookingId;
                                                $status = strtolower(htmlspecialchars($notif['status'] ?? ''));
                                                $display_status = ucfirst($status);
                                                $status_class = "status-" . str_replace(' ', '-', $status);
                                                $status_pill = '<span class="status-pill ' . $status_class . '">' . $display_status . '</span>';
                                                $time = htmlspecialchars(date('g:i A', strtotime($notif['checkinTime'] ?? 'now')));
                                                $message = htmlspecialchars($notif['guestName'] . " - " . $notif['roomName']);
                                                $icon = "fas fa-calendar-alt";
                                                if ($data['type'] === 'today') {
                                                    $message = "Reservation #{$bookingId}: " . $message;
                                                    $icon = "fas fa-calendar-check";
                                                } else {
                                                    $notification_date = date('M j', strtotime($notif['checkinDate']));
                                                    if ($notif['checkinDate'] == date('Y-m-d', strtotime('+1 day'))) {
                                                        $notification_date = 'Tomorrow';
                                                    }
                                                    $message = "{$notification_date} - Reservation #{$bookingId}: " . $message;
                                                }
                                            }
                                            ?>
                                            <a class="notification-item <?php echo $is_read_class; ?>" href="<?php echo $link; ?>">
                                                <i class="<?php echo $icon; ?> notification-type-icon"></i>
                                                <div class="notification-content">
                                                    <div class="notification-message">
                                                        <?php echo $message; ?>
                                                        <?php echo $status_pill; ?>
                                                    </div>
                                                    <small class="notification-time"><?php echo $time; ?></small>
                                                </div>
                                            </a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <li><a class="dropdown-item view-all" href="notifications.php">View all notifications</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            Welcome, <strong class="ms-2 me-3"><?php echo $adminName; ?></strong>
            <button id="profileBtn" class="btn btn-outline-light btn-sm me-2">My Profile</button>
            <button id="logoutBtn" class="btn btn-danger btn-sm">Logout</button>
        </div>
    </div>
</nav>

<div class="sidebar" id="sidebar">
    <nav class="nav flex-column">
        <a href="dashboard.php" class="nav-link active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="analytics.php" class="nav-link"><i class="fas fa-star"></i> Room Rating</a>
        <a href="bookinganaly.php" class="nav-link"><i class="fas fa-calendar-check"></i> Booking Analysis</a>
        <a href="cottages.php" class="nav-link"><i class="fas fa-money-bill-wave"></i> Sales Analytics</a>
        <a href="predictive.php" class="nav-link"><i class="fas fa-chart-bar"></i> Predict</a>
        <a href="add-room.php" class="nav-link"><i class="fas fa-plus-circle"></i> Add Room</a>
        <a href="room-bookings.php" class="nav-link"><i class="fas fa-bed"></i> Room Bookings</a>
        <a href="events.php" class="nav-link"><i class="fas fa-calendar-alt"></i> Events</a>
        <a href="admin_create_staff.php" class="nav-link"><i class="fas fa-user-plus"></i> Add Staff</a>
        <a href="admin_manage_staff.php" class="nav-link"><i class="fas fa-users-cog"></i> Manage Staff</a>
        <a href="calen.php" class="nav-link"><i class="fas fa-calendar"></i> Calendar</a>
    </nav>
</div>

<div class="main-content">
    <div class="container-fluid">
        <h1 class="mt-4 mb-4">Admin Dashboard</h1>

        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card text-white bg-primary h-100 card-stat">
                    <div class="card-body">
                        <h5 class="card-title">Total Bookings</h5>
                        <p class="card-text stat-value"><?php echo number_format($totalBookings); ?></p>
                        <p class="card-text"><small>All time</small></p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card text-white bg-success h-100 card-stat">
                    <div class="card-body">
                        <h5 class="card-title">Available Rooms</h5>
                        <p class="card-text stat-value"><?php echo number_format($availableRooms); ?></p>
                        <p class="card-text"><small>Currently</small></p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card text-dark bg-warning h-100 card-stat">
                    <div class="card-body">
                        <h5 class="card-title">Pending Bookings</h5>
                        <p class="card-text stat-value"><?php echo number_format($pendingBookings); ?></p>
                        <p class="card-text"><small>Awaiting action</small></p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card text-white bg-info h-100 card-stat">
                    <div class="card-body">
                        <h5 class="card-title">Monthly Sales</h5>
                        <p class="card-text stat-value">₱<?php echo number_format($totalSales, 2); ?></p>
                        <p class="card-text"><small>This month</small></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="card shadow-sm chart-container">
                    <div class="card-body">
                        <h5 class="card-title">Monthly Sales Overview</h5>
                        <canvas id="monthlyRevenueChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm chart-container">
                    <div class="card-body">
                        <h5 class="card-title">Booking Status</h5>
                        <canvas id="bookingStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Recent Bookings</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Guest</th>
                                        <th>Room</th>
                                        <th>Check-in</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Booked At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentBookings)): ?>
                                        <tr><td colspan="7" class="text-center">No recent bookings.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($recentBookings as $booking): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($booking['id']); ?></td>
                                                <td><?php echo htmlspecialchars($booking['guestName']); ?></td>
                                                <td><?php echo htmlspecialchars($booking['roomName']); ?></td>
                                                <td><?php echo htmlspecialchars(date('M d, Y', strtotime($booking['checkinDate']))); ?></td>
                                                <td><span class="booking-status <?php echo strtolower(htmlspecialchars($booking['status'])); ?>"><?php echo htmlspecialchars($booking['status']); ?></span></td>
                                                <td><span class="booking-status <?php echo strtolower(htmlspecialchars($booking['payment_status'])); ?>"><?php echo htmlspecialchars($booking['payment_status']); ?></span></td>
                                                <td><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($booking['created_at']))); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.getElementById('logoutBtn').addEventListener('click', function() {
        Swal.fire({
            title: 'Logout',
            text: "Are you sure you want to logout?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, logout'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../logout.php';
            }
        });
    });

    document.getElementById('profileBtn').addEventListener('click', function() {
        // Redirect to a profile page or show a modal
        alert('My Profile clicked! Create a profile page or modal.');
    });

    Chart.register(ChartDataLabels);

    // Monthly Sales Chart
    const monthlyRevenueCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
    new Chart(monthlyRevenueCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($monthlySalesLabels); ?>,
            datasets: [{
                label: 'Sales (₱)',
                data: <?php echo json_encode($monthlySalesValues); ?>,
                backgroundColor: 'rgba(0, 123, 255, 0.2)',
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                datalabels: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Total Sales (₱)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Month'
                    }
                }
            }
        }
    });

    // Booking Status Chart
    const bookingStatusCtx = document.getElementById('bookingStatusChart').getContext('2d');
    new Chart(bookingStatusCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($bookingStatusLabels); ?>,
            datasets: [{
                data: <?php echo json_encode($bookingStatusValues); ?>,
                backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                title: { display: false },
                datalabels: {
                    formatter: (value, ctx) => {
                        let sum = 0;
                        let dataArr = ctx.chart.data.datasets[0].data;
                        dataArr.map(data => { sum += data; });
                        if (sum === 0) return '0%';
                        let percentage = (value * 100 / sum).toFixed(2) + "%";
                        return percentage;
                    },
                    color: '#fff',
                }
            }
        }
    });
</script>
</body>
</html>

<?php
if (isset($conn)) {
    $conn->close();
}
?>