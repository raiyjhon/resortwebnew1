<?php
session_start();
require 'db.php'; // Database connection

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare("SELECT fullname, email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
} else {
    echo "User not found.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Profile</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f0f2f5;
      padding: 40px;
    }
    .profile-container {
      background: white;
      max-width: 400px;
      margin: auto;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 10px #ccc;
    }
    .profile-container h2 {
      text-align: center;
      margin-bottom: 20px;
    }
    .profile-container p {
      font-size: 16px;
      margin: 10px 0;
    }
    .profile-container a {
      display: block;
      text-align: center;
      margin-top: 20px;
      color: #fff;
      background: #007bff;
      padding: 10px;
      border-radius: 5px;
      text-decoration: none;
    }
    .profile-container a:hover {
      background: #0056b3;
    }
  </style>
</head>
<body>
  <div class="profile-container">
    <h2>My Profile</h2>
    <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user['fullname']); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
    <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
    
    <a href="logout.php">Logout</a>
  </div>
</body>
</html>
