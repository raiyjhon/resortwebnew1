<?php
$host = "	sql312.infinityfree.com";
$username = "if0_39801700";
$password = "Q7GtTyfOS5f";
$database = "if0_39801700_Dentofarm";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>
