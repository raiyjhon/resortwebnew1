<?php
session_start();
session_destroy();

// Redirect to homepage with a logout flag
header("Location: index.php?logout=success");
exit;
