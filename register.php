<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (isset($_SESSION["email"])) {
    header("Location: dashboard.php");
    exit();
}

require 'config/db.php';

// Debug database connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);
    
    // Validate inputs
    if (empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        if (!$stmt) {
            $error = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $error = "Email already exists.";
            } else {
                // Hash password
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                
                // Insert new user
                $insert_stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
                if (!$insert_stmt) {
                    $error = "Prepare failed: " . $conn->error;
                } else {
                    $insert_stmt->bind_param("ss", $email, $password_hash);
                    
                    if ($insert_stmt->execute()) {
                        $_SESSION["email"] = $email;
                        $_SESSION["user_id"] = $insert_stmt->insert_id;
                        header("Location: dashboard.php");
                        exit();
                    } else {
                        $error = "Registration failed: " . $insert_stmt->error;
                    }
                    $insert_stmt->close();
                }
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MathGenius | Register</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-header">
            <h1><i class="fas fa-calculator"></i> MathGenius</h1>
            <p>Create your account</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="form-box">
            <form action="register.php" method="POST" onsubmit="return validateForm()">
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="password" placeholder="Password" required minlength="8">
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-user-plus"></i> Register
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="index.php">Login here</a></p>
            </div>
        </div>
    </div>

    <script>
        function validateForm() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 8) {
                alert('Password must be at least 8 characters long!');
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>