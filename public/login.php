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
    <title>Login - Flavor POS</title>
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
        .shell { width: min(520px, 100%); }
        .panel { background: var(--card); border: 1px solid var(--border); border-radius: 18px; box-shadow: var(--shadow); padding: 36px; }
        h2 { margin: 0; font-size: 26px; letter-spacing: -0.01em; }
        .subtitle { color: var(--muted); margin: 6px 0 0; font-size: 15px; }
        form { display: grid; gap: 16px; margin-top: 18px; }
        label { display: block; margin-bottom: 6px; color: var(--muted); font-weight: 600; font-size: 13px; }
        .field { position: relative; }
        input {
            width: 100%; padding: 14px; border-radius: 12px; border: 1px solid var(--border);
            background: #f9fafb; color: var(--text); font-size: 15px; outline: none; transition: border 0.2s, box-shadow 0.2s;
        }
        input:focus { border-color: var(--accent); box-shadow: 0 10px 28px rgba(255,71,87,0.14); }
        .btn {
            border: none; border-radius: 12px; padding: 14px; font-weight: 700; font-size: 15px; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-primary { background: var(--accent); color: #fff; box-shadow: 0 16px 40px rgba(255,71,87,0.25); }
        .btn-primary:hover { background: var(--accent-dark); transform: translateY(-1px); }
        .meta-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; font-size: 13px; color: var(--muted);  margin-top: 20px;}
        .auth-link { color: var(--accent); font-weight: 700; text-decoration: none; }
        .message { padding: 12px 14px; border-radius: 10px; font-size: 14px; border: 1px solid var(--border); margin-top: 12px; }
        .message.error { background: #fff1f2; color: #b91c1c; border-color: #fecdd3; }
        .message.success { background: #ecfdf3; color: #166534; border-color: #bbf7d0; }
    </style>
</head>
<body>
    <div class="shell">
        <section class="panel auth-card">
            <div>
                <h2>Welcome back</h2>
                <p class="subtitle">Use your account to continue.</p>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="field">
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

                <div class="field">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        placeholder="Enter your password"
                    >
                </div>

                <button type="submit" class="btn btn-primary">Login</button>
            </form>

            <div class="meta-row">
                <span>New here?</span>
                <a class="auth-link" href="register.php">Create an account</a>
            </div>
        </section>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" defer></script>
</body>
</html>
