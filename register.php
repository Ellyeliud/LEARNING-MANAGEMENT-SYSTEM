<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>registration</title>
  <style>
    body {
      background-color: aquamarine;
      margin: auto;
    }
    form {
      color: blue;
      background-color: aqua;
      height: 100%;
      width: 55%;
      margin-top: 10%;
      margin-left: 20%;
      padding: 15px;
      border: 2px solid black;
      border-radius: 30px;
    }
    .input, select, option {
      width: 45%;
      padding: 4px;
      background-color: azure;
      margin: auto;
      border-radius: 7px;
    }
    button {
      margin: auto;
      text-align: center;
      border-radius: 10px;
      background-color: blue;
      width: 200px;
      color: white;
      padding: 10px;
    }
    h1 {
      color: black;
      text-align: center;
      font-size: 20px;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      font-weight: 900;
    }
  </style>
</head>
<body>

  <form action="register.php" method="POST" style="text-align: justify;">
    <h1>LEARNING MANAGEMENT SYSTEM</h1>
    <h6>Registration to the System</h6>

    <input type="text" name="name" placeholder="Full Name" required style="padding: 4px; width: 50%; color: blue; border-radius: 7px;"><br><br>
    <input type="email" name="email" placeholder="Email" required style="padding: 4px; width: 50%; color: blue; border-radius: 7px;"><br><br>
    <input type="password" name="password" placeholder="Password" required style="padding: 4px; width: 50%; color: blue; border-radius: 7px;"><br><br>

    <select name="role" required>
      <option value="student">Register as Student</option>
      <option value="instructor">Register as Instructor</option>
    </select><br><br>

    <button type="submit" name="register">Register</button><br><br>
    Already have an account? <a href="login.php" style="font-weight: 600;">Login here</a>

    <p style="font-weight: bold; color: green;">
      <?php
      include 'db.php';
      if (isset($_POST['register'])) {
          $name = $_POST['name'];
          $email = $_POST['email'];
          $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
          $role = $_POST['role'];

          // Insert user
          $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
          $stmt = $conn->prepare($sql);
          $stmt->bind_param("ssss", $name, $email, $pass, $role);
          $stmt->execute();

          // Auto-enroll if role is student
          if ($role === 'student') {
              $student_id = $stmt->insert_id;
              $default_course_id = 1; // Change this to your actual course ID

              $enroll_stmt = $conn->prepare("INSERT INTO enrollments (user_id, course_id) VALUES (?, ?)");
              $enroll_stmt->bind_param("ii", $student_id, $default_course_id);
              $enroll_stmt->execute();
              $enroll_stmt->close();
          }

          echo "Registered successfully!";
      }
      ?>
    </p>
  </form>

</body>
</html>
