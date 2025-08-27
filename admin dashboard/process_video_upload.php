<?php
session_start();
include 'db.php'; // Your database connection file

// Check if user is logged in and has an ID
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$uploader_id = $_SESSION['user_id'];
$video_description = $_POST['description'] ?? '';

// Check if the video file was uploaded without errors
if (isset($_FILES['video']) && $_FILES['video']['error'] == UPLOAD_ERR_OK) {
    
    $file_tmp_path = $_FILES['video']['tmp_name'];
    $file_name = $_FILES['video']['name'];
    $file_size = $_FILES['video']['size'];
    $file_type = $_FILES['video']['type'];
    
    // Sanitize the file name to prevent directory traversal attacks
    $sanitized_file_name = preg_replace("/[^a-zA-Z0-9\.]/", "_", $file_name);
    
    // Define the upload directory
    $upload_dir = '../videouploads/';
    
    // Create the directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate a unique file name to avoid overwriting existing files
    $unique_file_name = uniqid() . '_' . $sanitized_file_name;
    $dest_path = $upload_dir . $unique_file_name;

    // Move the uploaded file to the destination directory
    if (move_uploaded_file($file_tmp_path, $dest_path)) {
        
        // File upload was successful, now save the info to the database
        $sql = "INSERT INTO videos (video_path, description, uploader_id) VALUES (?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssi", $dest_path, $video_description, $uploader_id);
            
            if ($stmt->execute()) {
                // Success message
                echo '<script>alert("Video uploaded and saved successfully!"); window.location.href = "events.php";</script>';
                exit();
            } else {
                // Database error
                echo '<script>alert("Database error: ' . $stmt->error . '"); window.location.href = "events.php";</script>';
            }
            $stmt->close();
        } else {
            // Statement preparation error
            echo '<script>alert("Database statement error: ' . $conn->error . '"); window.location.href = "events.php";</script>';
        }
    } else {
        // File move error
        echo '<script>alert("Error moving the uploaded file."); window.location.href = "events.php";</script>';
    }

} else {
    // No file uploaded or an error occurred during upload
    $error_message = "File upload failed with error code: " . ($_FILES['video']['error'] ?? 'Unknown');
    echo '<script>alert("' . $error_message . '"); window.location.href = "events.php";</script>';
}

$conn->close();
?>
