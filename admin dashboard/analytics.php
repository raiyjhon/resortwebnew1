<?php
session_start();

// Enable error reporting for development. Disable or log errors in production.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db.php';  // Your DB connection file

// Ensure database connection is established
if (!isset($conn) || $conn->connect_error) {
    error_log("Database connection failed: " . ($conn->connect_error ?? "Unknown error during connection."));
    header("Location: error.php"); // Redirect to a generic error page
    exit();
}

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
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

// Fetch rooms and their rating stats
// Using a prepared statement for the room query is not strictly necessary here
// as there are no user inputs, but it's good practice for consistency
// and if any part of the query might become dynamic in the future.
$query_rooms_ratings = "
    SELECT r.id, r.name,
           IFNULL(AVG(rt.rating), 0) AS avg_rating,
           COUNT(rt.rating) AS rating_count
    FROM rooms r
    LEFT JOIN ratings rt ON r.id = rt.room_id
    GROUP BY r.id, r.name
    ORDER BY avg_rating DESC
";

$rooms = [];
// For simple SELECT queries without user input, query() is fine.
// If room IDs or names were dynamically searched, a prepared statement would be essential.
$result_rooms_ratings = $conn->query($query_rooms_ratings);
if ($result_rooms_ratings) {
    while ($row = $result_rooms_ratings->fetch_assoc()) {
        $rooms[] = $row;
    }
} else {
    error_log("Failed to fetch room ratings: " . $conn->error);
}

// Data for Chart.js
$chartLabels = [];
$chartData = [];
$chartColors = []; // To store colors for each segment

// Define a palette of colors for the chart for better consistency and aesthetics
$colorPalette = [
    '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
    '#858796', '#fd7e14', '#6f42c1', '#20c997', '#6610f2'
];
$colorIndex = 0;

foreach ($rooms as $room) {
    $chartLabels[] = htmlspecialchars($room['name']);
    // Ensure chart data is numeric and rounded for cleaner presentation
    $chartData[] = round(floatval($room['avg_rating']), 2);
    // Assign colors from the palette, cycling through them
    $chartColors[] = $colorPalette[$colorIndex % count($colorPalette)];
    $colorIndex++;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Room Ratings Analytics</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            display: flex;
            min-height: 100vh;
            margin: 0;
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
            padding: 10px 15px; /* Added padding for better click area */
            display: block; /* Make the whole link clickable */
            transition: background-color 0.3s ease; /* Smooth hover */
        }
        .sidebar .nav-link:hover {
            background-color: #495057;
        }
        .sidebar .nav-link.active {
            background-color: #495057;
        }
        .sidebar .nav-link i {
            margin-right: 10px; /* Space between icon and text */
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
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
            line-height: 1; /* Adjust line-height for better vertical alignment */
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

        /* Room Ratings Specific Styles */
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 30px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin: 20px 0;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
            vertical-align: middle; /* Align text vertically */
        }

        th {
            background-color: #003366;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        tr:nth-child(odd) {
            background-color: white;
        }

        tr:hover {
            background-color: rgba(0, 123, 255, 0.1);
        }

        thead {
            background: linear-gradient(to right, #003366, #004080);
        }

        /* Style for rating cells based on value */
        .rating-cell { /* New class for styling rating values */
            font-weight: bold;
        }

        .rating-excellent {
            color: #28a745;
            background-color: rgba(40, 167, 69, 0.1);
        }

        .rating-good {
            color: #17a2b8;
            background-color: rgba(23, 162, 184, 0.1);
        }

        .rating-average {
            color: #ffc107;
            background-color: rgba(255, 193, 7, 0.1);
        }

        .rating-poor {
            color: #dc3545;
            background-color: rgba(220, 53, 69, 0.1);
        }

        .chart-container {
            width: 90%; /* Increased chart width slightly */
            max-width: 700px; /* Increased max-width */
            margin: 0 auto;
        }

        .center-content {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        h2 {
            color: #003366;
            margin-top: 0;
            border-bottom: 2px solid #003366;
            padding-bottom: 10px;
            margin-bottom: 20px;
            text-align: center; /* Center the heading */
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding-top: 20px; /* Reduce top padding for smaller screens */
            }
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                top: 0; /* Remove fixed positioning adjustment */
                padding-top: 0; /* Remove padding top */
            }
            .navbar {
                position: relative; /* Make navbar not fixed on small screens */
            }
            .card {
                padding: 15px;
            }
            th, td {
                padding: 8px;
                font-size: 0.9em; /* Smaller font for table content */
            }
            .chart-container {
                width: 100%;
            }
            .notification-dropdown {
                width: 250px; /* Adjust dropdown width for small screens */
            }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">DentoReserve</a>
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
            <button id="profileBtn" class="btn btn-outline-light btn-sm me-2">My Profile</button>
            <button id="logoutBtn" class="btn btn-danger btn-sm">Logout</button>
        </div>
    </div>
</nav>

<div class="sidebar" id="sidebar">
  <nav class="nav flex-column">
    <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="analytics.php" class="nav-link active"><i class="fas fa-star"></i> Room Rating</a>
    <a href="bookinganaly.php" class="nav-link"><i class="fas fa-calendar-check"></i> Booking Analysis</a>
    <a href="cottages.php" class="nav-link"><i class="fas fa-money-bill-wave"></i> Sales Analytics</a>
    <a href="predictive.php" class="nav-link"><i class="fas fa-chart-bar"></i> Predict</a>
    <a href="add-room.php" class="nav-link"><i class="fas fa-plus-circle"></i> Add Room</a>
    <a href="room-bookings.php" class="nav-link"><i class="fas fa-bed"></i> Room Bookings</a>
    <a href="events.php" class="nav-link"><i class="fas fa-calendar-alt"></i> Events</a>
    <a href="admin_create_staff.php" class="nav-link"><i class="fas fa-user-plus"></i> Add Staff</a>
    <a href="admin_manage_staff.php" class="nav-link"><i class="fas fa-users-cog"></i> Manage Staff</a>
    <a href="calen.php" class="nav-link"><i class="fas fa-users"></i> Calendar</a>
  </nav>
</div>

<div class="main-content">
    <div class="card center-content">
        <h2>Room Rating Statistics</h2>
        
        <div class="table-responsive"> <table>
                <thead>
                    <tr>
                        <th>Room Name</th>
                        <th>Average Rating</th>
                        <th>Total Ratings</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rooms)): ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted">No room ratings data available.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rooms as $room):
                            // Determine rating class based on value
                            $ratingClass = '';
                            if ($room['avg_rating'] >= 4) {
                                $ratingClass = 'rating-excellent';
                            } elseif ($room['avg_rating'] >= 3) {
                                $ratingClass = 'rating-good';
                            } elseif ($room['avg_rating'] >= 2) {
                                $ratingClass = 'rating-average';
                            } else {
                                $ratingClass = 'rating-poor';
                            }
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($room['name']); ?></td>
                                <td class="rating-cell <?php echo $ratingClass; ?>"><?php echo number_format($room['avg_rating'], 2); ?> <i class="fas fa-star"></i></td>
                                <td><?php echo number_format($room['rating_count']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card center-content">
        <h2>Rating Distribution</h2>
        <div class="chart-container">
            <canvas id="ratingChart"></canvas>
            <?php if (empty($rooms)): ?>
                <p class="text-center text-muted mt-3">No data to display in the chart.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profileModalLabel">My Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($adminName); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($adminEmail); ?></p>
                <p><strong>Role:</strong> Administrator</p>
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

    // Chart.js for Rating Distribution
    const ctx = document.getElementById('ratingChart').getContext('2d');
    const chartDataExists = <?php echo json_encode(!empty($rooms)); ?>;

    if (chartDataExists) {
        const labels = <?php echo json_encode($chartLabels); ?>;
        const data = <?php echo json_encode($chartData); ?>;
        const backgroundColors = <?php echo json_encode($chartColors); ?>; // Use the PHP-generated colors

        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: backgroundColors,
                    borderColor: '#fff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                // Calculate percentage for the tooltip
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total === 0 ? 0 : Math.round((value / total) * 100);
                                return `${label}: ${value} Avg (${percentage}%)`;
                            }
                        }
                    },
                    title: {
                        display: false, // Title is handled by H2 tag in HTML
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