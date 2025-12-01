<?php
require_once __DIR__ . '/../privée/database.php';
use Privee\Database;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['mail']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if ($password !== $password_confirm) {
        header('Location: views/register.html?error=confirm');
        exit;
    }

    $pdo = Database::getPdo();

    // Vérifier si l'utilisateur existe déjà
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        header('Location: views/register.html?error=exists');
        exit;
    }

    // Insérer le nouvel utilisateur
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
    $stmt->execute([$username, $email, $hash]);

    header('Location: events.php');
    exit;
}