<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/../privée/database.php';
use Privee\Database;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: views/login.html');
    exit;
}

try {
    $pdo = Database::getPdo();
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        header('Location: views/login.html?error=1');
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'];
        header('Location: events.php');
        exit;
    } else {
        header('Location: views/login.html?error=1');
        exit;
    }
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
    exit;
}