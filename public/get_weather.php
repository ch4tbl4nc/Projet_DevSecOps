<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../private/WeatherService.php';
use Privee\WeatherService;

$city = $_GET['city'] ?? '';
$date = $_GET['date'] ?? '';
$country = $_GET['country'] ?? 'FR';

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
