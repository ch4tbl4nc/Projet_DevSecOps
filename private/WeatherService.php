<?php
namespace Privee;

require_once __DIR__ . '/config.php';

class WeatherService {
    private static $apiKey;
    private static $baseUrl = 'https://api.openweathermap.org/data/2.5/';
    
    public static function init() {
        self::$apiKey = Config::getApiKey();
    }
    
    /**
     * Effectue une requête HTTP (essaie cURL puis file_get_contents)
     */
    private static function fetchUrl($url) {
        // Méthode 1: cURL
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                CURLOPT_HTTPHEADER => ['Accept: application/json']
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($response !== false && $httpCode === 200) {
                return $response;
            }
        }
        
        // Méthode 2: file_get_contents avec contexte
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Accept: application/json\r\nUser-Agent: Mozilla/5.0\r\n",
                'timeout' => 15
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        return $response !== false ? $response : null;
    }
    
    /**
     * Récupère les prévisions sur 5 jours directement par nom de ville
     */
    public static function getForecast($city, $country = 'FR') {
        self::init();
        
        // Utiliser directement le nom de la ville (plus fiable)
        $url = self::$baseUrl . "forecast?q=" . urlencode($city) . "," . $country
             . "&appid=" . self::$apiKey 
             . "&units=" . Config::WEATHER_UNITS 
             . "&lang=" . Config::WEATHER_LANG;
        
        $response = self::fetchUrl($url);
        if ($response === null) {
            return null;
        }
        
        $data = json_decode($response, true);
        
        // Vérifier si l'API a retourné une erreur
        if (isset($data['cod']) && $data['cod'] !== "200" && $data['cod'] !== 200) {
            return null;
        }
        
        return $data;
    }
    
    /**
     * Récupère la prévision pour une date spécifique
     * Si la date est trop loin, cherche la prévision la plus proche disponible
     */
    public static function getWeatherForDate($city, $date, $country = 'FR') {
        // Nettoyer la ville (enlever espaces et caractères spéciaux)
        $city = trim($city);
        if (empty($city)) {
            return [
                'available' => false,
                'reason' => 'no_city',
                'message' => 'Ville non spécifiée'
            ];
        }
        
        $forecast = self::getForecast($city, $country);
        
        if (!$forecast || !isset($forecast['list']) || empty($forecast['list'])) {
            return null;
        }
        
        $targetDate = date('Y-m-d', strtotime($date));
        $today = date('Y-m-d');
        $maxForecastDate = date('Y-m-d', strtotime('+5 days'));
        
        // Si la date est dans le passé
        if ($targetDate < $today) {
            return [
                'available' => false,
                'reason' => 'past',
                'message' => 'Événement passé'
            ];
        }
        
        // Si la date est trop loin (> 5 jours), on affiche la dernière prévision disponible
        if ($targetDate > $maxForecastDate) {
            $daysUntil = self::getDaysUntil($targetDate);
            
            // Prendre la dernière prévision disponible
            $lastForecast = end($forecast['list']);
            $lastDate = date('Y-m-d', $lastForecast['dt']);
            
            return [
                'available' => true,
                'partial' => true,
                'forecast_date' => $lastDate,
                'event_date' => $targetDate,
                'days_until' => $daysUntil,
                'temp' => round($lastForecast['main']['temp']),
                'temp_min' => round($lastForecast['main']['temp_min']),
                'temp_max' => round($lastForecast['main']['temp_max']),
                'feels_like' => round($lastForecast['main']['feels_like']),
                'humidity' => $lastForecast['main']['humidity'],
                'description' => ucfirst($lastForecast['weather'][0]['description']),
                'icon' => $lastForecast['weather'][0]['icon'],
                'wind_speed' => round($lastForecast['wind']['speed'] * 3.6),
                'clouds' => $lastForecast['clouds']['all'],
                'main' => $lastForecast['weather'][0]['main'],
                'city' => isset($forecast['city']) ? $forecast['city']['name'] : $city
            ];
        }
        
        // Chercher la prévision la plus proche de midi pour cette date
        $bestMatch = null;
        $bestTimeDiff = PHP_INT_MAX;
        $targetNoon = strtotime($targetDate . ' 12:00:00');
        
        foreach ($forecast['list'] as $item) {
            $itemDate = date('Y-m-d', $item['dt']);
            if ($itemDate === $targetDate) {
                $timeDiff = abs($item['dt'] - $targetNoon);
                if ($timeDiff < $bestTimeDiff) {
                    $bestTimeDiff = $timeDiff;
                    $bestMatch = $item;
                }
            }
        }
        
        // Si pas de match exact, prendre le premier disponible pour cette date ou après
        if (!$bestMatch) {
            foreach ($forecast['list'] as $item) {
                $itemDate = date('Y-m-d', $item['dt']);
                if ($itemDate >= $targetDate) {
                    $bestMatch = $item;
                    break;
                }
            }
        }
        
        // Si toujours pas de match, prendre le premier disponible
        if (!$bestMatch && !empty($forecast['list'])) {
            $bestMatch = $forecast['list'][0];
        }
        
        if (!$bestMatch) {
            return [
                'available' => false,
                'reason' => 'no_data',
                'message' => 'Pas de données disponibles'
            ];
        }
        
        return [
            'available' => true,
            'temp' => round($bestMatch['main']['temp']),
            'temp_min' => round($bestMatch['main']['temp_min']),
            'temp_max' => round($bestMatch['main']['temp_max']),
            'feels_like' => round($bestMatch['main']['feels_like']),
            'humidity' => $bestMatch['main']['humidity'],
            'description' => ucfirst($bestMatch['weather'][0]['description']),
            'icon' => $bestMatch['weather'][0]['icon'],
            'wind_speed' => round($bestMatch['wind']['speed'] * 3.6),
            'clouds' => $bestMatch['clouds']['all'],
            'main' => $bestMatch['weather'][0]['main'],
            'city' => isset($forecast['city']) ? $forecast['city']['name'] : $city
        ];
    }
    
    /**
     * Calcule le nombre de jours jusqu'à une date
     */
    private static function getDaysUntil($date) {
        $now = new \DateTime();
        $target = new \DateTime($date);
        return $now->diff($target)->days;
    }
    
    /**
     * Retourne l'icône Font Awesome correspondant à la météo
     */
    public static function getWeatherIcon($weatherMain) {
        $icons = [
            'Clear' => 'fa-sun',
            'Clouds' => 'fa-cloud',
            'Rain' => 'fa-cloud-rain',
            'Drizzle' => 'fa-cloud-rain',
            'Thunderstorm' => 'fa-cloud-bolt',
            'Snow' => 'fa-snowflake',
            'Mist' => 'fa-smog',
            'Fog' => 'fa-smog',
            'Haze' => 'fa-smog'
        ];
        
        return $icons[$weatherMain] ?? 'fa-cloud';
    }
}
