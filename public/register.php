<?
php
require_once __DIR__ . '/security-headers.php';
require_once __DIR__ . '/../privée/database.php';
use Privee\Database;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification CSRF
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        header('Location: views/register.html?error=csrf');
        exit;
    }

    // Validation et nettoyage des entrées
    $username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
    $email = isset($_POST['mail']) ? sanitizeInput($_POST['mail']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $passwordConfirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';

    // Validation des champs obligatoires
    if (empty($username) || empty($email) || empty($password) || empty($passwordConfirm)) {
        header('Location: views/register.html?error=empty');
        exit;
    }

    // Validation de l'email
    if (!validateEmail($email)) {
        header('Location: views/register.html?error=email');
        exit;
    }

    // Validation du mot de passe (min 8 caractères, majuscule, minuscule, chiffre)
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || 
        !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        header('Location: views/register.html?error=password');
        exit;
    }

    // Vérification de la confirmation du mot de passe
    if ($password !== $passwordConfirm) {
        header('Location: views/register.html?error=confirm');
        exit;
    }

    // Limite de longueur
    if (strlen($username) > 50 || strlen($email) > 100) {
        header('Location: views/register.html?error=invalid');
        exit;
    }

    try {
        $pdo = Database::getPdo();

        // Vérifier si l'utilisateur existe déjà
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            header('Location: views/register.html?error=exists');
            exit;
        }

        // Insérer le nouvel utilisateur avec hash sécurisé
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $stmt = $pdo->prepare('INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$username, $email, $hash]);

        // Connecter automatiquement l'utilisateur
        session_regenerate_id(true);
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['username'] = $username;

        header('Location: events.php');
        exit;
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        header('Location: views/register.html?error=server');
        exit;
    }
}

// Redirection si accès GET
header('Location: views/register.html');
exit;