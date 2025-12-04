<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once '/var/www/private/database.php';
use Privee\Database;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: views/register.html');
    exit;
}

try {
    $pdo = Database::getPdo();
    
    // Accepter différents noms de champs possibles
    $username = trim($_POST['username'] ?? $_POST['name'] ?? $_POST['user'] ?? '');
    $email = trim($_POST['email'] ?? $_POST['mail'] ?? '');
    $password = $_POST['password'] ?? $_POST['pwd'] ?? $_POST['pass'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? $_POST['password_confirm'] ?? $_POST['confirm'] ?? $_POST['password2'] ?? $password;
    
    // Debug - décommenter pour voir les données reçues
    // echo "<pre>Données reçues: "; print_r($_POST); echo "</pre>"; exit;
    
    // Vérifications
    if (empty($username) || empty($email) || empty($password)) {
        header('Location: views/register.html?error=empty');
        exit;
    }
    
    if ($password !== $confirm_password) {
        header('Location: views/register.html?error=password');
        exit;
    }
    
    // Vérifier si l'utilisateur existe déjà
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    
    if ($stmt->fetch()) {
        header('Location: views/register.html?error=exists');
        exit;
    }
    
    // Créer l'utilisateur
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, 0)");
    $stmt->execute([$username, $email, $hashedPassword]);
    
    // Connexion automatique
    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['username'] = $username;
    $_SESSION['is_admin'] = 0;
    
    header('Location: events.php');
    exit;
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
    exit;
}