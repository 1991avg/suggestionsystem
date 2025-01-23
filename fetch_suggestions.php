<?php
session_start();

// Database connection details
$host = 'localhost';
$dbname = 'employee_suggestions';
$username = 'root';
$password = 'YOUR_DB_PASSWORD'; // Replace with your actual database password

// Establish database connection using PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
} catch (PDOException $e) {
    die("Database connection failed.");
}

$userEmail = $_SESSION['username'];

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

// Return the data as JSON
echo json_encode($otherSuggestions);
?>
