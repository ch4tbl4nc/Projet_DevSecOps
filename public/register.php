<?php
require_once __DIR__ . '/security-headers.php';
session_start();

require_once '/var/www/private/database.php';
use Privee\Database;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: views/register.php');
    exit;
}

// Vérification CSRF
$csrf_token = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrf_token)) {
    header('Location: views/register.php?error=csrf');
    exit;
}

try {
    $pdo = Database::getPdo();
    
    // Sanitization des entrées
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? $password;
    
    // Vérifications
    if (empty($username) || empty($email) || empty($password)) {
        header('Location: views/register.php?error=empty');
        exit;
    }
    
    // Validation email
    if (!validateEmail($email)) {
        header('Location: views/register.php?error=email');
        exit;
    }
    
    // Validation longueur mot de passe
    if (strlen($password) < 6) {
        header('Location: views/register.php?error=password_length');
        exit;
    }
    
    if ($password !== $confirm_password) {
        header('Location: views/register.php?error=password');
        exit;
    }
    
    // Vérifier si l'utilisateur existe déjà
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    
    if ($stmt->fetch()) {
        header('Location: views/register.php?error=exists');
        exit;
    }
    
    // Créer l'utilisateur
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, 0)");
    $stmt->execute([$username, $email, $hashedPassword]);
    
    // Régénérer l'ID de session
    session_regenerate_id(true);
    
    // Connexion automatique
    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['username'] = $username;
    $_SESSION['is_admin'] = 0;
    
    header('Location: events.php');
    exit;
    
} catch (Exception $e) {
    error_log('Register error: ' . $e->getMessage());
    header('Location: views/register.php?error=server');
    exit;
}