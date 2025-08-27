<?php
session_start();
include 'db.php'; // Your DB connection

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user fullname
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
    die("DB error: " . $conn->error);
}

// Get rooms
$sql = "SELECT * FROM rooms"; // Your rooms table
$result = $conn->query($sql);
if (!$result) {
    die("Error fetching rooms: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>DentoFarm & Resort</title>
  <link href="https://cdn.jsdelivr.net/npm/remixicon@3.4.0/fonts/remixicon.css" rel="stylesheet" />
  <link rel="stylesheet" href="book.css" />
  <style>
    /* Add your CSS here (or keep it in book.css) */
    /* For brevity, not repeating your full CSS */
    body { font-family: Arial, sans-serif; background:#eee; margin:0; }
    nav { background:#008080; color:#fff; padding:10px; }
    nav .nav__logo { font-weight:bold; font-size:1.5em; }
    nav ul { list-style:none; padding:0; margin:0; display:flex; gap:15px; }
    nav ul li a { color:#fff; text-decoration:none; }
    .section__header { text-align:center; margin:20px 0; }
    .popular__grid { display:flex; flex-wrap:wrap; gap:20px; justify-content:center; }
    .card { background:#fff; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1); width:300px; overflow:hidden; }
    .card img { width:100%; height:180px; object-fit:cover; }
    .card-body { padding:15px; }
    .book__btn { background:#008080; color:#fff; border:none; padding:10px 15px; border-radius:5px; cursor:pointer; }
    .book__btn:hover { background:#006666; }
    /* Modal styles */
    .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; }
    .modal-content { background:#fff; padding:20px; border-radius:8px; width:90%; max-width:500px; position:relative; }
    .close-btn { position:absolute; top:10px; right:15px; font-size:24px; cursor:pointer; }
    .form-group { margin-bottom:15px; }
    label { display:block; margin-bottom:5px; font-weight:bold; }
    input, select { width:100%; padding:8px; border-radius:4px; border:1px solid #ccc; }
  </style>
</head>
<body>

<nav>
  <div class="nav__logo">DentoReserve</div>
  <ul class="nav__links">
    <li><a href="dashboardcustomer.php">Home</a></li>
    <li><a href="Booklogin.php">Book</a></li>
    <li><a href="Event.php">Event</a></li>
    <li><a href="contact.html">Contact Us</a></li>
    <li><a href="view_bookings.php">View</a></li>
    <li class="dropdown">
      <a href="#"><?php echo htmlspecialchars($fullname); ?> <i class="ri-arrow-down-s-line"></i></a>
      <div class="dropdown-content">
        <a href="profile.php">My Profile</a>
        <a href="logout.php">Logout</a>
      </div>
    </li>
  </ul>
</nav>

<section class="section__container popular__container">
  <h2 class="section__header">BOOK HERE</h2>
  <div class="popular__grid">

    <?php if ($result->num_rows > 0): ?>
      <?php while ($room = $result->fetch_assoc()): ?>
        <div class="card">
          <img src="uploads/<?php echo htmlspecialchars($room['image']); ?>" alt="<?php echo htmlspecialchars($room['name']); ?>" />
          <div class="card-body">
            <h5><?php echo htmlspecialchars($room['name']); ?></h5>
            <p><strong>Type:</strong> <?php echo htmlspecialchars($room['type']); ?></p>
            <p><strong>Price:</strong> ₱<?php echo number_format($room['price'], 2); ?></p>
            <p><?php echo nl2br(htmlspecialchars($room['description'])); ?></p>
            <button class="book__btn" onclick="openBookingModal('<?php echo addslashes($room['name']); ?>', '<?php echo number_format($room['price'], 2); ?>')">Reserve Now</button>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p>No rooms found.</p>
    <?php endif; ?>

  </div>
</section>

<!-- Booking Modal -->
<div id="bookingModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeBookingModal()">&times;</span>
    <h2>Book Your Reservation</h2>
    <p id="roomInfo" style="font-weight:bold;"></p>
    <form id="bookingForm" method="POST" action="submit_booking.php">
      <div class="form-group">
        <label for="guestName">Name:</label>
        <input type="text" id="guestName" name="guestName" value="<?php echo htmlspecialchars($fullname); ?>" required />
      </div>
      <div class="form-group">
        <label for="checkinDate">Date of Check-in:</label>
        <input type="date" id="checkinDate" name="checkinDate" required />
      </div>
      <div class="form-group">
        <label for="checkinTime">Time:</label>
        <input type="time" id="checkinTime" name="checkinTime" required />
      </div>
      <div class="form-group">
        <label for="stayDuration">Duration:</label>
        <select id="stayDuration" name="stayDuration" required>
          <option value="" disabled selected>Select Duration</option>
          <option value="6">Half Day (6 hours)</option>
          <option value="12">Whole Day (12 hours)</option>
        </select>
      </div>
      <div class="form-group">
        <label for="guestCount">Number of Guests:</label>
        <input type="number" id="guestCount" name="guestCount" min="1" max="50" required oninput="generateGuestFields()" />
      </div>

      <div id="guestFields"></div>

      <input type="hidden" id="roomName" name="roomName" />
      <input type="hidden" id="roomPrice" name="roomPrice" />
      <button type="submit" class="book__btn">Submit Booking</button>
    </form>
  </div>
</div>

<script>
  // Disable past dates for check-in
  const checkinInput = document.getElementById('checkinDate');
  const today = new Date().toISOString().split('T')[0];
  checkinInput.setAttribute('min', today);

  function generateGuestFields() {
    const count = document.getElementById('guestCount').value;
    const guestFields = document.getElementById('guestFields');
    guestFields.innerHTML = '';

    for (let i = 1; i <= count; i++) {
      const label = document.createElement('label');
      label.textContent = `Guest ${i} Name:`;
      const input = document.createElement('input');
      input.type = 'text';
      input.name = `guest_${i}`;
      input.required = true;
      guestFields.appendChild(label);
      guestFields.appendChild(input);
    }
  }

  function openBookingModal(roomName, roomPrice) {
    document.getElementById('roomInfo').innerText = roomName + " - ₱" + roomPrice;
    document.getElementById('roomName').value = roomName;
    document.getElementById('roomPrice').value = roomPrice;
    document.getElementById('bookingModal').style.display = 'flex';
  }

  function closeBookingModal() {
    document.getElementById('bookingModal').style.display = 'none';
  }

  window.onclick = function(event) {
    const modal = document.getElementById('bookingModal');
    if (event.target == modal) {
      closeBookingModal();
    }
  };
</script>

</body>
</html>
