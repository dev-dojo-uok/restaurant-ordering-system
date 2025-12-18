<?php

require_once '../app/controllers/AuthController.php';
require_once '../app/helpers/auth.php';

redirectIfLoggedIn();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $authController = new AuthController();

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $result = $authController->login($username, $password);
    if ($result['success']) {
        header('Location: ' . $result['redirect']);
        exit;
    } else {
        $message = $result['message'];
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Food Ordering System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <h2>Welcome Back!</h2>
        <p>Login to continue to your dashboard</p>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <!-- Username -->
            <div class="form-group">
                <label for="username">Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    required
                    placeholder="Enter your username"
                    autofocus
                >
            </div>
            
            <!-- Password -->
            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                    placeholder="Enter your password"
                >
            </div>
            
            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        
        <!-- Link to Register -->
        <div class="auth-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
        
        <!-- Test Credentials -->
        <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px; font-size: 13px;">
            <strong>🔐 Test Credentials:</strong><br>
            <strong>Admin:</strong> admin / admin123<br>
            <em>(You can register your own customer account above)</em>
        </div>
    </div>
</body>
</html>
