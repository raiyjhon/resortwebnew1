<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Required fields including time_slot
$required_fields = [
    'guestName', 'roomId', 'roomName', 'roomPrice', 
    'checkinDate', 'checkinTime', 'stayDuration', 
    'guestCount', 'totalPrice', 'time_slot'
];

foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        die("Missing required field: $field");
    }
}

$user_id = $_SESSION['user_id'];
$guestName = trim($_POST['guestName']);
$roomId = intval($_POST['roomId']);
$roomName = trim($_POST['roomName']);
$roomPrice = floatval($_POST['roomPrice']);
$checkinDate = $_POST['checkinDate'];
$checkinTime = $_POST['checkinTime'];
$stayDuration = intval($_POST['stayDuration']);
$guestCount = intval($_POST['guestCount']);
$guestNames = $_POST['guestNames'] ?? [];
$guestTypes = $_POST['guestTypes'] ?? [];
$totalPrice = floatval($_POST['totalPrice']);
$timeSlot = $_POST['time_slot']; // 'am', 'pm', or 'whole'

// Validate file upload
if (!isset($_FILES['validId']) || $_FILES['validId']['error'] !== UPLOAD_ERR_OK) {
    die("Please upload a valid ID file.");
}

// File upload settings
$uploadDir = 'uploads/';
$allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
$maxSize = 2 * 1024 * 1024; // 2MB

$fileType = $_FILES['validId']['type'];
$fileSize = $_FILES['validId']['size'];

if (!in_array($fileType, $allowedTypes)) {
    die("Only JPG, PNG, and PDF files are allowed.");
}

if ($fileSize > $maxSize) {
    die("File size must be less than 2MB.");
}

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$validIdName = time() . '_' . basename($_FILES["validId"]["name"]);
$targetFile = $uploadDir . $validIdName;

if (!move_uploaded_file($_FILES["validId"]["tmp_name"], $targetFile)) {
    die("Failed to upload valid ID.");
}

// Calculate checkout time (assuming stayDuration is in hours)
$checkinDateTime = new DateTime("$checkinDate $checkinTime");
$checkoutDateTime = clone $checkinDateTime;
$checkoutDateTime->add(new DateInterval("PT{$stayDuration}H"));
$checkoutTime = $checkoutDateTime->format('H:i:s');

// --- AVAILABILITY CHECK ---

// Get max stock for the room and time slot
$stockColumn = '';
if ($timeSlot === 'am') {
    $stockColumn = 'stock_6h_am';
} elseif ($timeSlot === 'pm') {
    $stockColumn = 'stock_6h_pm';
} else { // whole day
    $stockColumn = 'stock_12h';
}

$stmtStock = $conn->prepare("SELECT $stockColumn FROM rooms WHERE id = ?");
$stmtStock->bind_param("i", $roomId);
$stmtStock->execute();
$stockResult = $stmtStock->get_result()->fetch_assoc();
$stmtStock->close();

$maxStock = $stockResult[$stockColumn] ?? 0;
if ($maxStock == 0) {
    unlink($targetFile);
    die("Selected room/time slot is not available.");
}

// Count existing bookings for this room, date, and time slot with status Pending or Confirmed
$stmtCheck = $conn->prepare("SELECT COUNT(*) AS cnt FROM bookingstatus WHERE roomName = (SELECT name FROM rooms WHERE id = ?) AND checkinDate = ? AND time_slot = ? AND status IN ('Pending', 'Confirmed')");
$stmtCheck->bind_param("iss", $roomId, $checkinDate, $timeSlot);
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result()->fetch_assoc();
$stmtCheck->close();

$bookedCount = $resCheck['cnt'] ?? 0;

if ($bookedCount >= $maxStock) {
    unlink($targetFile);
    die("Selected time slot is fully booked. Please choose another.");
}

// --- END AVAILABILITY CHECK ---

// Start transaction
$conn->begin_transaction();

try {
    // Insert booking with time_slot
    $sql = "INSERT INTO bookingstatus 
            (user_id, guestName, roomName, roomPrice, checkinDate, checkinTime, 
             checkoutTime, stayDuration, guestCount, validId, totalPrice, time_slot) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("SQL error (bookingstatus insert): " . $conn->error);
    }
    
    $stmt->bind_param(
        "issdsssiisss",
        $user_id,
        $guestName,
        $roomName,
        $roomPrice,
        $checkinDate,
        $checkinTime,
        $checkoutTime,
        $stayDuration,
        $guestCount,
        $targetFile,
        $totalPrice,
        $timeSlot
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Execution error (bookingstatus insert): " . $stmt->error);
    }
    
    $booking_id = $conn->insert_id;
    $stmt->close();
    
    // Insert guests if provided
    if (!empty($guestNames) && !empty($guestTypes) && count($guestNames) === count($guestTypes)) {
        $stmt = $conn->prepare("INSERT INTO booking_guests (booking_id, guest_name, guest_type) VALUES (?, ?, ?)");
        if (!$stmt) {
            throw new Exception("SQL error (booking_guests insert): " . $conn->error);
        }
        
        for ($i = 0; $i < count($guestNames); $i++) {
            $name = trim($guestNames[$i]);
            $type = trim($guestTypes[$i]);
            if ($name === '' || $type === '') continue;
            
            $stmt->bind_param("iss", $booking_id, $name, $type);
            if (!$stmt->execute()) {
                throw new Exception("Execution error (booking_guests insert): " . $stmt->error);
            }
        }
        $stmt->close();
    }
    
    $conn->commit();
    header("Location: my_bookings.php");
    exit();
    
} catch (Exception $e) {
    $conn->rollback();
    // Clean up uploaded file if transaction failed
    if (file_exists($targetFile)) {
        unlink($targetFile);
    }
    die("Error processing booking: " . $e->getMessage());
}
?>
