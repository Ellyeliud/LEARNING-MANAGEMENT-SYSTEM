<?php
session_start();
include 'db.php'; // Your database connection file

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $message = "<p class='error-message'>Please enter your email address.</p>";
    } else {
        // 1. Check if the email exists in the users table
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
        if ($stmt === false) {
            error_log("Forgot password prepare failed: " . $conn->error);
            $message = "<p class='error-message'>An error occurred. Please try again.</p>";
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $user_id = $user['id'];
                $user_name = $user['name'];

                // 2. Generate a unique, secure token
                // PHP 7+ random_bytes for cryptographically secure random string
                $token = bin2hex(random_bytes(32)); // 64 character hex string
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token valid for 1 hour

                // 3. Store the token in the password_reset_tokens table
                // First, delete any existing tokens for this user to ensure only one is active
                $delete_stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE user_id = ?");
                $delete_stmt->bind_param("i", $user_id);
                $delete_stmt->execute();
                $delete_stmt->close();

                $insert_stmt = $conn->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
                if ($insert_stmt === false) {
                    error_log("Token insert prepare failed: " . $conn->error);
                    $message = "<p class='error-message'>An error occurred. Please try again.</p>";
                } else {
                    $insert_stmt->bind_param("iss", $user_id, $token, $expires_at);
                    if ($insert_stmt->execute()) {
                        // 4. Send the password reset email
                        $reset_link = "http://localhost/lms/reset_password.php?token=" . $token; // Adjust URL if needed

                        $subject = "Password Reset Request for your LMS Account";
                        $headers = "From: no-reply@yourlms.com\r\n"; // Change this to your actual email
                        $headers .= "MIME-Version: 1.0\r\n";
                        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

                        $email_body = "
                        <html>
                        <head>
                            <title>Password Reset Request</title>
                        </head>
                        <body>
                            <p>Dear " . htmlspecialchars($user_name) . ",</p>
                            <p>You have requested a password reset for your LMS account.</p>
                            <p>Please click on the link below to reset your password:</p>
                            <p><a href='" . htmlspecialchars($reset_link) . "'>" . htmlspecialchars($reset_link) . "</a></p>
                            <p>This link will expire in 1 hour.</p>
                            <p>If you did not request a password reset, please ignore this email.</p>
                            <p>Thank you,</p>
                            <p>Your LMS Team</p>
                        </body>
                        </html>
                        ";

                        if (mail($email, $subject, $email_body, $headers)) {
                            // Important: Show a generic success message for security (don't confirm if email exists)
                            $message = "<p class='success-message'>If an account exists for " . htmlspecialchars($email) . ", a password reset link has been sent to it.</p>";
                            $_POST = array(); // Clear form
                        } else {
                            $message = "<p class='error-message'>Failed to send reset email. Please try again later.</p>";
                            error_log("Failed to send password reset email to: " . $email);
                        }
                    } else {
                        $message = "<p class='error-message'>Error storing reset token: " . $insert_stmt->error . "</p>";
                    }
                    $insert_stmt->close();
                }
            } else {
                // Important: Show the same generic message even if email doesn't exist
                $message = "<p class='success-message'>If an account exists for " . htmlspecialchars($email) . ", a password reset link has been sent to it.</p>";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password - LMS</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .container { background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        h1 { color: #2c3e50; margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; text-align: left; font-weight: bold; }
        input[type="email"] { width: calc(100% - 22px); padding: 10px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 5px; font-size: 1em; }
        button { background-color: #007BFF; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 1.1em; width: 100%; transition: background-color 0.3s ease; }
        button:hover { background-color: #0056b3; }
        .message { margin-bottom: 20px; padding: 10px; border-radius: 5px; }
        .success-message { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error-message { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .back-link { margin-top: 15px; }
        .back-link a { color: #007BFF; text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Forgot Password</h1>
        <?php echo $message; ?>
        <form method="POST" action="forgot_password.php">
            <label for="email">Enter your email address:</label>
            <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            <button type="submit">Send Reset Link</button>
        </form>
        <p class="back-link"><a href="login.php">Back to Login</a></p>
    </div>
</body>
</html>