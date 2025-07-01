<?php
session_start();
include 'db.php';

// Redirect if not logged in or not instructor/admin
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'instructor'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['create_course'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $instructor_id = $_SESSION['user']['id'];

    $sql = "INSERT INTO courses (title, description, instructor_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $title, $description, $instructor_id);
    $stmt->execute();

    echo "Course created successfully!";
}
?>

<h2>Create a Course</h2>
<form method="POST" action="create_course.php">
  <input type="text" name="title" placeholder="Course Title" required><br>
  <textarea name="description" placeholder="Course Description" required></textarea><br>
  <button type="submit" name="create_course">Create Course</button>
</form>
