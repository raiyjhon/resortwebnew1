<?php 
session_start(); 

if (isset($_POST['submit'])) { 
    include '../db.php'; // your DB connection 

    $roomName = $_POST['roomName']; 
    $roomType = $_POST['roomType']; 
    $roomPrice = $_POST['roomPrice']; 
    $roomDescription = $_POST['roomDescription']; 
    $guestLimit = $_POST['guestLimit']; // Get the new guest limit value

    $image = $_FILES['roomImage']['name']; 
    $temp = $_FILES['roomImage']['tmp_name']; 
    $uploadDir = "../uploads/"; 

    // Create uploads folder if it doesn't exist 
    if (!is_dir($uploadDir)) { 
        mkdir($uploadDir, 0777, true); 
    } 

    // Sanitize and generate unique file name to avoid collisions 
    $imageFileType = strtolower(pathinfo($image, PATHINFO_EXTENSION)); 
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']; 

    $safeImageName = uniqid('room_', true) . '.' . $imageFileType; 

    if (!in_array($imageFileType, $allowedTypes)) { 
        $_SESSION['status'] = 'error'; 
        $_SESSION['message'] = 'Invalid image file type. Allowed types: jpg, jpeg, png, gif.'; 
    } else { 
        $target = $uploadDir . $safeImageName; 

        if (move_uploaded_file($temp, $target)) { 
            // Use prepared statement to avoid SQL Injection 
            $stmt = $conn->prepare("INSERT INTO rooms (name, type, price, description, image, guest_limit) VALUES (?, ?, ?, ?, ?, ?)"); 
            $stmt->bind_param("ssissi", $roomName, $roomType, $roomPrice, $roomDescription, $safeImageName, $guestLimit); 

            if ($stmt->execute()) { 
                $_SESSION['status'] = 'success'; 
                $_SESSION['message'] = 'Room added successfully!'; 
            } else { 
                $_SESSION['status'] = 'error'; 
                $_SESSION['message'] = 'Database error: ' . $stmt->error; 
            } 
            $stmt->close(); 
        } else { 
            $_SESSION['status'] = 'error'; 
            $_SESSION['message'] = 'Failed to upload image.'; 
        } 
    } 

    header("Location: " . $_SERVER['PHP_SELF']); 
    exit; 
} 
?> 

<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <meta charset="UTF-8" /> 
    <title>Resort Dashboard - Add Room</title> 
    <meta name="viewport" content="width=device-width, initial-scale=1" /> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" /> 
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> 
    <style> 
        body { 
            display: flex; 
            min-height: 100vh; 
            margin: 0; 
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
        } 
        .sidebar .nav-link.active { 
            background-color: #495057; 
        } 
        .main-content { 
            margin-left: 250px; 
            padding: 20px; 
            background-color: #f8f9fa; 
            width: 100%; 
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
        .table thead { 
            background-color: #e9ecef; 
        } 
        .chart-container canvas { 
            width: 100% !important; 
            height: 400px !important; 
        } 
        #previewImg { 
            display: none; 
            margin-top: 10px; 
            max-width: 100%; 
            height: auto; 
            border-radius: 5px; 
        } 
    </style> 
</head> 
<body> 

<nav class="navbar navbar-expand-lg navbar-dark bg-dark"> 
    <div class="container-fluid"> 
        <a class="navbar-brand" href="#">DentoReserve</a> 
        <div class="ms-auto text-white d-flex align-items-center"> 
            Welcome, <strong class="ms-2 me-3">Admin</strong> 
            <button id="profileBtn" class="btn btn-outline-light btn-sm me-2">My Profile</button> 
            <a href="logout.php" class="btn btn-danger btn-sm">Logout</a> 
        </div> 
    </div> 
</nav> 

<div class="sidebar" id="sidebar"> 
    <nav class="nav flex-column"> 
        <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a> 
        <a href="analytics.php" class="nav-link"><i class="fas fa-star"></i> Room Rating</a> 
        <a href="bookinganaly.php" class="nav-link"><i class="fas fa-calendar-check"></i> Booking Analysis</a> 
        <a href="cottages.php" class="nav-link"><i class="fas fa-money-bill-wave"></i> Sales Analytics</a> 
        <a href="predictive.php" class="nav-link"><i class="fas fa-chart-bar"></i> Predict</a> 
        <a href="add-room.php" class="nav-link"><i class="fas fa-plus-circle"></i> Add Room</a> 
        <a href="room-bookings.php" class="nav-link"><i class="fas fa-bed"></i> Room Bookings</a> 
        <a href="events.php" class="nav-link"><i class="fas fa-calendar-alt"></i> Events</a> 
        <a href="admin_create_staff.php" class="nav-link"><i class="fas fa-user-plus"></i> Add Staff</a> 
        <a href="admin_manage_staff.php" class="nav-link"><i class="fas fa-users-cog"></i> Manage Staff</a> 
    </nav> 
</div> 

<div class="main-content container"> 
    <h2 class="mb-4 mt-5">Add New Room</h2> 
    <form method="POST" enctype="multipart/form-data" novalidate> 
        <div class="mb-3"> 
            <label for="roomName" class="form-label">Room Name</label> 
            <input type="text" class="form-control" id="roomName" name="roomName" required /> 
        </div> 

        <div class="mb-3"> 
            <label for="roomType" class="form-label">Room Type</label> 
            <select class="form-select" id="roomType" name="roomType" required> 
                <option value="">-- Select Type --</option> 
                <option value="VipRoom">VipRoom</option> 
                <option value="Cottage">Cottage</option> 
            </select> 
        </div> 

        <div class="mb-3"> 
            <label for="roomPrice" class="form-label">Price</label> 
            <input type="number" class="form-control" id="roomPrice" name="roomPrice" required min="0" /> 
        </div> 
        
        <div class="mb-3"> 
            <label for="guestLimit" class="form-label">Guest Limit</label> 
            <input type="number" class="form-control" id="guestLimit" name="guestLimit" required min="1" value="1" /> 
        </div> 

        <div class="mb-3"> 
            <label for="roomDescription" class="form-label">Description</label> 
            <textarea class="form-control" id="roomDescription" name="roomDescription" rows="3"></textarea> 
        </div> 

        <div class="mb-3"> 
            <label for="roomImage" class="form-label">Room Image</label> 
            <input type="file" class="form-control" id="roomImage" name="roomImage" accept="image/*" onchange="previewImage(event)" required /> 
            <img id="previewImg" alt="Image Preview" /> 
        </div> 

        <button type="submit" name="submit" class="btn btn-primary">Save Room</button> 
    </form> 
</div> 

<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true"> 
    <div class="modal-dialog"> 
        <div class="modal-content"> 
            <div class="modal-header"> 
                <h5 class="modal-title" id="statusModalLabel">Status</h5> 
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> 
            </div> 
            <div class="modal-body" id="modalMessage"></div> 
            <div class="modal-footer"> 
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> 
            </div> 
        </div> 
    </div> 
</div> 

<script> 
    function previewImage(event) { 
        const reader = new FileReader(); 
        reader.onload = function () { 
            const output = document.getElementById('previewImg'); 
            output.src = reader.result; 
            output.style.display = 'block'; 
        }; 
        reader.readAsDataURL(event.target.files[0]); 
    } 

    // Show modal if there's a message from PHP session 
    <?php if (isset($_SESSION['message'])): ?> 
        window.addEventListener('DOMContentLoaded', () => { 
            const modalMessage = document.getElementById('modalMessage'); 
            modalMessage.textContent = "<?php echo htmlspecialchars($_SESSION['message'], ENT_QUOTES); ?>"; 

            const statusModal = new bootstrap.Modal(document.getElementById('statusModal')); 
            statusModal.show(); 
        }); 
    <?php 
    unset($_SESSION['message'], $_SESSION['status']); 
    endif; ?> 
</script> 

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> 
</body> 
</html>