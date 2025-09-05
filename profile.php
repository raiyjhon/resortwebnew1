<?php
session_start();
require 'db.php'; // Database connection

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$user_id = $_SESSION['user_id']; // For consistency with nav bar code

// Fetch user info
$stmt = $conn->prepare("SELECT fullname, email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $fullname = $user['fullname']; // Assign to fullname for the nav bar
} else {
    // Handle user not found, maybe destroy session and redirect
    session_destroy();
    header("Location: login.php?error=user_not_found");
    exit();
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>My Profile - DentoFarm & Resort</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.4.0/fonts/remixicon.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        :root {
            --primary-color: #2c3855;
            --primary-color-dark: #435681;
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
            padding-top: 80px;
            background-color: var(--extra-light);
        }
        /* Navigation Styles */
        nav {
            max-width: var(--max-width);
            margin: auto;
            padding: 2rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.9);
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
        .nav__links {
            display: flex;
            align-items: center;
            list-style: none;
            gap: 2rem;
        }
        .nav__links .link a {
            font-weight: 500;
            color: var(--text-dark);
            transition: 0.3s;
            text-decoration: none;
        }
        .nav__links .link a:hover {
            color: var(--primary-color);
        }
        .dropdown {
            position: relative;
        }
        .dropbtn {
            background: none;
            border: none;
            color: var(--text-dark);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            font-family: "Poppins", sans-serif;
            font-size: 1rem;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: var(--white);
            min-width: 200px;
            box-shadow: 0px 8px 16px rgba(0,0,0,0.1);
            border-radius: 0.5rem;
            z-index: 1;
            right: 0;
            padding: 0.5rem 0;
        }
        .dropdown-content a {
            color: var(--text-dark);
            padding: 0.75rem 1rem;
            text-decoration: none;
            display: block;
            transition: all 0.3s;
        }
        .dropdown-content a:hover {
            background-color: var(--extra-light);
            color: var(--primary-color);
        }
        .dropdown:hover .dropdown-content {
            display: block;
        }
        .profile-header {
            padding: 1rem;
            border-bottom: 1px solid var(--extra-light);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .profile-icon {
            font-size: 1.5rem;
            color: var(--primary-color);
        }
        .profile-name {
            font-weight: 600;
            font-size: 0.9rem;
        }
        .profile-email {
            font-size: 0.8rem;
            color: var(--text-light);
        }
        .nav__menu__btn {
            display: none;
            font-size: 1.5rem;
            color: var(--primary-color);
            cursor: pointer;
        }

        /* Main Content Styles */
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
        
        /* Profile Card Styles */
        .profile-card {
            background: var(--white);
            max-width: 600px;
            margin: auto;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .profile-info {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .info-item {
            display: flex;
            align-items: center;
            font-size: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--extra-light);
        }
        .info-item i {
            font-size: 1.2rem;
            color: var(--primary-color);
            margin-right: 1rem;
            width: 20px;
            text-align: center;
        }
        .info-item .label {
            font-weight: 600;
            color: var(--text-dark);
            width: 100px;
        }
        .info-item .value {
            color: var(--text-light);
        }
        
        .profile-actions {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            font-family: "Poppins", sans-serif;
        }
        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
        }
        .btn-primary:hover {
            background-color: var(--primary-color-dark);
        }
        .btn-secondary {
            background-color: #6c757d;
            color: var(--white);
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }

        /* --- NEW MODAL STYLES --- */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 2000; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.5); 
            justify-content: center;
            align-items: center;
        }
        .modal-open {
            display: flex;
        }
        .modal-content {
            background-color: var(--white);
            margin: auto;
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .modal-content h3 {
            margin-top: 0;
            margin-bottom: 1rem;
            color: var(--text-dark);
        }
        .modal-content p {
            margin-bottom: 1.5rem;
            color: var(--text-light);
        }
        .modal-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
        /* End of Modal Styles */

        /* Responsive Styles */
        @media (max-width: 768px) {
            .nav__links {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 80px;
                left: 0;
                width: 100%;
                background-color: var(--white);
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                padding: 1rem 0;
                z-index: 999;
            }
            .nav__links.open { display: flex; }
            .nav__links .link { width: 100%; text-align: center; padding: 0.75rem 0; }
            .nav__links .link a, .nav__links .link .dropbtn { padding: 0.75rem 1rem; display: block; }
            .dropdown-content { position: static; box-shadow: none; border-radius: 0; width: 100%; padding-left: 1rem; }
            .profile-header { flex-direction: column; text-align: center; }
            .profile-icon { margin-bottom: 0.5rem; }
            .nav__menu__btn { display: block; }
        }
    </style>
</head>
<body>
    <nav>
        <div class="nav__logo">DentoReserve</div>
        <ul class="nav__links" id="navLinks">
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
                    <a href="javascript:void(0);" onclick="showLogoutModal()"><i class="ri-logout-box-line"></i> Logout</a>
                </div>
            </li>
        </ul>
        <div class="nav__menu__btn" id="menuToggle">
            <i class="ri-menu-line"></i>
        </div>
    </nav>

    <section class="section__container">
        <h2 class="section__header">My Profile</h2>

        <div class="profile-card">
            <div class="profile-info">
                <div class="info-item">
                    <i class="ri-user-fill"></i>
                    <span class="label">Full Name:</span>
                    <span class="value"><?php echo htmlspecialchars($user['fullname']); ?></span>
                </div>
                <div class="info-item">
                    <i class="ri-mail-fill"></i>
                    <span class="label">Email:</span>
                    <span class="value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="info-item">
                    <i class="ri-phone-fill"></i>
                    <span class="label">Phone:</span>
                    <span class="value"><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></span>
                </div>
            </div>
            
            <div class="profile-actions">
                <a href="dashboardcustomer.php" class="btn btn-primary">Back to Dashboard</a>
                <a href="javascript:void(0);" onclick="showLogoutModal()" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </section>

    <!-- NEW LOGOUT MODAL -->
    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <h3>Confirm Logout</h3>
            <p>Are you sure you want to log out?</p>
            <div class="modal-actions">
                <button id="cancelLogout" class="btn btn-secondary">Cancel</button>
                <a href="logout.php" class="btn btn-primary">Logout</a>
            </div>
        </div>
    </div>

    <script>
        // Mobile Navigation Toggle
        const menuToggle = document.getElementById('menuToggle');
        const navLinks = document.getElementById('navLinks');
        menuToggle.addEventListener('click', () => navLinks.classList.toggle('open'));

        // --- NEW MODAL LOGIC ---
        const logoutModal = document.getElementById('logoutModal');
        const cancelLogoutBtn = document.getElementById('cancelLogout');

        // Function to show the modal
        function showLogoutModal() {
            logoutModal.classList.add('modal-open');
        }

        // Function to hide the modal
        function closeLogoutModal() {
            logoutModal.classList.remove('modal-open');
        }

        // Event listener for the cancel button
        cancelLogoutBtn.addEventListener('click', closeLogoutModal);

        // Event listener to close modal if clicking the background overlay
        window.addEventListener('click', function(event) {
            if (event.target == logoutModal) {
                closeLogoutModal();
            }
        });
    </script>
</body>
</html>