<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
    body{
      background-color: aquamarine;
      margin:auto;
    }
form{
  color: blue;
  background-color:aqua;
  height: 100%;
  width: 55%;
  margin-top: 10%;
  margin-left: 20%;
  padding: 15px;
  border: 2px solid black;
  
  border-radius: 30px;
 
l
}
.input{
  width: 45%;
  padding: 4px;
  background-color: azure;
  margin: auto;
  border-radius: 7px;

}
button{
  margin: auto;
  text-align: center;
  border-radius: 10px;
  background-color: blue;
  width: 200px;
}
h1{
  color: black;
  text-align: center;
  font-size: 20;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  font-weight: 900;
}
h4{
    text-align: center;
}

    </style>
</head>
<body>
    <form action="login.php" method="POST">
        <h1>LEARNING MANAGEMENT SYSTEM</h1>
        <h4>LOGIN </h4>
  <label for="email">enter your email</label> <br><input type="email" name="email" placeholder="Email" required style="padding: 4px; width: 50%; color: blue;border-radius: 7px;"><br><br>
  <label for="password">enter password</label><br><input type="password" name="password" placeholder="Password" required style="padding: 4px; width: 50%; color: blue;border-radius: 7px;"><br><br>
  <button type="submit" name="login">Login</button><br>
  you have no accout click here to  <a href="register.php" style="font-weight: 600;">regist</a>
  <p class="forgot-password-link" style="margin-top: 15px;"><a href="forgot_password.php">Forgot Password?</a></p>
</form>
<br><br><br>
 <p style="font-size: 10;">BY ELLYPRO software development</p>
</body>
</html>
<?php
include 'db.php';
session_start();

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $pass = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['user'] = $user;
        header("Location: dashboard.php");
    } else {
        echo "Invalid email or password!";
    }
}
?>
