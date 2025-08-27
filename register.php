<?php
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $fullname = $_POST['fullname'];
  $email = $_POST['email'];
  $phone = $_POST['phone'];
  $password = $_POST['password'];
  $confirm_password = $_POST['confirm_password'];

  // Check if passwords match
  if ($password !== $confirm_password) {
    echo "<div class='message error'>❌ Passwords do not match!</div>";
  } else {
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Prepare SQL statement
    $stmt = $conn->prepare("INSERT INTO users (fullname, email, phone, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $fullname, $email, $phone, $hashed_password);

    if ($stmt->execute()) {
      echo "<div class='message success'>✅ Account created successfully. <a href='index.php'>Login here</a></div>";
    } else {
      echo "<div class='message error'>❌ Error: " . $stmt->error . "</div>";
    }

    $stmt->close();
    $conn->close();
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register</title>
  <style>
    .message {
      padding: 15px;
      margin: 20px auto;
      width: 90%;
      max-width: 500px;
      border-radius: 5px;
      font-weight: bold;
      text-align: center;
      font-family: Arial, sans-serif;
    }

    .message.success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .message.error {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .message a {
      color: #155724;
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <!-- You can place your form here -->
</body>
</html>
