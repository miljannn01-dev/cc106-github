<?php
require_once __DIR__ . '/includes/bootstrap.php';
$user = current_user();
$dashboardLink = $user
    ? ($user['user_type'] === 'admin' ? base_path('admin/dashboard.php') : base_path('founder/dashboard.php'))
    : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitize(app_name()); ?> - Startup Grant Application</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f1fa 0%, #e8ddf5 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .welcome-container {
            background: white;
            padding: 60px 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(200, 150, 230, 0.15);
            text-align: center;
            max-width: 650px;
            width: 90%;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo { font-size: 52px; margin-bottom: 20px; }

        h1 {
            font-size: 38px;
            margin-bottom: 12px;
            color: #5a3fa3;
            font-weight: 700;
        }

        .subtitle {
            color: #7d6b8f;
            font-size: 16px;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 50px;
        }

        .btn {
            padding: 14px 35px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #d4a5e8 0%, #c68edb 100%);
            color: white;
            box-shadow: 0 5px 20px rgba(212, 165, 232, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(212, 165, 232, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #c68edb;
            border: 2px solid #d4a5e8;
        }

        .btn-secondary:hover {
            background: #f3e9f8;
            transform: translateY(-3px);
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-top: 40px;
        }

        .feature {
            padding: 25px;
            background: linear-gradient(135deg, #f9f5fc 0%, #f3e9f8 100%);
            border-radius: 15px;
            border: 1px solid #e8d9f0;
            transition: all 0.3s ease;
        }

        .feature:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(212, 165, 232, 0.15);
        }

        .feature-icon { font-size: 32px; margin-bottom: 10px; }

        .feature-title {
            font-weight: 600;
            color: #5a3fa3;
            margin-bottom: 8px;
            font-size: 15px;
        }

        .feature-desc {
            font-size: 13px;
            color: #7d6b8f;
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <div class="logo">💼</div>
        <h1><?php echo sanitize(app_name()); ?></h1>
        <p class="subtitle">Empowering startups through strategic funding. Discover opportunities, apply with ease, and grow your business.</p>
        
        <div class="button-group">
            <?php if ($user): ?>
                <a href="<?php echo $dashboardLink; ?>" class="btn btn-primary">Go to Dashboard</a>
                <a href="<?php echo base_path('auth/logout.php'); ?>" class="btn btn-secondary">Logout</a>
            <?php else: ?>
                <a href="<?php echo base_path('auth/login.php'); ?>" class="btn btn-primary">Login</a>
                <a href="<?php echo base_path('auth/register.php'); ?>" class="btn btn-secondary">Register</a>
            <?php endif; ?>
        </div>

        <div class="features">
            <div class="feature">
                <div class="feature-icon">🚀</div>
                <div class="feature-title">For Startups</div>
                <div class="feature-desc">Discover and apply for tailored opportunities</div>
            </div>
            <div class="feature">
                <div class="feature-icon">👨‍💼</div>
                <div class="feature-title">For Admins</div>
                <div class="feature-desc">Create and manage grant programs</div>
            </div>
            <div class="feature">
                <div class="feature-icon">📊</div>
                <div class="feature-title">Real-time</div>
                <div class="feature-desc">Track applications instantly</div>
            </div>
            <div class="feature">
                <div class="feature-icon">🔒</div>
                <div class="feature-title">Secure</div>
                <div class="feature-desc">Your data is always protected</div>
            </div>
        </div>
    </div>
</body>
</html>
