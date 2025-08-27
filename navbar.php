
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
    <a href="settings.php"><i class="ri-settings-line"></i> Settings</a>
    <a href="booking-history.php"><i class="ri-history-line"></i> Booking History</a>
    <a href="logout.php"><i class="ri-logout-box-line"></i> Logout</a>
  </div>
</li>