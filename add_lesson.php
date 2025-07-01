<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['id'];

if (!isset($_GET['course_id'])) {
    echo "No course selected.";
    exit();
}

$course_id = $_GET['course_id'];

$stmt = $conn->prepare("SELECT title FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->bind_param("ii", $course_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Course not found or unauthorized access.";
    exit();
}

$course = $result->fetch_assoc();
$course_title = $course['title'];

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lesson_title = trim($_POST['title']);
    $lesson_content = trim($_POST['content']);

    if (empty($lesson_title) || empty($lesson_content)) {
        $error = "Please fill in all fields.";
    } else {
        $insert_stmt = $conn->prepare("INSERT INTO lessons (course_id, title, content) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("iss", $course_id, $lesson_title, $lesson_content);

        if ($insert_stmt->execute()) {
            // ✅ Redirect to view lessons page
            header("Location: view_lessons.php?course_id=" . $course_id);
            exit();
        } else {
            $error = "❌ Error adding lesson: " . $conn->error;
        }

        $insert_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Lesson - <?= htmlspecialchars($course_title) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f7fa;
            padding: 0;
            margin: 0;
        }

        .container {
            max-width: 700px;
            margin: 40px auto;
            background: #fff;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        }

        h1 {
            color: #2c3e50;
            font-size: 24px;
            margin-bottom: 25px;
            text-align: center;
        }

        label {
            display: block;
            margin-top: 18px;
            font-weight: bold;
            color: #34495e;
        }

        input[type="text"], textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            margin-top: 6px;
            box-sizing: border-box;
        }

        textarea {
            height: 180px;
            resize: vertical;
        }

        .error {
            background-color: #ffecec;
            color: #c0392b;
            border: 1px solid #e74c3c;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        button {
            margin-top: 25px;
            background: #3498db;
            color: #fff;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #2980b9;
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
    <h1>Add Lesson to "<?= htmlspecialchars($course_title) ?>"</h1>

    <?php if (!empty($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="title">Lesson Title</label>
        <input type="text" name="title" id="title" required>

        <label for="content">Lesson Content</label>
        <textarea name="content" id="content" required></textarea>

        <button type="submit">➕ Add Lesson</button>
    </form>

    <a class="back-
