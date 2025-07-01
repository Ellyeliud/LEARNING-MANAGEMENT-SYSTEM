<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['id'];
$role = $user['role'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - LMS</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f7f6; color: #333; }
        h1 { color: #2c3e50; margin-bottom: 10px;}
        h2 { color: #34495e; border-bottom: 2px solid #ddd; padding-bottom: 5px; margin-top: 30px;}
        h3 { color: #2980b9; margin-bottom: 5px; }
        p { margin-bottom: 10px; }

        .stats { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
        .card {
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            width: 220px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            background-color: #ffffff;
            text-align: center;
        }
        .card p { font-size: 1.8em; font-weight: bold; color: #3498db; margin-top: 10px; }

        .courses-list ul { list-style: none; padding: 0; }
        .courses-list li {
            border: 1px solid #e0e0e0;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
        }
        .courses-list li strong { color: #555; }
        .courses-list li div { margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap;}

        a.button, button {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.3s ease;
            display: inline-block;
            white-space: nowrap;
        }
        a.button { background: #007BFF; }
        a.button:hover { background: #0056b3; }

        a.edit-button { background-color: #28a745; }
        a.edit-button:hover { background-color: #218838; }
        a.delete-button { background-color: #dc3545; }
        a.delete-button:hover { background-color: #c82333; }
        a.add-lesson-button { background-color: #ffc107; color: #333; }
        a.add-lesson-button:hover { background-color: #e0a800; }

        button { background: #6c757d; width: 200px; }
        button a { color: white; text-decoration: none; }
        button:hover { background: #5a6268; }

        .note-card {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            background-color: #fefefe;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .note-card strong { color: #444; }
        .note-card a { color: #007BFF; text-decoration: none; }
        .note-card a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<h1>Welcome, <?= htmlspecialchars($user['name']) ?>!</h1>
<p>You are logged in as <strong><?= ucfirst($role) ?></strong>.</p>

<div class="stats">
<?php
if ($role === 'student') {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM enrollments WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $total_enrolled = $stmt->get_result()->fetch_assoc()['total'];
    ?>
    <div class="card">
        <h3>Enrolled Courses</h3>
        <p><?= $total_enrolled ?></p>
    </div>
    <div class="card">
        <h3>Completed Courses</h3>
        <p>0</p></div>
    <div class="card">
        <h3>Upcoming Quizzes</h3>
        <p>0</p></div>
<?php
} elseif ($role === 'instructor') {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM courses WHERE instructor_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $total_courses = $stmt->get_result()->fetch_assoc()['total'];

    $stmt = $conn->prepare("SELECT COUNT(DISTINCT e.user_id) as total_students FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE c.instructor_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $total_students = $stmt->get_result()->fetch_assoc()['total_students'];
    ?>
    <div class="card">
        <h3>Courses Created</h3>
        <p><?= $total_courses ?></p>
    </div>
    <div class="card">
        <h3>Students Enrolled</h3>
        <p><?= $total_students ?></p>
    </div>
    <div class="card">
        <h3>Pending Grading</h3>
        <p>0</p></div>
<?php
} elseif ($role === 'admin') {
    $total_users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
    $total_courses = $conn->query("SELECT COUNT(*) as total FROM courses")->fetch_assoc()['total'];
    $total_enrollments = $conn->query("SELECT COUNT(*) as total FROM enrollments")->fetch_assoc()['total'];
    ?>
    <div class="card">
        <h3>Total Users</h3>
        <p><?= $total_users ?></p>
    </div>
    <div class="card">
        <h3>Total Courses</h3>
        <p><?= $total_courses ?></p>
    </div>
    <div class="card">
        <h3>Total Enrollments</h3>
        <p><?= $total_enrollments ?></p>
    </div>
<?php } ?>
</div>

<?php if ($role === 'instructor' || $role === 'admin'): ?>
<div style="margin-bottom: 30px; text-align: center;">
    <a class="button" style="background-color: #3498db; padding: 12px 25px; font-size: 1.1em;" href="add_course.php">Add New Course</a>
</div>
<?php endif; ?>

<div class="courses-list">
    <h2>
        <?php
        if ($role === 'student') echo "Your Enrolled Courses";
        elseif ($role === 'instructor') echo "Courses You Created";
        elseif ($role === 'admin') echo "All Courses";
        ?>
    </h2>

    <?php
    $courses_stmt = null;
    $courses_result = null;

    if ($role === 'student') {
        $courses_stmt = $conn->prepare("SELECT c.* FROM courses c JOIN enrollments e ON c.id = e.course_id WHERE e.user_id = ? ORDER BY c.title ASC");
        $courses_stmt->bind_param("i", $user_id);
        $courses_stmt->execute();
        $courses_result = $courses_stmt->get_result();
    } elseif ($role === 'instructor') {
        $courses_stmt = $conn->prepare("SELECT * FROM courses WHERE instructor_id = ? ORDER BY title ASC");
        $courses_stmt->bind_param("i", $user_id);
        $courses_stmt->execute();
        $courses_result = $courses_stmt->get_result();
    } else {
        $courses_result = $conn->query("SELECT * FROM courses ORDER BY title ASC");
    }

    if ($courses_result && $courses_result->num_rows > 0) {
        echo "<ul>";
        while ($course = $courses_result->fetch_assoc()) {
            echo "<li>";
            echo "<strong>Title:</strong> " . htmlspecialchars($course['title']) . "<br>";
            echo "<strong>Description:</strong> " . htmlspecialchars(substr($course['description'] ?? 'No description provided.', 0, 150)) . "...<br>";
            echo "<strong>Created:</strong> " . date('Y-m-d', strtotime($course['created_at'])) . "<br>";

            echo "<div>";
            echo "<a class='button' href='view_lessons.php?course_id=" . $course['id'] . "'>View Lessons</a>";

            if ($role === 'instructor' || $role === 'admin') {
                echo " <a class='button add-lesson-button' href='add_lesson.php?course_id=" . $course['id'] . "'>Add Lesson</a>";
                echo " <a class='button edit-button' href='edit_course.php?course_id=" . $course['id'] . "'>Edit Course</a>";
                echo " <a class='button delete-button' href='delete_course.php?course_id=" . $course['id'] . "' onclick='return confirm(\"Are you sure you want to delete this course?\")'>Delete Course</a>";
            }
            echo "</div>";
            echo "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No courses found.</p>";
    }

    if ($courses_stmt) {
        $courses_stmt->close();
    }
    ?>
</div>

<div>
<?php
if ($role === 'instructor') {
    $query = "SELECT c.title AS course_title, n.title AS note_title, n.filename, n.content, n.created_at
              FROM courses c
              JOIN notes n ON c.id = n.course_id
              WHERE c.instructor_id = ? ORDER BY n.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    echo "<h3>Your Uploaded Notes</h3>";
    if ($result->num_rows > 0) {
        while ($note = $result->fetch_assoc()) {
            echo "<div class='note-card'>";
            echo "<strong>Course:</strong> " . htmlspecialchars($note['course_title']) . "<br>";
            echo "<strong>Note:</strong> " . htmlspecialchars($note['note_title']) . "<br>";
            echo "<strong>Description:</strong> " . htmlspecialchars($note['content']) . "<br>";
            echo "<strong>Date:</strong> " . date('Y-m-d H:i', strtotime($note['created_at'])) . "<br>";
            echo "<a href='" . htmlspecialchars($note['filename']) . "' target='_blank'>📄 View/Download PDF</a>";
            echo "</div>";
        }
    } else {
        echo "<p>No notes uploaded yet.</p>";
    }
    $stmt->close();
}
?>
</div>

<p><a href="logout.php"><input type="button" value="logout" style="width: 200px; background-color: #5a6268; height: 30px;"></a></p>

</body>
</html>
