<?php
session_start();
include 'db.php';

// Only instructors or students can access
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
$role = $user['role'];

// Check if course_id is provided
if (!isset($_GET['course_id'])) {
    echo "Course ID is missing.";
    exit();
}

$course_id = $_GET['course_id'];

// Get course title
$stmt = $conn->prepare("SELECT title FROM courses WHERE id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course_result = $stmt->get_result();

if ($course_result->num_rows === 0) {
    echo "Course not found.";
    exit();
}

$course = $course_result->fetch_assoc();
$course_title = $course['title'];

// Get lessons for this course
$lessons_stmt = $conn->prepare("SELECT title, content, created_at FROM lessons WHERE course_id = ? ORDER BY id DESC");
$lessons_stmt->bind_param("i", $course_id);
$lessons_stmt->execute();
$lessons_result = $lessons_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lessons - <?= htmlspecialchars($course_title) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f7fa;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 800px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            color: #2c3e50;
        }

        .lesson {
            margin-bottom: 30px;
            padding: 20px;
            border-bottom: 1px solid #ccc;
        }

        .lesson h2 {
            color: #34495e;
            margin-bottom: 10px;
        }

        .lesson p {
            white-space: pre-line;
            color: #555;
        }

        .lesson .date {
            font-size: 13px;
            color: #999;
            margin-top: 10px;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #007bff;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Lessons for "<?= htmlspecialchars($course_title) ?>"</h1>

    <?php if ($lessons_result->num_rows > 0): ?>
        <?php while ($lesson = $lessons_result->fetch_assoc()): ?>
            <div class="lesson">
                <h2><?= htmlspecialchars($lesson['title']) ?></h2>
                <p><?= nl2br(htmlspecialchars($lesson['content'])) ?></p>
                <div class="date">Posted on: <?= $lesson['created_at'] ?></div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No lessons have been added to this course yet.</p>
    <?php endif; ?>

    <a class="back-link" href="dashboard.php">← Back to Dashboard</a>
</div>
</body>
</html>
