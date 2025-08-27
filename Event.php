<?php
session_start();
include 'db.php'; // Your database connection file

if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to login page.
    header("Location: login.php");
    exit();
}

// Get user data from the session.
$email = $_SESSION['email'];
$user_id = $_SESSION['user_id'];

// Define admin and staff email patterns or addresses.
$admin_email = 'admin@dentofarm.com';
$is_staff = strpos($email, '@staff.dentofarm.com') !== false;

// CORRECTED: Allow access only for regular users. Deny access for admins and staff.
if ($email === $admin_email || $is_staff) {
    // If the user is an admin or staff, deny access and redirect.
    header("Location: login.php?error=access_denied");
    exit();
}

// Fetch user's full name from the database.
if ($stmt = $conn->prepare("SELECT fullname FROM users WHERE id = ?")) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($fullname);
    $stmt->fetch();
    $stmt->close();

    // If fullname is not found, destroy the session and redirect.
    if (empty($fullname)) {
        session_destroy();
        header("Location: login.php");
        exit();
    }
} else {
    // Handle database connection or query preparation errors.
    die("Database error: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>DentoReserve - What's New</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.4.0/fonts/remixicon.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color:rgb(24, 26, 32);
            --primary-color-dark:rgb(32, 34, 39);
            --text-dark: #333333;
            --text-light: #767268;
            --extra-light: #f3f4f6;
            --white: #ffffff;
            --max-width: 1200px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Poppins", sans-serif;
            background: var(--extra-light);
            padding-top: 100px;
        }

        .section__container {
            max-width: var(--max-width);
            margin: auto;
            padding: 2rem 1rem;
        }

        .section__header {
            font-size: 2rem;
            font-weight: 600;
            color: var(--text-dark);
            text-align: center;
            margin-bottom: 2rem;
        }

        /* Nav and Hero Section Styles */
        nav {
            max-width: var(--max-width);
            margin: auto;
            padding: 1.5rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.95);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .nav__logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .nav__logo span {
            color: var(--primary-color-dark);
        }

        .nav__links {
            list-style: none;
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .link a {
            font-weight: 500;
            color: var(--text-dark);
            transition: 0.3s;
            text-decoration: none;
            position: relative;
            padding: 0.5rem 0;
        }

        .link a:hover {
            color: var(--primary-color);
        }

        .link a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: width 0.3s ease;
        }

        .link a:hover::after {
            width: 100%;
        }

        .hero {
            background: url('assets/header.jpg') center/cover no-repeat;
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            margin-bottom: 3rem;
        }

        .hero__content {
            text-align: center;
            color: var(--white);
            max-width: 800px;
            padding: 0 1rem;
        }

        .hero__content h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .hero__content p {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }

        /* Section and Heading Styles */
        .section {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        h2 {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        h2 i {
            font-size: 1.8rem;
        }

        /* Video Section Styles */
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
        }

        .video-card {
            background: var(--extra-light);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .video-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .video-card__video-container {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
            overflow: hidden;
        }

        .video-card__video-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .video-card__content {
            padding: 1.5rem;
            flex-grow: 1;
        }

        .video-card__content h4 {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .video-card__content p {
            color: var(--text-light);
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }

        .video-card__content small {
            display: block;
            color: var(--text-light);
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }

        /* Footer Styles */
        footer {
            background: var(--primary-color-dark);
            color: var(--white);
            padding: 3rem 1rem;
            margin-top: 3rem;
        }

        .footer__content {
            max-width: var(--max-width);
            margin: auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .footer__col h3 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .footer__col h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 2px;
            background: var(--white);
        }

        .footer__col p {
            margin-bottom: 1rem;
            opacity: 0.8;
            line-height: 1.6;
        }

        .footer__links {
            list-style: none;
        }

        .footer__links li {
            margin-bottom: 0.8rem;
        }

        .footer__links a {
            color: var(--white);
            text-decoration: none;
            opacity: 0.8;
            transition: opacity 0.3s;
        }

        .footer__links a:hover {
            opacity: 1;
        }

        .social__icons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social__icons a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s;
        }

        .social__icons a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .footer__bottom {
            max-width: var(--max-width);
            margin: auto;
            text-align: center;
            padding-top: 2rem;
            margin-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            opacity: 0.8;
            font-size: 0.9rem;
        }

        /* Dropdown Styles */
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
            color: #4a5568;
            font-weight: 500;
            font-size: 1rem;
            font-family: inherit;
            padding: 0.5rem 0;
        }

        .dropbtn:hover {
            color: #2a4365;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #ffffff;
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
            border-bottom: 1px solid #e2e8f0;
        }

        .profile-icon {
            font-size: 2rem;
            color: #2a4365;
            margin-right: 1rem;
        }

        .profile-name {
            font-weight: 600;
            color: #1a202c;
        }

        .profile-email {
            font-size: 0.8rem;
            color: #718096;
        }

        .dropdown-content a {
            display: flex;
            align-items: center;
            padding: 0.7rem 1.5rem;
            text-decoration: none;
            color: #4a5568;
            transition: all 0.2s ease;
        }

        .dropdown-content a i {
            margin-right: 0.8rem;
            font-size: 1.1rem;
            color: #4a5568;
        }

        .dropdown-content a:hover {
            background-color: #f7fafc;
            color: #2a4365;
        }

        .dropdown-content a:hover i {
            color: #2a4365;
        }

        .nav__menu_btn {
            font-size: 1.5rem;
            color: var(--primary-color);
            cursor: pointer;
            display: none;
        }

        @media (max-width: 768px) {
            .nav__menu_btn {
                display: block;
            }

            .nav__links {
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                flex-direction: column;
                background-color: rgba(255, 255, 255, 0.95);
                box-shadow: 0 2px 6px rgba(0,0,0,0.1);
                padding: 1rem 0;
                gap: 0.5rem;
                display: none;
                align-items: flex-start;
            }

            .nav__links.open {
                display: flex;
            }

            .nav__links .link {
                width: 100%;
                text-align: left;
                padding: 0 1rem;
            }

            .nav__links .link a {
                display: block;
                padding: 0.8rem 0;
                width: 100%;
            }
            
            .nav__links .link a::after {
                left: 1rem;
            }

            .dropdown {
                width: 100%;
                padding: 0 1rem;
            }
            
            .dropdown-content {
                position: static;
                width: 100%;
                box-shadow: none;
                margin-top: 0;
                border-radius: 0;
                padding: 0;
            }

            .dropdown-content .profile-header,
            .dropdown-content a {
                padding-left: 2rem;
            }

            body {
                padding-top: 80px;
            }

            .hero__content h1 {
                font-size: 2.2rem;
            }
            
            .section__header {
                font-size: 1.8rem;
            }
            
            h2 {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 600px) {
            .hero {
                height: 300px;
            }
            
            .hero__content h1 {
                font-size: 1.8rem;
            }
            
            .hero__content p {
                font-size: 1rem;
            }
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

    <div class="section__container">
        <div class="hero">
            <div class="hero__content">
                <h1>Discover What's New events at DentoReserve</h1>
                <p>Experience comfort and unforgettable moments at our resort </p>
            </div>
        </div>

        <?php include 'db.php'; ?>

        <div class="section">
            <h2><i class="ri-movie-line"></i> Latest Videos</h2>
            <div class="video-grid">
                <?php
                // Fetch videos from the database
                $videos_query = "SELECT v.id, v.video_path, v.description, v.upload_date, u.fullname as uploader_name 
                                 FROM videos v 
                                 JOIN users u ON v.uploader_id = u.id 
                                 ORDER BY v.upload_date DESC";
                $videos_result = $conn->query($videos_query);

                if ($videos_result && $videos_result->num_rows > 0) {
                    while ($row = $videos_result->fetch_assoc()) {
                        // Correct the video path to point to the parent directory.
                        $videoPath = htmlspecialchars('videouploads/' . basename($row['video_path']));
                        $description = htmlspecialchars($row['description']);
                        $uploadDate = date("F j, Y", strtotime($row['upload_date']));
                        $uploaderName = htmlspecialchars($row['uploader_name']);

                        echo '
                        <div class="video-card">
                            <div class="video-card__video-container">
                                <video controls controlslist="nodownload" muted autoplay playsinline>
                                    <source src="' . $videoPath . '" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                            <div class="video-card__content">
                                <h4>' . $description . '</h4>
                                <p>Uploaded by: <strong>' . $uploaderName . '</strong></p>
                                <small>Published: ' . $uploadDate . '</small>
                            </div>
                        </div>';
                    }
                } else {
                    echo '<p>No videos available at this time. Check back later!</p>';
                }
                ?>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const navbarNav = document.getElementById('navbarNav');

            menuToggle.addEventListener('click', function() {
                navbarNav.classList.toggle('open');
            });
        });
    </script>
</body>
</html>