<?
php
require_once __DIR__ . '/security-headers.php';
require_once __DIR__ . '/../privée/database.php';
use Privee\Database;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification CSRF
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        header('Location: views/login.html?error=csrf');
        exit;
    }

    // Validation et nettoyage des entrées
    $username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($username) || empty($password)) {
        header('Location: views/login.html?error=empty');
        exit;
    }

    // Limite de longueur pour éviter les attaques
    if (strlen($username) > 50 || strlen($password) > 255) {
        header('Location: views/login.html?error=invalid');
        exit;
    }

    try {
        $pdo = Database::getPdo();

        // Récupérer l'utilisateur avec requête préparée
        $stmt = $pdo->prepare('SELECT id, password FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Régénérer l'ID de session pour éviter la fixation de session
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            
            header('Location: events.php');
            exit;
        }
        
        // Message d'erreur générique pour ne pas révéler si l'utilisateur existe
        header('Location: views/login.html?error=invalid');
        exit;
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        header('Location: views/login.html?error=server');
        exit;
    }
}

// Redirection si accès GET
header('Location: views/login.html');
exit;