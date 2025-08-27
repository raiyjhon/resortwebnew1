<?php
session_start();
include 'db.php'; // Your database connection file

//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require 'vendor/autoload.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];

// Define admin and staff email patterns or addresses
$admin_email = 'admin@dentofarm.com';
$is_staff = strpos($email, '@staff.dentofarm.com') !== false;

if ($email === $admin_email || $is_staff) {
    // If user is admin or staff, deny access and redirect
    header("Location: login.php?error=access_denied");
    exit();
}

$user_id = $_SESSION['user_id'];
$fullname = '';

// Fetch user's full name
if ($stmt = $conn->prepare("SELECT fullname FROM users WHERE id = ?")) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($fullname);
    $stmt->fetch();
    $stmt->close();

    if (empty($fullname)) {
        session_destroy();
        header("Location: login.php");
        exit();
    }
} else {
    die("Database error: " . $conn->error);
}

// === Form Submission Logic ===
$message_sent = false;
$error_message = '';

if (isset($_POST["sendMessage"])) {
    $form_fullname = $_POST["fullname"];
    $form_email = $_POST["email"];
    $form_subject = $_POST["subject"];
    $form_message = $_POST["message"];

    //Instantiation and passing `true` enables exceptions
    $mail = new PHPMailer(true);

    try {
        //Enable verbose debug output
        $mail->SMTPDebug = 0; //SMTP::DEBUG_SERVER;

        //Send using SMTP
        $mail->isSMTP();

        //Set the SMTP server to send through
        $mail->Host = 'smtp.gmail.com';

        //Enable SMTP authentication
        $mail->SMTPAuth = true;

        //SMTP username
        $mail->Username = 'testkoto1230@gmail.com'; // Your Gmail address

        //SMTP password
        $mail->Password = 'ygoe hzoy wcba gvxy'; // Your Gmail App Password

        //Enable TLS encryption;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        //TCP port to connect to
        $mail->Port = 587;

        //Recipients
        $mail->setFrom('testkoto1230@gmail.com', 'DentoReserve Contact Form'); // Can be the same as Username
        $mail->addAddress('Dentofarmresort@gmail.com', 'Dento Farm Resort'); // The email address where you want to receive messages

        //Add a reply-to address from the form
        $mail->addReplyTo($form_email, $form_fullname);

        //Set email format to HTML
        $mail->isHTML(true);

        $mail->Subject = 'New Contact Form Submission: ' . $form_subject;
        $mail->Body    = "<h2>Contact Form Submission</h2>
                        <p><b>Name:</b> {$form_fullname}</p>
                        <p><b>Email:</b> {$form_email}</p>
                        <p><b>Message:</b></p>
                        <p>{$form_message}</p>";
        
        $mail->AltBody = "Name: {$form_fullname}\nEmail: {$form_email}\n\nMessage:\n{$form_message}";

        $mail->send();
        $message_sent = true;
    } catch (Exception $e) {
        $error_message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | DentoReserve</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    />
    <link
        href="https://cdn.jsdelivr.net/npm/remixicon@3.4.0/fonts/remixicon.css"
        rel="stylesheet"
    />
    <style>
        /* ====== Global Styles ====== */
        :root {
            --primary-color: #2c3855;
            --primary-color-light: #00bcd4;
            --text-light: #767268;
            --text-dark: #333333;
            --white: #ffffff;
            --extra-light: #f3f4f6;
            --max-width: 1200px;
        }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: url('images/city-bg.jpg') no-repeat center center fixed;
            background-size: cover;
            color: var(--white); /* Default text color for body, adjusted for readability */
            padding-top: 100px; /* Space for fixed navbar */
        }

        /* ====== Nav Styles ====== */
        nav {
            max-width: var(--max-width);
            margin: auto;
            padding: 1.5rem 1rem; /* Slightly reduced padding */
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.95); /* Increased opacity for better readability */
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .nav__logo {
            font-size: 1.5rem;
            font-weight: 700; /* Increased font-weight */
            color: var(--primary-color); /* Used primary color */
        }

        .nav__links {
            list-style: none;
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .link a {
            font-weight: 500;
            color: var(--text-dark); /* Changed to text-dark for better contrast */
            transition: 0.3s;
            text-decoration: none;
            position: relative;
            padding: 0.5rem 0;
        }

        .link a:hover {
            color: var(--primary-color-light); /* Changed hover color */
        }

        .link a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-color-light); /* Changed underline color */
            transition: width 0.3s ease;
        }

        .link a:hover::after {
            width: 100%;
        }

        /* Dropdown styles */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropbtn {
            background: none;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            color: var(--text-dark); /* Changed to text-dark */
            font-weight: 500;
            font-size: 1rem;
            font-family: inherit;
            padding: 0.5rem 0;
        }

        .dropbtn:hover {
            color: var(--primary-color-light); /* Changed hover color */
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: var(--white);
            min-width: 240px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
            z-index: 1;
            padding: 1rem 0;
            margin-top: 0.5rem;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        .profile-header {
            display: flex;
            align-items: center;
            padding: 0 1rem 1rem;
            margin-bottom: 0.5rem;
            border-bottom: 1px solid var(--extra-light);
        }

        .profile-icon {
            font-size: 2rem;
            color: var(--primary-color);
            margin-right: 1rem;
        }

        .profile-name {
            font-weight: 600;
            color: var(--text-dark);
        }

        .profile-email {
            font-size: 0.8rem;
            color: var(--text-light);
        }

        .dropdown-content a {
            display: flex;
            align-items: center;
            padding: 0.7rem 1.5rem;
            text-decoration: none;
            color: var(--text-dark);
            transition: all 0.2s ease;
        }

        .dropdown-content a i {
            margin-right: 0.8rem;
            font-size: 1.1rem;
            color: var(--text-light);
        }

        .dropdown-content a:hover {
            background-color: var(--extra-light);
            color: var(--primary-color-light);
        }

        .dropdown-content a:hover i {
            color: var(--primary-color-light);
        }

        /* Hamburger menu button */
        .nav__menu_btn {
            font-size: 1.8rem; /* Larger icon for better touch target */
            color: var(--primary-color);
            cursor: pointer;
            display: none; /* Hidden by default */
        }

        /* ====== Contact Page Styles ====== */
        .contact-container {
            max-width: var(--max-width);
            margin: auto;
            padding: 60px 1rem; /* Adjusted padding and max-width */
            min-height: calc(100vh - 100px); /* Adjust height for nav */
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center; /* Center content vertically */
            align-items: center;
        }

        .contact-header {
            margin-bottom: 40px;
        }

        .contact-header h1 {
            font-size: 3rem; /* Larger font size */
            margin-bottom: 15px;
            color: var(--white); /* Ensured header is white */
            text-shadow: 0 2px 5px rgba(0,0,0,0.4);
        }

        .contact-header p {
            max-width: 800px; /* Wider paragraph */
            margin: 0 auto 40px;
            font-size: 1.1rem; /* Slightly larger text */
            color: rgba(255, 255, 255, 0.9); /* Lighter white for text over background */
            line-height: 1.6;
        }

        .contact-box {
            display: flex;
            justify-content: center; /* Center items when they wrap */
            align-items: flex-start;
            flex-wrap: wrap;
            width: 100%; /* Take full width of container */
            gap: 2rem; /* Consistent gap */
            text-align: left;
        }

        /* ====== Info Cards ====== */
        .contact-info {
            flex: 1; /* Allows it to grow and shrink */
            min-width: 300px; /* Minimum width before wrapping */
            display: flex;
            flex-direction: column;
            gap: 1.5rem; /* Gap between info blocks */
        }

        .info-block {
            background: rgba(255, 255, 255, 0.95);
            color: var(--text-dark);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.2s ease;
        }

        .info-block:hover {
            transform: translateY(-5px);
        }

        .icon {
            font-size: 1.8rem; /* Slightly larger icons */
            width: 55px;
            height: 55px;
            background-color: var(--primary-color-light); /* Used primary-color-light */
            color: var(--white);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-shrink: 0; /* Prevent icon from shrinking */
        }

        .info-title {
            color: var(--primary-color);
            margin: 0;
            font-weight: 600; /* Bolder title */
            font-size: 1.1rem;
        }

        .info-block p {
            font-size: 0.95rem;
            color: var(--text-dark);
            margin: 0;
        }

        .info-block a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s;
        }

        .info-block a:hover {
            color: var(--primary-color-light);
            text-decoration: underline; /* Add underline on hover for links */
        }

        /* ====== Form Card ====== */
        .contact-form {
            flex: 2; /* Takes more space than info blocks */
            min-width: 320px; /* Minimum width before wrapping */
            background: rgba(255, 255, 255, 0.98); /* Slightly more opaque */
            color: var(--text-dark);
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }

        .contact-form:hover {
            transform: translateY(-5px);
        }

        .contact-form h3 {
            margin-bottom: 20px;
            font-size: 24px; /* Slightly larger title */
            color: var(--primary-color);
            text-align: center;
        }

        .contact-form label {
            display: block;
            margin-bottom: 8px; /* More space below labels */
            color: var(--primary-color-light); /* Brighter color for labels */
            font-size: 0.95rem;
            font-weight: 500;
        }

        .contact-form input,
        .contact-form textarea {
            width: 100%;
            padding: 12px;
            box-sizing: border-box; /* Important for width calculation */
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: var(--extra-light);
            outline: none;
            color: var(--text-dark);
            font-family: inherit;
            font-size: 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .contact-form input:focus,
        .contact-form textarea:focus {
            border-color: var(--primary-color-light);
            box-shadow: 0 0 0 3px rgba(0, 188, 212, 0.2); /* Focus glow */
        }

        .contact-form textarea {
            resize: vertical; /* Allow vertical resizing */
        }

        .contact-form button {
            background: var(--primary-color-light);
            color: var(--white);
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            width: 100%;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .contact-form button:hover {
            background: #0097a7;
            transform: translateY(-2px);
        }
        
        /* Status Messages */
        .success-message { 
            color: #155724; 
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            font-weight: bold; 
        }
        .error-message { 
            color: #721c24; 
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            font-weight: bold; 
        }

        /* --- Responsive Design --- */
        @media (max-width: 768px) {
            /* Navigation adjustments */
            .nav__menu_btn { display: block; }
            .nav__links {
                position: absolute;
                top: 100%; left: 0; width: 100%;
                flex-direction: column;
                background-color: rgba(255, 255, 255, 0.98);
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                padding: 1rem 0; gap: 0.5rem;
                display: none; align-items: flex-start;
                border-top: 1px solid var(--extra-light);
            }
            .nav__links.open { display: flex; }
            .nav__links .link { width: 100%; text-align: left; padding: 0 1rem; }
            .nav__links .link a { display: block; padding: 0.8rem 0; width: 100%; color: var(--text-dark); }
            .nav__links .link a::after { left: 1rem; }
            .dropdown { width: 100%; padding: 0 1rem; }
            .dropdown-content { position: static; width: 100%; box-shadow: none; margin-top: 0; border-radius: 0; padding: 0; background-color: var(--white); }
            .dropdown-content .profile-header, .dropdown-content a { padding-left: 2rem; }
            body { padding-top: 80px; }
            /* Contact section adjustments */
            .contact-header h1 { font-size: 2.5rem; }
            .contact-header p { font-size: 1rem; }
            .contact-box { flex-direction: column; align-items: center; gap: 3rem; }
            .contact-info, .contact-form { width: 90%; max-width: 500px; min-width: unset; }
        }
        @media (max-width: 480px) {
            .contact-container { padding: 40px 0.8rem; }
            .contact-header h1 { font-size: 2rem; }
            .contact-header p { font-size: 0.9rem; }
            .info-block { flex-direction: column; text-align: center; align-items: center; gap: 10px; }
            .icon { margin-bottom: 5px; }
        }
    </style>
</head>
<body>
    <nav>
        <div class="nav__logo">DentoReserve</div>
        <div class="nav__menu_btn" id="menuToggle">
            <i class="ri-menu-line"></i>
        </div>
        <ul class="nav__links" id="navbarNav">
            <li class="link"><a href="dashboardcustomer.php">Home</a></li>
            <li class="link"><a href="booklogin.php">Book</a></li>
            <li class="link"><a href="Event.php">Event</a></li>
            <li class="link"><a href="cuscontact.php">Contact Us</a></li>
            <li class="link dropdown">
                <button class="dropbtn">
                    <?php echo htmlspecialchars($fullname); ?> 
                    <i class="ri-arrow-down-s-line"></i>
                </button>
                <div class="dropdown-content">
                    <div class="profile-header">
                        <i class="ri-user-3-fill profile-icon"></i>
                        <div>
                            <div class="profile-name"><?php echo htmlspecialchars($fullname); ?></div>
                            <div class="profile-email">User ID: <?php echo htmlspecialchars($user_id); ?></div>
                        </div>
                    </div>
                    <a href="profile.php"><i class="ri-user-line"></i> My Profile</a>
                    <a href="my_bookings.php"><i class="ri-history-line"></i> Booking History</a>
                    <a href="settings.php"><i class="ri-settings-line"></i> Settings</a>
                    <a href="logout.php"><i class="ri-logout-box-line"></i> Logout</a>
                </div>
            </li>
        </ul>
    </nav>

    <div class="contact-container">
        <div class="contact-header">
            <h1>Contact Us</h1>
            <p>
                Planning a getaway or have questions about our resort? Get in touch
                with us! We’re here to help you make your stay at Dento Farm Resort
                unforgettable.
            </p>
        </div>

        <div class="contact-box">
            <div class="contact-info">
                <div class="info-block">
                    <div class="icon"><i class="ri-map-pin-2-fill"></i></div>
                    <div>
                        <h4 class="info-title">Address</h4>
                        <p>Estrella, Rizal, Central Luzon, Philippines</p>
                    </div>
                </div>
                <div class="info-block">
                    <div class="icon"><i class="ri-phone-fill"></i></div>
                    <div>
                        <h4 class="info-title">Phone</h4>
                        <p>0939 977 1125</p>
                    </div>
                </div>
                <div class="info-block">
                    <div class="icon"><i class="ri-mail-send-fill"></i></div>
                    <div>
                        <h4 class="info-title">Email</h4>
                        <p>Dentofarmresort@gmail.com</p>
                    </div>
                </div>
                <div class="info-block">
                    <div class="icon"><i class="ri-facebook-box-fill"></i></div>
                    <div>
                        <h4 class="info-title">Facebook</h4>
                        <p>
                            <a
                                href="https://web.facebook.com/profile.php?id=100083176998067"
                                target="_blank"
                            >Dento Farm Resort</a>
                        </p>
                    </div>
                </div>
            </div>

            <div class="contact-form">
                <form method="POST">
                    <h3>Send us a Message</h3>

                    <?php if ($message_sent): ?>
                        <p class="success-message">Thank you for contacting us! We will get back to you shortly.</p>
                    <?php endif; ?>

                    <?php if (!empty($error_message)): ?>
                        <p class="error-message"><?php echo $error_message; ?></p>
                    <?php endif; ?>
                    
                    <label for="fullname">Full Name</label>
                    <input type="text" id="fullname" name="fullname" placeholder="Enter your full name" required />
                    
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required />
                    
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" placeholder="Enter the subject" required />
                    
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="6" placeholder="Enter your message" required></textarea>

                    <button type="submit" name="sendMessage">Send Message</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const navbarNav = document.getElementById('navbarNav');

            // Toggle mobile navigation
            menuToggle.addEventListener('click', function() {
                navbarNav.classList.toggle('open');
            });
        });
    </script>
</body>
</html>