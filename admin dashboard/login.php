<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!empty($_POST['email']) && !empty($_POST['password'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT id, fullname, password FROM users WHERE email = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $fullname, $hashed_password);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                // Set session variables
                $_SESSION['user_id'] = $id;
                $_SESSION['fullname'] = $fullname;
                $_SESSION['email'] = $email;

                // Wait 2 seconds before redirect
                sleep(2);

                // Redirect user based on email type
                if ($email === 'admin@dentofarm.com') {
                    header("Location: admin dashboard/dashboard.php");
                } 
                // Check if email belongs to staff
                else if (strpos($email, '@staff.dentofarm.com') !== false) {
                    header("Location: staff dashboard/manage_bookings.php");
                } 
                else {
                    header("Location: dashboardcustomer.php");
                }
                exit();
            } else {
                // Wait 2 seconds before redirect
                sleep(2);
                header("Location: login.php?error=invalid_password");
                exit();
            }
        } else {
            // Wait 2 seconds before redirect
            sleep(2);
            header("Location: login.php?error=user_not_found");
            exit();
        }

        $stmt->close();
        $conn->close();
    } else {
        // Wait 2 seconds before redirect
        sleep(2);
        header("Location: login.php?error=missing_fields");
        exit();
    }
} else {
    // Wait 2 seconds before redirect
    sleep(2);
    header("Location: login.php?error=invalid_request");
    exit();
}
?>
