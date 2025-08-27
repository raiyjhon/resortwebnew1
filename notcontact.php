<?php
session_start();
include 'db.php'; // Your database connection file

// Check if user is logged in
$user_id = $_SESSION['user_id'] ?? null;
$fullname = '';
if ($user_id) {
    if ($stmt = $conn->prepare("SELECT fullname, email FROM users WHERE id = ?")) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($fullname, $email);
        $stmt->fetch();
        $stmt->close();
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
    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background: url('images/city-bg.jpg') no-repeat center center fixed;
      background-size: cover;
      color: white;
      padding-top: 100px; /* Space for fixed navbar */
    }

    /* ====== Nav Styles ====== */
    :root {
      --primary-color: #2c3855;
      --text-light: #767268;
      --text-dark: #333333;
      --max-width: 1200px;
    }

    nav {
      max-width: var(--max-width);
      margin: auto;
      padding: 1.5rem 1rem; /* Adjusted padding to be consistent */
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: rgba(255, 255, 255, 0.9);
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1000;
      height: 90px; /* Consistent fixed height */
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
      transition: color 0.3s;
      text-decoration: none; /* No underline */
      position: relative; /* For the underline effect */
      padding: 0.5rem 0; /* For the underline effect */
    }

    .link a:hover {
      color: var(--primary-color);
    }

    /* Underline effect for links */
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

      .nav__links .link a,
      .nav__links .link .dropbtn { /* Apply block style to dropbtn as well */
        display: block; /* Make links block to fill width */
        padding: 0.8rem 0; /* More vertical padding */
        width: 100%;
        color: var(--text-dark); /* Ensure link color is readable */
      }
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
      color: var(--text-light); /* Inherit from link styles */
      font-weight: 500;
      font-size: 1rem;
      font-family: inherit;
      padding: 0.5rem 0;
      text-decoration: none; /* Remove default button underline */
    }

    .dropbtn:hover {
      color: var(--primary-color);
    }

    .dropdown-content {
      display: none;
      position: absolute;
      right: 0; /* Align to the right for desktop */
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

    /* Dropdown links */
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
      color: var(--text-dark);
    }

    .dropdown-content a:hover {
      background-color: var(--extra-light);
      color: var(--primary-color);
    }

    .dropdown-content a:hover i {
      color: var(--primary-color);
    }

    /* Adjust dropdown content for mobile */
    @media (max-width: 768px) {
        .dropdown-content {
            position: static; /* Stack naturally in the column layout */
            width: 100%;
            box-shadow: none; /* Remove box shadow as it's part of the main nav */
            border-radius: 0;
            margin-top: 0;
            padding: 0;
            background-color: transparent; /* Make background transparent */
        }

        .dropdown:hover .dropdown-content {
            display: none; /* Mobile dropdown is controlled by JS, not hover */
        }
        
        /* Ensure dropdown links are properly styled within the mobile menu */
        .nav__links.open .dropdown-content {
            display: block; /* Show when parent .nav__links has 'open' and dropdown is active */
            padding-left: 1rem; /* Indent dropdown items slightly */
        }

        .dropdown-content a {
            padding-left: 2rem; /* Further indent dropdown items */
        }
    }


    /* ====== Contact Page Styles ====== */
    .contact-container {
      padding: 60px 20px;
      min-height: 100vh;
      text-align: center;
    }

    .contact-header h1 {
      font-size: 36px;
      margin-bottom: 10px;
       color: #333333;
    }

    .contact-header p {
      max-width: 700px;
      margin: 0 auto 40px;
      font-size: 16px;
      color: #333333;; /* Lighten text for better contrast */
    }

    .contact-box {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      flex-wrap: wrap;
      max-width: 1000px;
      margin: 0 auto;
      gap: 50px;
      text-align: left;
    }

    /* ====== Info Cards ====== */
    .contact-info {
      flex: 1;
      min-width: 300px; /* Allow cards to wrap */
    }

    .info-block {
      background: rgba(255, 255, 255, 0.95);
      color: #333;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 20px;
    }

    .icon {
      font-size: 28px;
      width: 50px;
      height: 50px;
      background-color: #00bcd4;
      color: white;
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      flex-shrink: 0; /* Prevent icon from shrinking */
    }

    .info-title {
      color: #2c3855;
      margin: 0;
      font-weight: bold;
    }

    /* ====== Form Card ====== */
    .contact-form {
      flex: 1;
      min-width: 300px;
      background: rgba(255, 255, 255, 0.95);
      color: #333;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .contact-form h3 {
      margin-bottom: 20px;
      font-size: 22px;
      color: #2c3855;
    }

    .contact-form label {
      display: block;
      margin-bottom: 5px;
      color: #e91e63;
      font-size: 14px;
    }

    .contact-form input,
    .contact-form textarea {
      width: 100%;
      padding: 10px;
      margin-bottom: 20px;
      border: none;
      border-bottom: 2px solid #ccc;
      background: transparent;
      outline: none;
      color: #333;
    }

    .contact-form button {
      background: #00bcd4;
      color: white;
      padding: 12px 30px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 16px;
    }

    .contact-form button:hover {
      background: #0097a7;
    }

    /* Facebook link no underline */
    .info-block a {
      color: #2c3855;
      text-decoration: none;
    }

    .info-block a:hover {
      text-decoration: none;
      color: #00bcd4;
    }

    /* Responsive adjustments for overall layout */
    @media (max-width: 900px) {
        .contact-box {
            flex-direction: column;
            align-items: center;
        }
        .contact-info, .contact-form {
            width: 100%;
            max-width: 500px; /* Constrain width on smaller screens */
        }
    }

    @media (max-width: 600px) {
      body {
        padding-top: 90px; /* Adjust padding-top for smaller nav on mobile */
      }
      .contact-header h1 {
        font-size: 28px;
      }
      .contact-header p {
        font-size: 14px;
      }
      .info-block {
        flex-direction: column;
        text-align: center;
      }
      .icon {
        margin-bottom: 10px;
      }
    }

 
    

    .btn:hover {
        background-color: var(--primary-color-dark);
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
          <div class="icon">📍</div>
          <div>
            <h4 class="info-title">Address</h4>
            <p>Estrella, Rizal, Central Luzon, Philippines</p>
          </div>
        </div>
        <div class="info-block">
          <div class="icon">📞</div>
          <div>
            <h4 class="info-title">Phone</h4>
            <p>0939 977 1125</p>
          </div>
        </div>
        <div class="info-block">
          <div class="icon">✉️</div>
          <div>
            <h4 class="info-title">Email</h4>
            <p>Dentofarmresort@gmail.com</p>
          </div>
        </div>
        <div class="info-block">
          <div class="icon">📘</div>
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
        <h3>Send Message</h3>
        <form>
          <label for="name">Full Name</label>
          <input type="text" id="name" name="name" required />

          <label for="email">Email</label>
          <input type="email" id="email" name="email" required />

          <label for="message">Type your Message...</label>
          <textarea id="message" name="message" rows="4" required></textarea>

          <button type="submit">Send</button>
        </form>
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
            // Only close if it's a direct link, not if it's the dropdown button
            if (!item.classList.contains('dropbtn')) {
                navbarNav.classList.remove('open');
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
    });
  </script>
</body>
</html>