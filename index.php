<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

// Database connection details
$host = 'localhost';
$dbname = 'employee_suggestions';
$username = 'root';
$password = 'Msik9agm3'; // Replace with your actual database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password); // Added charset
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("لا يمكن الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// Check if the logout request is made
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// LDAP and Admin credentials
$ldap_host = "ldap://10.69.48.10";
$ldap_dn = "OU=FRA Users,OU=FRA,DC=fra,DC=internal,DC=shj,DC=ae";
$ldap_user_attr = "mail";
$ldap_bind_user = "CN=Administrator,CN=Users,DC=fra,DC=internal,DC=shj,DC=ae";
$ldap_bind_pass = '#FRA@1340!';

// Admin credentials (hardcoded)
$admin_username = 'admin23@fra.shj.ae'; // Replace with your admin username
$admin_password = 'admin123'; // Replace with your admin password

// Admin email address
$admin_email = 'suggestions@fra.shj.ae'; // Admin email for notifications

// LDAP authentication
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email_or_username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Check if the login is for admin
    if ($email_or_username === $admin_username && $password === $admin_password) {
        // Admin login successful
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $admin_username;
        header("Location: admin.php");
        exit();
    } else {
        // Proceed with LDAP authentication
        $ldap_conn = ldap_connect($ldap_host);
        if ($ldap_conn) {
            ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

            $ldap_bind = ldap_bind($ldap_conn, $ldap_bind_user, $ldap_bind_pass);
            if (!$ldap_bind) {
                echo "فشل في الاتصال بخادم LDAP: " . ldap_error($ldap_conn);
                exit();
            }

            $filter = "($ldap_user_attr=$email_or_username)";
            $search = ldap_search($ldap_conn, $ldap_dn, $filter);
            if (!$search) {
                echo "خطأ في بحث LDAP: " . ldap_error($ldap_conn);
                exit();
            }

            $entries = ldap_get_entries($ldap_conn, $search);
            if ($entries['count'] > 0) {
                $user_dn = $entries[0]['dn'];
                if (@ldap_bind($ldap_conn, $user_dn, $password)) {
                    $_SESSION['logged_in'] = true;
                    $_SESSION['username'] = $email_or_username;
                    header("Location: index.php");
                    exit();
                } else {
                    $login_error = "بيانات الاعتماد غير صحيحة. حاول مرة أخرى.";
                }
            } else {
                $login_error = "المستخدم غير موجود.";
            }

            ldap_close($ldap_conn);
        } else {
            $login_error = "تعذر الاتصال بخادم LDAP.";
        }
    }
}

// If admin is logged in, redirect to admin.php
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    header("Location: admin.php");
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['logged_in'])) {
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <!-- Existing head content -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اقتراحي - تسجيل الدخول</title>
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Cairo Font -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Include your styles -->
    <style>
        /* Login Page Styles */

        :root {
            --primary-bg: #4B8BB0;
            --accent-color: #FF8661;
            --gradient-start: #FF8F5D;
            --gradient-end: #E97A3B;
            --text-light: #FFFFFF;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Cairo', sans-serif;
            direction: rtl;
        }

        body {
            min-height: 100vh;
            background-color: var(--primary-bg);
            background-image: url('background2.png'); /* Check the path to ensure it's correct */
            background-size: cover;
            background-position: center;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            margin: 0;
            overflow: hidden;
            position: relative;
        }

        .logo-section {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .logo-section img {
            width: 220px;
            height: auto;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.2));
            display: block;
            max-width: 100%;
        }

        .wrapper {
            width: 100%;
            max-width: 500px;
            z-index: 2;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 45px;
            width: 100%;
            box-shadow: 
                0 25px 45px rgba(0, 0, 0, 0.2),
                0 2px 10px rgba(0, 0, 0, 0.1),
                inset 0 1px 1px rgba(255, 255, 255, 0.2);
            animation: containerFloat 1s ease-out forwards;
            transform-style: preserve-3d;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        @keyframes containerFloat {
            0% {
                opacity: 0;
                transform: translateY(30px) rotateX(10deg);
            }
            100% {
                opacity: 1;
                transform: translateY(0) rotateX(0);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 35px;
            position: relative;
        }

        .login-header::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(to right, var(--gradient-start), var(--gradient-end));
            border-radius: 2px;
        }

        .login-header h2 {
            font-size: 36px;
            color: var(--text-light);
            font-weight: 700;
            margin-bottom: 12px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 18px;
        }

        .input-group {
            position: relative;
            margin-bottom: 25px;
            width: 100%;
        }

        .input-group i {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
            font-size: 20px;
            transition: all 0.3s ease;
        }

        .input-group input {
            width: 100%;
            padding: 12px 55px 12px 25px; /* Adjusted padding */
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            color: var(--text-light);
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .input-group input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .input-group input:focus {
            border-color: var(--accent-color);
            background: rgba(255, 255, 255, 0.15);
            outline: none;
            box-shadow: 
                0 0 0 4px rgba(233, 122, 59, 0.1),
                0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .input-group input:focus + i {
            color: var(--accent-color);
            transform: translateY(-50%) scale(1.1);
        }

        button[type="submit"] {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            border: none;
            border-radius: 16px;
            color: white;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 
                0 10px 25px rgba(233, 122, 59, 0.4),
                0 5px 10px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            animation: buttonBreathing 3s ease-in-out infinite;
        }

        @keyframes buttonBreathing {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        button[type="submit"]::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.2),
                transparent
            );
            transition: 0.5s;
        }

        button[type="submit"]:hover::before {
            left: 100%;
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 
                0 15px 30px rgba(233, 122, 59, 0.4),
                0 8px 15px rgba(0, 0, 0, 0.15);
            background: linear-gradient(135deg, #E97A3B, #FF8F5D);
        }

        .error-message {
            background: rgba(255, 59, 48, 0.1);
            border: 1px solid rgba(255, 59, 48, 0.2);
            color: #ff3b30;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }

            .logo-section {
                top: 15px;
                right: 15px;
            }

            .logo-section img {
                width: 160px;
            }

            .login-header h2 {
                font-size: 28px;
            }

            .input-group input {
                padding: 10px 45px 10px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="logo-section">
        <img src="fra-logo1.png" alt="FRA Logo">
    </div>

    <div class="wrapper">
        <div class="login-container">
            <div class="login-header">
                <h2>اقتراحي</h2>
            </div>

            <?php if (isset($login_error)): ?>
                <div class="error-message">
                    <?php echo $login_error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" id="username" placeholder="مثال@fra.shj.ae" required>
                </div>

                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="password" placeholder="كلمة المرور" required>
                </div>

                <button type="submit" name="login">
                    <i class="fas fa-sign-in-alt"></i>
                    تسجيل الدخول
                </button>
            </form>
        </div>
    </div>
</body>
</html>
<?php
    exit();
}

// Handle Like Functionality
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['like_suggestion'])) {
    $suggestionId = $_POST['suggestion_id'];
    $userEmail = $_SESSION['username'];

    // Fetch the suggestion to check if it belongs to the user
    $stmt = $pdo->prepare("SELECT user_email FROM suggestions WHERE id = ?");
    $stmt->execute([$suggestionId]);
    $suggestion = $stmt->fetch();

    if ($suggestion && $suggestion['user_email'] !== $userEmail) {
        // Check if the user has already liked this suggestion
        $stmt = $pdo->prepare("SELECT * FROM likes WHERE suggestion_id = ? AND user_email = ?");
        $stmt->execute([$suggestionId, $userEmail]);
        $like = $stmt->fetch();

        if ($like) {
            // User has already liked; remove the like (unlike)
            $stmt = $pdo->prepare("DELETE FROM likes WHERE id = ?");
            $stmt->execute([$like['id']]);
        } else {
            // Add a new like
            $stmt = $pdo->prepare("INSERT INTO likes (suggestion_id, user_email) VALUES (?, ?)");
            $stmt->execute([$suggestionId, $userEmail]);
        }
    }

    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle Comment Functionality
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment_suggestion'])) {
    $suggestionId = $_POST['suggestion_id'];
    $commentText = $_POST['comment_text'] ?? '';
    $userEmail = $_SESSION['username'];

    if ($commentText) {
        $stmt = $pdo->prepare("INSERT INTO comments (suggestion_id, user_email, comment_text) VALUES (?, ?, ?)");
        $stmt->execute([$suggestionId, $userEmail, $commentText]);
    }

    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Form handling for submitting new suggestions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['login']) && !isset($_POST['like_suggestion']) && !isset($_POST['comment_suggestion'])) {
    $title = $_POST['title'] ?? '';
    $suggestionText = $_POST['suggestion_text'] ?? '';
    $targetDepartment = $_POST['target_department'] ?? '';
    $recommendations = $_POST['recommendations'] ?? '';
    $file = $_FILES['file_upload'];
    $userEmail = $_SESSION['username'];

    // Simple file upload handling
    $filePath = '';
    if ($file['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        $filePath = $uploadDir . basename($file['name']);

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        move_uploaded_file($file['tmp_name'], $filePath);
    }

    // Insert suggestion into the database
    $stmt = $pdo->prepare("INSERT INTO suggestions (user_email, title, suggestion_text, target_department, recommendations, file_path) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userEmail, $title, $suggestionText, $targetDepartment, $recommendations, $filePath]);

    // Prepare variables for email
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $currentYear = date('Y');

    // Send email acknowledgment to user
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->SMTPDebug = 0;  // Disable verbose debug output
        $mail->isSMTP();
        $mail->Host = 'smtpmail.shj.ae';
        $mail->SMTPAuth = true;
        $mail->Username = 'noreply@fra.shj.ae';
        $mail->Password = 'Agri@6637';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        // Recipients
        $mail->setFrom('noreply@fra.shj.ae', 'نظام اقتراحي');
        $mail->addAddress($userEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = '=?UTF-8?B?' . base64_encode('تأكيد تقديم المقترح') . '?=';

        // Email Template
        $emailBody = <<<EOT
        <!DOCTYPE html>
        <html lang="ar" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>تأكيد تقديم المقترح</title>
        </head>
        <body style="margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; background-color: #f7f7f7; direction: rtl; text-align: right;">
            <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 0; padding: 0; background-color: #f7f7f7;">
                <tr>
                    <td align="center" style="padding: 20px 0;">
                        <!-- Email Container -->
                        <table role="presentation" style="width: 600px; border-collapse: collapse; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">
                            <!-- Header -->
                            <tr>
                                <td style="background-color: #487C9F; padding: 20px; text-align: center; color: #ffffff; font-size: 20px; font-weight: bold;">
                                    نظام اقتراحي
                                </td>
                            </tr>

                            <!-- Body -->
                            <tr>
                                <td style="padding: 30px; font-size: 16px; color: #333333; line-height: 1.8; text-align: right;">
                                    <p style="margin: 0 0 20px;">عزيزي الموظف،</p>
                                    <p style="margin: 0 0 20px;">شكراً لمشاركتكم بمقترح "<span style="color: #487C9F; font-weight: bold;">{$safeTitle}</span>".</p>
                                    <p style="margin: 0 0 20px;">سيتم دراسته والرد عليكم في أقرب فرصة ممكنة.</p>

                                    <!-- Status Box -->
                                    <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 20px 0; background-color: #f0f7fb; padding: 20px; border-radius: 6px; border-right: 4px solid #487C9F;">
                                        <tr>
                                            <td style="font-size: 14px; color: #487C9F;">
                                                <strong>حالة المقترح:</strong> قيد المراجعة
                                            </td>
                                        </tr>
                                    </table>

                                    <p style="margin: 0;">مع تحيات،<br>هيئة الشارقة للثروة السمكية</p>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style="background-color: #E97A3B; padding: 15px; text-align: center; color: #ffffff; font-size: 14px;">
                                    © 2024 FRA IT Department
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        EOT;
        $mail->Body = $emailBody;

        // Set plain text version
        $mail->AltBody = "عزيزي الموظف،\n\n" .
                        "شكراً لمشاركتكم بمقترح \"$safeTitle\". " .
                        "سيتم دراسته والرد عليكم في أقرب فرصة ممكنة.\n\n" .
                        "مع تحيات،\nهيئة الشارقة للثروة السمكية";

        $mail->send();
        $_SESSION['success_message'] = "تم إرسال تأكيد بالبريد الإلكتروني. تم تقديم الاقتراح بنجاح.";
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        $_SESSION['error_message'] = "حدث خطأ أثناء إرسال البريد الإلكتروني. يرجى المحاولة مرة أخرى لاحقاً.";
    }

    // ============================
    // New Code: Send Notification to Admin
    // ============================
    try {
        // Create a new PHPMailer instance for admin notification
        $adminMail = new PHPMailer(true);

        // Server settings
        $adminMail->SMTPDebug = 0;  // Disable verbose debug output
        $adminMail->isSMTP();
        $adminMail->Host = 'smtpmail.shj.ae';
        $adminMail->SMTPAuth = true;
        $adminMail->Username = 'noreply@fra.shj.ae';
        $adminMail->Password = 'Agri@6637';
        $adminMail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $adminMail->Port = 587;
        $adminMail->CharSet = 'UTF-8';

        // Recipients
        $adminMail->setFrom('noreply@fra.shj.ae', 'نظام اقتراحي');
        $adminMail->addAddress($admin_email);

        // Content
        $adminMail->isHTML(true);
        $adminMail->Subject = '=?UTF-8?B?' . base64_encode('تم تقديم مقترح جديد') . '?=';

        // Admin Email Body Template
        $adminEmailBody = <<<EOT
        <!DOCTYPE html>
        <html lang="ar" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>مقترح جديد تم تقديمه</title>
        </head>
        <body style="margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; background-color: #f7f7f7; direction: rtl; text-align: right;">
            <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 0; padding: 0; background-color: #f7f7f7;">
                <tr>
                    <td align="center" style="padding: 20px 0;">
                        <!-- Email Container -->
                        <table role="presentation" style="width: 600px; border-collapse: collapse; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">
                            <!-- Header -->
                            <tr>
                                <td style="background-color: #487C9F; padding: 20px; text-align: center; color: #ffffff; font-size: 20px; font-weight: bold;">
                                    نظام اقتراحي
                                </td>
                            </tr>

                            <!-- Body -->
                            <tr>
                                <td style="padding: 30px; font-size: 16px; color: #333333; line-height: 1.8; text-align: right;">
                                    <p style="margin: 0 0 20px;">عزيزي المدير،</p>
                                    <p style="margin: 0 0 20px;">تم تقديم مقترح جديد من قبل <strong>{$userEmail}</strong>.</p>
                                    <p style="margin: 0 0 20px;"><strong>عنوان المقترح:</strong> {$safeTitle}</p>
                                    <p style="margin: 0 0 20px;"><strong>تفاصيل المقترح:</strong> {$suggestionText}</p>
                                    <p style="margin: 0 0 20px;"><strong>الإدارة المعنية:</strong> {$targetDepartment}</p>
                                    <p style="margin: 0 0 20px;"><strong>آلية التطبيق:</strong> {$recommendations}</p>
        EOT;

        if ($filePath) {
            $adminEmailBody .= "<p style=\"margin: 0 0 20px;\"><strong>المرفقات:</strong> <a href=\"{$filePath}\" style=\"color: #487C9F; text-decoration: none;\" target=\"_blank\">عرض المرفق</a></p>";
        }

        $adminEmailBody .= <<<EOT
                                    <p style="margin: 0;">يرجى مراجعة المقترح واتخاذ الإجراءات اللازمة.</p>
                                    <p style="margin: 0;">مع تحيات،<br>نظام اقتراحي</p>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style="background-color: #E97A3B; padding: 15px; text-align: center; color: #ffffff; font-size: 14px;">
                                    © 2024 FRA IT Department
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        EOT;

        $adminMail->Body = $adminEmailBody;

        // Set plain text version
        $adminMail->AltBody = "عزيزي المدير،\n\n" .
                                "تم تقديم مقترح جديد من قبل {$userEmail}.\n\n" .
                                "عنوان المقترح: {$safeTitle}\n" .
                                "تفاصيل المقترح: {$suggestionText}\n" .
                                "الإدارة المعنية: {$targetDepartment}\n" .
                                "آلية التطبيق: {$recommendations}\n" .
                                ($filePath ? "المرفقات: {$filePath}\n" : "") .
                                "\nيرجى مراجعة المقترح واتخاذ الإجراءات اللازمة.\n\n" .
                                "مع تحيات،\nنظام اقتراحي";

        $adminMail->send();
    } catch (Exception $e) {
        error_log("Admin notification email failed: " . $adminMail->ErrorInfo);
        // Optionally, you can set an error message for the user or admin
    }

    // ============================

    // Store messages in session before redirect
    if (!headers_sent()) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "<script>window.location.href='" . $_SERVER['PHP_SELF'] . "';</script>";
        exit();
    }
}

// Prepare messages for JavaScript
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $errorMessage = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Fetch user's own suggestions
$userEmail = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT * FROM suggestions WHERE user_email = ? ORDER BY created_at DESC");
$stmt->execute([$userEmail]);
$userSuggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch suggestions from other users
$stmt = $pdo->prepare("
    SELECT s.*, 
        (SELECT COUNT(*) FROM likes WHERE suggestion_id = s.id) AS like_count,
        (SELECT COUNT(*) FROM likes WHERE suggestion_id = s.id AND user_email = ?) AS user_liked
    FROM suggestions s
    WHERE s.user_email != ?
    ORDER BY s.created_at DESC
");
$stmt->execute([$userEmail, $userEmail]);
$otherSuggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>اقتراحي</title>
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Cairo Font -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* General Styles */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Cairo', sans-serif;
            direction: rtl;
        }

        body {
            min-height: 100vh;
            background: #E9F8FA;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
            animation: backgroundFade 5s ease-in-out infinite alternate;
        }

        @keyframes backgroundFade {
            0% { background: #E9F8FA; }
            100% { background: #487C9F; }
        }

        .logout-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            padding: 6px 12px;
            background: #E97A3B;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease;
            animation: buttonGlow 2s infinite alternate;
        }

        @keyframes buttonGlow {
            from { box-shadow: 0 0 5px #E97A3B; }
            to { box-shadow: 0 0 15px #E97A3B; }
        }

        .main-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            max-width: 1400px;
            gap: 30px;
        }

        .content-wrapper {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            gap: 20px;
            width: 100%;
        }

        .feed-section {
            background: white;
            border-radius: 20px;
            padding: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            flex: 1;
            max-width: 300px;
            max-height: 700px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .form-section {
            background: white;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            flex: 2;
            max-width: 600px;
            animation: slideIn 1s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-header h1, .feed-header {
            font-size: 24px;
            font-weight: 600;
            color: #0B314A;
            margin-bottom: 16px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #0B314A;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            font-size: 14px;
            color: #333;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .form-control:focus {
            border-color: #487C9F;
            outline: none;
            box-shadow: 0 0 0 3px rgba(72, 124, 159, 0.2);
        }

        .form-control option:hover {
            background-color: #E97A3B;
            color: white;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #E97A3B, #D86B35);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(233, 122, 59, 0.4);
            animation: pulseEffect 2s infinite alternate;
        }

        @keyframes pulseEffect {
            from { transform: scale(1); }
            to { transform: scale(1.05); }
        }

        .submit-btn:hover {
            background: #D86B35;
            transform: translateY(-2px);
        }

        .feed-header {
            background: #487C9F;
            color: white;
            padding: 16px;
            font-size: 18px;
            font-weight: bold;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }

        .feed-items-wrapper {
            overflow-y: auto;
            flex-grow: 1;
            padding: 16px;
            scrollbar-width: thin;
            scrollbar-color: #487C9F transparent;
        }

        .feed-items-wrapper::-webkit-scrollbar {
            width: 8px;
        }

        .feed-items-wrapper::-webkit-scrollbar-thumb {
            background-color: #487C9F;
            border-radius: 4px;
        }

        .feed-item {
            background: #E9F8FA;
            border-radius: 12px;
            margin-bottom: 16px;
            padding: 16px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .feed-item:hover {
            background: #d8f0f3;
        }

        .feed-item-header {
            color: #0B314A;
            font-size: 16px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .suggestion-status {
            font-size: 14px;
            color: #888;
            margin-right: 10px;
        }

        .feed-item-content {
            color: #333;
            margin-top: 10px;
            font-size: 14px;
        }

        .recommendation-section {
            background: #f0f8ff;
            border-radius: 8px;
            padding: 10px;
            margin-top: 10px;
            font-size: 13px;
        }

        .admin-message {
            background: #fff3cd;
            border-left: 4px solid #ffeeba;
            padding: 10px;
            margin-top: 10px;
            border-radius: 4px;
            font-size: 14px;
        }

        .feed-item-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            color: #487C9F;
            font-size: 12px;
        }

        .attachment-link {
            color: #E97A3B;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .attachment-link:hover {
            color: #D86B35;
        }

        /* Notification Popup Styles */
        .notification-popup {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #4BB543; /* Green color for success */
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.4s ease, transform 0.4s ease;
        }

        .notification-popup.error {
            background-color: #ff3b30; /* Red color for error */
        }

        .notification-popup.show {
            opacity: 1;
            transform: translateY(0);
        }

        /* Like Button Styles */
        .like-section {
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .like-button {
            background: none;
            border: none;
            color: #E97A3B;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: color 0.2s ease;
        }

        .like-button.liked {
            color: #D86B35;
        }

        .like-button:hover {
            color: #D86B35;
        }

        .like-count {
            font-size: 14px;
            color: #333;
        }

        /* Comments Section Styles */
        .comments-section {
            margin-top: 10px;
            border-top: 1px solid #e1e1e1;
            padding-top: 10px;
        }

        .comment {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .comment-user {
            font-weight: bold;
            color: #487C9F;
            margin-right: 5px;
        }

        .comment-text {
            color: #333;
        }

        .comment-form {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }

        .comment-input {
            flex: 1;
            padding: 6px 8px;
            border: 1px solid #e1e1e1;
            border-radius: 4px;
            font-size: 14px;
        }

        .comment-button {
            padding: 6px 12px;
            background: #E97A3B;
            color: white;
            border: none;
            border-radius: 4px;
            margin-left: 8px;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .comment-button:hover {
            background: #D86B35;
        }

        /* Modal Styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5); /* Black with opacity */
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto; /* 15% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 12px;
            position: relative;
            animation: fadeInModal 0.3s ease;
        }

        @keyframes fadeInModal {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .close-modal {
            color: #aaa;
            position: absolute;
            top: 10px;
            left: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .close-modal:hover,
        .close-modal:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }

        .modal-header {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #0B314A;
        }

        .modal-body {
            font-size: 16px;
            color: #333;
        }

        .modal-footer {
            margin-top: 20px;
            font-size: 14px;
            color: #487C9F;
            display: flex;
            justify-content: space-between;
        }

        .modal .attachment-link {
            font-size: 16px;
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .content-wrapper {
                flex-direction: column;
                align-items: center;
            }

            .feed-section {
                max-width: none;
                width: 100%;
                margin-top: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Logout button -->
    <form method="GET" action="">
        <button type="submit" name="logout" class="logout-btn">↪ تسجيل الخروج</button>
    </form>

    <div class="main-container">
        <div class="content-wrapper">
            <!-- User's Own Suggestions Feed -->
            <div class="feed-section">
                <h2 class="feed-header">اقتراحاتي</h2>
                <div class="feed-items-wrapper">
                    <?php if ($userSuggestions): foreach ($userSuggestions as $suggestion): ?>
                        <div class="feed-item" data-id="<?php echo $suggestion['id']; ?>" data-title="<?php echo htmlspecialchars($suggestion['title'] ?? ''); ?>" data-content="<?php echo htmlspecialchars($suggestion['suggestion_text'] ?? ''); ?>" data-recommendations="<?php echo htmlspecialchars($suggestion['recommendations'] ?? ''); ?>" data-date="<?php echo date('F j, Y', strtotime($suggestion['created_at'])); ?>" data-file="<?php echo htmlspecialchars($suggestion['file_path'] ?? ''); ?>" data-admin-message="<?php echo htmlspecialchars($suggestion['admin_message'] ?? ''); ?>" data-status="<?php echo $suggestion['status']; ?>">
                            <div class="feed-item-header">
                                <?php echo htmlspecialchars($suggestion['title'] ?? ''); ?>
                                <span class="suggestion-status">
                                    (<?php
                                        if ($suggestion['status'] == 'pending') {
                                            echo 'قيد المراجعة';
                                        } elseif ($suggestion['status'] == 'accepted') {
                                            echo 'مقبول';
                                        } else {
                                            echo 'مرفوض';
                                        }
                                    ?>)
                                </span>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <p style="text-align: center; color: #487C9F;">لا توجد اقتراحات حتى الآن.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Your form section -->
            <div class="form-section">
                <div class="form-header">
                    <h1>تقديم مقترح جديد</h1>
                </div>
                <form action="" method="POST" enctype="multipart/form-data">
                    <!-- Form fields -->
                    <div class="form-group">
                        <label>العنوان</label>
                        <input type="text" class="form-control" name="title" placeholder="أدخل عنوان المقترح" required>
                    </div>

                    <div class="form-group">
                        <label>تفاصيل المقترح</label>
                        <textarea class="form-control" name="suggestion_text" placeholder="تفاصيل المقترح" required></textarea>
                    </div>

                    <div class="form-group">
                        <label>الإدارة المعنية بالمقترح</label>
                        <select class="form-control" name="target_department" required>
                            <option value="" disabled selected>اختر الإدارة</option>
                            <option value="مكتب الرئيس">مكتب رئيس الهيئة</option>
                            <option value="الخدمات الداعمة">إدارة الخدمات المساندة</option>
                            <option value="قسم تقنية المعلومات">إدارة تقنية المعلومات</option>
                            <option value="الصحة والتصاريح">إدارة التصاريح والرقابة الصحية</option>
                            <option value="القانوني">مكتب الشؤون القانونية</option>
                            <option value="GCD">إدارة الإتصال الحكومي</option>
                            <option value="director">مكتب مدير الهيئة</option>
                            <option value="fishresource">إدارة الثروة السمكية</option>
                            <option value="corpdev">مكتب التطوير المؤسسي</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>آلية تطبيق المقترح</label>
                        <textarea class="form-control" name="recommendations" placeholder="(اختياري)"></textarea>
                    </div>

                    <div class="form-group">
                        <label>الملفات المرفقة (اختياري)</label>
                        <input type="file" name="file_upload" class="form-control file-input">
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> ارسال المقترح
                    </button>
                </form>
            </div>

            <!-- Suggestions from Other Users Feed -->
            <div class="feed-section">
                <h2 class="feed-header">اقتراحات الزملاء</h2>
                <div class="feed-items-wrapper">
                    <?php if ($otherSuggestions): foreach ($otherSuggestions as $suggestion): ?>
                        <?php
                        // Fetch comments for this suggestion
                        $stmtComments = $pdo->prepare("SELECT * FROM comments WHERE suggestion_id = ? ORDER BY created_at ASC");
                        $stmtComments->execute([$suggestion['id']]);
                        $comments = $stmtComments->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <div class="feed-item" data-id="<?php echo $suggestion['id']; ?>" data-title="<?php echo htmlspecialchars($suggestion['title'] ?? ''); ?>" data-content="<?php echo htmlspecialchars($suggestion['suggestion_text'] ?? ''); ?>" data-recommendations="<?php echo htmlspecialchars($suggestion['recommendations'] ?? ''); ?>" data-date="<?php echo date('F j, Y', strtotime($suggestion['created_at'])); ?>" data-file="<?php echo htmlspecialchars($suggestion['file_path'] ?? ''); ?>">
                            <div class="feed-item-header">
                                <?php echo htmlspecialchars($suggestion['title'] ?? ''); ?>
                            </div>
                            <div class="like-section" onclick="event.stopPropagation();">
                                <form method="POST" action="">
                                    <input type="hidden" name="suggestion_id" value="<?php echo $suggestion['id']; ?>">
                                    <button type="submit" name="like_suggestion" class="like-button <?php echo $suggestion['user_liked'] ? 'liked' : ''; ?>">
                                        <i class="fas fa-thumbs-up"></i>
                                        <?php echo $suggestion['user_liked'] ? 'إلغاء الإعجاب' : 'إعجاب'; ?>
                                    </button>
                                    <span class="like-count">(<?php echo $suggestion['like_count']; ?>)</span>
                                </form>
                            </div>

                            <!-- Comments Section -->
                            <div class="comments-section" onclick="event.stopPropagation();">
                                <?php if ($comments): ?>
                                    <?php foreach ($comments as $comment): ?>
                                        <div class="comment">
                                            <span class="comment-user">مستخدم:</span>
                                            <span class="comment-text"><?php echo htmlspecialchars($comment['comment_text']); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <!-- Comment Form -->
                                <form method="POST" action="" class="comment-form" onclick="event.stopPropagation();">
                                    <input type="hidden" name="suggestion_id" value="<?php echo $suggestion['id']; ?>">
                                    <input type="text" name="comment_text" class="comment-input" placeholder="اكتب تعليق..." required>
                                    <button type="submit" name="comment_suggestion" class="comment-button">تعليق</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <p style="text-align: center; color: #487C9F;">لا توجد اقتراحات حتى الآن.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Suggestion Details -->
    <div id="suggestionModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div class="modal-header"></div>
            <div class="modal-body"></div>
            <div class="modal-footer"></div>
        </div>
    </div>

    <!-- Notification Popup Script -->
    <script>
    // Pass PHP messages to JavaScript variables
    <?php if (isset($successMessage)): ?>
        var successMessage = "<?php echo addslashes($successMessage); ?>";
    <?php endif; ?>
    <?php if (isset($errorMessage)): ?>
        var errorMessage = "<?php echo addslashes($errorMessage); ?>";
    <?php endif; ?>

    // Function to show notification
    function showNotification(message, isError = false) {
        var notification = document.createElement('div');
        notification.className = 'notification-popup' + (isError ? ' error' : '');
        notification.innerText = message;
        document.body.appendChild(notification);

        // Show the notification
        setTimeout(function() {
            notification.classList.add('show');
        }, 100);

        // Hide the notification after 5 seconds
        setTimeout(function() {
            notification.classList.remove('show');
            // Remove the notification from DOM after transition
            setTimeout(function() {
                document.body.removeChild(notification);
            }, 400);
        }, 5000);
    }

    // Display the notification if messages are set
    if (typeof successMessage !== 'undefined') {
        showNotification(successMessage);
    }
    if (typeof errorMessage !== 'undefined') {
        showNotification(errorMessage, true);
    }

    // Modal functionality
    var modal = document.getElementById('suggestionModal');
    var modalHeader = modal.querySelector('.modal-header');
    var modalBody = modal.querySelector('.modal-body');
    var modalFooter = modal.querySelector('.modal-footer');
    var closeModalBtn = modal.querySelector('.close-modal');

    // Function to open modal with suggestion details
    function openModal(event) {
        var target = event.currentTarget;
        var title = target.getAttribute('data-title');
        var content = target.getAttribute('data-content');
        var recommendations = target.getAttribute('data-recommendations');
        var date = target.getAttribute('data-date');
        var filePath = target.getAttribute('data-file');
        var adminMessage = target.getAttribute('data-admin-message');
        var status = target.getAttribute('data-status');

        modalHeader.innerText = title;
        modalBody.innerHTML = '<p>' + content.replace(/\n/g, '<br>') + '</p>';

        if (recommendations) {
            modalBody.innerHTML += '<div class="recommendation-section"><strong>التوصيات:</strong><br>' + recommendations.replace(/\n/g, '<br>') + '</div>';
        }

        if (adminMessage && status !== 'pending') {
            modalBody.innerHTML += '<div class="admin-message"><strong>رسالة المسؤول:</strong><br>' + adminMessage.replace(/\n/g, '<br>') + '</div>';
        }

        var footerContent = '<span>' + date + '</span>';

        if (filePath) {
            footerContent += '<a href="' + filePath + '" class="attachment-link" target="_blank"><i class="fas fa-paperclip"></i> عرض المستند</a>';
        }

        modalFooter.innerHTML = footerContent;

        modal.style.display = 'block';
    }

    // Close modal function
    function closeModal() {
        modal.style.display = 'none';
    }

    // Event listeners for feed items
    var feedItems = document.querySelectorAll('.feed-item');
    feedItems.forEach(function(item) {
        item.addEventListener('click', openModal);
    });

    // Close modal when clicking on the close button
    closeModalBtn.addEventListener('click', closeModal);

    // Close modal when clicking outside the modal content
    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            closeModal();
        }
    });
    </script>
</body>
</html>