<?php
//Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require 'vendor/autoload.php';

$message = '';
$redirect_to_index = false;

if (isset($_POST["verify_email"]))
{
    $email = $_POST["email"];
    $verification_code = $_POST["verification_code"];

    // Include the database connection file
    require_once 'db.php'; // Adjust path if db.php is in a different directory
    
    // Check if the connection was successful (from db.php)
    if (!$conn) {
        $message = "Database connection failed. Please try again later.";
    } else {
        // Use a prepared statement to prevent SQL injection
        $sql = "UPDATE users SET email_verified_at = NOW() WHERE email = ? AND verification_code = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt === false) {
            $message = "Error preparing statement: " . mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param($stmt, "ss", $email, $verification_code);
            mysqli_stmt_execute($stmt);

            // Check if a row was updated
            if (mysqli_stmt_affected_rows($stmt) > 0)
            {
                $message = "Email verified successfully! Redirecting...";
                $redirect_to_index = true;
            }
            else
            {
                $message = "Verification code failed. Please try again.";
            }
            
            // Close the statement
            mysqli_stmt_close($stmt);
        }
        // Close the connection (important to do after all database operations)
        mysqli_close($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f4f7f6;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: #333;
        }
        .container {
            background: #fff;
            padding: 2.5em;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 100%;
            max-width: 400px;
            box-sizing: border-box;
        }
        h2 {
            margin-top: 0;
            margin-bottom: 0.5em;
            font-size: 1.8em;
            color: #1a202c;
        }
        p {
            margin-bottom: 2em;
            color: #718096;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 1em;
        }
        input[type="text"], input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1em;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        input[type="text"]:focus, input[type="email"]:focus {
            outline: none;
            border-color: #4c51bf;
            box-shadow: 0 0 0 3px rgba(76, 81, 191, 0.2);
        }
        input[type="submit"] {
            background-color: #4c51bf;
            color: #fff;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        input[type="submit"]:hover {
            background-color: #434190;
        }
        .message {
            margin-top: 1.5em;
            padding: 1em;
            border-radius: 8px;
            font-weight: bold;
        }
        .success {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .error {
            background-color: #f8d7da;
            color: #842029;
        }
        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #4c51bf;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
            display: none;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Verify Your Email</h2>
    <p>Please enter the 6-digit verification code sent to your email.</p>
    
    <form method="POST">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email']); ?>" required>
        <input type="text" name="verification_code" placeholder="Enter verification code" required>
        <input type="submit" name="verify_email" value="Verify Email">
    </form>

    <?php if ($message): ?>
        <div class="message <?php echo $redirect_to_index ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div id="loader" class="loader"></div>
</div>

<script>
    <?php if ($redirect_to_index): ?>
        // Show loader and message
        document.getElementById('loader').style.display = 'block';
        
        // Wait 2 seconds and then redirect
        setTimeout(function() {
            window.location.href = 'index.php';
        }, 2000); // 2000 milliseconds = 2 seconds
    <?php endif; ?>
</script>

</body>
</html>