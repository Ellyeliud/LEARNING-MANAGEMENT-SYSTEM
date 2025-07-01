<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'student') {
    header("Location: login.php");
    exit();
}

if (isset($_POST['enroll'])) {
    $user_id = $_SESSION['user']['id'];
    $course_id = $_POST['course_id'];

    // Check if already enrolled
    $check = $conn->prepare("SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?");
    $check->bind_param("ii", $user_id, $course_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows == 0) {
        $insert = $conn->prepare("INSERT INTO enrollments (user_id, course_id) VALUES (?, ?)");
        $insert->bind_param("ii", $user_id, $course_id);
        $insert->execute();
        echo "Successfully enrolled!";
    } else {
        echo "You are already enrolled in this course.";
    }
}

header("Location: courses.php");
exit();
?>
