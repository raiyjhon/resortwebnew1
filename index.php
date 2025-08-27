<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.4.0/fonts/remixicon.css" rel="stylesheet" />
    <link rel="stylesheet" href="styles.css" />
    <title>DentoFarm&Resort</title>
    <style>
      /* ====== Global Variables and Base Styles (kept from your original) ====== */
      :root {
          --primary-color: #2c3855; /* Assuming you have this or similar in styles.css */
          --text-dark: #333333; /* Assuming you have this or similar in styles.css */
          --text-light: #767268; /* Assuming you have this or similar in styles.css */
          --max-width: 1200px; /* Assuming you have this or similar in styles.css */
      }

      body {
        padding-top: 80px; /* Added to prevent navbar overlap */
      }
      
      /* ====== Responsive Navigation Styles Only ====== */
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
        height: 80px; /* Added fixed height */
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
      }

      .link a:hover {
        color: var(--primary-color);
      }

      /* Hamburger menu button - hidden by default on large screens */
      .nav__menu_btn {
        font-size: 2rem;
        color: var(--text-dark);
        cursor: pointer;
        display: none; /* Hidden on desktop */
      }

      /* Media query for smaller screens (e.g., tablets and mobile) */
      @media (max-width: 768px) {
        .nav__menu_btn {
          display: block; /* Show hamburger menu */
        }

        .nav__links {
          position: absolute;
          top: 80px; /* Position below the fixed navbar */
          left: 0;
          width: 100%;
          flex-direction: column;
          background-color: rgba(255, 255, 255, 0.98); /* Almost opaque white */
          box-shadow: 0 4px 8px rgba(0,0,0,0.1);
          padding: 1rem 0;
          gap: 0.5rem;
          display: none; /* Hidden by default, toggled by JavaScript */
          align-items: flex-start; /* Align links to the left */
          border-top: 1px solid #eee; /* Separator */
        }

        .nav__links.open {
          display: flex; /* Show when 'open' class is added */
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

      /* Your existing styles below this line (unmodified) */

      /* Modal background */
      .login-modal .modal-content {
        background-color: #fff;
        margin: 4% auto;
        padding: 1.5rem 2.5rem 2rem 2.5rem;
        border-radius: 12px;
        max-width: 400px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        position: relative;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }
      
      /* ====== NEW: Login Error Message Style ====== */
      .login-error-message {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
        padding: 0.75rem 1.25rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        text-align: center;
        font-size: 0.95rem;
      }

      /* Comment section styles */
      .client__container {
        padding: 2rem 0;
      }
      
      .client__grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
      }
      
      .client__card {
        background-color: white;
        padding: 1.5rem;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      }
      
      .commentor-name {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.5rem;
      }
      
      .comment-text {
        color: #555;
        line-height: 1.6;
      }
      
      .like-section {
        margin-top: 1rem;
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
      }
      
      .like-btn i {
        font-size: 1.2rem;
      }
      
      .like-count {
        font-size: 0.9rem;
      }

      /* Rest of your existing styles... */
      .login-modal .close {
        position: absolute;
        top: 15px;
        right: 20px;
        font-size: 24px;
        font-weight: 700;
        color: #555;
        cursor: pointer;
        transition: color 0.3s ease;
      }

      .login-modal .close:hover {
        color: #e74c3c;
      }

      /* Section header */
      .login-modal .section__header {
        margin-bottom: 1.5rem;
        font-size: 1.8rem;
        color: #222;
        text-align: center;
        font-weight: 600;
      }

      /* Form styles */
      .login-modal .login__form {
        display: flex;
        flex-direction: column;
        gap: 1.2rem;
      }

      .login-modal .form__group {
        display: flex;
        flex-direction: column;
      }

      .login-modal label {
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
        color: #444;
        font-weight: 500;
      }

      /* Input fields */
      .login-modal input[type="email"],
      .login-modal input[type="password"],
      .login-modal input[type="text"].captcha-input { /* Added style for captcha input */
        padding: 0.6rem 1rem;
        font-size: 1rem;
        border: 1.8px solid #ccc;
        border-radius: 8px;
        transition: border-color 0.3s ease, box-shadow 0.3s ease; /* Added for smoother transitions */
      }

      .login-modal input[type="email"]:focus,
      .login-modal input[type="password"]:focus {
        border-color: #3498db;
        outline: none;
        box-shadow: 0 0 5px #3498db;
      }
      
      /* Captcha feedback styles */
      .login-modal .captcha-input.correct {
        border-color: #28a745; /* Green for correct */
        box-shadow: 0 0 5px rgba(40, 167, 69, 0.5);
      }

      .login-modal .captcha-input.wrong {
        border-color: #dc3545; /* Red for wrong */
        box-shadow: 0 0 5px rgba(220, 53, 69, 0.5);
      }

      /* Password wrapper for toggle icon */
      .login-modal .password__wrapper {
        position: relative;
        display: flex;
        align-items: center;
      }

      .login-modal .password__wrapper input {
        flex-grow: 1;
      }

      .login-modal #togglePassword {
        position: absolute;
        right: 12px;
        cursor: pointer;
        font-size: 1.2rem;
        color: #888;
        user-select: none;
        transition: color 0.3s ease;
      }

      .login-modal #togglePassword:hover {
        color: #3498db;
      }

      /* Submit button */
      .login-modal .btn {
        background-color: #3498db;
        color: #fff;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.3s ease, opacity 0.3s ease; /* Added opacity for disabled state */
      }

      .login-modal .btn:hover {
        background-color: #2980b9;
      }

      .login-modal .btn:disabled {
        background-color: #cccccc; /* Grey out when disabled */
        cursor: not-allowed;
        opacity: 0.7;
      }

      /* Create account text */
      .login-modal .create__account {
        text-align: center;
        margin-top: 1rem;
        font-size: 0.9rem;
        color: #555;
      }

      .login-modal .create__account a {
        color: #3498db;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
      }

      .login-modal .create__account a:hover {
        color: #2980b9;
      }

      /* Modal Styles */
      .login__container {
        text-align: center;
      }

      .form__group {
        display: flex;
        flex-direction: column;
        text-align: left;
      }

      .form__group label {
        font-weight: 600;
        margin-bottom: 0.5rem;
      }

      .form__group input,
      .form__group textarea {
        padding: 0.75rem;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 1rem;
      }

      .btn {
        padding: 0.75rem;
        background-color: rgb(55, 0, 185);
        color: #fff;
        border: none;
        border-radius: 6px;
        font-weight: bold;
        cursor: pointer;
        transition: background-color 0.2s ease;
      }

      .btn:hover {
        background-color: #187bcd;
      }

      .modal {
        display: none;
        position: fixed;
        z-index: 999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.5);
      }

      /* Captcha specific styles */
      .captcha-group {
        border: 1px dashed #ccc; /* Add a dashed border for visual separation */
        padding: 1rem;
        border-radius: 8px;
        background-color: #f9f9f9;
        margin-top: 1rem;
        display: flex;
        flex-direction: column;
        align-items: center; /* Center the content inside the captcha group */
      }

      .captcha-container {
        display: flex;
        align-items: center;
        justify-content: center; /* Center the question and input */
        gap: 10px;
        margin-bottom: 0.5rem; /* Reduced margin */
        width: 100%; /* Ensure it takes full width of its parent */
      }

      .captcha-text {
        font-size: 1.2rem; /* Slightly larger font */
        font-weight: 700; /* Bolder */
        color: #555; /* Darker text */
        background-color: #e8e8e8; /* Light background for the question */
        padding: 0.5rem 1rem;
        border-radius: 5px;
      }

      .captcha-input {
        width: 100px; /* Adjust width as needed */
        text-align: center;
        font-size: 1.1rem !important; /* Make it slightly larger than other inputs */
        font-weight: 600;
      }

      .captcha-error {
        color: #dc3545; /* Red for error */
        font-size: 0.85rem;
        margin-top: 0.5rem;
        text-align: center;
        display: none; /* Hide by default */
      }
      
      .captcha-success {
        color: #28a745; /* Green for success */
        font-size: 0.85rem;
        margin-top: 0.5rem;
        text-align: center;
        display: none; /* Hide by default */
      }

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

      @media (max-width: 768px) {
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
        <li class="link"><button class="btn" onclick="openLoginModal()">Login</button></li>
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
      <h2 class="section__header">RESERVE HERE</h2>
      <div class="popular__grid">
        <?php
        include 'db.php';
        if (!isset($conn) || !$conn) {
            $conn = mysqli_connect("localhost", "root", "", "dentoreserve");
        }
        if (!$conn) {
            die("Connection failed: " . mysqli_connect_error());
        }

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
            <a href="book.php" class="book__btn">Reserve Now</a>
          </div>
        </div>
        <?php endwhile; else: ?>
          <p>No rooms available at the moment.</p>
        <?php endif; ?>
      </div>
    </section>

    <section class="client">
      <div class="section__container client__container">
        <h2 class="section__header">What our clients say</h2>
        
        <?php
        if (!isset($conn) || !$conn) {
            $conn = mysqli_connect("localhost", "root", "", "dentoreserve");
        }
        if (!$conn) {
            die("Connection failed for comments section: " . mysqli_connect_error());
        }

        $comments_query = "SELECT c.id, c.name, c.comment, c.created_at, 
                                  COUNT(l.id) AS like_count
                                  FROM comments c
                                  LEFT JOIN comment_likes l ON c.id = l.comment_id
                                  GROUP BY c.id
                                  ORDER BY c.created_at DESC
                                  LIMIT 5";
        
        $comments_result = mysqli_query($conn, $comments_query);
        
        if (mysqli_num_rows($comments_result) > 0):
        ?>
          <div class="client__grid">
            <?php while ($comment = mysqli_fetch_assoc($comments_result)): ?>
              <div class="client__card">
                <p class="commentor-name"><?php echo htmlspecialchars($comment['name']); ?></p>
                <p class="comment-text">"<?php echo htmlspecialchars($comment['comment']); ?>"</p>
                <div class="like-section">
                  <span class="like-btn">
                    <i class="ri-heart-fill" style="color: #e74c3c;"></i>
                    <span class="like-count"><?php echo $comment['like_count']; ?></span>
                  </span>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <p style="text-align: center; margin-top: 2rem;">No comments yet. Be the first to leave a review!</p>
        <?php endif; 
        mysqli_close($conn);
        ?>
      </div>
    </section>

    <section class="login-modal">
      <div id="loginModal" class="modal">
        <div class="modal-content login__container">
          <span class="close" onclick="closeLoginModal()">&times;</span>
          <h2 class="section__header">Login</h2>
          <form class="login__form" action="login.php" method="POST" id="loginForm">
            <!-- Container for login error message will be inserted here by JS -->
            <div id="loginErrorContainer"></div>
            <div class="form__group">
              <label for="email">Email</label>
              <input type="email" id="email" name="email" placeholder="Enter your email" required />
            </div>
            <div class="form__group">
              <label for="password">Password</label>
              <div class="password__wrapper">
                <input type="password" id="password" name="password" placeholder="Enter your password" required />
                <span id="togglePassword" class="ri-eye-line"></span>
              </div>
              <div class="forgot__password">
                <a href="forgot_password.php">Forgot Password?</a>
              </div>
            </div>
            
            <div class="form__group captcha-group">
              <label for="captcha">Solve this:</label>
              <div class="captcha-container">
                <span id="captchaQuestion" class="captcha-text"></span>
                <input type="text" id="captchaAnswer" class="captcha-input" placeholder="Your answer" required />
              </div>
              <p id="captchaError" class="captcha-error">Incorrect answer. Please try again.</p>
              <p id="captchaSuccess" class="captcha-success">Correct!</p>
            </div>
            <button type="submit" class="btn" id="loginBtn" disabled>Login</button>
            <div class="create__account">
              <p>Don't have an account? <a href="register-trasion.php">Create an account</a></p>
            </div>
          </form>
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
      document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.getElementById('menuToggle');
        const navbarNav = document.getElementById('navbarNav');

        if (menuToggle && navbarNav) {
          menuToggle.addEventListener('click', function() {
            navbarNav.classList.toggle('open');
          });

          navbarNav.querySelectorAll('a, button.btn').forEach(item => {
            item.addEventListener('click', () => {
              if (item.classList.contains('btn')) {
                setTimeout(() => navbarNav.classList.remove('open'), 100);  
              } else {
                navbarNav.classList.remove('open');
              }
            });
          });
        }

        const loginModal = document.getElementById('loginModal');
        const loginForm = document.getElementById('loginForm');
        const captchaQuestionSpan = document.getElementById('captchaQuestion');
        const captchaAnswerInput = document.getElementById('captchaAnswer');
        const captchaError = document.getElementById('captchaError');
        const captchaSuccess = document.getElementById('captchaSuccess');
        const loginBtn = document.getElementById('loginBtn');
        let correctAnswer;

        function generateCaptcha() {
          const num1 = Math.floor(Math.random() * 10) + 1;
          const num2 = Math.floor(Math.random() * 10) + 1;
          correctAnswer = num1 + num2;
          captchaQuestionSpan.textContent = `${num1} + ${num2} = ?`;
          captchaAnswerInput.value = '';
          captchaAnswerInput.classList.remove('correct', 'wrong');
          captchaError.style.display = 'none';
          captchaSuccess.style.display = 'none';
          loginBtn.disabled = true;
        }

        function openLoginModal() {
          if (loginModal) {
            generateCaptcha();
            loginModal.style.display = 'block';
             // Clear any previous error messages when opening manually
            const errorContainer = document.getElementById('loginErrorContainer');
            errorContainer.innerHTML = '';
          }
        }

        function closeLoginModal() {
          if (loginModal) {
            loginModal.style.display = 'none';
          }
        }

        window.openLoginModal = openLoginModal;
        window.closeLoginModal = closeLoginModal;

        window.onclick = function(event) {
          if (event.target === loginModal) {
            closeLoginModal();
          }
        };

        captchaAnswerInput.addEventListener('input', function() {
          const userAnswer = parseInt(this.value, 10);
          
          captchaError.style.display = 'none';
          captchaSuccess.style.display = 'none';
          this.classList.remove('correct', 'wrong');

          if (this.value === '') {
            loginBtn.disabled = true;
            return;
          }

          if (userAnswer === correctAnswer) {
            this.classList.add('correct');
            captchaSuccess.style.display = 'block';
            loginBtn.disabled = false;
          } else {
            this.classList.add('wrong');
            captchaError.style.display = 'block';
            loginBtn.disabled = true;
          }
        });

        loginForm.addEventListener('submit', function(event) {
          const userAnswer = parseInt(captchaAnswerInput.value, 10);
          if (userAnswer !== correctAnswer || captchaAnswerInput.value === '') {
            event.preventDefault();
            captchaError.style.display = 'block';
            captchaSuccess.style.display = 'none';
            captchaAnswerInput.classList.add('wrong');
            loginBtn.disabled = true;
          }
        });

        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        if (togglePassword && passwordInput) {
          togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('ri-eye-line');
            this.classList.toggle('ri-eye-off-line');
          });
        }
        
        // ====== NEW: Check for login errors on page load ======
        const urlParams = new URLSearchParams(window.location.search);
        const loginError = urlParams.get('error');

        if (loginError) {
            // Get the container where the error will be displayed
            const errorContainer = document.getElementById('loginErrorContainer');

            // Create the error message element
            const errorMessageDiv = document.createElement('div');
            errorMessageDiv.className = 'login-error-message';
            errorMessageDiv.textContent = decodeURIComponent(loginError); // Decode the message from URL

            // Add the error message to the container
            errorContainer.appendChild(errorMessageDiv);

            // Automatically open the login modal to show the error
            openLoginModal();
        }
      });
    </script>
  </body>
</html>