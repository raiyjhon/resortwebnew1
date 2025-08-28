<?php
session_start();
include 'db.php'; // Your database connection file

// Check if user is logged in
$user_id = $_SESSION['user_id'] ?? null;
$fullname = '';
if ($user_id) {
    if ($stmt = $conn->prepare("SELECT fullname FROM users WHERE id = ?")) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($fullname);
        $stmt->fetch();
        $stmt->close();
    }
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
      --primary-color:rgb(38, 39, 44);
      --primary-color-dark:rgb(27, 29, 33);
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
      padding-top: 100px; /* To account for fixed nav */
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

    /* --- Navigation Styles --- */
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
      height: 90px; /* Ensure a fixed height for the nav bar */
      box-shadow: 0 2px 10px rgba(0,0,0,0.05); /* Added for better visual */
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
      margin: 0; /* Remove default ul margin */
      padding: 0; /* Remove default ul padding */
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
        top: 90px; /* Position below the fixed navbar height */
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

    /* --- Existing Styles (unmodified unless necessary for nav compatibility) --- */

    /* Hero Section */
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

    /* Content Sections */
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

    /* Grid Layouts */
    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 2rem;
    }

    .card {
      background: var(--extra-light);
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .card__image {
      width: 100%;
      height: 200px;
      object-fit: cover;
    }

    .card__content {
      padding: 1.5rem;
    }

    .card__header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      margin-bottom: 0.5rem;
    }

    .card__header h4 {
      font-size: 1.2rem;
      font-weight: 600;
      color: var(--text-dark);
    }

    .card p {
      color: var(--text-light);
      margin-bottom: 1rem;
      font-size: 0.95rem;
    }

    /* Testimonials */
    .testimonial-card {
      background-color: var(--primary-color);
      color: var(--white);
      padding: 1.5rem;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      transition: transform 0.3s ease;
    }

    .testimonial-card:hover {
      transform: scale(1.02);
    }

    .testimonial-card .icon {
      font-size: 2rem;
      margin-bottom: 1rem;
      display: block;
      color: rgba(255, 255, 255, 0.7);
    }

    .testimonial-card p {
      font-style: italic;
      margin-bottom: 1.5rem;
      line-height: 1.6;
    }

    .testimonial-card .author {
      text-align: right;
      font-weight: 600;
      font-size: 1.1rem;
    }

    /* Gallery */
    .gallery {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 1rem;
    }

    .gallery img {
      width: 100%;
      height: 200px;
      object-fit: cover;
      border-radius: 8px;
      transition: transform 0.3s ease;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .gallery img:hover {
      transform: scale(1.03);
    }

    /* Footer */
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

    /* Responsive Design for overall layout (existing) */
    @media (max-width: 768px) {
      /* .nav__links already handled by new responsive nav */
      
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
      /* .nav styling below 600px is now handled by the new responsive nav, 
         so original flex-direction: column for nav here is removed. */
      
      body {
        padding-top: 90px; /* Adjust padding-top to match new nav height */
      }
      
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

    /* Dropdown styles (unmodified) */
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

    /* Profile header in dropdown */
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

    /* Dropdown links */
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
      <li class="link"><a href="book.php">Book</a></li>
      <li class="link"><a href="notevent.php">Event</a></li>
      <li class="link"><a href="notcontact.php">Contact Us</a></li>
    </ul>
  </nav>

  <div class="section__container">
    <div class="hero">
      <div class="hero__content">
        <h1>Discover What's New events at DentoReserve</h1>
        <p>Experience comfort and unforgettable moments at our resort </p>
      </div>
    </div>

    <div class="section">
      <h2><i class="ri-microphone-line"></i> Featured Performers</h2>
      <div class="grid">
        <?php
        $performers = $conn->query("SELECT * FROM featured_performer ORDER BY id DESC");
        if ($performers && $performers->num_rows > 0) {
          while ($row = $performers->fetch_assoc()) {
            echo "<div class='card'>
                    <img src='uploads/" . htmlspecialchars($row['image']) . "' alt='" . htmlspecialchars($row['name']) . "' class='card__image'>
                    <div class='card__content'>
                      <div class='card__header'>
                        <h4>" . htmlspecialchars($row['name']) . "</h4>
                        <small>" . htmlspecialchars($row['schedule']) . "</small>
                      </div>
                    </div>
                  </div>";
          }
        } else {
          echo "<p>No featured performers at the moment.</p>";
        }
        ?>
      </div>
    </div>

    <div class="section">
      <h2><i class="ri-calendar-event-line"></i> Upcoming Events</h2>
      <div class="grid">
        <?php
        $events = $conn->query("SELECT * FROM upcoming_events ORDER BY id DESC");
        if ($events && $events->num_rows > 0) {
          while ($row = $events->fetch_assoc()) {
            echo "<div class='card'>
                    <div class='card__content'>
                      <div class='card__header'>
                        <h4>" . htmlspecialchars($row['event_title']) . "</h4>
                        <small>Date: " . htmlspecialchars($row['event_date']) . "</small>
                      </div>
                    </div>
                  </div>";
          }
        } else {
          echo "<p>No upcoming events at the moment.</p>";
        }
        ?>
      </div>
    </div>

    <div class="section">
      <h2><i class="ri-user-star-line"></i> Performing Artists</h2>
      <div class="grid">
        <?php
        $artists = $conn->query("SELECT * FROM artists ORDER BY id DESC");
        if ($artists && $artists->num_rows > 0) {
          while ($row = $artists->fetch_assoc()) {
            echo "<div class='card'>
                    <img src='uploads/" . htmlspecialchars($row['image']) . "' alt='" . htmlspecialchars($row['name']) . "' class='card__image'>
                    <div class='card__content'>
                      <h4>" . htmlspecialchars($row['name']) . "</h4>
                      <p>" . nl2br(htmlspecialchars($row['info'])) . "</p>
                    </div>
                  </div>";
          }
        } else {
          echo "<p>No artist profiles available.</p>";
        }
        ?>
      </div>
    </div>

    <div class="section">
      <h2><i class="ri-image-line"></i> Gallery</h2>
      <div class="gallery">
        <?php
        $gallery = $conn->query("SELECT * FROM gallery ORDER BY id DESC");
        if ($gallery && $gallery->num_rows > 0) {
          while ($row = $gallery->fetch_assoc()) {
            echo "<img src='uploads/" . htmlspecialchars($row['image']) . "' alt='Gallery Image'>";
          }
        } else {
          echo "<p>No images in the gallery.</p>";
        }
        ?>
      </div>
    </div>

    <div class="section">
      <h2><i class="ri-star-smile-line"></i> Guest Testimonials</h2>
      <div class="grid">
        <?php
        $testimonials = $conn->query("SELECT * FROM testimonials ORDER BY id DESC");
        if ($testimonials && $testimonials->num_rows > 0) {
          while ($row = $testimonials->fetch_assoc()) {
            echo "<div class='testimonial-card'>
                    <span class='icon'>❝</span>
                    <p>" . nl2br(htmlspecialchars($row['testimonial'])) . "</p>
                    <div class='author'>- " . htmlspecialchars($row['guest_name']) . "</div>
                  </div>";
          }
        } else {
          echo "<p>No testimonials yet.</p>";
        }
        ?>
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

        // Close mobile navigation when a link or dropdown button is clicked
        navbarNav.querySelectorAll('a, button.dropbtn').forEach(item => {
          item.addEventListener('click', () => {
            // For dropdowns, let the dropdown itself handle its state
            // For simple links, close the nav
            if (!item.classList.contains('dropbtn')) {
                navbarNav.classList.remove('open');
            } else {
                // If it's the dropdown button, we might want to prevent immediate close
                // or ensure it doesn't interfere with the dropdown's own toggle.
                // For now, let it be, the dropdown's hover/click will manage it.
            }
          });
        });

        // Close nav if a click occurs outside of it
        document.addEventListener('click', function(event) {
            const isClickInsideNav = navbarNav.contains(event.target) || menuToggle.contains(event.target);
            if (!isClickInsideNav && navbarNav.classList.contains('open')) {
                navbarNav.classList.remove('open');
            }
        });
      }

      // Placeholder for openLoginModal if it's used elsewhere (not defined in this snippet)
      // If you have a login modal, define openLoginModal() and closeLoginModal()
      // For this particular page, the Login button is only shown if the user is NOT logged in.
      // If a login modal is needed on this page, ensure its HTML and JS are also included.
      function openLoginModal() {
        alert('Login functionality would open here!'); // Replace with actual modal opening
      }
      window.openLoginModal = openLoginModal; // Make it globally accessible if called by onclick attribute
    });
  </script>
</body>
</html>