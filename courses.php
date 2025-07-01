<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['id'];

$courses = $conn->query("SELECT c.*, u.name AS instructor_name FROM courses c JOIN users u ON c.instructor_id = u.id");

echo "<h2>Available Courses</h2>";
while ($course = $courses->fetch_assoc()) {
    echo "<h3>" . htmlspecialchars($course['title']) . "</h3>";
    echo "<p>" . htmlspecialchars($course['description']) . "</p>";
    echo "<p>Instructor: " . htmlspecialchars($course['instructor_name']) . "</p>";

    // Check if enrolled
    $check = $conn->prepare("SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?");
    $check->bind_param("ii", $user_id, $course['id']);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo "<strong>Enrolled</strong>";
    } else if ($user['role'] == 'student') {
        echo '<form method="POST" action="enroll.php">';
        echo '<input type="hidden" name="course_id" value="' . $course['id'] . '">';
        echo '<button type="submit" name="enroll">Enroll</button>';
        echo '</form>';
    }
    echo "<hr>";
}
