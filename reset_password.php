<?php
include 'db.php';

$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $newPassword = $_POST['password'];

    if (empty($token) || empty($newPassword)) {
        $error = "All fields are required.";
    } else {
        $query = $conn->prepare("SELECT * FROM users WHERE reset_token=?");
        $query->bind_param("s", $token);
        $query->execute();
        $result = $query->get_result();

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            $update = $conn->prepare("UPDATE users SET password=?, reset_token=NULL WHERE id=?");
            $update->bind_param("si", $hashedPassword, $user['id']);
            $update->execute();

            $success = "Your password has been reset successfully.";
        } else {
            $error = "Invalid or expired token.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Reset Password</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f3f6f9;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }

    .reset-box {
      background-color: #fff;
      padding: 2rem 2.5rem;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 420px;
      text-align: center;
    }

    .reset-box h2 {
      margin-bottom: 1.5rem;
      color: #333;
    }

    .reset-box input[type="password"] {
      width: 100%;
      padding: 0.6rem;
      margin-bottom: 1rem;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 1rem;
    }

    .reset-box button {
      background-color: #007BFF;
      color: white;
      border: none;
      padding: 0.6rem 1.2rem;
      font-size: 1rem;
      border-radius: 6px;
      cursor: pointer;
    }

    .reset-box button:hover {
      background-color: #0056b3;
    }

    .message {
      margin-top: 1rem;
      color: green;
      font-weight: bold;
    }

    .error {
      margin-top: 1rem;
      color: red;
      font-weight: bold;
    }

    .btn-back {
      display: inline-block;
      margin-top: 1rem;
      padding: 0.5rem 1.2rem;
      background-color: #28a745;
      color: white;
      text-decoration: none;
      border-radius: 6px;
      font-weight: bold;
      transition: background-color 0.3s ease;
    }

    .btn-back:hover {
      background-color: #1e7e34;
    }
  </style>
</head>
<body>

  <div class="reset-box">
    <h2>Reset Your Password</h2>

    <?php if (isset($error)): ?>
      <p class="error"><?= $error ?></p>
    <?php elseif (isset($success)): ?>
      <p class="message"><?= $success ?></p>
      <a href="index.php" class="btn-back">Go to Login</a>
    <?php else: ?>
      <form method="POST" action="">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <input type="password" name="password" placeholder="Enter new password" required />
        <button type="submit">Reset Password</button>
      </form>
    <?php endif; ?>
  </div>

</body>
</html>
