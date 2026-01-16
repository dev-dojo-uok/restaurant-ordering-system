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
    <title>Register - Flavor POS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f5f6fb;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #6b7280;
            --accent: #ff4757;
            --accent-dark: #e53b4c;
            --border: #e5e7eb;
            --shadow: 0 20px 60px rgba(15, 23, 42, 0.12);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; font-family: 'Space Grotesk', sans-serif; color: var(--text);
            background: var(--bg);
            display: flex; align-items: center; justify-content: center; padding: 48px 16px;
        }
        .shell { width: min(620px, 100%); }
        .panel { background: var(--card); border: 1px solid var(--border); border-radius: 18px; box-shadow: var(--shadow); padding: 36px; }
        h2 { margin: 0; font-size: 26px; letter-spacing: -0.01em; }
        .subtitle { color: var(--muted); margin: 6px 0 0; font-size: 15px; }
        form { display: grid; gap: 14px; margin-top: 18px; }
        label { display: block; margin-bottom: 6px; color: var(--muted); font-weight: 600; font-size: 13px; }
        .field { position: relative; }
        input, textarea {
            width: 100%; padding: 14px; border-radius: 12px; border: 1px solid var(--border);
            background: #f9fafb; color: var(--text); font-size: 15px; outline: none; transition: border 0.2s, box-shadow 0.2s;
        }
        textarea { resize: vertical; min-height: 90px; }
        input:focus, textarea:focus { border-color: var(--accent); box-shadow: 0 10px 28px rgba(255,71,87,0.14); }
        .btn { border: none; border-radius: 12px; padding: 14px; font-weight: 700; font-size: 15px; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
        .btn-primary { background: var(--accent); color: #fff; box-shadow: 0 16px 40px rgba(255,71,87,0.25); }
        .btn-primary:hover { background: var(--accent-dark); transform: translateY(-1px); }
        .message { padding: 12px 14px; border-radius: 10px; font-size: 14px; border: 1px solid var(--border); margin-top: 12px; }
        .message.error { background: #fff1f2; color: #b91c1c; border-color: #fecdd3; }
        .message.success { background: #ecfdf3; color: #166534; border-color: #bbf7d0; }
        .meta-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; font-size: 13px; color: var(--muted); margin-top: 20px;   }
        .auth-link { color: var(--accent); font-weight: 700; text-decoration: none; }
    </style>
</head>
<body>
    <div class="shell">
        <section class="panel auth-card">
            <div>
                <h2>Create your account</h2>
                <p class="subtitle">It only takes a minute.</p>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="field">
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

                <div class="field">
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

                <div class="field">
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

                <div class="field">
                    <label for="phone">Phone Number</label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                        placeholder="+1234567890"
                    >
                </div>

                <div class="field" style="padding-left:0;">
                    <label for="address">Delivery Address</label>
                    <textarea 
                        id="address" 
                        name="address" 
                        rows="2"
                        placeholder="Your delivery address (optional)"
                    ><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                </div>

                <div class="field">
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

                <div class="field">
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

                <button type="submit" class="btn btn-primary">Create account</button>
            </form>

            <div class="meta-row">
                <span>Already registered?</span>
                <a class="auth-link" href="login.php">Sign in</a>
            </div>
        </section>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" defer></script>
    <script>
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
