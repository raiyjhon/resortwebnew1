<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reset Password</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f0f4f8;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }

    .reset-container {
      background-color: white;
      padding: 2rem 2.5rem;
      border-radius: 12px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      text-align: center;
      width: 100%;
      max-width: 500px;
    }

    .reset-container h2 {
      margin-bottom: 1.5rem;
      color: #333;
    }

    .reset-container p {
      margin-bottom: 1rem;
      color: #555;
    }

    .reset-container a {
      display: inline-block;
      margin-top: 1rem;
      padding: 0.5rem 1rem;
      background-color: #007BFF;
      color: white;
      text-decoration: none;
      border-radius: 5px;
      transition: background-color 0.3s ease;
    }

    .reset-container a:hover {
      background-color: #0056b3;
    }

    .error {
      color: red;
      font-weight: bold;
    }

    .success {
      color: green;
      font-weight: bold;
    }
  </style>
</head>
<body>

  <div class="reset-container">
    <?php
    include 'db.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email']);

        if (empty($email)) {
            echo "<p class='error'>Please enter your email.</p>";
            exit;
        }

        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);

        if (!$stmt) {
            echo "<p class='error'>Database error: " . $conn->error . "</p>";
            exit;
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $token = md5($user['email'] . time());

            $update = $conn->prepare("UPDATE users SET reset_token=? WHERE id=?");
            if (!$update) {
                echo "<p class='error'>Database error: " . $conn->error . "</p>";
                exit;
            }

            $update->bind_param("si", $token, $user['id']);
            $update->execute();

            $reset_link = "http://localhost/resortwebnew1/reset_password.php?token=$token";

            echo "<p class='success'>Password reset link has been generated:</p>";
            echo "<a href='$reset_link'>$reset_link</a>";
        } else {
            echo "<p class='error'>Email not found in our system.</p>";
        }
    }
    ?>
  </div>

</body>
</html>
