<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>DentoFarm & Resort</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.4.0/fonts/remixicon.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color:rgb(75, 111, 194);
            --primary-color-dark:rgb(70, 112, 210);
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
            padding-top: 80px; /* To account for fixed nav */
        }

        .section__container {
            max-width: var(--max-width);
            margin: auto;
            padding: 5rem 1rem;
        }

        .section__header {
            font-size: 2rem;
            font-weight: 600;
            color: var(--text-dark);
            text-align: center;
        }

        /* --- Navigation Styles --- */
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
            height: 80px; /* Ensure fixed height for proper positioning */
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); /* Added for better visual */
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
            margin: 0; /* Remove default ul margin */
            padding: 0; /* Remove default ul padding */
        }

        .link a {
            font-weight: 500;
            color: var(--text-light);
            transition: 0.3s;
            text-decoration: none;
        }

        .link a:hover {
            color: var(--primary-color);
        }

        /* --- Hamburger menu button --- */
        .nav__menu_btn {
            font-size: 2rem;
            color: var(--text-dark);
            cursor: pointer;
            display: none; /* Hidden on desktop by default */
        }

        /* --- Media Queries for Responsive Navigation --- */
        @media (max-width: 768px) {
            .nav__menu_btn {
                display: block; /* Show hamburger menu on smaller screens */
            }

            .nav__links {
                position: absolute;
                top: 80px; /* Position below the fixed navbar */
                left: 0;
                width: 100%;
                flex-direction: column;
                background-color: rgba(255, 255, 255, 0.98); /* Almost opaque white for dropdown */
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                padding: 1rem 0;
                gap: 0.5rem;
                display: none; /* Hidden by default, toggled by JavaScript */
                align-items: flex-start; /* Align links to the left in dropdown */
                border-top: 1px solid #eee; /* Separator */
            }

            .nav__links.open {
                display: flex; /* Show when 'open' class is added by JavaScript */
            }

            .nav__links .link {
                width: 100%;
                text-align: left;
                padding: 0 1rem; /* Add padding for better spacing in dropdown */
            }

            .nav__links .link a {
                display: block; /* Make links block to fill width */
                padding: 0.8rem 0; /* More vertical padding */
                width: 100%;
                color: var(--text-dark); /* Ensure link color is readable */
            }
        }

        /* --- Your existing styles below this line (unmodified) --- */

        /* Room Listing Styles */
        .popular__grid {
            margin-top: 4rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .popular__card {
            overflow: hidden;
            border-radius: 1rem;
            box-shadow: 5px 5px 20px rgba(0, 0, 0, 0.1);
            background-color: var(--white);
        }

        .popular__card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .popular__content {
            padding: 1.5rem;
        }

        .popular__card__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }

        .popular__card__header h4 {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .popular__content p {
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }

        .book__btn {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.5rem 1.2rem;
            color: var(--white);
            border: none;
            border-radius: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .book__btn:hover {
            background-color: var(--primary-color-dark);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            /* Use flex for centering */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal__content {
            background-color: var(--white);
            /* margin: 10% auto; Removed as flex handles centering */
            padding: 2rem;
            border-radius: 1rem;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .close__btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-light);
        }

        .login__form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .form__group {
            display: flex;
            flex-direction: column;
        }

        .form__group label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }

        .form__group input {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 0.5rem;
            font-size: 1rem;
        }

        .btn {
            padding: 0.75rem;
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: var(--primary-color-dark);
        }

        .create__account {
            text-align: center;
            margin-top: 1rem;
            color: var(--text-light);
        }

        .create__account a {
            color: var(--primary-color);
            text-decoration: none;
        }

        @media (width < 600px) {
            .nav__links {
                gap: 1rem;
            }
            
            .section__container {
                padding: 3rem 1rem;
            }
            
            .section__header {
                font-size: 1.5rem;
            }
        }
        
        /* Styles specifically for the filter dropdown and grid within the popular section */
        .popular__container {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .section__header {
            text-align: center;
            font-size: 2rem;
            color: #333;
            margin-bottom: 1.5rem;
        }
        
        .popular__grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .popular__card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .popular__card:hover {
            transform: translateY(-5px);
        }
        
        .popular__card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .popular__content {
            padding: 1.5rem;
        }
        
        .popular__card__header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .popular__card__header h4 {
            margin: 0;
            font-size: 1.2rem;
            color: #333;
        }
        
        .popular__content p {
            margin: 0.5rem 0;
            color: #666;
        }
        
        .book__btn {
            width: 100%;
            padding: 0.75rem;
            margin-top: 1rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s ease;
        }
        
        .book__btn:hover {
            background: #4a148c;
        }
        
        /* Modal Styles */
        /* Note: These modal styles are duplicated. Ensure they are merged or kept consistently. 
            The initial modal styles above the popular section are likely intended for the login modal. */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }
        
        .modal-content { /* Renamed to avoid conflict with .modal__content */
            background: white;
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
        
        .modal h3 {
            margin-top: 0;
            color: #6a0dad;
        }
        
        .close {
            position: absolute;
            top: 1rem;
            right: 1.5rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        
        .btn-secondary {
            background: #333;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #111;
        }
        
        .guest-field {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .guest-field input {
            flex: 1;
        }
        
        @media (max-width: 768px) {
            .popular__grid {
                grid-template-columns: 1fr;
            }
        }

        /* Filter dropdown specific styles */
        form[method="GET"] {
            flex-direction: row; /* Default for larger screens */
            justify-content: flex-end; /* Align to the right */
        }

        @media (max-width: 480px) {
            form[method="GET"] {
                flex-direction: column; /* Stack on very small screens */
                align-items: flex-start;
            }
            form[method="GET"] label,
            form[method="GET"] select {
                width: 100%; /* Full width for stacked elements */
                margin-bottom: 10px; /* Space between stacked elements */
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
            <li class="link"><a href="index.php">Home</a></li>
            <li class="link"><a href="Book.php">Book</a></li>
            <li class="link"><a href="notevent.php">Event</a></li>
            <li class="link"><a href="notcontact.php">Contact Us</a></li>
            <li class="link"><button class="btn" onclick="openLoginModal()">Login</button></li>
        </ul>
    </nav>

    
<?php
// Start the session at the very beginning of your PHP file
if (session_status() == PHP_SESSION_NONE) {
}

// Ensure db.php is included only once and $conn is available
if (!isset($conn)) {
    include 'db.php';
}

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
?>

<section class="section__container popular__container">
    <h2 class="section__header">BOOK HERE</h2>

    <form method="GET" style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
        <label for="type" style="font-weight: 500; color: #333;">Filter by Type:</label>
        <select name="type" id="type" onchange="this.form.submit()" style="padding: 8px 12px; border-radius: 4px; border: 1px solid #ddd; background: white; cursor: pointer;">
            <option value="">All Rooms</option>
            <option value="VipRoom" <?php if (isset($_GET['type']) && $_GET['type'] === 'VipRoom') echo 'selected'; ?>>VIP Room</option>
            <option value="Cottage" <?php if (isset($_GET['type']) && $_GET['type'] === 'Cottage') echo 'selected'; ?>>Cottage</option>
        </select>
    </form>

    <div class="popular__grid">
        <?php
        // Build the SQL query based on filter
        $sql = "SELECT * FROM rooms";
        if (isset($_GET['type']) && !empty($_GET['type'])) {
            $type = mysqli_real_escape_string($conn, $_GET['type']);
            $sql .= " WHERE type = '$type'";
        }
        
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) > 0):
            while ($row = mysqli_fetch_assoc($result)):
                $name = $row['name'];
                $price = $row['price'];
                $description = explode("\n", $row['description']);
                $image = 'uploads/' . $row['image'];
                $room_id = $row['id'];
        ?>
        <div class="popular__card">
            <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($name); ?>" />
            <div class="popular__content">
                <div class="popular__card__header">
                    <h4><?php echo htmlspecialchars($name); ?></h4>
                    <h4><?php echo '₱' . number_format($price, 2); ?></h4>
                </div>
                <?php foreach ($description as $line): ?>
                    <p><?php echo htmlspecialchars(trim($line)); ?></p>
                <?php endforeach; ?>
                <?php 
                // Conditionally render the button based on login status
                if ($is_logged_in): 
                ?>
                    <button class="book__btn" onclick="openBookingModal(<?php echo $room_id; ?>, '<?php echo htmlspecialchars($name); ?>', <?php echo $price; ?>)">Reserve Now</button>
                <?php else: ?>
                    <button class="book__btn" onclick="openLoginModal()">Reserve Now</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 2rem;">
                <p style="font-size: 1.1rem; color: #666;">No rooms available matching your criteria.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<div id="bookingModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('bookingModal')">&times;</span>
        <h3>Book <span id="modalRoomName"></span></h3>
        <form action="process_booking.php" method="POST">
            <input type="hidden" name="room_id" id="modalRoomId">
            <input type="hidden" name="room_name" id="modalRoomNameInput">
            <input type="hidden" name="room_price" id="modalRoomPriceInput">

            <p>Price: ₱<span id="modalRoomPrice"></span></p>

            <div class="form-group">
                <label for="check_in_date">Check-in Date:</label>
                <input type="date" id="check_in_date" name="check_in_date" required>
            </div>
            <div class="form-group">
                <label for="check_out_date">Check-out Date:</label>
                <input type="date" id="check_out_date" name="check_out_date" required>
            </div>
            <div class="form-group">
                <label for="num_guests">Number of Guests:</label>
                <input type="number" id="num_guests" name="num_guests" min="1" required onchange="generateGuestFields()">
            </div>
            
            <div id="guestFieldsContainer">
                </div>

            <button type="submit" class="btn">Confirm Booking</button>
        </form>
    </div>
</div>

<div id="loginModal" class="modal">
    <div class="modal__content">
        <span class="close__btn" onclick="closeLoginModal()">&times;</span>
        <h2>Login</h2>
        <form class="login__form" action="login.php" method="POST">
            <div class="form__group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required />
            </div>
            <div class="form__group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required />
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
        <div class="create__account">
            <p>Don't have an account? <a href="register.php">Create one</a></p>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.getElementById('menuToggle');
        const navbarNav = document.getElementById('navbarNav');

        // Toggle mobile navigation
        if (menuToggle && navbarNav) {
            menuToggle.addEventListener('click', function() {
                navbarNav.classList.toggle('open');
            });

            // Close mobile navigation when a link or button inside it is clicked
            navbarNav.querySelectorAll('a, button').forEach(item => {
                item.addEventListener('click', () => {
                    // Small delay for the Login button to allow modal to open
                    if (item.classList.contains('btn')) {
                        setTimeout(() => navbarNav.classList.remove('open'), 100); 
                    } else {
                        navbarNav.classList.remove('open');
                    }
                });
            });
        }
    });

    // --- Modal Functions (from your original script) ---
    // Open login modal
    function openLoginModal() {
        document.getElementById('loginModal').style.display = 'flex'; // Use flex for centering
    }
    
    // Open booking modal with room details
    function openBookingModal(roomId, roomName, roomPrice) {
        document.getElementById('modalRoomId').value = roomId;
        document.getElementById('modalRoomName').textContent = roomName;
        document.getElementById('modalRoomNameInput').value = roomName;
        document.getElementById('modalRoomPrice').textContent = roomPrice.toFixed(2);
        document.getElementById('modalRoomPriceInput').value = roomPrice;
        
        // Clear previous guest fields
        document.getElementById('guestFieldsContainer').innerHTML = '';
        
        document.getElementById('bookingModal').style.display = 'flex'; // Use flex for centering
    }
    
    // Close modal
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    function closeLoginModal() {
      document.getElementById('loginModal').style.display = 'none';
    }
    
    // Add guest field based on number of guests input
    function generateGuestFields() {
        const numGuests = parseInt(document.getElementById('num_guests').value);
        const container = document.getElementById('guestFieldsContainer');
        container.innerHTML = ''; // Clear existing fields

        if (numGuests > 0) {
            for (let i = 1; i <= numGuests; i++) {
                const guestDiv = document.createElement('div');
                guestDiv.className = 'form-group guest-field'; // Added form-group for consistency
                guestDiv.innerHTML = `
                    <label for="guest_name_${i}">Guest ${i} Name:</label>
                    <input type="text" id="guest_name_${i}" name="guest_names[]" placeholder="Full Name" required>
                    <label for="guest_age_${i}">Age:</label>
                    <input type="number" id="guest_age_${i}" name="guest_ages[]" placeholder="Age" min="1" max="120" required>
                `;
                container.appendChild(guestDiv);
            }
        }
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const loginModal = document.getElementById('loginModal');
        const bookingModal = document.getElementById('bookingModal');

        if (event.target === loginModal) {
            closeLoginModal();
        }
        if (event.target === bookingModal) {
            closeModal('bookingModal');
        }
    }
</script>
</body>
</html>