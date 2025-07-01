<?php
session_start();
include 'db.php'; // Your database connection file (should be in C:\xampp\htdocs\lms\)

// 1. Access Control: Redirect if not logged in or not authorized
// Only instructors and admins can edit courses
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] !== 'instructor' && $_SESSION['user']['role'] !== 'admin')) {
    header("Location: login.php"); // Or an access denied page
    exit();
}

$user_id = $_SESSION['user']['id'];
$user_role = $_SESSION['user']['role'];

// 2. Get Course ID: Retrieve the 'course_id' from the URL
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

if ($course_id === 0) {
    die("Error: Course ID not provided in the URL.");
}

// 3. Fetch Course Details: Get the existing course information from the database
$course = null; // Initialize variable to store course data

$stmt_fetch = $conn->prepare("SELECT * FROM courses WHERE id = ?");
if ($stmt_fetch === false) {
    die("Error preparing statement to fetch course details: " . $conn->error);
}
$stmt_fetch->bind_param("i", $course_id);
$stmt_fetch->execute();
$result_fetch = $stmt_fetch->get_result();

if ($result_fetch->num_rows > 0) {
    $course = $result_fetch->fetch_assoc();

    // Security Check for Instructors: Ensure instructors can only edit their own courses
    if ($user_role === 'instructor' && $course['instructor_id'] !== $user_id) {
        die("Access Denied: You are not authorized to edit this course.");
    }

} else {
    die("Error: Course not found in the database.");
}
$stmt_fetch->close(); // Close the statement after fetching details

// 4. Handle Form Submission: Process updates when the form is submitted via POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_title = trim($_POST['title'] ?? ''); // Use null coalescing to prevent undefined index
    $new_description = trim($_POST['description'] ?? '');

    // Basic validation
    if (empty($new_title)) {
        echo "<p style='color: red;'>Error: Course Title cannot be empty.</p>";
    } elseif (empty($new_description)) {
        echo "<p style='color: red;'>Error: Course Description cannot be empty.</p>";
    } else {
        // Prepare and execute the UPDATE query
        $stmt_update = $conn->prepare("UPDATE courses SET title = ?, description = ? WHERE id = ?");
        if ($stmt_update === false) {
            die("Error preparing update statement: " . $conn->error);
        }
        $stmt_update->bind_param("ssi", $new_title, $new_description, $course_id);

        if ($stmt_update->execute()) {
            echo "<p style='color: green;'>Course updated successfully!</p>";
            // Redirect back to dashboard after successful update
            header("Location: dashboard.php?update_success=true");
            exit();
        } else {
            echo "<p style='color: red;'>Error updating course: " . $stmt_update->error . "</p>";
        }
        $stmt_update->close(); // Close the update statement
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Course - <?= htmlspecialchars($course['title'] ?? 'N/A') ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f7f6; color: #333; }
        h1 { color: #2c3e50; margin-bottom: 20px; text-align: center; }
        form {
            background: #ffffff;
            padding: 25px;
            border-radius: 8px;
            max-width: 600px;
            margin: 20px auto;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; }
        input[type="text"], textarea {
            width: calc(100% - 24px); /* Account for padding and border */
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box; /* Include padding and border in the element's total width and height */
        }
        textarea { height: 150px; resize: vertical; } /* Allow vertical resizing */
        input[type="submit"] {
            background: #007BFF;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            display: block;
            width: 100%;
            transition: background-color 0.3s ease;
        }
        input[type="submit"]:hover { background: #0056b3; }
        .back-link { display: block; text-align: center; margin-top: 30px; }
        .back-link a { text-decoration: none; color: #007BFF; font-weight: bold; font-size: 1.1em; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<h1>Edit Course: <?= htmlspecialchars($course['title'] ?? 'N/A') ?></h1>

<form method="POST" action="edit_course.php?course_id=<?= $course_id ?>">
    <label for="title">Course Title:</label>
    <input type="text" id="title" name="title" value="<?= htmlspecialchars($course['title'] ?? '') ?>" required><br>

    <label for="description">Course Description:</label>
    <textarea id="description" name="description" required><?= htmlspecialchars($course['description'] ?? '') ?></textarea><br>

    <input type="submit" value="Update Course">
</form>

<p class="back-link"><a href="dashboard.php">Back to Dashboard</a></p>

</body>
</html>