<?php
session_start();
include 'db.php'; // your database connection file

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- PAGINATION SETUP ---
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}
$offset = ($page - 1) * $records_per_page;

// Handle POST requests (payment, cancellation, archiving, deletion)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['booking_id']) && is_numeric($_POST['booking_id'])) {
        $booking_id = (int)$_POST['booking_id'];

        // Handle cancellation (which is now deletion as per request)
        if (isset($_POST['cancel_booking'])) {
            try {
                // Verify the booking belongs to the user, is cancellable, and get file paths for deletion
                $verify_sql = "SELECT id, status, validId, payment_proof FROM bookingstatus WHERE id = ? AND user_id = ?";
                $verify_stmt = $conn->prepare($verify_sql);
                $verify_stmt->bind_param("ii", $booking_id, $user_id);
                $verify_stmt->execute();
                $verify_result = $verify_stmt->get_result();

                if ($verify_result->num_rows === 1) {
                    $booking_data = $verify_result->fetch_assoc();

                    // Allow cancellation only for Pending or Approved bookings
                    if ($booking_data['status'] === 'Pending' || $booking_data['status'] === 'Approved') {
                        // Delete physical files associated with the booking
                        if (!empty($booking_data['validId']) && file_exists($booking_data['validId'])) {
                            unlink($booking_data['validId']);
                        }
                        if (!empty($booking_data['payment_proof']) && file_exists($booking_data['payment_proof'])) {
                            unlink($booking_data['payment_proof']);
                        }

                        // First, delete related records (guests)
                        $delete_guests_sql = "DELETE FROM booking_guests WHERE booking_id = ?";
                        $delete_guests_stmt = $conn->prepare($delete_guests_sql);
                        $delete_guests_stmt->bind_param("i", $booking_id);
                        $delete_guests_stmt->execute();
                        $delete_guests_stmt->close();

                        // Then, delete the booking record itself
                        $delete_sql = "DELETE FROM bookingstatus WHERE id = ? AND user_id = ?";
                        $delete_stmt = $conn->prepare($delete_sql);
                        $delete_stmt->bind_param("ii", $booking_id, $user_id);

                        if ($delete_stmt->execute()) {
                            $_SESSION['success_message'] = "Booking #$booking_id has been cancelled and deleted successfully.";
                        } else {
                            throw new Exception("Failed to delete booking after cancellation.");
                        }
                        $delete_stmt->close();
                    } else {
                        $_SESSION['error_message'] = "This booking cannot be cancelled as it's already " . $booking_data['status'] . ".";
                    }
                } else {
                    $_SESSION['error_message'] = "Invalid booking ID or you don't have permission to access this booking.";
                }
                $verify_stmt->close();
            } catch (Exception $e) {
                error_log("Cancellation/Deletion error: " . $e->getMessage());
                $_SESSION['error_message'] = "An error occurred during cancellation: " . $e->getMessage();
            }
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
        // Handle archiving
        else if (isset($_POST['archive_booking'])) {
            try {
                // Verify the booking belongs to the user and get its status
                $verify_sql = "SELECT id, status, payment_status FROM bookingstatus WHERE id = ? AND user_id = ?";
                $verify_stmt = $conn->prepare($verify_sql);
                $verify_stmt->bind_param("ii", $booking_id, $user_id);
                $verify_stmt->execute();
                $verify_result = $verify_stmt->get_result();

                if ($verify_result->num_rows === 1) {
                    $booking_data = $verify_result->fetch_assoc();

                    // Allow archiving only for specific completed statuses to match frontend logic
                    $is_archivable = ($booking_data['status'] === 'Cancelled' || $booking_data['status'] === 'Rejected' || ($booking_data['status'] === 'Approved' && $booking_data['payment_status'] === 'paid'));

                    if ($is_archivable) {
                        $archive_sql = "UPDATE bookingstatus SET status = 'Archived' WHERE id = ? AND user_id = ?";
                        $archive_stmt = $conn->prepare($archive_sql);
                        $archive_stmt->bind_param("ii", $booking_id, $user_id);

                        if ($archive_stmt->execute()) {
                            $_SESSION['success_message'] = "Booking #$booking_id has been archived.";
                        } else {
                            throw new Exception("Failed to archive booking.");
                        }
                        $archive_stmt->close();
                    } else {
                        $_SESSION['error_message'] = "Booking #$booking_id cannot be archived in its current state (Status: " . htmlspecialchars($booking_data['status']) . ", Payment: " . htmlspecialchars($booking_data['payment_status']) . ").";
                    }
                } else {
                    $_SESSION['error_message'] = "Invalid booking ID or you don't have permission to access this booking.";
                }
                $verify_stmt->close();
            } catch (Exception $e) {
                error_log("Archiving error: " . $e->getMessage());
                $_SESSION['error_message'] = $e->getMessage();
            }
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
        // Handle deletion
        else if (isset($_POST['delete_booking'])) {
            try {
                // Verify the booking belongs to the user and get file paths for deletion
                $verify_sql = "SELECT id, validId, payment_proof FROM bookingstatus WHERE id = ? AND user_id = ?";
                $verify_stmt = $conn->prepare($verify_sql);
                $verify_stmt->bind_param("ii", $booking_id, $user_id);
                $verify_stmt->execute();
                $verify_result = $verify_stmt->get_result();

                if ($verify_result->num_rows === 1) {
                    $booking_data = $verify_result->fetch_assoc();

                    // Delete physical files associated with the booking
                    if (!empty($booking_data['validId']) && file_exists($booking_data['validId'])) {
                        unlink($booking_data['validId']);
                    }
                    if (!empty($booking_data['payment_proof']) && file_exists($booking_data['payment_proof'])) {
                        unlink($booking_data['payment_proof']);
                    }

                    // First delete related records (guests)
                    $delete_guests_sql = "DELETE FROM booking_guests WHERE booking_id = ?";
                    $delete_guests_stmt = $conn->prepare($delete_guests_sql);
                    $delete_guests_stmt->bind_param("i", $booking_id);
                    $delete_guests_stmt->execute();
                    $delete_guests_stmt->close();

                    // Then delete the booking
                    $delete_sql = "DELETE FROM bookingstatus WHERE id = ? AND user_id = ?";
                    $delete_stmt = $conn->prepare($delete_sql);
                    $delete_stmt->bind_param("ii", $booking_id, $user_id);

                    if ($delete_stmt->execute()) {
                        $_SESSION['success_message'] = "Booking #$booking_id has been permanently deleted.";
                    } else {
                        throw new Exception("Failed to delete booking.");
                    }
                    $delete_stmt->close();
                } else {
                    $_SESSION['error_message'] = "Invalid booking ID or you don't have permission to access this booking.";
                }
                $verify_stmt->close();
            } catch (Exception $e) {
                error_log("Deletion error: " . $e->getMessage());
                $_SESSION['error_message'] = $e->getMessage();
            }
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
        // Handle payment submission
        else {
            try {
                // Verify the booking belongs to the user first and get status
                $verify_sql = "SELECT id, totalPrice, status, payment_status FROM bookingstatus WHERE id = ? AND user_id = ?";
                $verify_stmt = $conn->prepare($verify_sql);
                $verify_stmt->bind_param("ii", $booking_id, $user_id);
                $verify_stmt->execute();
                $verify_result = $verify_stmt->get_result();

                if ($verify_result->num_rows === 1) {
                    $booking_data = $verify_result->fetch_assoc();

                    // Check if booking is cancelled or already paid/pending payment
                    if ($booking_data['status'] === 'Cancelled') {
                        throw new Exception("Payment cannot be processed because this booking has been cancelled.");
                    }
                    if ($booking_data['payment_status'] === 'paid' || $booking_data['payment_status'] === 'pending') {
                        throw new Exception("This booking payment is already " . $booking_data['payment_status'] . ".");
                    }

                    $total_price = $booking_data['totalPrice'];
                    $required_payment = $total_price * 0.25; // 25% of total price

                    // Handle file upload
                    $payment_proof_path = null;
                    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
                        $upload_dir = 'payment_proofs/';
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }

                        $file_ext = strtolower(pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION));
                        $file_name = 'proof_' . $booking_id . '_' . time() . '.' . $file_ext;
                        $target_file = $upload_dir . $file_name;

                        // Validate file
                        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
                        $max_size = 2 * 1024 * 1024; // 2MB

                        if (in_array($file_ext, $allowed_types) && $_FILES['payment_proof']['size'] <= $max_size) {
                            if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $target_file)) {
                                $payment_proof_path = $target_file;
                            } else {
                                throw new Exception("Failed to move uploaded file.");
                            }
                        } else {
                            throw new Exception("Invalid file type or size too large (max 2MB).");
                        }
                    } else {
                        throw new Exception("Please upload your payment proof.");
                    }

                    // Update the booking status with down payment amount
                    $update_sql = "UPDATE bookingstatus SET payment_status = 'pending', payment_method = 'GCash', payment_proof = ?, amount_paid = ? WHERE id = ? AND user_id = ? AND status != 'Cancelled'";
                    $update_stmt = $conn->prepare($update_sql);

                    if ($update_stmt === false) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }

                    $update_stmt->bind_param("sdii", $payment_proof_path, $required_payment, $booking_id, $user_id);

                    if (!$update_stmt->execute()) {
                        throw new Exception("Execute failed: " . $update_stmt->error);
                    }

                    if ($update_stmt->affected_rows === 0) {
                        throw new Exception("Payment could not be processed. The booking may have been cancelled or already paid/pending verification.");
                    }

                    $_SESSION['success_message'] = "Down payment (25%) of ₱" . number_format($required_payment, 2) . " submitted successfully! We'll verify your payment shortly. Remaining balance of ₱" . number_format($total_price - $required_payment, 2) . " to be paid upon check-in.";
                } else {
                    $_SESSION['error_message'] = "Invalid booking ID or you don't have permission to access this booking.";
                }

                $verify_stmt->close();
                if (isset($update_stmt)) $update_stmt->close();

            } catch (Exception $e) {
                error_log("Payment processing error: " . $e->getMessage());
                $_SESSION['error_message'] = $e->getMessage();
            }
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}

// Fetch bookings for the logged-in user
try {
    // First, count the total number of records for pagination
    $count_sql = "SELECT COUNT(id) FROM bookingstatus WHERE user_id = ? AND status != 'Archived'";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("i", $user_id);
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_row()[0];
    $total_pages = ceil($total_records / $records_per_page);
    $count_stmt->close();

    // Now, fetch the records for the current page
    $sql = "SELECT * FROM bookingstatus WHERE user_id = ? AND status != 'Archived' ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("iii", $user_id, $records_per_page, $offset);

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "An error occurred while fetching your bookings. Please try again later.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Bookings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f7f6;
            color: #333;
        }

        .container {
            max-width: 1300px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 25px;
            font-size: 2em;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 0 10px;
        }

        .book-new-btn {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.3s ease;
        }

        .book-new-btn:hover {
            background-color: #218838;
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        table {
            border-collapse: collapse;
            width: 100%;
            min-width: 900px;
            margin-top: 0;
        }

        th, td {
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background-color: #e9ecef;
            color: #495057;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9em;
            white-space: nowrap;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        img { max-width: 100px; height: auto; }

        .status-badge, .payment-text {
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .status-pending { background-color: #ffeeba; color: #856404; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        .status-cancelled { background-color: #e2e3e5; color: #6c757d; }
        .status-archived { background-color: #d1ecf1; color: #0c5460; }

        .payment-pending { background-color: #fff3cd; color: #856404; }
        .payment-paid { background-color: #d1ecf1; color: #0c5460; }
        .payment-cancelled { background-color: #e2e3e5; color: #6c757d; }

        .payment-details {
            display: flex;
            flex-direction: column;
            gap: 5px;
            font-size: 0.9em;
        }
        .payment-details small {
            display: block;
            margin: 0;
            line-height: 1.2;
        }
        .payment-details .amount { font-weight: bold; color: #333; }
        .payment-details .balance { color: #555; }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert i {
            font-size: 1.2em;
        }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .btn {
            background-color: #007bff;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            white-space: nowrap;
        }

        .btn:hover {
            background-color: #0056b3;
            transform: translateY(-1px);
        }

        .btn-success { background-color: #28a745; }
        .btn-success:hover { background-color: #218838; }

        .btn-danger { background-color: #dc3545; }
        .btn-danger:hover { background-color: #c82333; }

        .btn-secondary { background-color: #6c757d; }
        .btn-secondary:hover { background-color: #5a6268; }

        .btn-info { background-color: #17a2b8; }
        .btn-info:hover { background-color: #138496; }

        .paynow-btn {
            background-color: #ffc107;
            color: #333;
            font-weight: bold;
            border: 1px solid #ffc107;
            padding: 10px 15px;
        }
        .paynow-btn:hover {
            background-color: #e0a800;
            color: white;
        }

        .paid-badge {
            width: 50px;
            height: auto;
            vertical-align: middle;
            margin-right: 5px;
        }

        .proof-link {
            font-weight: bold;
            color: #007bff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .proof-link:hover {
            text-decoration: underline;
        }

        .file-input-container { margin: 15px 0; text-align: left; }
        .file-input-label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; }
        .file-input { width: calc(100% - 20px); padding: 10px; border: 1px solid #ccc; border-radius: 5px; background-color: #fdfdfd; }

        .payment-terms {
            text-align: left;
            font-size: 0.85em;
            color: #777;
            margin-top: 20px;
            line-height: 1.5;
            background-color: #e9f7ef;
            border-left: 4px solid #28a745;
            padding: 10px 15px;
            border-radius: 5px;
        }
        .payment-terms strong { color: #333; }

        .payment-breakdown {
            font-size: 1.1em;
            margin: 15px 0;
            text-align: center;
            line-height: 1.6;
        }
        .payment-breakdown p { margin: 5px 0; }
        .payment-breakdown strong { color: #007bff; }

        /* Modal Styles */
        .guest-modal, .payment-modal, .cancel-modal, .archive-modal, .delete-modal {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 450px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 5px 25px rgba(0,0,0,0.2);
            position: relative;
            text-align: center;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-content h4 {
            margin-top: 0;
            color: #34495e;
            font-size: 1.5em;
            margin-bottom: 15px;
        }

        .modal-content .close {
            position: absolute;
            top: 15px; right: 20px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #6c757d;
            transition: color 0.2s ease;
        }

        .modal-content .close:hover { color: #333; }

        .gcash-qr {
            width: 180px;
            height: 180px;
            margin: 15px auto;
            display: block;
            border: 1px solid #eee;
            border-radius: 5px;
        }

        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-start;
        }

        /* Payment Terms Styling */
        .payment-terms-container {
            margin: 20px 0;
        }

        .payment-terms-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            background-color: #f9f9f9;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .payment-terms-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            color: #333;
        }

        .payment-terms-header i {
            font-size: 24px;
            margin-right: 10px;
            color: #28a745;
        }

        .payment-terms-header h4 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }

        .payment-terms-content ul {
            padding-left: 20px;
            margin: 10px 0;
        }

        .payment-terms-content li {
            margin-bottom: 8px;
            position: relative;
            padding-left: 10px;
        }

        .payment-terms-content li:before {
            content: "•";
            color: #28a745;
            font-weight: bold;
            display: inline-block;
            width: 1em;
            margin-left: -1em;
        }

        .payment-agreement {
            display: flex;
            align-items: center;
            margin: 15px 0;
            padding: 15px 0;
            border-top: 1px solid #e0e0e0;
            border-bottom: 1px solid #e0e0e0;
        }

        .payment-notice {
            display: flex;
            align-items: center;
            margin-top: 15px;
            color: #6c757d;
            font-size: 13px;
        }

        .payment-notice i {
            margin-right: 8px;
            color: #17a2b8;
        }

        .payment-terms-content a {
            color: #17a2b8;
            text-decoration: underline;
        }

        .payment-terms-content a:hover {
            text-decoration: none;
        }

        /* Checkbox Styling */
        .checkbox-wrapper {
            position: relative;
            margin-right: 10px;
        }

        .checkbox-wrapper input[type="checkbox"] {
            opacity: 0;
            position: absolute;
        }

        .checkbox-wrapper label {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #28a745;
            border-radius: 4px;
            cursor: pointer;
            position: relative;
        }

        .checkbox-wrapper input[type="checkbox"]:checked + label:after {
            content: "✓";
            position: absolute;
            top: -2px;
            left: 3px;
            color: #28a745;
            font-weight: bold;
        }

        /* Disabled button styling */
        .btn-disabled {
            background-color: #cccccc !important;
            cursor: not-allowed !important;
            opacity: 0.7;
        }
        
        /* Time slot badge styling */
        .time-slot-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.8em;
        }
        .time-slot-am { background-color: #e3f2fd; color: #0d47a1; }
        .time-slot-pm { background-color: #fff8e1; color: #e65100; }
        .time-slot-whole { background-color: #e8f5e9; color: #1b5e20; }

        /* --- PAGINATION STYLES --- */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 30px;
            padding: 10px;
            list-style: none;
        }
        .pagination a {
            color: #007bff;
            padding: 10px 18px;
            text-decoration: none;
            border: 1px solid #ddd;
            margin: 0 4px;
            border-radius: 5px;
            transition: background-color 0.3s, color 0.3s;
            font-weight: bold;
        }
        .pagination a.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
            cursor: default;
        }
        .pagination a:hover:not(.active) {
            background-color: #f1f1f1;
        }
        .pagination a.disabled {
            color: #ccc;
            cursor: not-allowed;
            border-color: #ddd;
        }

    </style>
</head>
<body>

<div class="container">
    <div class="header-actions">
        <h2>My Bookings</h2>
        <a href="booklogin.php" class="book-new-btn">
            <i class="fas fa-plus-circle"></i> Book New Room
        </a>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-error"><i class="fas fa-times-circle"></i> <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (!isset($error) && $result->num_rows === 0): ?>
        <p style="text-align: center; font-size: 1.1em; color: #555; padding: 30px;"><?php echo ($total_records > 0) ? "No bookings found on this page." : "No active bookings found yet. Click \"Book New Room\" to get started!"; ?></p>
    <?php elseif (!isset($error)): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Guest Name</th>
                        <th>Room Name</th>
                        <th>Price</th>
                        <th>Check-in Date</th>
                        <th>Check-in Time</th>
                        <th>Checkout Time</th>
                        <th>Duration (hours)</th>
                        <th>Time Slot</th>
                        <th>Guests</th>
                        <th>Valid ID</th>
                        <th>Total Price</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Actions</th>
                        <th>Booked At</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($booking = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($booking['id']); ?></td>
                        <td><?php echo htmlspecialchars($booking['guestName']); ?></td>
                        <td><?php echo htmlspecialchars($booking['roomName']); ?></td>
                        <td><?php echo "₱" . number_format($booking['roomPrice'], 2); ?></td>
                        <td><?php echo htmlspecialchars($booking['checkinDate']); ?></td>
                        <td><?php echo htmlspecialchars($booking['checkinTime']); ?></td>
                        <td><?php echo htmlspecialchars($booking['checkoutTime']); ?></td>
                        <td><?php echo htmlspecialchars($booking['stayDuration']); ?></td>
                        <td>
                            <?php 
                            $time_slot = htmlspecialchars($booking['time_slot']);
                            echo '<span class="time-slot-badge time-slot-'.$time_slot.'">'.ucfirst($time_slot).'</span>';
                            ?>
                        </td>
                        <td>
                            <button class="btn btn-secondary" onclick="showGuestModal('modal-<?php echo $booking['id']; ?>')">
                                <i class="fas fa-users"></i> View Guests
                            </button>

                            <div id="modal-<?php echo $booking['id']; ?>" class="guest-modal">
                                <div class="modal-content">
                                    <span class="close" onclick="closeModal('modal-<?php echo $booking['id']; ?>')">&times;</span>
                                    <h4>Guest Details for Booking #<?php echo $booking['id']; ?></h4>
                                    <ul style="list-style: none; padding: 0; text-align: left;">
                                        <?php
                                        $booking_id = $booking['id'];
                                        $guestSql = "SELECT guest_name, guest_type FROM booking_guests WHERE booking_id = ?";
                                        $guestStmt = $conn->prepare($guestSql);
                                        if ($guestStmt) {
                                            $guestStmt->bind_param("i", $booking_id);
                                            $guestStmt->execute();
                                            $guestResult = $guestStmt->get_result();
                                            while ($g = $guestResult->fetch_assoc()):
                                        ?>
                                                <li style="margin-bottom: 5px;"><i class="fas fa-user-tag"></i> <?php echo htmlspecialchars($g['guest_name']) . " - Type: " . htmlspecialchars($g['guest_type']); ?></li>
                                        <?php
                                            endwhile;
                                            $guestStmt->close();
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($booking['validId']) && file_exists($booking['validId'])): ?>
                                <a href="<?php echo htmlspecialchars($booking['validId']); ?>" target="_blank" class="proof-link"><i class="fas fa-id-card"></i> View ID</a>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td><?php echo "₱" . number_format($booking['totalPrice'], 2); ?></td>
                        <td>
                            <span class="status-badge <?php
                                $status_class = strtolower($booking['status']);
                                echo 'status-' . $status_class;
                            ?>">
                                <i class="
                                <?php
                                    if ($status_class === 'pending') echo 'fas fa-clock';
                                    else if ($status_class === 'approved') echo 'fas fa-check-circle';
                                    else if ($status_class === 'rejected') echo 'fas fa-times-circle';
                                    else if ($status_class === 'cancelled') echo 'fas fa-ban';
                                    else if ($status_class === 'archived') echo 'fas fa-archive';
                                ?>
                                "></i>
                                <?php echo htmlspecialchars($booking['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="payment-details">
                            <?php if ($booking['status'] === 'Cancelled' || $booking['payment_status'] === 'cancelled'): ?>
                                <span class="payment-cancelled"><i class="fas fa-ban"></i> Booking Cancelled</span>
                            <?php elseif (empty($booking['payment_status']) || $booking['payment_status'] === 'unpaid'): ?>
                                <button class="paynow-btn" onclick="showPaymentModal('payment-modal-<?php echo $booking['id']; ?>')">
                                    <i class="fas fa-money-bill-wave"></i> Pay 25% Down
                                </button>
                                <small class="amount">₱<?php echo number_format($booking['totalPrice'] * 0.25, 2); ?></small>

                                <div id="payment-modal-<?php echo $booking['id']; ?>" class="payment-modal">
                                    <div class="modal-content">
                                        <span class="close" onclick="closeModal('payment-modal-<?php echo $booking['id']; ?>')">&times;</span>
                                        <h4>GCash Payment - 25% Down</h4>

                                        <div class="payment-breakdown">
                                            <p>Total Price: ₱<?php echo number_format($booking['totalPrice'], 2); ?></p>
                                            <p><strong>Down Payment (25%): ₱<?php echo number_format($booking['totalPrice'] * 0.25, 2); ?></strong></p>
                                            <p>Balance Due: ₱<?php echo number_format($booking['totalPrice'] * 0.75, 2); ?></p>
                                        </div>

                                        <img src="gcash-qr.png" alt="GCash QR Code" class="gcash-qr">

                                        <p style="font-weight: bold;">
                                            Account Name: Your Business Name<br>
                                            Account Number: 09123456789
                                        </p>

                                        <form method="POST" action="" enctype="multipart/form-data" id="paymentForm-<?php echo $booking['id']; ?>">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">

                                            <div class="file-input-container">
                                                <label for="payment_proof_<?php echo $booking['id']; ?>" class="file-input-label">
                                                    Upload Payment Proof:
                                                </label>
                                                <input type="file" id="payment_proof_<?php echo $booking['id']; ?>" name="payment_proof" class="file-input" accept="image/*,.pdf" required>
                                                <small style="display: block; margin-top: 5px; color: #888;">(JPEG, PNG, GIF, or PDF, max 2MB)</small>
                                            </div>

                                            <div class="payment-terms-container">
                                                <div class="payment-terms-card">
                                                    <div class="payment-terms-header">
                                                        <i class="fas fa-money-bill-wave"></i>
                                                        <h4>Payment Terms</h4>
                                                    </div>
                                                    <div class="payment-terms-content">
                                                        <ul>
                                                            <li><strong>25% down payment</strong> required to confirm reservation</li>
                                                            <li><strong>Remaining balance</strong> due upon check-in</li>
                                                            <li><strong>Non-refundable</strong> but reschedulable with 24 hours notice</li>
                                                            <li>Reservation confirmed after <strong>payment verification</strong></li>
                                                        </ul>
                                                        <div class="payment-agreement">
                                                            <div class="checkbox-wrapper">
                                                                <input type="checkbox" id="agreePaymentTerms-<?php echo $booking['id']; ?>" name="agreePaymentTerms" required />
                                                                <label for="agreePaymentTerms-<?php echo $booking['id']; ?>"></label>
                                                            </div>
                                                            <span>I understand and agree to the payment terms</span>
                                                        </div>
                                                        <div class="payment-notice">
                                                            <i class="fas fa-info-circle"></i>
                                                            <span>Please review our full <a href="/payment-policy" target="_blank">Payment Policy</a> for details</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <button type="submit" class="btn btn-success btn-disabled" id="submitPaymentBtn-<?php echo $booking['id']; ?>" disabled>Submit Payment Proof</button>
                                        </form>
                                    </div>
                                </div>
                            <?php elseif ($booking['payment_status'] === 'pending'): ?>
                                <span class="payment-pending"><i class="fas fa-hourglass-half"></i> Verifying Payment</span>
                                <small class="amount">Paid: ₱<?php echo number_format($booking['amount_paid'], 2); ?></small>
                                <small class="balance">Balance: ₱<?php echo number_format($booking['totalPrice'] - $booking['amount_paid'], 2); ?></small>
                                <?php if (!empty($booking['payment_proof'])): ?>
                                    <a href="<?php echo htmlspecialchars($booking['payment_proof']); ?>" target="_blank" class="proof-link">
                                        <i class="fas fa-receipt"></i> View Proof
                                    </a>
                                <?php endif; ?>
                            <?php elseif ($booking['payment_status'] === 'paid'): ?>
                                <span class="payment-paid"><i class="fas fa-check-circle"></i> Payment Complete</span>
                                <small class="amount">Paid: ₱<?php echo number_format($booking['amount_paid'], 2); ?></small>
                                <small class="balance">Balance: ₱<?php echo number_format($booking['totalPrice'] - $booking['amount_paid'], 2); ?></small>
                                <?php if (!empty($booking['payment_proof'])): ?>
                                    <a href="<?php echo htmlspecialchars($booking['payment_proof']); ?>" target="_blank" class="proof-link">
                                        <i class="fas fa-receipt"></i> View Proof
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <?php if ($booking['status'] !== 'Cancelled' && $booking['status'] !== 'Rejected' && $booking['status'] !== 'Archived' && $booking['payment_status'] !== 'paid'): ?>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <button type="button" class="btn btn-danger" onclick="showCancelModal('cancel-modal-<?php echo $booking['id']; ?>')">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                        
                                        <div id="cancel-modal-<?php echo $booking['id']; ?>" class="cancel-modal">
                                            <div class="modal-content">
                                                <span class="close" onclick="closeModal('cancel-modal-<?php echo $booking['id']; ?>')">&times;</span>
                                                <h4>Cancel Booking #<?php echo $booking['id']; ?></h4>
                                                <p>Are you sure you want to cancel? This will permanently delete the booking record. This action cannot be undone.</p>
                                                <div class="modal-buttons">
                                                    <button type="button" class="btn btn-secondary" onclick="closeModal('cancel-modal-<?php echo $booking['id']; ?>')">
                                                        <i class="fas fa-arrow-left"></i> Go Back
                                                    </button>
                                                    <button type="submit" name="cancel_booking" class="btn btn-danger">
                                                        <i class="fas fa-times"></i> Confirm Cancel
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($booking['status'] === 'Cancelled' || $booking['status'] === 'Rejected' || ($booking['status'] === 'Approved' && $booking['payment_status'] === 'paid')): ?>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <button type="button" class="btn btn-info" onclick="showArchiveModal('archive-modal-<?php echo $booking['id']; ?>')">
                                            <i class="fas fa-archive"></i> Archive
                                        </button>
                                        
                                        <div id="archive-modal-<?php echo $booking['id']; ?>" class="archive-modal">
                                            <div class="modal-content">
                                                <span class="close" onclick="closeModal('archive-modal-<?php echo $booking['id']; ?>')">&times;</span>
                                                <h4>Archive Booking #<?php echo $booking['id']; ?></h4>
                                                <p>Are you sure you want to archive this booking? It will be moved to your booking history and hidden from this page.</p>
                                                <div class="modal-buttons">
                                                    <button type="button" class="btn btn-secondary" onclick="closeModal('archive-modal-<?php echo $booking['id']; ?>')">
                                                        <i class="fas fa-arrow-left"></i> Go Back
                                                    </button>
                                                    <button type="submit" name="archive_booking" class="btn btn-info">
                                                        <i class="fas fa-archive"></i> Confirm Archive
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($booking['status'] === 'Cancelled' || $booking['status'] === 'Rejected'): ?>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <button type="button" class="btn btn-danger" onclick="showDeleteModal('delete-modal-<?php echo $booking['id']; ?>')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                        
                                        <div id="delete-modal-<?php echo $booking['id']; ?>" class="delete-modal">
                                            <div class="modal-content">
                                                <span class="close" onclick="closeModal('delete-modal-<?php echo $booking['id']; ?>')">&times;</span>
                                                <h4>Delete Booking #<?php echo $booking['id']; ?></h4>
                                                <p>Are you sure you want to permanently delete this booking? This action cannot be undone.</p>
                                                <div class="modal-buttons">
                                                    <button type="button" class="btn btn-secondary" onclick="closeModal('delete-modal-<?php echo $booking['id']; ?>')">
                                                        <i class="fas fa-arrow-left"></i> Go Back
                                                    </button>
                                                    <button type="submit" name="delete_booking" class="btn btn-danger">
                                                        <i class="fas fa-trash"></i> Confirm Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?php echo date('M d, Y h:i A', strtotime($booking['created_at'])); ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- --- PAGINATION LINKS --- -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <!-- Previous Button -->
            <a href="<?php echo $page > 1 ? '?page=' . ($page - 1) : '#'; ?>" class="<?php echo $page <= 1 ? 'disabled' : ''; ?>">&laquo; Previous</a>

            <!-- Page Number Links -->
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" class="<?php echo $page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            
            <!-- Next Button -->
            <a href="<?php echo $page < $total_pages ? '?page=' . ($page + 1) : '#'; ?>" class="<?php echo $page >= $total_pages ? 'disabled' : ''; ?>">Next &raquo;</a>
        </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<script>
    // Show modal functions
    function showGuestModal(modalId) {
        document.getElementById(modalId).style.display = 'flex';
    }

    function showPaymentModal(modalId) {
        document.getElementById(modalId).style.display = 'flex';
    }

    function showCancelModal(modalId) {
        document.getElementById(modalId).style.display = 'flex';
    }

    function showArchiveModal(modalId) {
        document.getElementById(modalId).style.display = 'flex';
    }

    function showDeleteModal(modalId) {
        document.getElementById(modalId).style.display = 'flex';
    }

    // Close modal function
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
        if (event.target.className.includes('guest-modal') || 
            event.target.className.includes('payment-modal') || 
            event.target.className.includes('cancel-modal') || 
            event.target.className.includes('archive-modal') || 
            event.target.className.includes('delete-modal')) {
            event.target.style.display = 'none';
        }
    }

    // Enable payment submit button when terms are agreed
    document.querySelectorAll('input[type="checkbox"][name="agreePaymentTerms"]').forEach(checkbox => {
        const formId = checkbox.id.split('-')[1];
        const submitBtn = document.getElementById(`submitPaymentBtn-${formId}`);
        
        checkbox.addEventListener('change', function() {
            submitBtn.disabled = !this.checked;
            if (this.checked) {
                submitBtn.classList.remove('btn-disabled');
            } else {
                submitBtn.classList.add('btn-disabled');
            }
        });
    });
</script>

</body>
</html>

<?php
// Close database connection
if (isset($stmt)) $stmt->close();
if (isset($conn)) $conn->close();
?>