<?php
session_start();
include 'db.php'; // Your database connection file

if (!isset($_SESSION['user_id'])) {
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>DentoFarm & Resort</title>
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
            padding-top: 80px; /* To account for fixed nav */
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
        /* Mobile Menu */
        .nav__menu__btn {
            display: none; /* Hidden on desktop */
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
        .popular__grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .popular__card {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            background: var(--white);
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .popular__card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
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
            align-items: center;
            margin-bottom: 1rem;
        }
        .popular__card__header h4 {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-dark);
        }
        .popular__card__header h4:last-child {
            color: var(--primary-color);
        }
        .popular__content p {
            color: var(--text-light);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .card-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-light);
            margin-bottom: 1rem;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .card-info i {
            font-size: 1.1rem;
            color: var(--primary-color);
        }

        .book__btn {
            display: inline-block;
            width: 100%;
            margin-top: 1rem;
            padding: 0.75rem;
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
            text-align: center;
        }
        .book__btn:hover {
            background-color: var(--primary-color-dark);
        }
        .book__btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        #type {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 0.5rem;
            font-family: "Poppins", sans-serif;
            margin-left: 0.5rem;
        }
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: var(--white);
            margin: 1rem;
            padding: 2rem;
            border-radius: 1rem;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
        .close-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-light);
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-dark);
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 0.5rem;
            font-family: "Poppins", sans-serif;
        }
        /* Additional styles for buttons, terms, etc. */
        .btn {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background-color: #4a6baf;
            color: white;
            border: 1px solid #3a5a9f;
        }
        .btn-primary:hover {
            background-color: #3a5a9f;
        }
        .btn-outline-secondary {
            background-color: transparent;
            color: #6c757d;
            border: 1px solid #6c757d;
        }
        .btn-outline-secondary:hover {
            background-color: #f8f9fa;
        }
        .d-flex {
            display: flex;
        }
        .align-items-center {
            align-items: center;
        }
        .mr-3 {
            margin-right: 1rem;
        }
        .terms-container {
            margin: 20px 0;
        }
        .terms-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            background-color: #f9f9f9;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .terms-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            color: #333;
        }
        .terms-header i {
            font-size: 24px;
            margin-right: 10px;
            color: #4a6baf;
        }
        .terms-header h4 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        .terms-content {
            font-size: 14px;
            line-height: 1.5;
        }
        .terms-content ul {
            padding-left: 20px;
            margin: 10px 0;
        }
        .terms-content li {
            margin-bottom: 5px;
        }
        .terms-agreement {
            display: flex;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }
        .checkbox-wrapper {
            position: relative;
            margin-right: 10px;
        }
        .checkbox-wrapper input[type="checkbox"] {
            opacity: 0;
            position: absolute;
        }
        .checkbox-wrapper label {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #4a6baf;
            border-radius: 4px;
            cursor: pointer;
            position: relative;
        }
        .checkbox-wrapper input[type="checkbox"]:checked + label:after {
            content: "✓";
            position: absolute;
            top: -2px;
            left: 3px;
            color: #4a6baf;
            font-weight: bold;
        }
        .terms-agreement a {
            color: #4a6baf;
            text-decoration: underline;
        }
        .terms-agreement a:hover {
            text-decoration: none;
        }
        #submitBtn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .book__btn {
            background-color: #4a6baf;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .book__btn:hover:not(:disabled) {
            background-color: #3a5a9f;
        }

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
            .nav__links.open {
                display: flex;
            }
            .nav__links .link {
                width: 100%;
                text-align: center;
                padding: 0.75rem 0;
            }
            .nav__links .link a, .nav__links .link .dropbtn {
                padding: 0.75rem 1rem;
                display: block;
            }
            .dropdown-content {
                position: static;
                box-shadow: none;
                border-radius: 0;
                width: 100%;
                padding-left: 1rem;
            }
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            .profile-icon {
                margin-bottom: 0.5rem;
            }
            .nav__menu__btn {
                display: block;
            }
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
                    <a href="logout.php"><i class="ri-logout-box-line"></i> Logout</a>
                </div>
            </li>
        </ul>
        <div class="nav__menu__btn" id="menuToggle">
            <i class="ri-menu-line"></i>
        </div>
    </nav>

    <section class="section__container popular__container">
        <h2 class="section__header">BOOK HERE</h2>

        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
            <form method="GET" style="margin: 0;">
                <label for="type">Filter by Type:</label>
                <select name="type" id="type" onchange="this.form.submit()">
                    <option value="">All</option>
                    <option value="VipRoom" <?php if (($_GET['type'] ?? '') === 'VipRoom') echo 'selected'; ?>>VipRoom</option>
                    <option value="Cottage" <?php if (($_GET['type'] ?? '') === 'Cottage') echo 'selected'; ?>>Cottage</option>
                </select>
            </form>
            
            <a href="calendar.php" class="calendar-btn" style="padding: 0.5rem 1rem; background-color: #4a6baf; color: white; border-radius: 0.5rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
                <i class="ri-calendar-line"></i> View Reservation
            </a>
        </div>

        <div class="popular__grid">
            <?php
            $typeFilter = $_GET['type'] ?? '';
            if ($typeFilter === 'VipRoom' || $typeFilter === 'Cottage') {
                $stmt = $conn->prepare("SELECT * FROM rooms WHERE type = ?");
                $stmt->bind_param("s", $typeFilter);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = $conn->query("SELECT * FROM rooms");
            }

            if ($result && $result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
                    $id = $row['id'];
                    $name = $row['name'];
                    $price = $row['price'];
                    $stock_6h_am = $row['stock_6h_am'];
                    $stock_6h_pm = $row['stock_6h_pm'];
                    $stock_12h = $row['stock_12h'];
                    $description = explode("\n", $row['description']);
                    $image = 'uploads/' . $row['image'];
                    $guest_limit = $row['guest_limit'] ?? 1;
                    $type = $row['type'] ?? 'N/A';
                    $is_available = ($stock_6h_am > 0 || $stock_6h_pm > 0 || $stock_12h > 0);
            ?>
                <div class="popular__card">
                    <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($name); ?>" />
                    <div class="popular__content">
                        <div class="popular__card__header">
                            <h4><?php echo htmlspecialchars($name); ?></h4>
                            <h4>P<?php echo number_format($price); ?></h4>
                        </div>

                        <div class="card-info">
                            <i class="ri-group-line"></i>
                            <span>Up to <?php echo htmlspecialchars($guest_limit); ?> guests</span>
                        </div>

                        <?php foreach ($description as $line): ?>
                            <p><?php echo htmlspecialchars(trim($line)); ?></p>
                        <?php endforeach; ?>

                        <button class="book__btn" 
                                onclick="openBookingModal(
                                    '<?php echo addslashes($name); ?>', 
                                    <?php echo $price; ?>,
                                    <?php echo $stock_6h_am; ?>,
                                    <?php echo $stock_6h_pm; ?>,
                                    <?php echo $stock_12h; ?>,
                                    '<?php echo $id; ?>',
                                    <?php echo $guest_limit; ?>,
                                    '<?php echo addslashes($type); ?>'
                                )" 
                                <?php echo !$is_available ? 'disabled' : ''; ?>>
                            <?php echo $is_available ? 'Reserve Now' : 'Fully Booked'; ?>
                        </button>
                    </div>
                </div>
            <?php endwhile; else: ?>
                <p>No rooms available at the moment.</p>
            <?php endif; ?>
        </div>
    </section>

    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeBookingModal()">&times;</span>
            <h2>Book Your Reservation</h2>
            <p id="roomInfo" style="font-weight: bold; margin-bottom: 0.25rem;"></p>
            <p id="roomTypeInfo" style="color: #555; font-style: italic; margin-bottom: 1rem;"></p>
            
            <form id="bookingForm" method="POST" action="submit_booking.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="guestName">Name:</label>
                    <input type="text" id="guestName" name="guestName" value="<?php echo htmlspecialchars($fullname); ?>" required />
                </div>

                <div class="form-group">
                    <label for="stayDuration">1. Select Duration:</label>
                    <select id="stayDuration" name="stayDuration" required onchange="onDurationChange()">
                        <option value="" disabled selected>Select Duration</option>
                        <option value="6">Half Day (6 hours)</option>
                        <option value="12">Whole Day (12 hours)</option>
                        <option value="22">Extended Day (22 hours)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="checkinDate">2. Date of Check-in:</label>
                    <input type="date" id="checkinDate" name="checkinDate" required onchange="updateAvailability()" disabled />
                </div>

                <!-- Group for fixed cottage time slots -->
                <div id="cottageTimeSlotGroup" class="form-group" style="display:none;">
                    <label for="cottageTimeSlot">3. Time Slot:</label>
                    <!-- **** FIX: Changed name from "time_slot_select" to "time_slot" to match the other inputs **** -->
                    <select id="cottageTimeSlot" name="time_slot" onchange="setFixedTimes()"></select>
                    <div id="stockNoticeCottage" style="color: red; font-size: 0.9rem; margin-top: 0.25rem;"></div>
                </div>

                <!-- Group for regular time-in (for user input ONLY) -->
                <div id="regularTimeInGroup" class="form-group" style="display:none;">
                    <label for="checkinTimeInput">Timein:</label>
                    <!-- **** FIX 1: REMOVED name="checkinTime" FROM THIS INPUT **** -->
                    <input type="time" id="checkinTimeInput" onchange="calculateTimeout()" />
                </div>

                <!-- Group for AM/PM radio buttons for non-cottages -->
                <div id="timeSlotGroup" class="form-group" style="display:none;">
                    <label>Time Slot:</label>
                    <div id="timeSlotOptions"></div>
                </div>

                <!-- Group for regular time-out -->
                <div id="regularTimeOutGroup" class="form-group" style="display:none;">
                    <label for="checkoutTime">Timeout:</label>
                    <input type="time" id="checkoutTime" name="checkoutTime" readonly />
                </div>
                
                <div id="stockNotice" style="color: red; font-size: 0.9rem; margin-top: 0.25rem;"></div>

                <div class="form-group">
                    <label for="guestCount">Number of Guests: <span id="guestLimitText" style="font-weight: normal; color: #555; font-size: 0.9em;"></span></label>
                    <input type="number" id="guestCount" name="guestCount" min="1" required onchange="generateGuestFields(); calculateTotal();" />
                </div>

                <div id="guestFields"></div>

                <div class="form-group">
                    <label for="validId">Upload Valid ID:</label>
                    <div class="d-flex align-items-center">
                        <label class="btn btn-primary mr-3" for="validId">
                            <i class="fas fa-upload"></i> Choose File
                            <input type="file" id="validId" name="validId" accept="image/*,.pdf" required hidden />
                        </label>
                        <a href="/terms-and-conditions" class="btn btn-outline-secondary" target="_blank">
                            <i class="fas fa-question-circle"></i> ID Requirements
                        </a>
                    </div>
                    <div id="file-name" class="mt-2 text-muted small">No file chosen</div>
                    <small class="form-text text-danger" style="font-weight: bold;">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Important:</strong> Please upload a valid, government-issued ID for verification.
                    </small>
                </div>

                <div class="form-group terms-container">
                    <div class="terms-card">
                        <div class="terms-header">
                            <i class="fas fa-file-contract"></i>
                            <h4>Terms & Conditions</h4>
                        </div>
                        <div class="terms-content">
                            <p>By proceeding with this booking, you agree to our:</p>
                            <ul>
                                <li>Cancellation policy</li>
                                <li>ID verification requirements</li>
                                <li>Payment terms</li>
                                <li>House rules</li>
                            </ul>
                            <div class="terms-agreement">
                                <div class="checkbox-wrapper">
                                    <input type="checkbox" id="agreeTerms" name="agreeTerms" required />
                                    <label for="agreeTerms"></label>
                                </div>
                                <span>I have read and agree to the <a href="/terms-and-conditions" target="_blank">full Terms and Conditions</a></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Total Price:</label>
                    <input type="text" id="totalPriceDisplay" readonly style="font-weight:bold; border:none; background:none; font-size: 1.2rem;" value="₱0" />
                </div>
                
                <!-- **** FIX 2: This is now the ONLY input that submits the checkinTime **** -->
                <input type="hidden" id="finalCheckinTime" name="checkinTime" />
                
                <input type="hidden" id="roomPrice" name="roomPrice" />
                <input type="hidden" id="roomName" name="roomName" />
                <input type="hidden" id="roomId" name="roomId" />
                <input type="hidden" id="totalPrice" name="totalPrice" />

                <button type="submit" class="book__btn" id="submitBtn" disabled>Submit Booking</button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('validId').addEventListener('change', function(e) {
            var fileName = e.target.files[0] ? e.target.files[0].name : 'No file chosen';
            document.getElementById('file-name').textContent = fileName;
            checkSubmitConditions();
        });

        document.getElementById('agreeTerms').addEventListener('change', checkSubmitConditions);

        function checkSubmitConditions() {
            const fileUploaded = document.getElementById('validId').files.length > 0;
            const termsAgreed = document.getElementById('agreeTerms').checked;
            document.getElementById('submitBtn').disabled = !(fileUploaded && termsAgreed);
        }

        function openBookingModal(roomName, roomPrice, stock6hAM, stock6hPM, stock12h, roomId, guestLimit, roomType) {
            const modal = document.getElementById('bookingModal');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';

            document.getElementById('roomInfo').textContent = `${roomName} - P${roomPrice.toLocaleString()}`;
            document.getElementById('roomTypeInfo').textContent = `Type: ${roomType}`;
            document.getElementById('roomPrice').value = roomPrice;
            document.getElementById('roomName').value = roomName;
            document.getElementById('roomId').value = roomId;
            document.getElementById('guestCount').setAttribute('max', guestLimit);
            
            const bookingForm = document.getElementById('bookingForm');
            bookingForm.reset();
            // **** FIX 3: Clear the hidden checkinTime field when the modal opens ****
            document.getElementById('finalCheckinTime').value = ''; 
            
            bookingForm.dataset.roomType = roomType; // Store room type

            document.getElementById('guestLimitText').textContent = `(Max: ${guestLimit})`;

            const stayDurationSelect = document.getElementById('stayDuration');
            const options = stayDurationSelect.options;

            for (let i = 0; i < options.length; i++) {
                options[i].disabled = false;
                options[i].style.display = '';
            }

            if (roomType === 'VipRoom') {
                for (let i = 0; i < options.length; i++) {
                    if (options[i].value !== '22' && options[i].value !== '') {
                        options[i].disabled = true;
                        options[i].style.display = 'none';
                    }
                }
                stayDurationSelect.value = '22';
            } else if (roomType === 'Cottage') {
                for (let i = 0; i < options.length; i++) {
                    if (options[i].value === '22') {
                        options[i].disabled = true;
                        options[i].style.display = 'none';
                    }
                }
            }

            const today = new Date().toISOString().split('T')[0];
            document.getElementById('checkinDate').setAttribute('min', today);
            
            document.getElementById('checkinDate').disabled = true;
            document.getElementById('cottageTimeSlotGroup').style.display = 'none';
            document.getElementById('regularTimeInGroup').style.display = 'none';
            document.getElementById('timeSlotGroup').style.display = 'none';
            document.getElementById('regularTimeOutGroup').style.display = 'none';

            onDurationChange(); 
        }

        function onDurationChange() {
            const duration = document.getElementById('stayDuration').value;
            const checkinDate = document.getElementById('checkinDate');
            const roomType = document.getElementById('bookingForm').dataset.roomType;

            document.getElementById('cottageTimeSlotGroup').style.display = 'none';
            document.getElementById('regularTimeInGroup').style.display = 'none';
            document.getElementById('timeSlotGroup').style.display = 'none';
            document.getElementById('regularTimeOutGroup').style.display = 'none';
            document.getElementById('stockNotice').textContent = '';
            document.getElementById('stockNoticeCottage').textContent = '';

            if (duration) {
                checkinDate.disabled = false;
                
                if (roomType === 'Cottage') {
                    document.getElementById('cottageTimeSlotGroup').style.display = 'block';
                    const cottageTimeSlot = document.getElementById('cottageTimeSlot');
                    cottageTimeSlot.innerHTML = '<option value="" disabled selected>Select a time slot</option>';

                    if (duration === '6') {
                        cottageTimeSlot.innerHTML += '<option value="am">8:00 AM - 3:00 PM</option>';
                        cottageTimeSlot.innerHTML += '<option value="pm">3:00 PM - 10:00 PM</option>';
                    } else if (duration === '12') {
                        cottageTimeSlot.innerHTML += '<option value="whole">8:00 AM - 10:00 PM</option>';
                    }
                } else {
                    document.getElementById('regularTimeInGroup').style.display = 'block';
                    document.getElementById('regularTimeOutGroup').style.display = 'block';
                }
                updateAvailability();
            } else {
                checkinDate.disabled = true;
            }
        }
        
        // **** FIX 4: This function now populates the single hidden input field ****
        function setFixedTimes() {
            const selectedSlot = document.getElementById('cottageTimeSlot').value;
            const finalCheckinTime = document.getElementById('finalCheckinTime');

            if (selectedSlot === 'am') {
                finalCheckinTime.value = '08:00';
            } else if (selectedSlot === 'pm') {
                finalCheckinTime.value = '15:00';
            } else if (selectedSlot === 'whole') {
                finalCheckinTime.value = '08:00';
            }
        }

        function closeBookingModal() {
            document.getElementById('bookingModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('bookingModal');
            if (event.target === modal) {
                closeBookingModal();
            }
        };

        // **** FIX 5: This function now uses the new input IDs and populates the hidden field ****
        function calculateTimeout() {
            const timeInValue = document.getElementById("checkinTimeInput").value;
            const duration = parseInt(document.getElementById("stayDuration").value, 10);
            const checkoutTimeInput = document.getElementById("checkoutTime");
            const finalCheckinTime = document.getElementById('finalCheckinTime');

            // Set the value for the hidden input that will be submitted to the server
            finalCheckinTime.value = timeInValue;

            if (timeInValue && !isNaN(duration)) {
                const [hours, minutes] = timeInValue.split(':').map(Number);
                const date = new Date();
                date.setHours(hours, minutes, 0);
                date.setHours(date.getHours() + duration);
                const outHours = String(date.getHours()).padStart(2, '0');
                const outMinutes = String(date.getMinutes()).padStart(2, '0');
                checkoutTimeInput.value = `${outHours}:${outMinutes}`;
            } else {
                checkoutTimeInput.value = "";
            }
        }

        async function updateAvailability() {
            const duration = document.getElementById('stayDuration').value;
            const roomId = document.getElementById('roomId').value;
            const checkinDate = document.getElementById('checkinDate').value;
            const roomType = document.getElementById('bookingForm').dataset.roomType;
            
            const stockNotice = document.getElementById('stockNotice');
            stockNotice.textContent = '';
            
            if (!checkinDate || !duration) {
                return;
            }

            try {
                const response = await fetch(`check_availability.php?roomId=${encodeURIComponent(roomId)}&date=${encodeURIComponent(checkinDate)}&duration=${encodeURIComponent(duration)}`);
                const availability = await response.json();

                if (roomType === 'Cottage') {
                    const cottageTimeSlot = document.getElementById('cottageTimeSlot');
                    const stockNoticeCottage = document.getElementById('stockNoticeCottage');
                    stockNoticeCottage.textContent = '';

                    if (duration === '6') {
                        cottageTimeSlot.querySelector('option[value="am"]').disabled = !availability.am;
                        cottageTimeSlot.querySelector('option[value="am"]').textContent = `8:00 AM - 3:00 PM (${availability.am ? 'Available' : 'Booked'})`;
                        cottageTimeSlot.querySelector('option[value="pm"]').disabled = !availability.pm;
                        cottageTimeSlot.querySelector('option[value="pm"]').textContent = `3:00 PM - 10:00 PM (${availability.pm ? 'Available' : 'Booked'})`;
                        if (!availability.am && !availability.pm) {
                             stockNoticeCottage.textContent = 'All slots are fully booked for this date.';
                        }
                    } else if (duration === '12') {
                         cottageTimeSlot.querySelector('option[value="whole"]').disabled = !availability.whole;
                         cottageTimeSlot.querySelector('option[value="whole"]').textContent = `8:00 AM - 10:00 PM (${availability.whole ? 'Available' : 'Booked'})`;
                         if (!availability.whole) {
                            stockNoticeCottage.textContent = 'This option is fully booked for this date.';
                         }
                    }
                } else {
                    const timeSlotGroup = document.getElementById('timeSlotGroup');
                    const timeSlotOptions = document.getElementById('timeSlotOptions');
                    timeSlotOptions.innerHTML = '';
                    
                    if (duration === '6') {
                        document.getElementById('regularTimeInGroup').style.display = 'none';
                        timeSlotGroup.style.display = 'block';
                        timeSlotOptions.innerHTML = `
                            <div style="margin-bottom: 0.5rem;"><input type="radio" id="timeAM" name="time_slot" value="am" ${!availability.am && 'disabled'} required><label for="timeAM"> AM</label><span style="margin-left: 0.5rem; color: ${availability.am ? 'green' : 'red'};">(${availability.am ? 'Available' : 'Fully booked'})</span></div>
                            <div><input type="radio" id="timePM" name="time_slot" value="pm" ${!availability.pm && 'disabled'} required><label for="timePM"> PM</label><span style="margin-left: 0.5rem; color: ${availability.pm ? 'green' : 'red'};">(${availability.pm ? 'Available' : 'Fully booked'})</span></div>`;
                    } else if (duration === '12' || duration === '22') {
                        timeSlotGroup.style.display = 'none';
                        document.getElementById('regularTimeInGroup').style.display = 'block';
                        if (!availability.whole) {
                            stockNotice.textContent = 'This duration is fully booked for the selected date.';
                        } else {
                            timeSlotOptions.innerHTML = '<input type="hidden" name="time_slot" value="whole">';
                        }
                    }
                }
            } catch (err) {
                console.error('Error fetching availability:', err);
                stockNotice.textContent = 'Error checking availability.';
            }
        }

        function generateGuestFields() {
            const guestCountInput = document.getElementById('guestCount');
            const count = parseInt(guestCountInput.value, 10);
            const maxGuests = parseInt(guestCountInput.getAttribute('max'), 10);
            const guestFields = document.getElementById('guestFields');
            guestFields.innerHTML = '';

            if (isNaN(count) || count < 1) return;
            if (count > maxGuests) {
                alert(`Maximum guests allowed for this accommodation: ${maxGuests}`);
                guestCountInput.value = maxGuests;
                return generateGuestFields();
            }

            const container = document.createElement('div');
            container.className = 'guest-fields-container';
            const title = document.createElement('div');
            title.className = 'guest-title';
            title.textContent = 'Guest Details';
            guestFields.appendChild(title);

            for (let i = 1; i <= count; i++) {
                const guestCard = document.createElement('div');
                guestCard.className = 'guest-card';
                const numberBadge = document.createElement('div');
                numberBadge.className = 'guest-number';
                numberBadge.textContent = i;
                guestCard.appendChild(numberBadge);
                const nameRow = document.createElement('div');
                nameRow.className = 'guest-row';
                const nameGroup = document.createElement('div');
                nameGroup.className = 'form-group';
                const nameLabel = document.createElement('label');
                nameLabel.htmlFor = `guestName${i}`;
                nameLabel.textContent = 'Full Name';
                const nameInput = document.createElement('input');
                nameInput.type = 'text';
                nameInput.name = 'guestNames[]';
                nameInput.id = `guestName${i}`;
                nameInput.required = true;
                nameInput.placeholder = 'Enter full name';
                nameGroup.appendChild(nameLabel);
                nameGroup.appendChild(nameInput);
                nameRow.appendChild(nameGroup);
                guestCard.appendChild(nameRow);
                const typeRow = document.createElement('div');
                typeRow.className = 'guest-row';
                const typeGroup = document.createElement('div');
                typeGroup.className = 'form-group';
                const typeLabel = document.createElement('label');
                typeLabel.htmlFor = `guestType${i}`;
                typeLabel.textContent = 'Guest Type';
                const typeSelect = document.createElement('select');
                typeSelect.name = 'guestTypes[]';
                typeSelect.id = `guestType${i}`;
                typeSelect.required = true;
                typeSelect.addEventListener('change', calculateTotal);
                const options = [
                    {value: 'baby', text: 'Baby (0-3 years old) - Free'},
                    {value: 'regular', text: 'Regular (4-59 years old) - ₱100'},
                    {value: 'senior', text: 'Senior/PWD (60+ years old) - Free'}
                ];
                options.forEach(option => {
                    const optionElement = document.createElement('option');
                    optionElement.value = option.value;
                    optionElement.textContent = option.text;
                    typeSelect.appendChild(optionElement);
                });
                typeGroup.appendChild(typeLabel);
                typeGroup.appendChild(typeSelect);
                typeRow.appendChild(typeGroup);
                guestCard.appendChild(typeRow);
                container.appendChild(guestCard);
            }
            guestFields.appendChild(container);
            calculateTotal();
        }

        function calculateTotal() {
            const roomPrice = parseFloat(document.getElementById('roomPrice').value) || 0;
            const guestCount = parseInt(document.getElementById('guestCount').value, 10) || 0;
            let extraGuestsCharge = 0;
            for (let i = 1; i <= guestCount; i++) {
                const typeSelect = document.getElementById(`guestType${i}`);
                if (typeSelect && typeSelect.value === 'regular') {
                    extraGuestsCharge += 100;
                }
            }
            const total = Math.round((roomPrice + extraGuestsCharge) * 100) / 100;
            document.getElementById('totalPriceDisplay').value = `₱${total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            document.getElementById('totalPrice').value = total;
        }

        // Mobile Navigation Toggle
        const menuToggle = document.getElementById('menuToggle');
        const navLinks = document.getElementById('navLinks');
        menuToggle.addEventListener('click', () => navLinks.classList.toggle('open'));
        navLinks.querySelectorAll('.link a').forEach(link => link.addEventListener('click', () => navLinks.classList.remove('open')));
        window.addEventListener('click', e => {
            document.querySelectorAll('.dropdown').forEach(dropdown => {
                if (!dropdown.contains(e.target)) {
                    dropdown.querySelector('.dropdown-content').style.display = 'none';
                }
            });
        });
        document.querySelectorAll('.dropbtn').forEach(button => {
            button.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    const dropdownContent = this.nextElementSibling;
                    dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
                }
            });
        });

        checkSubmitConditions();
    </script>
</body>
</html>