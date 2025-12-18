<?php

require_once '../app/controllers/AuthController.php';
require_once '../app/helpers/auth.php';

redirectIfLoggedIn();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $authController = new AuthController();

    $formData = [
        'username' => $_POST['username'] ?? '',
        'email' => $_POST['email'] ?? '',
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'full_name' => $_POST['full_name'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'address' => $_POST['address'] ?? '',
        'role' => 'customer'
    ];

    $result = $authController->register($formData);
    
    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';
    if ($result['success']) {
        header('refresh:2;url=login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Food Ordering System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <h2>Create Account</h2>
        <p>Join us and start ordering delicious food!</p>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <!-- Username -->
            <div class="form-group">
                <label for="username">Username *</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    required
                    minlength="3"
                    placeholder="Choose a username"
                >
            </div>
            
            <!-- Email -->
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    required
                    placeholder="your@email.com"
                >
            </div>
            
            <!-- Full Name -->
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input 
                    type="text" 
                    id="full_name" 
                    name="full_name" 
                    value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                    required
                    placeholder="John Doe"
                >
            </div>
            
            <!-- Phone (Optional) -->
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input 
                    type="tel" 
                    id="phone" 
                    name="phone" 
                    value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                    placeholder="+1234567890"
                >
            </div>
            
            <!-- Address (Optional) -->
            <div class="form-group">
                <label for="address">Delivery Address</label>
                <textarea 
                    id="address" 
                    name="address" 
                    rows="2"
                    placeholder="Your delivery address (optional)"
                ><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
            </div>
            
            <!-- Password -->
            <div class="form-group">
                <label for="password">Password *</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                    minlength="6"
                    placeholder="At least 6 characters"
                >
            </div>
            
            <!-- Confirm Password -->
            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    required
                    minlength="6"
                    placeholder="Re-enter your password"
                >
            </div>
            
            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary">Register</button>
        </form>
        
        <!-- Link to Login -->
        <div class="auth-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
    
    <script>
        // Client-side password match validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
</html>
