<?php
// check_availability.php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$roomId = $_GET['roomId'] ?? '';
$date = $_GET['date'] ?? '';
$duration = $_GET['duration'] ?? '';

if (!$roomId || !$date || !$duration) {
    echo json_encode(['error' => 'Missing parameters']);
    exit();
}

// Determine time slots to check based on duration
$timeSlots = [];
if ($duration == '6') {
    $timeSlots = ['am', 'pm'];
} else {
    $timeSlots = ['whole'];
}

// Get max stock for room and each time slot
$stmtStock = $conn->prepare("SELECT stock_6h_am, stock_6h_pm, stock_12h FROM rooms WHERE id = ?");
$stmtStock->bind_param("i", $roomId);
$stmtStock->execute();
$stockResult = $stmtStock->get_result()->fetch_assoc();
$stmtStock->close();

$availability = [];

foreach ($timeSlots as $slot) {
    // Get max stock for slot
    if ($slot == 'am') $maxStock = $stockResult['stock_6h_am'] ?? 0;
    elseif ($slot == 'pm') $maxStock = $stockResult['stock_6h_pm'] ?? 0;
    else $maxStock = $stockResult['stock_12h'] ?? 0;

    if ($maxStock == 0) {
        $availability[$slot] = false;
        continue;
    }

    // Count existing bookings for this room, date, and slot
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM bookingstatus WHERE roomName = (SELECT name FROM rooms WHERE id = ?) AND checkinDate = ? AND time_slot = ? AND status IN ('Pending', 'Confirmed')");
    $stmt->bind_param("iss", $roomId, $date, $slot);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $bookedCount = $res['cnt'] ?? 0;
    $stmt->close();

    $availability[$slot] = ($bookedCount < $maxStock);
}

header('Content-Type: application/json');
echo json_encode($availability);
