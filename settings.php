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

// Handle profile update (ONLY for fullname)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_fullname = trim($_POST['fullname']);
    
    if (!empty($new_fullname)) {
        // Prepare statement to update ONLY the fullname
        $stmt = $conn->prepare("UPDATE users SET fullname = ? WHERE id = ?");
        $stmt->bind_param("si", $new_fullname, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['fullname'] = $new_fullname;
            $fullname = $new_fullname;
            $success = "Profile updated successfully!";
        } else {
            $error = "Error updating profile: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Full name is required!";
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
    /* --- Global Styles --- */
    :root {
      --primary-color: #4a90e2;
      --primary-hover-color: #357abd;
      --text-color: #333;
      --background-color: #f4f7f9;
      --container-background: #ffffff;
      --border-color: #e0e0e0;
      --error-color: #e74c3c;
      --success-color: #2ecc71;
      --input-background: #fdfdfd;
      --static-field-bg: #f0f0f0;
      --navbar-background: #ffffff;
      --navbar-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .dark-mode {
      --text-color: #f1f1f1;
      --background-color: #1a1a1a;
      --container-background: #2c2c2c;
      --border-color: #444;
      --input-background: #333;
      --static-field-bg: #3a3a3a;
      --navbar-background: #252525;
      --navbar-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      background-color: var(--background-color);
      color: var(--text-color);
      line-height: 1.6;
      transition: background-color 0.3s, color 0.3s;
    }

    /* --- Navigation Bar --- */
    .navbar {
      background-color: var(--navbar-background);
      box-shadow: var(--navbar-shadow);
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 1000;
      transition: background-color 0.3s;
    }

    .navbar-brand {
      font-size: 1.5rem;
      font-weight: 600;
      color: var(--primary-color);
      text-decoration: none;
      display: flex;
      align-items: center;
    }

    .navbar-brand i {
      margin-right: 0.5rem;
    }

    .navbar-nav {
      display: flex;
      align-items: center;
      list-style: none;
    }

    .nav-link {
      color: var(--text-color);
      text-decoration: none;
      padding: 0.5rem 1rem;
      display: flex;
      align-items: center;
      transition: color 0.2s;
    }

    .nav-link:hover {
      color: var(--primary-color);
    }

    .nav-link i {
      margin-right: 0.5rem;
    }

    /* User Avatar & Dropdown */
    .user-avatar {
      width: 36px;
      height: 36px;
      background-color: var(--primary-color);
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      margin-right: 0.75rem;
    }

    .dropdown {
      position: relative;
    }

    .dropdown-toggle {
      cursor: pointer;
    }

    .dropdown-menu {
      position: absolute;
      top: 130%;
      right: 0;
      background-color: var(--container-background);
      border: 1px solid var(--border-color);
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      list-style: none;
      padding: 0.5rem 0;
      min-width: 180px;
      opacity: 0;
      visibility: hidden;
      transform: translateY(10px);
      transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s;
    }

    .dropdown:hover .dropdown-menu {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    .dropdown-item {
      display: flex;
      align-items: center;
      padding: 0.75rem 1.25rem;
      color: var(--text-color);
      text-decoration: none;
      font-size: 0.95rem;
    }

    .dropdown-item i {
      margin-right: 0.75rem;
      width: 20px;
      text-align: center;
    }

    .dropdown-item:hover {
      background-color: var(--background-color);
      color: var(--primary-color);
    }

    .dropdown-divider {
      height: 1px;
      background-color: var(--border-color);
      margin: 0.5rem 0;
    }

    /* Mobile Menu */
    .menu-toggle {
      display: none;
      font-size: 1.5rem;
      cursor: pointer;
    }

    /* --- Settings Container --- */
    .settings-container {
      max-width: 800px;
      margin: 2rem auto;
      padding: 2rem;
      background-color: var(--container-background);
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      transition: background-color 0.3s;
    }

    h1 {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 1.5rem;
      color: var(--text-color);
    }

    h2 {
      font-size: 1.5rem;
      font-weight: 600;
      margin-top: 2.5rem;
      margin-bottom: 1.5rem;
      padding-bottom: 0.75rem;
      border-bottom: 1px solid var(--border-color);
    }

    /* --- Forms --- */
    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-group label {
      display: block;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }

    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="password"] {
      width: 100%;
      padding: 0.85rem 1rem;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      background-color: var(--input-background);
      color: var(--text-color);
      font-size: 1rem;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    
    .form-group .static-field {
      width: 100%;
      padding: 0.85rem 1rem;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      background-color: var(--static-field-bg);
      color: var(--text-color);
      font-size: 1rem;
      cursor: not-allowed;
    }
    
    .form-group small {
        display: block;
        margin-top: 0.5rem;
        color: #888;
    }
    .dark-mode .form-group small {
        color: #aaa;
    }

    .form-group input:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.25);
    }

    /* --- Buttons --- */
    .btn {
      background-color: var(--primary-color);
      color: #fff;
      border: none;
      padding: 0.85rem 1.75rem;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.2s, transform 0.2s;
    }

    .btn:hover {
      background-color: var(--primary-hover-color);
      transform: translateY(-2px);
    }

    /* --- Alerts --- */
    .alert {
      padding: 1rem;
      margin-bottom: 1.5rem;
      border-radius: 8px;
      font-weight: 500;
    }

    .alert-error {
      background-color: #fbebee;
      color: var(--error-color);
      border: 1px solid var(--error-color);
    }

    .alert-success {
      background-color: #f0f9f4;
      color: var(--success-color);
      border: 1px solid var(--success-color);
    }

    /* --- Responsive Design --- */
    @media (max-width: 768px) {
      .navbar {
        padding: 1rem;
      }
      
      .menu-toggle {
        display: block;
      }
      
      .navbar-nav {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background-color: var(--navbar-background);
        flex-direction: column;
        align-items: flex-start;
        padding: 1rem 0;
        box-shadow: var(--navbar-shadow);
      }
      
      .navbar-nav.active {
        display: flex;
      }
      
      .nav-link {
        width: 100%;
        padding: 1rem 1.5rem;
      }
      
      .dropdown {
        width: 100%;
      }

      .dropdown-menu {
        position: static;
        border: none;
        box-shadow: none;
        width: 100%;
        display: none;
        padding-left: 2rem;
      }
      
      .dropdown.active .dropdown-menu {
        display: block;
      }

      .settings-container {
        margin: 1rem;
        padding: 1.5rem;
      }

      h1 {
        font-size: 1.75rem;
      }
      
      h2 {
        font-size: 1.3rem;
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
          <label>Email Address</label>
          <div class="static-field"><?php echo htmlspecialchars($email); ?></div>
          <small>Your email address cannot be changed.</small>
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