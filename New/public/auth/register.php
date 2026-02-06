<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (current_user()) {
    $redirect = current_user()['user_type'] === 'admin'
        ? base_path('admin/dashboard.php')
        : base_path('founder/dashboard.php');
    redirect($redirect);
}

$errors = [];
$fullName = '';
$email = '';
$userType = '';
$companyName = '';

if (is_post()) {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $userType = $_POST['user_type'] ?? '';
    $companyName = trim($_POST['company_name'] ?? '');

    if ($fullName === '') {
        $errors[] = 'Full name is required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (!in_array($userType, ['admin', 'founder'], true)) {
        $errors[] = 'Please select a valid account type.';
    }

    if ($userType === 'founder' && $companyName === '') {
        $errors[] = 'Company name is required for founders.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors && find_user_by_email($email)) {
        $errors[] = 'An account with that email already exists.';
    }

    if (!$errors) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare('INSERT INTO users (full_name, email, password_hash, user_type, company_name) VALUES (:full_name, :email, :password_hash, :user_type, :company_name)');
        $stmt->execute([
            'full_name' => $fullName,
            'email' => $email,
            'password_hash' => $passwordHash,
            'user_type' => $userType,
            'company_name' => $userType === 'founder' ? $companyName : null,
        ]);

        $userId = (int) db()->lastInsertId();
        $user = [
            'id' => $userId,
            'full_name' => $fullName,
            'email' => $email,
            'user_type' => $userType,
            'company_name' => $userType === 'founder' ? $companyName : null,
        ];

        login_user($user);

        $redirect = $userType === 'admin'
            ? base_path('admin/dashboard.php')
            : base_path('founder/dashboard.php');
        redirect($redirect);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo sanitize(app_name()); ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f1fa 0%, #e8ddf5 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .register-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(200, 150, 230, 0.15);
            width: 100%;
            max-width: 450px;
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

        input[type="text"],
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

        input:focus,
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

        .btn-register {
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

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(212, 165, 232, 0.3);
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #7d6b8f;
            font-size: 14px;
        }

        .login-link a {
            color: #c68edb;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #c68edb;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <a href="../index.php" class="back-link">← Back to Home</a>
        <div class="logo">💼</div>
        <h2>Create Account</h2>
        <p class="subtitle">Join our grant community</p>

        <?php if ($errors): ?>
            <div style="background-color:#fde8e8;border:1px solid #e8c4c4;color:#a84848;padding:12px;border-radius:10px;margin-bottom:20px;">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo sanitize($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="form-group">
                <label for="fullName">Full Name</label>
                <input type="text" id="fullName" name="full_name" placeholder="John Smith" required value="<?php echo sanitize($fullName); ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="you@example.com" required value="<?php echo sanitize($email); ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>

            <div class="form-group">
                <label for="confirmPassword">Confirm Password</label>
                <input type="password" id="confirmPassword" name="confirm_password" placeholder="••••••••" required>
            </div>

            <div class="form-group">
                <label for="userType">Register As</label>
                <select id="userType" name="user_type" required>
                    <option value="">Select Account Type</option>
                    <option value="admin" <?php echo $userType === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                    <option value="founder" <?php echo $userType === 'founder' ? 'selected' : ''; ?>>Startup Founder</option>
                </select>
            </div>

            <div class="form-group" id="companyGroup" style="<?php echo $userType === 'founder' ? '' : 'display: none;'; ?>">
                <label for="companyName">Company Name</label>
                <input type="text" id="companyName" name="company_name" placeholder="Your Startup Name" value="<?php echo sanitize($companyName); ?>">
            </div>

            <button type="submit" class="btn-register">Create Account</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
    <script>
        const userTypeSelect = document.getElementById('userType');
        const companyGroup = document.getElementById('companyGroup');
        userTypeSelect.addEventListener('change', function () {
            companyGroup.style.display = this.value === 'founder' ? 'block' : 'none';
        });
    </script>
</body>
</html>
