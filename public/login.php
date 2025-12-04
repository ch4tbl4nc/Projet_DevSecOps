<?php
require_once __DIR__ . '/security-headers.php';
session_start();

require_once '/var/www/private/database.php';
use Privee\Database;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: views/login.php');
    exit;
}

// Vérification CSRF
$csrf_token = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrf_token)) {
    header('Location: views/login.php?error=csrf');
    exit;
}

try {
    $pdo = Database::getPdo();
    
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        header('Location: views/login.php?error=1');
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        // Régénérer l'ID de session pour éviter la fixation de session
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'];
        header('Location: events.php');
        exit;
    } else {
        header('Location: views/login.php?error=1');
        exit;
    }
    
} catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    header('Location: views/login.php?error=1');
    exit;
}