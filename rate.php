<?php
require 'db.php'; // database connection
session_start();

$room_id = $_POST['room_id'] ?? 0;
$rating = $_POST['rating'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;

if ($room_id && $rating && $user_id) {
    $check = $conn->prepare("SELECT * FROM ratings WHERE room_id = ? AND user_id = ?");
    $check->bind_param("ii", $room_id, $user_id);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();

    if ($existing) {
        echo "You already rated this room.";
    } else {
        $stmt = $conn->prepare("INSERT INTO ratings (room_id, user_id, rating) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $room_id, $user_id, $rating);
        $stmt->execute();
        echo "Thank you for your rating!";
    }
} else {
    echo "Please log in to rate.";
}
?>
