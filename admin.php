<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: /admin_login.php');
    exit();
}

// Include PHPMailer for email notifications
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

// Database connection details
$host = 'localhost';
$dbname = 'employee_suggestions';
$username = 'root';
$password = 'Msik9agm3'; // Replace with your actual database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("لا يمكن الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /admin_login.php');
    exit();
}

// Handle accept/reject actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $suggestionId = $_POST['suggestion_id'];
    $action = $_POST['action'];
    $adminMessage = $_POST['admin_message'] ?? '';

    // Update suggestion status
    $stmt = $pdo->prepare("UPDATE suggestions SET status = ?, admin_message = ? WHERE id = ?");
    $stmt->execute([$action, $adminMessage, $suggestionId]);

    // Fetch suggestion details
    $stmt = $pdo->prepare("SELECT * FROM suggestions WHERE id = ?");
    $stmt->execute([$suggestionId]);
    $suggestion = $stmt->fetch();

    // Send email notification to the submitter
    if ($suggestion) {
        $userEmail = $suggestion['user_email'];
        $title = $suggestion['title'];

        // Send email using PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtpmail.shj.ae';
            $mail->SMTPAuth = true;
            $mail->Username = 'noreply@fra.shj.ae';
            $mail->Password = 'Agri@6637'; // Replace with your actual email password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            // Recipients
            $mail->setFrom('noreply@fra.shj.ae', 'Notification');
            $mail->addAddress($userEmail);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'تحديث على اقتراحك';

            if ($action == 'accepted') {
                $statusMessage = 'تم قبول اقتراحك.';
            } else {
                $statusMessage = 'تم رفض اقتراحك.';
            }

            $mail->Body = "عزيزي الموظف،<br><br>$statusMessage<br><br>الرسالة من المسؤول:<br>$adminMessage<br><br>مع تحيات قسم الاقتراحات.";

            $mail->send();

            // Store success message in session
            $_SESSION['success_message'] = "تم تحديث حالة الاقتراح وإرسال إشعار إلى الموظف.";
        } catch (Exception $e) {
            // Store error message in session
            $_SESSION['error_message'] = "فشل في إرسال البريد الإلكتروني. الخطأ: {$mail->ErrorInfo}";
        }
    }

    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle search and filter
$searchQuery = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Pagination settings
$limit = 10; // Number of suggestions per page
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $limit;

// Build the SQL query with search and filter
$sql = "SELECT * FROM suggestions WHERE 1=1";
$params = [];

if ($searchQuery) {
    $sql .= " AND (title LIKE :search OR suggestion_text LIKE :search OR user_email LIKE :search)";
    $params[':search'] = "%$searchQuery%";
}

if ($statusFilter) {
    $sql .= " AND status = :status";
    $params[':status'] = $statusFilter;
}

$sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$params[':limit'] = $limit;
$params[':offset'] = $offset;

// Prepare and execute the query
$stmt = $pdo->prepare($sql);
foreach ($params as $key => &$val) {
    if ($key == ':limit' || $key == ':offset') {
        $stmt->bindParam($key, $val, PDO::PARAM_INT);
    } else {
        $stmt->bindParam($key, $val);
    }
}
$stmt->execute();
$allSuggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$countSql = "SELECT COUNT(*) FROM suggestions WHERE 1=1";
$countParams = [];

if ($searchQuery) {
    $countSql .= " AND (title LIKE :search OR suggestion_text LIKE :search OR user_email LIKE :search)";
    $countParams[':search'] = "%$searchQuery%";
}

if ($statusFilter) {
    $countSql .= " AND status = :status";
    $countParams[':status'] = $statusFilter;
}

$countStmt = $pdo->prepare($countSql);
foreach ($countParams as $key => &$val) {
    $countStmt->bindParam($key, $val);
}
$countStmt->execute();
$totalSuggestions = $countStmt->fetchColumn();
$totalPages = ceil($totalSuggestions / $limit);

// Fetch analytics data
$analyticsStmt = $pdo->query("SELECT target_department, COUNT(*) AS count FROM suggestions GROUP BY target_department");
$analytics = $analyticsStmt->fetchAll(PDO::FETCH_ASSOC);

$activeUsersStmt = $pdo->query("SELECT user_email, COUNT(*) AS count FROM suggestions GROUP BY user_email ORDER BY count DESC LIMIT 5");
$activeUsers = $activeUsersStmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare messages for JavaScript
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $errorMessage = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>لوحة تحكم المسؤول</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600&display=swap" rel="stylesheet">
    <!-- Include Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* (Include your existing styles here) */

        body {
            font-family: 'Cairo', sans-serif;
            direction: rtl;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }

        .admin-header {
            background-color: #487C9F;
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }

        .admin-header h2 {
            margin: 0;
        }

        .logout-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: #E97A3B;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .container {
            padding: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .analytics-section {
            margin-bottom: 40px;
        }

        .analytics-section h3 {
            margin-bottom: 20px;
            color: #0B314A;
        }

        /* Analytics Cards Styles */
        .cards-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .analytics-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            flex: 1;
            min-width: 250px;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .card-icon {
            font-size: 40px;
            color: #487C9F;
            margin-left: 15px;
        }

        .card-content h4 {
            margin: 0;
            color: #0B314A;
            font-size: 18px;
            margin-bottom: 8px;
        }

        .card-content p {
            margin: 0;
            font-size: 24px;
            color: #333;
        }

        .card-content ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .card-content li {
            font-size: 14px;
            color: #555;
            margin-bottom: 5px;
        }

        .search-filter {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            gap: 10px;
        }

        .search-filter input, .search-filter select {
            padding: 8px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 6px;
            flex: 1;
        }

        .search-filter button {
            padding: 8px 16px;
            background: #487C9F;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        /* Suggestion List Styles */
        .suggestion-list {
            margin-bottom: 20px;
        }

        .suggestion-item {
            background: white;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .suggestion-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .suggestion-title {
            font-size: 16px;
            color: #0B314A;
            margin: 0;
        }

        .suggestion-meta {
            font-size: 14px;
            color: #777;
        }

        .status {
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 4px;
            text-align: center;
            display: inline-block;
            min-width: 80px;
        }

        .status.pending {
            background-color: #ff9800;
            color: white;
        }

        .status.accepted {
            background-color: #4BB543;
            color: white;
        }

        .status.rejected {
            background-color: #ff3b30;
            color: white;
        }

        .suggestion-content {
            display: none;
            margin-top: 10px;
            font-size: 14px;
            color: #555;
        }

        .toggle-content {
            cursor: pointer;
            color: #487C9F;
            font-size: 14px;
            margin-top: 10px;
            display: inline-block;
        }

        .action-buttons {
            margin-top: 10px;
        }

        .action-buttons button {
            margin-right: 5px;
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            color: white;
            font-size: 14px;
        }

        .accept-btn {
            background-color: #4BB543;
        }

        .reject-btn {
            background-color: #ff3b30;
        }

        .message-textarea {
            width: 100%;
            height: 80px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
            margin-bottom: 10px;
            resize: vertical;
            font-size: 14px;
        }

        .admin-message {
            background: #fff3cd;
            border-left: 4px solid #ffeeba;
            padding: 10px;
            margin-top: 10px;
            border-radius: 4px;
            font-size: 14px;
        }

        .attachment-link {
            color: #E97A3B;
            text-decoration: none;
            transition: color 0.2s ease;
            font-size: 14px;
        }

        .attachment-link:hover {
            color: #D86B35;
        }

        /* Comments Section */
        .comments-section {
            margin-top: 15px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 8px;
            font-size: 14px;
        }

        .comments-section .comment {
            margin-bottom: 10px;
        }

        .comments-section .comment-user {
            font-weight: bold;
            color: #0B314A;
            margin-right: 5px;
        }

        .comments-section .comment-text {
            color: #333;
        }

        .comments-section .comment-time {
            font-size: 12px;
            color: #888;
            margin-right: 10px;
        }

        .comments-section strong {
            display: block;
            margin-bottom: 10px;
            color: #0B314A;
        }

        /* Pagination Styles */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }

        .pagination a {
            padding: 8px 12px;
            background: #e0e0e0;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.2s;
        }

        .pagination a.active, .pagination a:hover {
            background: #487C9F;
            color: white;
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

        /* Responsive */
        @media (max-width: 768px) {
            .cards-container {
                flex-direction: column;
            }

            .search-filter {
                flex-direction: column;
                align-items: stretch;
            }

            .search-filter input, .search-filter select, .search-filter button {
                width: 100%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h2>لوحة تحكم المسؤول</h2>
        <form method="GET" action="">
            <button type="submit" name="logout" class="logout-btn">تسجيل الخروج</button>
        </form>
    </div>

    <div class="container">
        <!-- Analytics Section -->
        <div class="analytics-section">
            <h3>إحصائيات</h3>
            <div class="cards-container">
                <!-- Total Suggestions Card -->
                <div class="analytics-card">
                    <div class="card-icon"><i class="fas fa-chart-bar"></i></div>
                    <div class="card-content">
                        <h4>إجمالي الاقتراحات</h4>
                        <p><?php echo $totalSuggestions; ?></p>
                    </div>
                </div>

                <!-- Suggestions by Department Card -->
                <div class="analytics-card">
                    <div class="card-icon"><i class="fas fa-building"></i></div>
                    <div class="card-content">
                        <h4>الاقتراحات حسب الإدارة</h4>
                        <ul>
                            <?php foreach ($analytics as $data): ?>
                                <li><?php echo htmlspecialchars($data['target_department'] ?? '', ENT_QUOTES, 'UTF-8'); ?>: <?php echo $data['count']; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Most Active Users Card -->
                <div class="analytics-card">
                    <div class="card-icon"><i class="fas fa-users"></i></div>
                    <div class="card-content">
                        <h4>أكثر المستخدمين نشاطًا</h4>
                        <ul>
                            <?php foreach ($activeUsers as $user): ?>
                                <li><?php echo htmlspecialchars($user['user_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>: <?php echo $user['count']; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <form method="GET" action="" class="search-filter">
            <input type="text" name="search" placeholder="بحث..." value="<?php echo htmlspecialchars($searchQuery); ?>">
            <select name="status">
                <option value="">كل الحالات</option>
                <option value="pending" <?php if ($statusFilter == 'pending') echo 'selected'; ?>>قيد المراجعة</option>
                <option value="accepted" <?php if ($statusFilter == 'accepted') echo 'selected'; ?>>مقبول</option>
                <option value="rejected" <?php if ($statusFilter == 'rejected') echo 'selected'; ?>>مرفوض</option>
            </select>
            <button type="submit">بحث</button>
        </form>

        <!-- Suggestions List -->
        <div class="suggestion-list">
            <?php if ($allSuggestions): ?>
                <?php foreach ($allSuggestions as $suggestion): ?>
                    <div class="suggestion-item">
                        <div class="suggestion-header">
                            <h4 class="suggestion-title"><?php echo htmlspecialchars($suggestion['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h4>
                            <div class="suggestion-meta">
                                <span><?php echo htmlspecialchars($suggestion['user_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span> |
                                <span><?php echo date('Y-m-d', strtotime($suggestion['created_at'] ?? '')); ?></span> |
                                <span class="status <?php echo $suggestion['status']; ?>">
                                    <?php
                                        if ($suggestion['status'] == 'pending') {
                                            echo 'قيد المراجعة';
                                        } elseif ($suggestion['status'] == 'accepted') {
                                            echo 'مقبول';
                                        } else {
                                            echo 'مرفوض';
                                        }
                                    ?>
                                </span>
                            </div>
                        </div>
                        <div class="toggle-content">عرض التفاصيل</div>
                        <div class="suggestion-content">
                            <p><?php echo nl2br(htmlspecialchars($suggestion['suggestion_text'] ?? '', ENT_QUOTES, 'UTF-8')); ?></p>
                            <?php if ($suggestion['recommendations']): ?>
                                <h5>آلية تطبيق المقترح:</h5>
                                <p><?php echo nl2br(htmlspecialchars($suggestion['recommendations'] ?? '', ENT_QUOTES, 'UTF-8')); ?></p>
                            <?php endif; ?>
                            <?php if ($suggestion['file_path']): ?>
                                <div style="margin-top: 5px;">
                                    <a href="<?php echo htmlspecialchars($suggestion['file_path'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="attachment-link" target="_blank">
                                        <i class="fas fa-paperclip"></i> عرض المرفق
                                    </a>
                                </div>
                            <?php endif; ?>

                            <?php if ($suggestion['status'] == 'pending'): ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="suggestion_id" value="<?php echo $suggestion['id']; ?>">
                                    <textarea name="admin_message" class="message-textarea" placeholder="أدخل رسالة إلى الموظف"></textarea>
                                    <div class="action-buttons">
                                        <button type="submit" name="action" value="accepted" class="accept-btn">قبول</button>
                                        <button type="submit" name="action" value="rejected" class="reject-btn">رفض</button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="admin-message">
                                    <strong>رسالة المسؤول:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($suggestion['admin_message'] ?? '', ENT_QUOTES, 'UTF-8')); ?>
                                </div>
                            <?php endif; ?>

                            <!-- Display comments -->
                            <?php
                            // Fetch comments for the current suggestion
                            $stmtComments = $pdo->prepare("SELECT * FROM comments WHERE suggestion_id = ? ORDER BY created_at ASC");
                            $stmtComments->execute([$suggestion['id']]);
                            $comments = $stmtComments->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <?php if ($comments): ?>
                                <div class="comments-section">
                                    <strong>التعليقات:</strong>
                                    <?php foreach ($comments as $comment): ?>
                                        <div class="comment">
                                            <span class="comment-user"><?php echo htmlspecialchars($comment['user_email']); ?>:</span>
                                            <span class="comment-text"><?php echo htmlspecialchars($comment['comment_text']); ?></span>
                                            <span class="comment-time">(<?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?>)</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>لا توجد اقتراحات مطابقة للبحث.</p>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchQuery); ?>&status=<?php echo urlencode($statusFilter); ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
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
    </script>

    <!-- Your existing JavaScript code for toggling content -->
    <script>
        // Toggle Suggestion Content
        const toggles = document.querySelectorAll('.toggle-content');
        toggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                const content = this.nextElementSibling;
                if (content.style.display === 'block') {
                    content.style.display = 'none';
                    this.textContent = 'عرض التفاصيل';
                } else {
                    content.style.display = 'block';
                    this.textContent = 'إخفاء التفاصيل';
                }
            });
        });
    </script>
</body>
</html>
