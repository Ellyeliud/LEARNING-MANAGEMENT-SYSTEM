<?php
session_start();
include 'db.php'; // Your database connection file

// 1. Access Control: Redirect if not logged in or not authorized
// Only instructors and admins can add courses
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] !== 'instructor' && $_SESSION['user']['role'] !== 'admin')) {
    header("Location: login.php"); // Or redirect to an access denied page
    exit();
}

$user_id = $_SESSION['user']['id'];
$user_role = $_SESSION['user']['role'];

$message = ''; // To store success or error messages

// 2. Handle Form Submission: Process course creation when the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Basic validation
    if (empty($title)) {
        $message = "<p style='color: red;'>Error: Course Title cannot be empty.</p>";
    } elseif (empty($description)) {
        $message = "<p style='color: red;'>Error: Course Description cannot be empty.</p>";
    } else {
        // Determine the instructor_id:
        // If admin is adding, they might choose an instructor or it defaults to them.
        // For simplicity, if admin adds, we'll assign it to their own ID.
        // If instructor adds, it's assigned to their ID.
        $instructor_to_assign_id = $user_id;
        // You could add logic here for admin to select an instructor if needed
        // e.g., if ($user_role === 'admin' && isset($_POST['instructor_id'])) { $instructor_to_assign_id = intval($_POST['instructor_id']); }

        // Prepare and execute the INSERT query
        $stmt = $conn->prepare("INSERT INTO courses (title, description, instructor_id, created_at) VALUES (?, ?, ?, NOW())");
        if ($stmt === false) {
            $message = "<p style='color: red;'>Error preparing statement: " . $conn->error . "</p>";
        } else {
            $stmt->bind_param("ssi", $title, $description, $instructor_to_assign_id);

            if ($stmt->execute()) {
                $message = "<p style='color: green;'>Course added successfully!</p>";
                // Clear form fields after successful submission
                $title = '';
                $description = '';
                // Optionally, redirect to dashboard or course details page
                // header("Location: dashboard.php?add_success=true");
                // exit();
            } else {
                $message = "<p style='color: red;'>Error adding course: " . $stmt->error . "</p>";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add New Course - LMS</title>
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
        textarea { height: 150px; resize: vertical; }
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
        .message { text-align: center; margin-bottom: 20px; font-weight: bold; }
        .back-link { display: block; text-align: center; margin-top: 30px; }
        .back-link a { text-decoration: none; color: #007BFF; font-weight: bold; font-size: 1.1em; }
        .back-link a:hover { text-decoration: underline; }
        
    </style>
</head>
<body>

<h1>Add New Course</h1>

<?php echo $message; // Display success/error message ?>

<form method="POST" action="add_course.php">
    <label for="title">Course Title:</label>
    <input type="text" id="title" name="title" value="<?= htmlspecialchars($title ?? '') ?>" required><br>

    <label for="description">Course Description:</label>
    <textarea id="description" name="description" required><?= htmlspecialchars($description ?? '') ?></textarea><br>

    <?php /*
    if ($user_role === 'admin') {
        echo "<label for='instructor_id'>Assign to Instructor:</label>";
        echo "<select id='instructor_id' name='instructor_id'>";
        // Fetch instructors from database and populate options
        // Example:
        // $instructors_stmt = $conn->query("SELECT id, name FROM users WHERE role = 'instructor'");
        // while($inst = $instructors_stmt->fetch_assoc()) {
        //     echo "<option value='" . $inst['id'] . "'>" . htmlspecialchars($inst['name']) . "</option>";
        // }
        echo "</select><br>";
    }
    */ ?>

    <input type="submit" value="Add Course">
</form>
<input type="submit" value ="">
<p class="back-link"><a href="dashboard.php">Back to Dashboard</a></p>

</body>
</html>