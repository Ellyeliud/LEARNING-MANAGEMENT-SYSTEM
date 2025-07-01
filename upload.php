<?php
session_start();
include 'db.php';

// Ensure only instructors can upload
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'instructor') {
    die("Access denied");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'];
    $note_title = $_POST['title'];
    $description = $_POST['description'];
    $file = $_FILES['note'];

    // Allow only PDF, JPG, PNG
    $allowed = ['application/pdf', 'image/jpeg', 'image/png'];
    if (in_array($file['type'], $allowed)) {
        $uploadDir = 'uploads/';
        $filename = time() . '_' . basename($file['name']);
        $filepath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $stmt = $conn->prepare("INSERT INTO notes (course_id, title, filename, content) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $course_id, $note_title, $filepath, $description);
            $stmt->execute();
            echo "Note uploaded successfully!";
        } else {
            echo "Failed to move file.";
        }
    } else {
        echo "Only PDF, JPG, and PNG files are allowed.";
    }
}
?>

<form method="post" enctype="multipart/form-data">
  <label>Title: <input type="text" name="title" required></label><br><br>
  <label>Description: <textarea name="description"></textarea></label><br><br>
  <label>Course ID: <input type="number" name="course_id" required></label><br><br>
  <label>Select file: <input type="file" name="note" required></label><br><br>
  <button type="submit">Upload</button>
</form>
