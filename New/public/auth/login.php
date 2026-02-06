<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (current_user()) {
    $redirect = current_user()['user_type'] === 'admin'
        ? base_path('admin/dashboard.php')
        : base_path('founder/dashboard.php');
    redirect($redirect);
}

$errors = [];
$email = '';
$userType = '';

if (is_post()) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $userType = $_POST['user_type'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if (!$errors) {
        $user = find_user_by_email($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Invalid credentials. Please try again.';
        } elseif ($userType && $user['user_type'] !== $userType) {
            $errors[] = 'Account type does not match the selected option.';
        } else {
            login_user($user);
            $redirect = $user['user_type'] === 'admin'
                ? base_path('admin/dashboard.php')
                : base_path('founder/dashboard.php');
            redirect($redirect);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo sanitize(app_name()); ?></title>
    <link rel="stylesheet" href="../css/shared.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f1fa 0%, #e8ddf5 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(200, 150, 230, 0.15);
            width: 100%;
            max-width: 420px;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo {
            text-align: center;
            font-size: 40px;
            margin-bottom: 15px;
        }

        h2 {
            text-align: center;
            color: #5a3fa3;
            margin-bottom: 8px;
            font-size: 26px;
            font-weight: 700;
        }

        .subtitle {
            text-align: center;
            color: #7d6b8f;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #5a3fa3;
            font-weight: 600;
            font-size: 14px;
        }

        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e8d9f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        input[type="email"]:focus,
        input[type="password"]:focus,
        select:focus {
            outline: none;
            border-color: #d4a5e8;
            box-shadow: 0 0 0 3px rgba(212, 165, 232, 0.1);
        }

        select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 20px;
            padding-right: 40px;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #d4a5e8 0%, #c68edb 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(212, 165, 232, 0.3);
        }

        .error-message {
            background-color: #fde8e8;
            color: #a84848;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
            border: 1px solid #e8c4c4;
        }

        .signup-link {
            text-align: center;
            margin-top: 20px;
            color: #7d6b8f;
            font-size: 14px;
        }

        .signup-link a {
            color: #c68edb;
            text-decoration: none;
            font-weight: 600;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #c68edb;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <a href="../index.php" class="back-link">← Back to Home</a>
        <div class="logo">💼</div>
        <h2>Welcome Back</h2>
        <p class="subtitle">Login to your account</p>

        <?php if ($errors): ?>
            <div class="error-message" style="display:block;">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo sanitize($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="you@example.com" required value="<?php echo sanitize($email); ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>

            <div class="form-group">
                <label for="userType">Login As</label>
                <select id="userType" name="user_type" required>
                    <option value="">Select User Type</option>
                    <option value="admin" <?php echo $userType === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                    <option value="founder" <?php echo $userType === 'founder' ? 'selected' : ''; ?>>Startup Founder</option>
                </select>
            </div>

            <button type="submit" class="btn-login">Login</button>
        </form>

        <div class="signup-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</body>
</html>
