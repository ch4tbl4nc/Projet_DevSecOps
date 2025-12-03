<?php
session_start();
require_once __DIR__ . '/../privée/database.php';
use Privee\Database;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $pdo = Database::getPdo();

    // Récupérer l'utilisateur
    $stmt = $pdo->prepare('SELECT id, username, is_admin, password FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Créer la session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'];
        
        // Connexion réussie
        header('Location: events.php');
        exit;
    }
    
    // Sinon, retour à la page de login avec erreur
    header('Location: views/login.html?error=1');
    exit;
}