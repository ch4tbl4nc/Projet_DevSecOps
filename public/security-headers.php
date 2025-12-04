<?php
/**
 * Configuration de sécurité globale
 * À inclure au début de chaque fichier PHP
 */

// Définir les headers de sécurité
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

// Content-Security-Policy
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://unpkg.com https://prod.spline.design; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: https:; frame-src 'self' https://www.google.com https://prod.spline.design; connect-src 'self' https://prod.spline.design;");

// Masquer la version de PHP
header_remove('X-Powered-By');

// Configuration de sécurité PHP
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('expose_php', 'Off');

// Session security (si sessions utilisées)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '1');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', '1');
}

/**
 * Fonction pour nettoyer les entrées utilisateur
 */
function sanitizeInput(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Fonction pour valider un email
 */
function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Fonction pour valider une date
 */
function validateDate(string $date): bool {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Fonction pour valider une heure
 */
function validateTime(string $time): bool {
    return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time) === 1;
}

/**
 * Génère un token CSRF
 */
function generateCsrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie un token CSRF
 */
function verifyCsrfToken(string $token): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
