<?php
session_start();
include 'db.php'; // Your database connection file

if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    // Not logged in at all
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

// Fetch user data with proper error handling
$stmt = $conn->prepare("SELECT fullname FROM users WHERE id = ?");
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$stmt->bind_result($fullname);
$stmt->fetch();
$stmt->close();

if (empty($fullname)) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Check if user has reservations
$reservation_check = $conn->prepare("SELECT COUNT(*) FROM bookingstatus WHERE user_id = ?");
if ($reservation_check === false) {
    die("Prepare failed: " . $conn->error);
}

$reservation_check->bind_param("i", $user_id);
if (!$reservation_check->execute()) {
    die("Execute failed: " . $reservation_check->error);
}

$reservation_check->bind_result($reservation_count);
$reservation_check->fetch();
$reservation_check->close();

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    if ($reservation_count == 0) {
        // User has no reservations, redirect with error message
        header("Location: dashboardcustomer.php?error=no_reservation");
        exit();
    }
    
    $comment = trim($_POST['comment']);
    if (!empty($comment)) {
        $stmt = $conn->prepare("INSERT INTO comments (user_id, name, comment) VALUES (?, ?, ?)");
        if ($stmt === false) {
            die("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("iss", $user_id, $fullname, $comment);
        if (!$stmt->execute()) {
            die("Execute failed: " . $stmt->error);
        }
        $stmt->close();
        
        header("Location: dashboardcustomer.php");
        exit();
    }
}

// Handle like/unlike action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'like') {
    $comment_id = intval($_POST['comment_id']);
    
    $check_stmt = $conn->prepare("SELECT id FROM comment_likes WHERE user_id = ? AND comment_id = ?");
    if ($check_stmt === false) {
        die(json_encode(['error' => 'Prepare failed: ' . $conn->error]));
    }
    
    $check_stmt->bind_param("ii", $user_id, $comment_id);
    if (!$check_stmt->execute()) {
        die(json_encode(['error' => 'Execute failed: ' . $check_stmt->error]));
    }
    
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        // Unlike
        $delete_stmt = $conn->prepare("DELETE FROM comment_likes WHERE user_id = ? AND comment_id = ?");
        if ($delete_stmt === false) {
            die(json_encode(['error' => 'Prepare failed: ' . $conn->error]));
        }
        
        $delete_stmt->bind_param("ii", $user_id, $comment_id);
        if (!$delete_stmt->execute()) {
            die(json_encode(['error' => 'Execute failed: ' . $delete_stmt->error]));
        }
        $delete_stmt->close();
        echo json_encode(['status' => 'unliked']);
    } else {
        // Like
        $insert_stmt = $conn->prepare("INSERT INTO comment_likes (user_id, comment_id) VALUES (?, ?)");
        if ($insert_stmt === false) {
            die(json_encode(['error' => 'Prepare failed: ' . $conn->error]));
        }
        
        $insert_stmt->bind_param("ii", $user_id, $comment_id);
        if (!$insert_stmt->execute()) {
            die(json_encode(['error' => 'Execute failed: ' . $insert_stmt->error]));
        }
        $insert_stmt->close();
        echo json_encode(['status' => 'liked']);
    }
    $check_stmt->close();
    exit();
}

// Fetch all comments with like counts with error handling
$comments = [];
$comments_query = "SELECT c.id, c.user_id, c.name, c.comment, c.created_at, 
                      COUNT(l.id) AS like_count,
                      SUM(CASE WHEN l.user_id = ? THEN 1 ELSE 0 END) AS user_liked
                      FROM comments c
                      LEFT JOIN comment_likes l ON c.id = l.comment_id
                      GROUP BY c.id
                      ORDER BY c.created_at DESC";

$stmt = $conn->prepare($comments_query);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $comments[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>DentoFarm & Resort</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.4.0/fonts/remixicon.css" rel="stylesheet" />
    <link rel="stylesheet" href="styles.css" />
    <style>
        /* General styles, kept as is or slightly adjusted for consistency */
        :root {
            --primary-color: #8a68da; /* Example primary color */
            --text-dark: #333;
            --text-light: #666;
            --max-width: 1200px;
        }

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
            font-weight: 600;
            color: var(--text-dark);
        }

        .nav__links {
            list-style: none;
            display: flex;
            align-items: center;
            gap: 2rem;
            transition: transform 0.3s ease-in-out; /* For mobile menu animation */
        }

        .link a {
            font-weight: 500;
            color: var(--text-light);
            transition: color 0.3s;
            text-decoration: none;
        }

        .link a:hover {
            color: var(--primary-color);
        }

        /* Hamburger Icon for Mobile */
        .menu-toggle {
            display: none; /* Hidden by default on larger screens */
            font-size: 2rem;
            cursor: pointer;
            color: var(--text-dark);
        }

        /* Dropdown Styles (Profile Dropdown) */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 220px;
            box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
            z-index: 1000;
            border-radius: 8px;
            padding: 15px;
            text-align: left;
            top: 100%; /* Position below the trigger */
            margin-top: 10px; /* Space between trigger and dropdown */
        }

        .dropdown.active .dropdown-content {
            display: block;
        }

        .profile-header {
            display: flex;
            align-items: center;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            margin-bottom: 10px;
        }

        .profile-icon {
            font-size: 2rem;
            margin-right: 10px;
            color: #00aaff;
        }

        .profile-name {
            font-weight: 600;
            font-size: 1rem;
        }

        .profile-email {
            font-size: 0.8rem;
            color: #666;
        }

        .dropdown-content a {
            color: #333;
            padding: 8px 0;
            text-decoration: none;
            display: block;
            transition: color 0.3s;
            font-size: 0.9rem;
        }

        .dropdown-content a:hover {
            color: #00aaff;
        }

        .dropdown-content a i {
            margin-right: 8px;
            width: 20px;
            text-align: center;
        }

        /* Comment Styles */
        .client__form {
            background-color: #f9f9f9;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .client__card {
            background-color: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
        }

        .commentor-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .comment-text {
            color: #555;
            margin-bottom: 1rem;
        }

        /* Like Button Styles */
        .like-section {
            display: flex;
            align-items: center;
        }

        .like-btn {
            background: none;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
            font-size: 0.9rem;
            padding: 0.3rem 0.5rem;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .like-btn:hover {
            background-color: #f0f0f0;
        }

        .like-btn i {
            transition: all 0.2s ease;
        }

        .like-btn.liked {
            color: #e74c3c;
        }

        .like-btn.liked i {
            color: #e74c3c;
        }

        .like-count {
            font-size: 0.8rem;
        }

        /* Form & Button Styles */
        .login__container {
            text-align: center;
        }

        .form__group {
            display: flex;
            flex-direction: column;
            text-align: left;
        }

        .form__group input,
        .form__group textarea {
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1rem;
            width: 100%;
            margin-bottom: 1rem;
        }

        .btn {
            padding: 0.75rem;
            background-color: var(--primary-color); /* Use primary color */
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #187bcd; /* A slightly darker blue for hover */
        }

        /* Logout Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .modal-btn {
            padding: 0.5rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        .modal-btn-confirm {
            background-color: #e74c3c;
            color: white;
            border: none;
        }

        .modal-btn-confirm:hover {
            background-color: #c0392b;
        }

        .modal-btn-cancel {
            background-color: #ecf0f1;
            color: #333;
            border: 1px solid #bdc3c7;
        }

        .modal-btn-cancel:hover {
            background-color: #d5dbdb;
        }

        /* Footer Styles */
        .footer {
            background-color: #f8f8f8;
            padding: 3rem 1rem;
            border-top: 1px solid #e0e0e0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }

        .footer__container {
            max-width: 1200px;
            margin: auto;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 2rem;
        }

        .footer__col {
            flex: 1 1 250px;
        }

        .footer__col h3,
        .footer__col h4 {
            color: #222;
            margin-bottom: 1rem;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .footer__col p {
            font-size: 0.95rem;
            line-height: 1.6;
            color: #555;
        }

        .footer__col a {
            color: #3498db;
            text-decoration: none;
            display: block;
            margin-top: 0.5rem;
            font-size: 0.95rem;
            transition: color 0.3s ease;
        }

        .footer__col a:hover {
            color: #2980b9;
        }

        .footer__bottom {
            text-align: center;
            padding-top: 1.5rem;
            font-size: 0.85rem;
            color: #777;
            border-top: 1px solid #ddd;
        }

        /* Reservation message styles */
        .no-reservation-message, .error-message {
            padding: 1rem;
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            color: #6c757d;
        }

        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .nav__links {
                position: absolute;
                top: 100%; /* Below the nav bar */
                left: 0;
                background-color: rgba(255, 255, 255, 0.95);
                width: 100%;
                flex-direction: column;
                align-items: flex-start;
                padding: 1rem 1rem;
                border-top: 1px solid #eee;
                display: none; /* Hidden by default on mobile */
                transform: translateY(-10px); /* Initial state for animation */
                opacity: 0; /* Initial state for animation */
            }

            .nav__links.active {
                display: flex; /* Show when active */
                transform: translateY(0); /* Animate into view */
                opacity: 1; /* Animate into view */
            }

            .nav__links .link {
                width: 100%;
                text-align: left;
                padding: 0.5rem 0;
            }

            .nav__links .link:last-child {
                border-bottom: none;
            }

            .menu-toggle {
                display: block; /* Show hamburger icon on mobile */
            }

            .footer__container {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .footer__col {
                flex: 1 1 100%;
            }

            .footer__col a {
                margin: 0.3rem 0;
            }

            /* Adjust dropdown for mobile within the mobile nav */
            .nav__links .dropdown {
                width: 100%;
            }

            .nav__links .dropdown-content {
                position: static; /* Stack vertically in mobile nav */
                width: 100%;
                box-shadow: none;
                border-top: 1px solid #eee;
                border-radius: 0;
                padding: 10px 0 0 20px; /* Indent dropdown items */
                margin-top: 0;
            }

            .nav__links .dropdown-content a {
                padding: 5px 0;
            }

            .nav__links .profile-header {
                padding-left: 20px; /* Align with dropdown items */
            }
        }
    </style>
</head>
<body>

<div id="logoutModal" class="modal">
    <div class="modal-content">
        <h3>Confirm Logout</h3>
        <p>Are you sure you want to log out?</p>
        <div class="modal-buttons">
            <button class="modal-btn modal-btn-cancel" onclick="closeLogoutModal()">Cancel</button>
            <button class="modal-btn modal-btn-confirm" onclick="performLogout()">Logout</button>
        </div>
    </div>
</div>

<nav class="navbar">
    <div class="nav__logo">DentoReserve</div>
    <div class="menu-toggle" id="mobileMenuToggle">
        <i class="ri-menu-line"></i> </div>
    <ul class="nav__links" id="navbarNav">
        <li class="link"><a href="dashboardcustomer.php">Home</a></li>
        <li class="link"><a href="booklogin.php">Book</a></li>
        <li class="link"><a href="Event.php">Event</a></li>
        <li class="link"><a href="cuscontact.php">Contact Us</a></li>
        <li class="link dropdown" id="profileDropdown">
            <a href="#" class="dropbtn" onclick="toggleDropdown(event)">
                <?php echo htmlspecialchars($fullname); ?> 
                <i class="ri-arrow-down-s-line"></i>
            </a>
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
                <a href="#" onclick="showLogoutModal()"><i class="ri-logout-box-line"></i> Logout</a>
            </div>
        </li>
    </ul>
</nav>

<header class="section__container header__container">
    <div class="header__image__container">
        <div class="header__content">
            <h1>Enjoy Your Dream Vacation</h1>
            <p>Unwind by the pool in style—your peaceful escape is just a reservation away..</p>
        </div>
    </div>
</header>

<section class="section__container popular__container">
    <h2 class="section__header">BOOK HERE</h2>
    <div class="popular__grid">
        <?php
        $sql = "SELECT * FROM rooms";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0):
            while ($row = mysqli_fetch_assoc($result)):
                $name = $row['name'];
                $price = $row['price'];
                $description = explode("\n", $row['description']);
                $image = 'uploads/' . $row['image'];
        ?>
            <div class="popular__card">
                <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($name); ?>" />
                <div class="popular__content">
                    <div class="popular__card__header">
                        <h4><?php echo htmlspecialchars($name); ?></h4>
                        <h4><?php echo 'P' . number_format($price); ?></h4>
                    </div>
                    <?php foreach ($description as $line): ?>
                        <p><?php echo htmlspecialchars(trim($line)); ?></p>
                    <?php endforeach; ?>
                    <a href="booklogin.php" class="book__btn">reserve Now</a>
                </div>
            </div>
        <?php
            endwhile;
        else:
        ?>
            <p>No rooms available at the moment.</p>
        <?php endif; ?>
    </div>
</section>

<section class="client">
    <div class="section__container client__container">
        <h2 class="section__header">What our clients say</h2>

        <div class="client__form">
            <h3>Add Your Comment</h3>
            <?php if ($reservation_count > 0): ?>
                <form id="commentForm" method="POST">
                    <div class="form__group">
                        <label for="comment">Comment</label>
                        <textarea id="comment" name="comment" placeholder="Your comment here..." required></textarea>
                    </div>
                    <button type="submit" class="btn">Submit Comment</button>
                </form>
            <?php else: ?>
                <p class="no-reservation-message">You need to have at least one reservation to leave a comment.</p>
                <?php if (isset($_GET['error']) && $_GET['error'] === 'no_reservation'): ?>
                    <p class="error-message">Please make a reservation before submitting a comment.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="client__grid" id="clientComments">
            <?php foreach ($comments as $comment): ?>
                <div class="client__card" data-comment-id="<?php echo $comment['id']; ?>">
                    <p class="commentor-name"><?php echo htmlspecialchars($comment['name']); ?></p>
                    <p class="comment-text">"<?php echo htmlspecialchars($comment['comment']); ?>"</p>
                    <div class="like-section">
                        <button class="like-btn <?php echo $comment['user_liked'] ? 'liked' : ''; ?>" 
                                onclick="toggleLike(this, <?php echo $comment['id']; ?>)">
                            <i class="<?php echo $comment['user_liked'] ? 'ri-heart-fill' : 'ri-heart-line'; ?>"></i> 
                            <span class="like-count"><?php echo $comment['like_count']; ?></span>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<footer class="footer">
    <div class="section__container footer__container">
        <div class="footer__col">
            <h3>DentoFarm Resort</h3>
            <p>DentoFarm Resort is located in Estrella, Rizal, Nueva Ecija...</p>
            <p>The resort seamlessly blends rustic charm with modern comforts...</p>
        </div>
        <div class="footer__col">
            <h4>Company</h4>
            <p>About Us</p>
            <p>Our Team</p>
            <p>Blog</p>
            <p>Book</p>
            <p>Contact Us</p>
        </div>
        <div class="footer__col">
            <h4>Amenities</h4>
            <p>Pool</p>
            <p>Room</p>
            <p>Cottage</p>
            <p>Food</p>
            <p>Basketball Court</p>
            <p>Farm Scene</p>
        </div>
        <div class="footer__col">
            <h4>Contact Us</h4>
            <p>+63 959 954 2057</p>
            <p>+63 922 973 1121</p>
            <p>Estrella, Rizal, Nueva Ecija</p>
            <p>dentofarmresort@email.com</p>
            <div class="footer__social">
                <i class="ri-facebook-fill"></i>
                <i class="ri-twitter-fill"></i>
                <i class="ri-instagram-fill"></i>
            </div>
        </div>
    </div>
</footer>

<script>
    // Toggle dropdown on click for profile dropdown
    function toggleDropdown(e) {
        e.preventDefault();
        const dropdown = document.getElementById('profileDropdown');
        dropdown.classList.toggle('active');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const profileDropdown = document.getElementById('profileDropdown');
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const navbarNav = document.getElementById('navbarNav');

        // Close profile dropdown if clicked outside
        if (!profileDropdown.contains(event.target)) {
            profileDropdown.classList.remove('active');
        }

        // Close mobile nav if clicked outside the toggle and the nav itself
        if (!mobileMenuToggle.contains(event.target) && !navbarNav.contains(event.target)) {
            navbarNav.classList.remove('active');
        }
    });

    // Mobile Navigation Toggle
    document.getElementById('mobileMenuToggle').addEventListener('click', function() {
        const navbarNav = document.getElementById('navbarNav');
        navbarNav.classList.toggle('active');
    });

    // Logout Modal Functions
    function showLogoutModal() {
        document.getElementById('logoutModal').style.display = 'flex';
    }

    function closeLogoutModal() {
        document.getElementById('logoutModal').style.display = 'none';
    }

    function performLogout() {
        window.location.href = 'logout.php';
    }

    // Handle like/unlike functionality
    function toggleLike(button, commentId) {
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=like&comment_id=${commentId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'liked' || data.status === 'unliked') {
                const icon = button.querySelector('i');
                const countSpan = button.querySelector('.like-count');
                let count = parseInt(countSpan.textContent);
                
                if (data.status === 'liked') {
                    icon.classList.remove('ri-heart-line');
                    icon.classList.add('ri-heart-fill');
                    countSpan.textContent = count + 1;
                    button.classList.add('liked');
                } else {
                    icon.classList.remove('ri-heart-fill');
                    icon.classList.add('ri-heart-line');
                    countSpan.textContent = count - 1;
                    button.classList.remove('liked');
                }
            }
        })
        .catch(error => console.error('Error:', error));
    }
</script>

</body>
</html>