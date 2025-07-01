<?php
session_start();
include 'db.php';

// Check if user is logged in and is an instructor with course management enabled
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'instructor' || empty($_SESSION['instructor_enabled'])) {
    header("Location: login.php");
    exit();
}

// Handle form submission
if (isset($_POST['add_note'])) {
    $course_id = $_POST['course_id'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $filename = "";

    // Handle PDF upload
    if (isset($_FILES['note_pdf']) && $_FILES['note_pdf']['error'] == 0) {
        $file_tmp = $_FILES['note_pdf']['tmp_name'];
        $file_name = basename($_FILES['note_pdf']['name']);
        $upload_dir = 'uploads/';
        $target_file = $upload_dir . time() . "_" . $file_name;

        // Move uploaded file to uploads directory
        if (move_uploaded_file($file_tmp, $target_file)) {
            $filename = $target_file;
        } else {
            die("Failed to upload PDF file.");
        }
    }

    // Save note details into database
    $stmt = $conn->prepare("INSERT INTO notes (course_id, title, content, filename) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $course_id, $title, $content, $filename);
    $stmt->execute();

    echo "<p style='color: green;'>Note uploaded successfully!</p>";
}

// Get courses of instructor for dropdown
$user_id = $_SESSION['user']['id'];
$courses = $conn->prepare("SELECT id, title FROM courses WHERE instructor_id = ?");
$courses->bind_param("i", $user_id);
$courses->execute();
$result = $courses->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Course Note</title>
</head>
<body>
    <h2>Upload PDF Note for Course</h2>
    <form method="POST" action="add_note.php" enctype="multipart/form-data">
        <label>Select Course:</label><br>
        <select name="course_id" required>
            <option value="">-- Choose a Course --</option>
            <?php while($course = $result->fetch_assoc()) { ?>
                <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['title']) ?></option>
            <?php } ?>
        </select><br><br>

        <label>Note Title:</label><br>
        <input type="text" name="title" required><br><br>

        <label>Description (Optional):</label><br>
        <textarea name="content"></textarea><br><br>

        <label>Attach PDF Note:</label><br>
        <input type="file" name="note_pdf" accept="application/pdf" required><br><br>

        <button type="submit" name="add_note">Upload Note</button>
    </form>
</body>
</html>
