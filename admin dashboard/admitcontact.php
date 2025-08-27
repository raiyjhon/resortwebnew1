<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contact Us | DentoReserve</title>
  <!-- Google Fonts (Poppins for nav) -->
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet"
  />
  <!-- Remixicon (optional) -->
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
    }

    .link a {
      font-weight: 500;
      color: var(--text-light);
      transition: color 0.3s;
      text-decoration: none; /* No underline */
    }

    .link a:hover {
      color: var(--primary-color);
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
    }

    .contact-header p {
      max-width: 700px;
      margin: 0 auto 40px;
      font-size: 16px;
      color: #000000;
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
  </style>
</head>
<body>
  <!-- Navigation Bar -->
  <nav>
    <div class="nav__logo">DentoReserve</div>
    <ul class="nav__links">
      <li class="link"><a href="index.php">Home</a></li>
      <li class="link"><a href="book.php">Book</a></li>
      <li class="link"><a href="notevent.php">Event</a></li>
      <li class="link"><a href="notcontact.php">Contact Us</a></li>
    </ul>
  </nav>

  <!-- Contact Page Content -->
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
      <!-- Left: Contact Info -->
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

      <!-- Right: Contact Form -->
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
</body>
</html>
