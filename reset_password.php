<?php
session_start();
include 'db.php'; // Your database connection file

$message = '';
$token = $_GET['token'] ?? ''; // Get token from URL

// If no token is provided, redirect or show error
if (empty($token)) {
    $message = "<p class='error-message'>Invalid or missing reset token.</p>";
    // Optional: header("Location: forgot_password.php"); exit();
} else {
    // 1. Verify the token from the database
    $stmt = $conn->prepare("SELECT user_id, expires_at FROM password_reset_tokens WHERE token = ?");
    if ($stmt === false) {
        error_log("Reset password token check prepare failed: " . $conn->error);
        $message = "<p class='error-message'>An error occurred. Please try again.</p>";
    } else {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $message = "<p class='error-message'>Invalid or expired reset token.</p>";
            $token = ''; // Invalidate token on front-end
        } else {
            $token_data = $result->fetch_assoc();
            $user_id = $token_data['user_id'];
            $expires_at = strtotime($token_data['expires_at']);

            // Check if token has expired
            if (time() > $expires_at) {
                $message = "<p class='error-message'>The reset link has expired. Please request a new one.</p>";
                $token = ''; // Invalidate token on front-end
                // Optionally delete expired token immediately
                $delete_stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE token = ?");
                $delete_stmt->bind_param("s", $token);
                $delete_stmt->execute();
                $delete_stmt->close();
            } else {
                // Token is valid, proceed with new password entry
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $new_password = $_POST['new_password'] ?? '';
                    $confirm_password = $_POST['confirm_password'] ?? '';

                    if (empty($new_password) || empty($confirm_password)) {
                        $message = "<p class='error-message'>Please fill in all password fields.</p>";
                    } elseif ($new_password !== $confirm_password) {
                        $message = "<p class='error-message'>Passwords do not match.</p>";
                    } elseif (strlen($new_password) < 6) { // Example: minimum 6 characters
                        $message = "<p class='error-message'>Password must be at least 6 characters long.</p>";
                    } else {
                        // Hash the new password
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                        // Update the user's password in the database
                        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                        if ($update_stmt === false) {
                            error_log("Password update prepare failed: " . $conn->error);
                            $message = "<p class='error-message'>An error occurred while updating password.</p>";
                        } else {
                            $update_stmt->bind_param("si", $hashed_password, $user_id);
                            if ($update_stmt->execute()) {
                                // Delete the token to prevent reuse
                                $delete_token_stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE token = ?");
                                $delete_token_stmt->bind_param("s", $token);
                                $delete_token_stmt->execute();
                                $delete_token_stmt->close();

                                $message = "<p class='success-message'>Your password has been successfully reset. You can now log in with your new password.</p>";
                                // Redirect to login page after a short delay or immediately
                                header("Refresh: 3; url=login.php"); // Redirect after 3 seconds
                                // header("Location: login.php?reset_success=true"); exit();
                            } else {
                                $message = "<p class='error-message'>Error updating password: " . $update_stmt->error . "</p>";
                            }
                            $update_stmt->close();
                        }
                    }
                }
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - LMS</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .container { background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        h1 { color: #2c3e50; margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; text-align: left; font-weight: bold; }
        input[type="password"] { width: calc(100% - 22px); padding: 10px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 5px; font-size: 1em; }
        button { background-color: #007BFF; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 1.1em; width: 100%; transition: background-color 0.3s ease; }
        button:hover { background-color: #0056b3; }
        .message { margin-bottom: 20px; padding: 10px; border-radius: 5px; }
        .success-message { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error-message { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .login-link { margin-top: 15px; }
        .login-link a { color: #007BFF; text-decoration: none; }
        .login-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Reset Password</h1>
        <?php echo $message; ?>

        <?php if (!empty($token) && strpos($message, 'Invalid') === false && strpos($message, 'expired') === false): // Only show form if token is valid ?>
            <form method="POST" action="reset_password.php?token=<?= htmlspecialchars($token) ?>">
                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" required>
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <button type="submit">Reset Password</button>
            </form>
        <?php endif; ?>

        <p class="login-link"><a href="login.php">Back to Login</a></p>
    </div>
</body>
</html>