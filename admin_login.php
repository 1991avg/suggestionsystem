<?php
session_start();

// Check if admin is already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    header('Location: admin.php');
    exit();
}

// Handle login form submission
// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $admin_username = 'admin23@fra.shj.ae';
    $admin_password = 'admin123';

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === $admin_username && $password === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['user_role'] = 'admin'; // Add role assignment here
        header('Location: admin.php');
        exit();
    } else {
        $login_error = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
    }
}

?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تسجيل دخول المسؤول</title>
    <!-- Include your styles -->
    <style>
        /* Simple styles for the admin login page */
        body {
            font-family: 'Cairo', sans-serif;
            direction: rtl;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            width: 400px;
        }

        .login-container h2 {
            margin-bottom: 30px;
            text-align: center;
            color: #0B314A;
        }

        .login-container label {
            display: block;
            margin-bottom: 8px;
            color: #333;
        }

        .login-container input {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
        }

        .login-container button {
            width: 100%;
            padding: 12px;
            background-color: #487C9F;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }

        .login-container button:hover {
            background-color: #3a6d85;
        }

        .error-message {
            color: red;
            margin-bottom: 20px;
            text-align: center;
        }

    </style>
</head>
<body>
    <div class="login-container">
        <h2>تسجيل دخول المسؤول</h2>
        <?php if (isset($login_error)): ?>
            <div class="error-message"><?php echo $login_error; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <label>اسم المستخدم:</label>
            <input type="text" name="username" required>
            <label>كلمة المرور:</label>
            <input type="password" name="password" required>
            <button type="submit">تسجيل الدخول</button>
        </form>
    </div>
</body>
</html>
