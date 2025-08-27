<?php
//Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
// Assuming 'vendor' is in the parent directory relative to this script's location
require '../vendor/autoload.php';

session_start();
include 'db.php';

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

// Function to update room stock
function updateRoomStock($conn, $roomName, $duration) {
    $column = '';
    switch ($duration) {
        case '6h AM': $column = 'stock_6h_am'; break;
        case '6h PM': $column = 'stock_6h_pm'; break;
        case '12h': $column = 'stock_12h'; break;
        default: return false;
    }
    
    $stmt = $conn->prepare("UPDATE rooms SET $column = 1 WHERE name = ?");
    $stmt->bind_param("s", $roomName);
    return $stmt->execute();
}

// Handle status update if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['booking_id'], $_POST['action'], $_POST['confirmed']) && $_POST['confirmed'] === 'yes') {
        $booking_id = intval($_POST['booking_id']);
        $action = $_POST['action'];

        // Get booking details including user's email, name, and check-in date for notifications
        $bookingQuery = $conn->prepare("SELECT b.roomName, b.stayDuration, b.checkinDate, u.email, u.fullname FROM bookingstatus b JOIN users u ON b.user_id = u.id WHERE b.id = ?");
        $bookingQuery->bind_param("i", $booking_id);
        $bookingQuery->execute();
        $bookingResult = $bookingQuery->get_result();
        $bookingDetails = $bookingResult->fetch_assoc();
        $bookingQuery->close();

        $allowedActions = [
            'approve' => 'Approved', 
            'decline' => 'Rejected', 
            'checkout' => 'Checked Out',
            'accept_payment' => 'paid'
        ];

        if (isset($allowedActions[$action]) && $bookingDetails) {
            
            // --- EMAIL NOTIFICATION LOGIC ---
            $emailSubject = '';
            $emailBody = '';
            $userFullName = htmlspecialchars($bookingDetails['fullname']);
            $roomName = htmlspecialchars($bookingDetails['roomName']);
            $reservationDate = htmlspecialchars($bookingDetails['checkinDate']); // Get the reservation date

            if ($action === 'approve') {
                $emailSubject = 'Your Reservation has been Approved!';
                $emailBody = "Hello, {$userFullName}!<br><br>We are pleased to inform you that your reservation (Booking ID: <b>{$booking_id}</b>) for the room <b>{$roomName}</b> on <b>{$reservationDate}</b> has been approved.<br><br>Thank you for choosing Dentofarm Resort!";
            } elseif ($action === 'decline') {
                $emailSubject = 'Update on Your Reservation';
                $emailBody = "Hello, {$userFullName},<br><br>We regret to inform you that your reservation (Booking ID: <b>{$booking_id}</b>) for the room <b>{$roomName}</b> on <b>{$reservationDate}</b> has been declined. If you have any questions, please contact our support.<br><br>Sincerely,<br>The Dentofarm Resort Team";
            } elseif ($action === 'accept_payment') {
                $emailSubject = 'Your Payment has been Confirmed!';
                $emailBody = "Hello, {$userFullName},<br><br>This is to confirm that your payment for reservation (Booking ID: <b>{$booking_id}</b>) for the room <b>{$roomName}</b> on <b>{$reservationDate}</b> has been successfully processed.<br><br>We look forward to welcoming you on your check-in date!<br>The Dentofarm Resort Team";
            }

            // Send the email if a subject and body were set
            if (!empty($emailSubject) && !empty($emailBody)) {
                $mail = new PHPMailer(true);
                try {
                    //Server settings
                    $mail->SMTPDebug = 0;
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'testkoto1230@gmail.com'; // Your email
                    $mail->Password = 'ygoe hzoy wcba gvxy';    // Your app password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    //Recipients
                    $mail->setFrom('testkoto1230@gmail.com', 'Dentofarm Resort');
                    $mail->addAddress($bookingDetails['email'], $bookingDetails['fullname']);

                    //Content
                    $mail->isHTML(true);
                    $mail->Subject = $emailSubject;
                    $mail->Body    = $emailBody;

                    $mail->send();
                } catch (Exception $e) {
                    // Optionally log the error without stopping the script
                    error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
                }
            }
            // --- END OF EMAIL LOGIC ---

            // --- DATABASE UPDATE LOGIC ---
            if ($action === 'checkout') {
                $checkoutTime = date('H:i:s');
                $updateSql = "UPDATE bookingstatus SET status = ?, checkoutTime = ? WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("ssi", $allowedActions[$action], $checkoutTime, $booking_id);
                updateRoomStock($conn, $bookingDetails['roomName'], $bookingDetails['stayDuration']);
            } elseif ($action === 'accept_payment') {
                $updateSql = "UPDATE bookingstatus SET payment_status = ? WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("si", $allowedActions[$action], $booking_id);
            } else {
                $updateSql = "UPDATE bookingstatus SET status = ? WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("si", $allowedActions[$action], $booking_id);
            }

            $updateStmt->execute();
            $updateStmt->close();
            // --- END OF DATABASE LOGIC ---

            header("Location: " . $_SERVER['PHP_SELF'] . "?tab=bookings");
            exit();
        }
    }
}

// Determine active tab and filters
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'bookings';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all';
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';

// Build the query for fetching bookings
$sql = "SELECT * FROM bookingstatus WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $sql .= " AND (guestName LIKE ? OR roomName LIKE ? OR id = ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search]);
    $types .= 'sss';
}
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
if (!empty($date_filter)) {
    $sql .= " AND DATE(created_at) = ?";
    $params[] = $date_filter;
    $types .= 's';
}
$sql .= " ORDER BY created_at DESC";

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
        }
        .sidebar .nav-link.active {
            background-color: #495057;
            border-left: 3px solid #007bff;
        }
        .sidebar .nav-link:hover {
            background-color: #495057;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
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


<div class="sidebar" id="sidebar">
    <nav class="nav flex-column">
        <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
        <a href="analytics.php" class="nav-link"><i class="fas fa-star me-2"></i> Room Rating</a>
        <a href="bookinganaly.php" class="nav-link"><i class="fas fa-calendar-check me-2"></i> Booking Analysis</a>
        <a href="cottages.php" class="nav-link active"><i class="fas fa-money-bill-wave me-2"></i> Sales Analytics</a>
        <a href="predictive.php" class="nav-link"><i class="fas fa-chart-bar me-2"></i> Predict</a>
        <a href="add-room.php" class="nav-link"><i class="fas fa-plus-circle me-2"></i> Add Room</a>
        <a href="room-bookings.php" class="nav-link"><i class="fas fa-bed me-2"></i> Room Bookings</a>
        <a href="events.php" class="nav-link"><i class="fas fa-calendar-alt me-2"></i> Events</a>
        <a href="admin_create_staff.php" class="nav-link"><i class="fas fa-user-plus me-2"></i> Add Staff</a>
        <a href="admin_manage_staff.php" class="nav-link"><i class="fas fa-users-cog me-2"></i> Manage Staff</a>
    </nav>
</div>

<div class="main-content">
    <h2 class="mb-4">Cottages Management</h2>

    <ul class="nav nav-tabs mb-4" id="cottageTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="bookings-tab-btn" data-bs-toggle="tab" data-bs-target="#bookings-tab-pane" type="button" role="tab" aria-controls="bookings-tab-pane" aria-selected="true">Bookings Management</button>
        </li>
    </ul>

    <div class="tab-content" id="cottageTabsContent">
        <div class="tab-pane fade show active" id="bookings-tab-pane" role="tabpanel" aria-labelledby="bookings-tab-btn">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">Bookings Management</h3>
                </div>
                <div class="card-body">
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
                                    $remainingSeconds = (20 * 3600) - $elapsedSeconds; // 20 hours in seconds
                                    
                                    if ($paymentStatus === 'unpaid' && $remainingSeconds > 0) {
                                        $remainingHours = floor($remainingSeconds / 3600);
                                        $remainingMinutes = floor(($remainingSeconds % 3600) / 60);
                                        $remainingSecs = $remainingSeconds % 60;
                                        
                                        $countdownClass = 'countdown-warning';
                                        if ($remainingHours < 1) {
                                            $countdownClass = 'countdown-danger';
                                        }
                                        
                                        $countdownHtml = '<div class="countdown-timer '.$countdownClass.'" data-endtime="'.($createdAt + (20 * 3600)).'">';
                                        $countdownHtml .= sprintf('%02d:%02d:%02d', $remainingHours, $remainingMinutes, $remainingSecs);
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
                                            if (!empty($validIdPath)) {
                                                // Correctly construct the URL for the proof
                                                $url = '/resortwebnew1/' . $validIdPath;
                                                echo '<a href="' . $url . '" target="_blank" class="btn btn-sm btn-outline-info">View ID</a>';
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
                                            <?php if (!empty($booking['payment_proof'])): 
                                                // Correctly construct the URL for the proof
                                                $proofUrl = '/resortwebnew1/' . ltrim($booking['payment_proof'], '/');
                                            ?>
                                                <br><a href="<?= htmlspecialchars($proofUrl); ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-1">View Proof</a>
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
                                                <button type="button" class="btn btn-warning btn-sm text-dark mb-1" onclick="showConfirmModal('accept_payment', <?= $booking['id'] ?>, 'Are you sure you want to accept payment for booking #<?= $booking['id'] ?>?')">Accept Payment</button>
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

    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="confirmModalText"></p>
                </div>
                <div class="modal-footer">
                    <form id="confirmForm" method="POST">
                        <input type="hidden" name="booking_id" id="confirmBookingId">
                        <input type="hidden" name="action" id="confirmAction">
                        <input type="hidden" name="confirmed" value="yes">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Cancel</button>
                        <button type="submit" class="btn btn-primary">Yes, Confirm</button>
                    </form>
                </div>
            </div>
        </div>
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
                <p><strong>Name:</strong> <?php echo htmlspecialchars($adminName); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($adminEmail); ?></p>
                <p><strong>Role:</strong> Administrator</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Show confirmation modal (using Bootstrap's built-in modal)
    function showConfirmModal(action, bookingId, message) {
        document.getElementById('confirmModalText').textContent = message;
        document.getElementById('confirmBookingId').value = bookingId;
        document.getElementById('confirmAction').value = action;
        var confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
        confirmModal.show();
    }

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
    
    // Countdown timer function
    function updateCountdownTimers() {
        const timers = document.querySelectorAll('.countdown-timer');
        const now = Math.floor(Date.now() / 1000); // Current time in seconds

        timers.forEach(timer => {
            const endTime = parseInt(timer.getAttribute('data-endtime'));
            const remainingSeconds = endTime - now;

            if (remainingSeconds <= 0) {
                timer.innerHTML = 'Expired';
                timer.className = 'countdown-timer countdown-danger';
                return;
            }

            const hours = Math.floor(remainingSeconds / 3600);
            const minutes = Math.floor((remainingSeconds % 3600) / 60);
            const seconds = remainingSeconds % 60;

            timer.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

            // Update styling based on remaining time
            if (hours < 1) {
                timer.className = 'countdown-timer countdown-danger';
            } else {
                timer.className = 'countdown-timer countdown-warning';
            }
        });
    }

    // Update countdown timers every second
    setInterval(updateCountdownTimers, 1000);
    // Initial call to display timers immediately
    document.addEventListener('DOMContentLoaded', updateCountdownTimers);
</script>

</body>
</html>

<?php
if(isset($stmt)) $stmt->close();
if(isset($conn)) $conn->close();
?>