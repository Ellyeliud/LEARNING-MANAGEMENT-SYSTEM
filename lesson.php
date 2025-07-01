<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
$role = $user['role'];

if (!isset($_GET['course_id'])) {
    echo "No course selected.";
    exit();
}

$course_id = $_GET['course_id'];

// Get course info
$course_stmt = $conn->prepare("SELECT title FROM courses WHERE id = ?");
$course_stmt->bind_param("i", $course_id);
$course_stmt->execute();
$course_result = $course_stmt->get_result();

if ($course_result->num_rows === 0) {
    echo "Course not found.";
    exit();
}

$course = $course_result->fetch_assoc();
$course_title = $course['title'];

// Get lessons
$lesson_stmt = $conn->prepare("SELECT * FROM lessons WHERE course_id = ? ORDER BY created_at DESC");
$lesson_stmt->bind_param("i", $course_id);
$lesson_stmt->execute();
$lessons = $lesson_stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Lessons - <?= htmlspecialchars($course_title) ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background-color: #f9f9f9; }
        h1 { color: #333; }
        .lesson {
            border-left: 5px solid #007BFF;
            background: #fff;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .lesson h3 { margin-top: 0; color: #007BFF; }
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .back-btn:hover { background: #5a6268; }
    </style>
</head>
<body>

<h1>Lessons for: <?= htmlspecialchars($course_title) ?></h1>

<?php
if ($lessons->num_rows > 0) {
    while ($lesson = $lessons->fetch_assoc()) {
        echo "<div class='lesson'>";
        echo "<h3>" . htmlspecialchars($lesson['title']) . "</h3>";
        echo "<p>" . nl2br(htmlspecialchars($lesson['content'])) . "</p>";
        echo "<small>Created at: " . htmlspecialchars($lesson['created_at']) . "</small>";
        echo "</div>";
    }
} else {
    echo "<p>No lessons found for this course.</p>";
}
?>

<a class="back-btn" href="dashboard.php">← Back to Dashboard</a>

</body>
</html>
