<?php
session_start();
include 'db.php';

// Basic validation
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['booking_id'], $_POST['status'])) {
    $booking_id = intval($_POST['booking_id']);
    $status = $_POST['status'] === 'Approved' ? 'Approved' : 'Rejected'; // only allow these two values

    $stmt = $conn->prepare("UPDATE bookingstatus SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $booking_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Booking status updated to $status.";
    } else {
        $_SESSION['error'] = "Error updating status.";
    }

    $stmt->close();
    $conn->close();
}

header("Location: cottages.php");
exit();
