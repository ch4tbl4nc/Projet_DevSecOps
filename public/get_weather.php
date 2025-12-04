<?php
header('Content-Type: application/json');

// Vérifier que l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

require_once '/var/www/private/WeatherService.php';
use Privee\WeatherService;

// Sanitization des entrées
$city = preg_replace('/[^a-zA-Z\s\-\'\x{00C0}-\x{017F}]/u', '', trim($_GET['city'] ?? ''));
$date = preg_replace('/[^0-9\-]/', '', $_GET['date'] ?? '');
$country = preg_replace('/[^A-Z]/', '', strtoupper(substr(trim($_GET['country'] ?? 'FR'), 0, 2)));

if (empty($city) || empty($date)) {
    echo json_encode(['error' => 'Paramètres manquants (ville ou date)']);
    exit;
}

try {
    $weather = WeatherService::getWeatherForDate($city, $date, $country);
    
    if ($weather === null) {
        echo json_encode(['error' => 'Ville non trouvée ou API indisponible']);
        exit;
    }
    
    // Ajouter l'icône Font Awesome
    if (isset($weather['main'])) {
        $weather['icon_fa'] = WeatherService::getWeatherIcon($weather['main']);
    }
    
    echo json_encode($weather);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur: ' . $e->getMessage()]);
}
