<?php
session_start();
include '../db.php';

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

// Handle status update if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle booking status update
    if (isset($_POST['booking_id'], $_POST['action'], $_POST['confirmed'])) {
        if ($_POST['confirmed'] === 'yes') {
            $booking_id = intval($_POST['booking_id']);

            $allowedActions = [
                'approve' => 'Approved',
                'decline' => 'Rejected',
                'checkout' => 'Checked Out',
                'accept_payment' => 'paid'
            ];

            if (isset($allowedActions[$_POST['action']])) {
                $statusToUpdate = $allowedActions[$_POST['action']];
                $updateSql = "";
                $updateStmt = null;

                if ($_POST['action'] === 'checkout') {
                    // Set checkoutTime to current server time when checking out
                    $checkoutTime = date('H:i:s');
                    $updateSql = "UPDATE bookingstatus SET status = ?, checkoutTime = ? WHERE id = ?";
                    $updateStmt = $conn->prepare($updateSql);
                    $updateStmt->bind_param("ssi", $statusToUpdate, $checkoutTime, $booking_id);
                } elseif ($_POST['action'] === 'accept_payment') {
                    $updateSql = "UPDATE bookingstatus SET payment_status = ? WHERE id = ?";
                    $updateStmt = $conn->prepare($updateSql);
                    $updateStmt->bind_param("si", $statusToUpdate, $booking_id);
                } else {
                    $updateSql = "UPDATE bookingstatus SET status = ? WHERE id = ?";
                    $updateStmt = $conn->prepare($updateSql);
                    $updateStmt->bind_param("si", $statusToUpdate, $booking_id);
                }

                if ($updateStmt) {
                    $updateStmt->execute();
                    $updateStmt->close();
                    $_SESSION['success'] = "Booking #{$booking_id} status updated to '{$statusToUpdate}' successfully!";
                } else {
                    $_SESSION['error'] = "Failed to prepare the update statement.";
                }

                header("Location: " . $_SERVER['PHP_SELF'] . "?tab=bookings");
                exit();
            } else {
                $_SESSION['error'] = "Invalid action specified.";
                header("Location: " . $_SERVER['PHP_SELF'] . "?tab=bookings");
                exit();
            }
        }
    }
}

// Determine active tab (only 'bookings' is available now)
$active_tab = 'bookings';

// Initialize search and filter variables for bookings tab
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all';
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';

// Build the base query for bookings
$sql = "SELECT * FROM bookingstatus WHERE 1=1";
$params = [];
$types = '';

// Add search condition
if (!empty($search)) {
    $sql .= " AND (guestName LIKE ? OR roomName LIKE ? OR id = ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search]);
    $types .= 'sss';
}

// Add status filter
if ($status_filter !== 'all') {
    if ($status_filter === 'payment_pending') {
        $sql .= " AND payment_status = 'pending'";
    } elseif ($status_filter === 'payment_paid') {
        $sql .= " AND payment_status = 'paid'";
    } else {
        $sql .= " AND status = ?";
        $params[] = $status_filter;
        $types .= 's';
    }
}

// Add date filter
if (!empty($date_filter)) {
    $sql .= " AND DATE(created_at) = ?";
    $params[] = $date_filter;
    $types .= 's';
}

$sql .= " ORDER BY created_at DESC";

// Prepare and execute the query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$bookings_result = $stmt->get_result();
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
    <style>
        body {
            display: flex;
            min-height: 100vh;
            margin: 0;
            padding-top: 56px; /* Add padding for fixed navbar */
            background-color: #f8f9fa;
        }
        .main-content {
            margin-left: 0;
            padding: 20px;
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
        .notification-dropdown .dropdown-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            display: block;
            position: relative;
        }
        .notification-dropdown .dropdown-item.unread {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .notification-dropdown .dropdown-item small {
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

        /* Status and Payment Styles */
        .status-pending { color: orange; font-weight: bold; }
        .status-approved { color: green; font-weight: bold; }
        .status-rejected { color: red; font-weight: bold; }
        .status-checked-out { color: blue; font-weight: bold; }
        .payment-pending { color: #FFA500; font-weight: bold; }
        .payment-paid { color: green; font-weight: bold; }
        .payment-unpaid { color: red; font-weight: bold; }

        /* Custom styles for messages */
        .success-message {
            color: green;
            background-color: #d4edda;
            border-color: #c3e6cb;
            padding: .75rem 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: .25rem;
        }
        .error-message {
            color: red;
            background-color: #f8d7da;
            border-color: #f5c6cb;
            padding: .75rem 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: .25rem;
        }

        /* Countdown timer styles */
        .countdown-timer {
            font-weight: bold;
            padding: 3px 6px;
            border-radius: 4px;
            display: inline-block;
        }
        .countdown-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        .countdown-danger {
            background-color: #f8d7da;
            color: #721c24;
            animation: blink 1s infinite;
        }
        @keyframes blink {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
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
            Welcome, <strong class="ms-2 me-3"><?php echo htmlspecialchars($adminName); ?></strong>
            <button id="profileBtn" class="btn btn-outline-light btn-sm me-2">My Profile</button>
            <button id="logoutBtn" class="btn btn-danger btn-sm">Logout</button>
        </div>
    </div>
</nav>

<div class="main-content">
    <h2 class="mb-4">Cottages Management</h2>

    <ul class="nav nav-tabs mb-4" id="cottageTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="bookings-tab-btn" data-bs-toggle="tab" data-bs-target="#bookings-tab-pane" type="button" role="tab" aria-controls="bookings-tab-pane" aria-selected="true" onclick="window.location.href='?tab=bookings'">Bookings Management</button>
        </li>
    </ul>

    <div class="tab-content" id="cottageTabsContent">
        <div class="tab-pane fade show active" id="bookings-tab-pane" role="tabpanel" aria-labelledby="bookings-tab-btn">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">Bookings Management</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success success-message">
                            <?= $_SESSION['success']; ?>
                            <?php unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger error-message">
                            <?= $_SESSION['error']; ?>
                            <?php unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="mb-4 p-3 bg-light rounded">
                        <form method="GET" action="" class="row g-3 align-items-end">
                            <input type="hidden" name="tab" value="bookings">
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search:</label>
                                <input type="text" id="search" name="search" class="form-control" placeholder="Guest name, room name, or booking ID" value="<?= htmlspecialchars($search) ?>">
                            </div>

                            <div class="col-md-3">
                                <label for="status_filter" class="form-label">Status:</label>
                                <select id="status_filter" name="status_filter" class="form-select">
                                    <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                                    <option value="Pending" <?= $status_filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="Approved" <?= $status_filter === 'Approved' ? 'selected' : '' ?>>Approved</option>
                                    <option value="Rejected" <?= $status_filter === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                    <option value="Checked Out" <?= $status_filter === 'Checked Out' ? 'selected' : '' ?>>Checked Out</option>
                                    <option value="payment_pending" <?= $status_filter === 'payment_pending' ? 'selected' : '' ?>>Payment Pending</option>
                                    <option value="payment_paid" <?= $status_filter === 'payment_paid' ? 'selected' : '' ?>>Payment Paid</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="date_filter" class="form-label">Booking Date:</label>
                                <input type="date" id="date_filter" name="date_filter" class="form-control" value="<?= htmlspecialchars($date_filter) ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100 mb-2">Apply Filters</button>
                                <button type="button" class="btn btn-secondary w-100" onclick="window.location.href='?tab=bookings'">Reset Filters</button>
                            </div>
                        </form>
                    </div>

                    <?php if ($bookings_result->num_rows === 0): ?>
                        <div class="alert alert-info" role="alert">
                            No bookings found matching your criteria.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Booking ID</th>
                                        <th>User ID</th>
                                        <th>Guest Name</th>
                                        <th>Room Name</th>
                                        <th>Price</th>
                                        <th>Check-in Date</th>
                                        <th>Check-in Time</th>
                                        <th>Checkout Time</th>
                                        <th>Duration</th>
                                        <th>Guest Count</th>
                                        <th>Guests</th>
                                        <th>Valid ID</th>
                                        <th>Total Price</th>
                                        <th>Status</th>
                                        <th>Payment Status</th>
                                        <th>Booked At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php while ($booking = $bookings_result->fetch_assoc()):
                                    // Calculate countdown for unpaid bookings
                                    $countdownHtml = '';
                                    $paymentStatus = strtolower($booking['payment_status'] ?? 'unpaid');
                                    $createdAt = strtotime($booking['created_at']);
                                    $currentTime = time();
                                    $elapsedSeconds = $currentTime - $createdAt;
                                    $remainingSeconds = (20 * 60) - $elapsedSeconds; // 20 minutes in seconds

                                    if ($paymentStatus === 'unpaid' && $remainingSeconds > 0) {
                                        $remainingMinutes = floor($remainingSeconds / 60);
                                        $remainingSecondsDisplay = $remainingSeconds % 60; // For display, actual value is used for calculation

                                        $countdownClass = 'countdown-warning';
                                        if ($remainingMinutes < 5) {
                                            $countdownClass = 'countdown-danger';
                                        }

                                        $countdownHtml = '<div class="countdown-timer '.$countdownClass.'" data-endtime="'.($createdAt + (20 * 60)).'">';
                                        $countdownHtml .= sprintf('%02d:%02d', $remainingMinutes, $remainingSecondsDisplay);
                                        $countdownHtml .= '</div>';
                                    }
                                ?>
                                    <tr>
                                        <td><?= $booking['id']; ?></td>
                                        <td><?= $booking['user_id']; ?></td>
                                        <td><?= htmlspecialchars($booking['guestName']); ?></td>
                                        <td><?= htmlspecialchars($booking['roomName']); ?></td>
                                        <td>₱<?= number_format($booking['roomPrice'], 2); ?></td>
                                        <td><?= $booking['checkinDate']; ?></td>
                                        <td><?= $booking['checkinTime']; ?></td>
                                        <td><?= !empty($booking['checkoutTime']) ? $booking['checkoutTime'] : '-'; ?></td>
                                        <td><?= $booking['stayDuration']; ?> hrs</td>
                                        <td><?= $booking['guestCount']; ?></td>
                                        <td>
                                            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#guestModal<?= $booking['id']; ?>">View Guests</button>

                                            <div class="modal fade" id="guestModal<?= $booking['id']; ?>" tabindex="-1" aria-labelledby="guestModalLabel<?= $booking['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="guestModalLabel<?= $booking['id']; ?>">Guest Details for Booking #<?= $booking['id']; ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <ul class="list-group">
                                                            <?php
                                                            $guestSql = "SELECT guest_name, guest_age FROM booking_guests WHERE booking_id = ?";
                                                            $guestStmt = $conn->prepare($guestSql);
                                                            $guestStmt->bind_param("i", $booking['id']);
                                                            $guestStmt->execute();
                                                            $guestResult = $guestStmt->get_result();
                                                            if ($guestResult->num_rows > 0) {
                                                                while ($g = $guestResult->fetch_assoc()):
                                                                    echo "<li class='list-group-item'>" . htmlspecialchars($g['guest_name']) . " (Age: " . htmlspecialchars($g['guest_age']) . ")</li>";
                                                                endwhile;
                                                            } else {
                                                                echo "<li class='list-group-item'>No guest details available.</li>";
                                                            }
                                                            $guestStmt->close();
                                                            ?>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $validIdPath = $booking['validId'];
                                            $base = '/resortwebnew1/'; // Ensure this matches your actual base path
                                            if (!empty($validIdPath)) {
                                                echo '<a href="' . $base . $validIdPath . '" target="_blank" class="btn btn-sm btn-outline-info">View ID</a>';
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                        <td>₱<?= number_format($booking['totalPrice'], 2); ?></td>
                                        <td class="status-<?= strtolower(str_replace(' ', '-', $booking['status'])); ?>">
                                            <?= $booking['status']; ?>
                                        </td>
                                        <td class="<?php
                                            $payment_status = strtolower($booking['payment_status'] ?? 'unpaid');
                                            echo $payment_status === 'pending' ? 'payment-pending' :
                                                ($payment_status === 'paid' ? 'payment-paid' : 'payment-unpaid');
                                        ?>">
                                            <?= ucfirst($booking['payment_status'] ?? 'Unpaid'); ?>
                                            <?php if (!empty($booking['payment_proof'])): ?>
                                                <br><a href="<?= htmlspecialchars($booking['payment_proof']); ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-1">View Proof</a>
                                            <?php endif; ?>
                                            <?= $countdownHtml ?>
                                        </td>
                                        <td><?= $booking['created_at']; ?></td>
                                        <td>
                                            <?php if ($booking['status'] === 'Pending'): ?>
                                                <button type="button" class="btn btn-success btn-sm mb-1" onclick="showConfirmModal('approve', <?= $booking['id'] ?>, 'Are you sure you want to approve booking #<?= $booking['id'] ?>?')">Approve</button>
                                                <button type="button" class="btn btn-danger btn-sm mb-1" onclick="showConfirmModal('decline', <?= $booking['id'] ?>, 'Are you sure you want to decline booking #<?= $booking['id'] ?>?')">Decline</button>
                                            <?php elseif ($booking['status'] === 'Approved'): ?>
                                                <button type="button" class="btn btn-primary btn-sm mb-1" onclick="showConfirmModal('checkout', <?= $booking['id'] ?>, 'Are you sure you want to checkout booking #<?= $booking['id'] ?>?')">Checkout</button>
                                            <?php endif; ?>

                                            <?php if (($booking['payment_status'] ?? '') === 'pending' && $booking['status'] === 'Approved'): ?>
                                                <button type="button" class="btn btn-warning btn-sm text-dark mb-1" onclick="showConfirmModal('accept_payment', <?= $booking['id'] ?>, 'Are you sure you want to mark payment for booking #<?= $booking['id'] ?> as paid?')">Accept Payment</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
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
            title: 'Are you sure?',
            text: "You will be logged out!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, logout!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../logout.php';
            }
        })
    });

    document.getElementById('profileBtn').addEventListener('click', function() {
        Swal.fire({
            title: 'Admin Profile',
            html: `
                <p><strong>Name:</strong> <?= htmlspecialchars($adminName); ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($adminEmail); ?></p>
                <p><strong>User ID:</strong> <?= htmlspecialchars($adminId); ?></p>
            `,
            icon: 'info',
            confirmButtonText: 'Close'
        });
    });

    function showConfirmModal(action, bookingId, message) {
        Swal.fire({
            title: 'Confirm Action',
            text: message,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, proceed!'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = ''; // Submit to the same page

                const bookingIdInput = document.createElement('input');
                bookingIdInput.type = 'hidden';
                bookingIdInput.name = 'booking_id';
                bookingIdInput.value = bookingId;
                form.appendChild(bookingIdInput);

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = action;
                form.appendChild(actionInput);

                const confirmedInput = document.createElement('input');
                confirmedInput.type = 'hidden';
                confirmedInput.name = 'confirmed';
                confirmedInput.value = 'yes';
                form.appendChild(confirmedInput);

                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    // Countdown timer logic
    function updateCountdown() {
        const timers = document.querySelectorAll('.countdown-timer');
        timers.forEach(timer => {
            const endTime = parseInt(timer.dataset.endtime) * 1000; // Convert to milliseconds
            const now = new Date().getTime();
            const distance = endTime - now;

            if (distance < 0) {
                timer.innerHTML = "EXPIRED";
                timer.classList.remove('countdown-warning', 'countdown-danger');
                timer.classList.add('countdown-expired'); // Add a class for expired state
                // You might want to trigger a refresh or an AJAX call here to update the booking status
            } else {
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                timer.innerHTML = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

                if (minutes < 5 && !timer.classList.contains('countdown-danger')) {
                    timer.classList.remove('countdown-warning');
                    timer.classList.add('countdown-danger');
                } else if (minutes >= 5 && !timer.classList.contains('countdown-warning')) {
                    timer.classList.remove('countdown-danger');
                    timer.classList.add('countdown-warning');
                }
            }
        });
    }

    // Update countdown every second
    setInterval(updateCountdown, 1000);

    // Initial call to display countdown immediately
    updateCountdown();

</script>
</body>
</html>