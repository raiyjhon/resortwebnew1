<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $conn->prepare("SELECT fullname, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($fullname, $email);
$stmt->fetch();
$stmt->close();

$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_fullname = trim($_POST['fullname']);
    $new_email = trim($_POST['email']);
    
    if (!empty($new_fullname) && !empty($new_email)) {
        $stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $new_fullname, $new_email, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['fullname'] = $new_fullname;
            $fullname = $new_fullname;
            $email = $new_email;
            $success = "Profile updated successfully!";
        } else {
            $error = "Error updating profile: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "All fields are required!";
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();
    $stmt->close();
    
    if (password_verify($current_password, $hashed_password)) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 8) {
                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $new_hashed_password, $user_id);
                
                if ($stmt->execute()) {
                    $success = "Password changed successfully!";
                } else {
                    $error = "Error changing password: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "Password must be at least 8 characters long!";
            }
        } else {
            $error = "New passwords don't match!";
        }
    } else {
        $error = "Current password is incorrect!";
    }
}

// Handle dark mode preference
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_dark_mode'])) {
    $dark_mode = isset($_POST['dark_mode']) ? 1 : 0;
    $_SESSION['dark_mode'] = $dark_mode;
    
    // Optional: Save to database for persistence across sessions
    $stmt = $conn->prepare("UPDATE users SET dark_mode = ? WHERE id = ?");
    $stmt->bind_param("ii", $dark_mode, $user_id);
    $stmt->execute();
    $stmt->close();
    
    $success = "Display preferences updated!";
}

// Check dark mode preference
$dark_mode = isset($_SESSION['dark_mode']) ? $_SESSION['dark_mode'] : 0;
?>

<!DOCTYPE html>
<html lang="en" class="<?php echo $dark_mode ? 'dark-mode' : ''; ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account Settings</title>
  <link href="https://cdn.jsdelivr.net/npm/remixicon@3.4.0/fonts/remixicon.css" rel="stylesheet">
  <style>
    :root {
      --bg-color: #ffffff;
      --text-color: #333333;
      --primary-color: #8a68da;
      --secondary-color: #f5f5f5;
      --card-bg: #ffffff;
      --border-color: #e0e0e0;
      --nav-bg: #ffffff;
      --nav-text: #333333;
      --nav-hover: #f0f0f0;
    }
    
    .dark-mode {
      --bg-color: #121212;
      --text-color: #f5f5f5;
      --primary-color: #9a7ae6;
      --secondary-color: #1e1e1e;
      --card-bg: #1e1e1e;
      --border-color: #333333;
      --nav-bg: #1e1e1e;
      --nav-text: #f5f5f5;
      --nav-hover: #333333;
    }
    
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: var(--bg-color);
      color: var(--text-color);
      margin: 0;
      padding: 0;
      transition: all 0.3s ease;
    }
    
    /* Navbar Styles */
    .navbar {
      background-color: var(--nav-bg);
      color: var(--nav-text);
      padding: 1rem 2rem;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 1000;
    }
    
    .navbar-brand {
      font-size: 1.5rem;
      font-weight: bold;
      color: var(--primary-color);
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .navbar-nav {
      display: flex;
      align-items: center;
      gap: 1.5rem;
    }
    
    .nav-item {
      position: relative;
    }
    
    .nav-link {
      color: var(--nav-text);
      text-decoration: none;
      padding: 0.5rem 0.75rem;
      border-radius: 4px;
      transition: background-color 0.3s ease;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .nav-link:hover {
      background-color: var(--nav-hover);
    }
    
    .dropdown {
      position: relative;
    }
    
    .dropdown-toggle::after {
      content: "▼";
      font-size: 0.6rem;
      margin-left: 0.5rem;
    }
    
    .dropdown-menu {
      position: absolute;
      right: 0;
      top: 100%;
      background-color: var(--nav-bg);
      border-radius: 6px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      min-width: 200px;
      padding: 0.5rem 0;
      opacity: 0;
      visibility: hidden;
      transform: translateY(10px);
      transition: all 0.3s ease;
      z-index: 1001;
    }
    
    .dropdown:hover .dropdown-menu {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }
    
    .dropdown-item {
      display: block;
      padding: 0.75rem 1.5rem;
      color: var(--nav-text);
      text-decoration: none;
      transition: background-color 0.3s ease;
    }
    
    .dropdown-item:hover {
      background-color: var(--nav-hover);
    }
    
    .dropdown-divider {
      height: 1px;
      background-color: var(--border-color);
      margin: 0.5rem 0;
    }
    
    .user-avatar {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      object-fit: cover;
      background-color: var(--primary-color);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 0.875rem;
    }
    
    /* Mobile menu toggle */
    .menu-toggle {
      display: none;
      cursor: pointer;
      font-size: 1.5rem;
    }
    
    /* Settings Container */
    .settings-container {
      max-width: 1000px;
      margin: 2rem auto;
      padding: 2rem;
      background-color: var(--card-bg);
      border-radius: 10px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    h1, h2, h3 {
      color: var(--primary-color);
    }
    
    .section {
      margin-bottom: 2rem;
      padding-bottom: 2rem;
      border-bottom: 1px solid var(--border-color);
    }
    
    .form-group {
      margin-bottom: 1.5rem;
    }
    
    label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
    }
    
    input[type="text"],
    input[type="email"],
    input[type="password"] {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      background-color: var(--secondary-color);
      color: var(--text-color);
      font-size: 1rem;
    }
    
    .btn {
      padding: 0.75rem 1.5rem;
      background-color: var(--primary-color);
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 1rem;
      transition: background-color 0.3s ease;
    }
    
    .btn:hover {
      background-color: #7a52d0;
    }
    
    .alert {
      padding: 1rem;
      border-radius: 6px;
      margin-bottom: 1rem;
    }
    
    .alert-success {
      background-color: #d4edda;
      color: #155724;
    }
    
    .alert-error {
      background-color: #f8d7da;
      color: #721c24;
    }
    
    .toggle-switch {
      position: relative;
      display: inline-block;
      width: 60px;
      height: 34px;
    }
    
    .toggle-switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }
    
    .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      transition: .4s;
      border-radius: 34px;
    }
    
    .slider:before {
      position: absolute;
      content: "";
      height: 26px;
      width: 26px;
      left: 4px;
      bottom: 4px;
      background-color: white;
      transition: .4s;
      border-radius: 50%;
    }
    
    input:checked + .slider {
      background-color: var(--primary-color);
    }
    
    input:checked + .slider:before {
      transform: translateX(26px);
    }
    
    .toggle-label {
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    
    /* Responsive styles */
    @media (max-width: 768px) {
      .navbar-nav {
        position: fixed;
        top: 70px;
        left: -100%;
        width: 80%;
        height: calc(100vh - 70px);
        background-color: var(--nav-bg);
        flex-direction: column;
        align-items: flex-start;
        padding: 2rem;
        transition: left 0.3s ease;
      }
      
      .navbar-nav.active {
        left: 0;
      }
      
      .menu-toggle {
        display: block;
      }
      
      .dropdown-menu {
        position: static;
        box-shadow: none;
        opacity: 1;
        visibility: visible;
        transform: none;
        display: none;
        padding-left: 1rem;
      }
      
      .dropdown:hover .dropdown-menu,
      .dropdown.active .dropdown-menu {
        display: block;
      }
      
      .settings-container {
        padding: 1rem;
        margin: 1rem;
      }
    }
  </style>
</head>
<body>
  <nav class="navbar">
    <a href="index.php" class="navbar-brand">
      <i class="ri-home-4-line"></i> Setting
    </a>
    
    <div class="menu-toggle" id="mobile-menu">
      <i class="ri-menu-line"></i>
    </div>
    
    <div class="navbar-nav" id="navbar-nav">
      <a href="dashboardcustomer.php" class="nav-link">
        <i class="ri-home-4-line"></i> Home
      </a>

      
      <div class="nav-item dropdown">
        <a href="#" class="nav-link dropdown-toggle">
          <div class="user-avatar"><?php echo strtoupper(substr($fullname, 0, 1)); ?></div>
          <?php echo htmlspecialchars($fullname); ?>
        </a>
        <div class="dropdown-menu">
          <a href="profile.php" class="dropdown-item">
            <i class="ri-user-line"></i> Profile
          </a>

          <div class="dropdown-divider"></div>
          <a href="logout.php" class="dropdown-item">
            <i class="ri-logout-box-line"></i> Logout
          </a>
        </div>
      </div>
    </div>
  </nav>
  
  <div class="settings-container">
    <h1>Account Settings</h1>
    
    <?php if ($error): ?>
      <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
      <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="section">
      <h2>Profile Information</h2>
      <form method="POST">
        <input type="hidden" name="update_profile" value="1">
        
        <div class="form-group">
          <label for="fullname">Full Name</label>
          <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($fullname); ?>" required>
        </div>
        
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>
        
        <button type="submit" class="btn">Update Profile</button>
      </form>
    </div>
    
    <div class="section">
      <h2>Change Password</h2>
      <form method="POST">
        <input type="hidden" name="change_password" value="1">
        
        <div class="form-group">
          <label for="current_password">Current Password</label>
          <input type="password" id="current_password" name="current_password" required>
        </div>
        
        <div class="form-group">
          <label for="new_password">New Password</label>
          <input type="password" id="new_password" name="new_password" required>
        </div>
        
        <div class="form-group">
          <label for="confirm_password">Confirm New Password</label>
          <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        
        <button type="submit" class="btn">Change Password</button>
      </form>
    </div>
    

  <script>
    // Mobile menu toggle
    document.getElementById('mobile-menu').addEventListener('click', function() {
      document.getElementById('navbar-nav').classList.toggle('active');
    });
    
    // Handle dropdowns on mobile
    const dropdowns = document.querySelectorAll('.dropdown');
    
    dropdowns.forEach(dropdown => {
      dropdown.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
          e.preventDefault();
          this.classList.toggle('active');
        }
      });
    });
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(e) {
      const navbarNav = document.getElementById('navbar-nav');
      const mobileMenu = document.getElementById('mobile-menu');
      
      if (window.innerWidth <= 768 && 
          !navbarNav.contains(e.target) && 
          !mobileMenu.contains(e.target)) {
        navbarNav.classList.remove('active');
      }
    });
  </script>
</body>
</html>