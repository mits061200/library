<?php
session_start();
include 'db.php'; 

// Check if user is already logged in
if(isset($_SESSION['personnelid'])) {
    header("Location: " . ($_SESSION['position'] === 'librarian' ? 'dashboard.php' : 'dashboard.php.php'));
    exit();
}

// Initialize variables
$error = '';
$username = '';

// Process form submission
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Validate inputs
    if(empty($username) || empty($password)) {
        $error = 'Username and password are required';
    } else {
        // Prepare SQL to get user data with position
        $stmt = $conn->prepare("SELECT pl.*, p.Position FROM personnellogin pl 
                               JOIN personnel p ON pl.PersonnelID = p.PersonnelID 
                               WHERE pl.Username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Check account status
            if($user['Status'] === 'inactive') {
                $error = 'Your account is inactive. Please contact administrator.';
            } 
            // Verify password
            elseif(password_verify($password, $user['Password'])) {
                // Password is correct, set session variables
                $_SESSION['personnelid'] = $user['PersonnelID'];
                $_SESSION['username'] = $user['Username'];
                $_SESSION['position'] = $user['Position'];
                $_SESSION['last_login'] = $user['LastLogin'];
                
                // Update last login time
                $update_stmt = $conn->prepare("UPDATE personnellogin SET LastLogin = NOW() WHERE LoginID = ?");
                $update_stmt->bind_param("i", $user['LoginID']);
                $update_stmt->execute();
                
                // Redirect based on position
                header("Location: " . ($user['Position'] === 'librarian' ? 'dashboard.php' : 'staff-dashboard.php'));
                exit();
            } else {
                $error = 'Invalid username or password';
            }
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library System - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-color);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-image: linear-gradient(rgba(255,255,255,0.9), rgba(255,255,255,0.9)), 
                              url('images/logo.png');
            background-size: cover;
            background-position: center;
        }
        
        .login-container {
            background: white;
            padding: 2.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
            transform: translateY(0);
            transition: transform 0.3s ease;
        }
        
        .login-container:hover {
            transform: translateY(-5px);
        }
        
        .logo {
            width: 100px;
            margin-bottom: 1.5rem;
        }
        
        .login-container h2 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
            font-weight: 500;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-color);
        }
        
        .form-group input {
            width: 85%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border 0.3s;
        }
        
        .form-group input:focus {
            border-color: var(--secondary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: var(--secondary-color);
            border: none;
            border-radius: 5px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-login:hover {
            background-color: #2980b9;
        }
        
        .forgot-password {
            display: block;
            margin-top: 1rem;
            color: var(--secondary-color);
            text-decoration: none;
            font-size: 14px;
        }
        
        .forgot-password:hover {
            text-decoration: underline;
        }
        
        .error-message {
            color: var(--accent-color);
            margin-bottom: 1rem;
            padding: 10px;
            background-color: #fadbd8;
            border-radius: 5px;
            display: <?php echo !empty($error) ? 'block' : 'none'; ?>;
        }
        
        .footer-text {
            margin-top: 1.5rem;
            color: #7f8c8d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="images/logo.png" alt="Library Logo" class="logo">
        <h2>Library System Login</h2>
        
        <?php if(!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> 
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="login.php">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-with-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Enter your username" 
                           value="<?php echo htmlspecialchars($username); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
            </div>
            
            <button type="submit" name="login" class="btn-login">Login</button>
            
            <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
        </form>
        
        <p class="footer-text">Library Management System © <?php echo date('Y'); ?></p>
    </div>
</body>
</html>