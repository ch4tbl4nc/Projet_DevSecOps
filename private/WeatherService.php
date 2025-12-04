<?php
namespace Privee;

require_once __DIR__ . '/config.php';

class WeatherService {
    private static $apiKey;
    private static $baseUrl = 'https://api.openweathermap.org/data/2.5/';
    private static $allowedDomain = 'api.openweathermap.org';

    public static function init() {
        self::$apiKey = Config::getApiKey();
    }

    /**
     * Valide que l'URL pointe vers le domaine autorisé
     */
    private static function isValidApiUrl($url) {
        $parsedUrl = parse_url($url);
        if (!$parsedUrl || !isset($parsedUrl['host'])) {
            return false;
        }
        return $parsedUrl['host'] === self::$allowedDomain
            && isset($parsedUrl['scheme'])
            && $parsedUrl['scheme'] === 'https';
    }

    /**
     * Effectue une requête HTTP sécurisée (valide l'URL avant exécution)
     */
    private static function fetchUrl($url) {
        // Validation de sécurité : l'URL doit pointer vers le domaine autorisé
        if (!self::isValidApiUrl($url)) {
            return null;
        }

        $response = self::fetchWithCurl($url);
        if ($response !== null) {
            return $response;
        }
        return self::fetchWithFileGetContents($url);
    }

    /**
     * Requête via cURL
     */
    private static function fetchWithCurl($url) {
        if (!function_exists('curl_init')) {
            return null;
        }
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

        return ($response !== false && $httpCode === 200) ? $response : null;
    }

    /**
     * Requête via file_get_contents
     */
    private static function fetchWithFileGetContents($url) {
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

        // Sanitization des entrées utilisateur
        $city = preg_replace('/[^a-zA-Z\s\-\'\x{00C0}-\x{017F}]/u', '', trim($city));
        $country = preg_replace('/[^A-Z]/', '', strtoupper(substr(trim($country), 0, 2)));

        if (empty($city) || empty($country)) {
            return null;
        }

        $url = self::$baseUrl . "forecast?q=" . urlencode($city) . "," . urlencode($country)
             . "&appid=" . self::$apiKey
             . "&units=" . Config::WEATHER_UNITS
             . "&lang=" . Config::WEATHER_LANG;

        $response = self::fetchUrl($url);
        if ($response === null) {
            return null;
        }

        $data = json_decode($response, true);
        $isValidResponse = isset($data['cod']) && ($data['cod'] === "200" || $data['cod'] === 200);

        return $isValidResponse ? $data : null;
    }

    /**
     * Récupère la prévision pour une date spécifique
     */
    public static function getWeatherForDate($city, $date, $country = 'FR') {
        return self::processWeatherRequest(trim($city), $date, $country);
    }

    /**
     * Traite la requête météo et retourne le résultat approprié
     */
    private static function processWeatherRequest($city, $date, $country) {
        if (empty($city)) {
            return self::createUnavailableResponse('no_city', 'Ville non spécifiée');
        }

        $forecast = self::getForecast($city, $country);
        if (!$forecast || !isset($forecast['list']) || empty($forecast['list'])) {
            return null;
        }

        return self::determineWeatherResult($forecast, $date, $city);
    }

    /**
     * Détermine le résultat météo selon la date
     */
    private static function determineWeatherResult($forecast, $date, $city) {
        $targetDate = date('Y-m-d', strtotime($date));
        $today = date('Y-m-d');
        $maxForecastDate = date('Y-m-d', strtotime('+5 days'));

        // Date dans le passé
        if ($targetDate < $today) {
            return self::createUnavailableResponse('past', 'Événement passé');
        }

        // Date trop loin - retourner dernière prévision disponible
        if ($targetDate > $maxForecastDate) {
            return self::createPartialForecast($forecast, $targetDate);
        }

        // Chercher la meilleure prévision pour la date cible
        return self::getMatchingForecast($forecast, $targetDate, $city);
    }

    /**
     * Obtient la prévision correspondante ou une réponse d'indisponibilité
     */
    private static function getMatchingForecast($forecast, $targetDate, $city) {
        $bestMatch = self::findBestForecastMatch($forecast['list'], $targetDate);

        if (!$bestMatch) {
            return self::createUnavailableResponse('no_data', 'Pas de données disponibles');
        }

        return self::formatWeatherData($bestMatch, $forecast, $city);
    }

    /**
     * Crée une réponse pour données non disponibles
     */
    private static function createUnavailableResponse($reason, $message) {
        return [
            'available' => false,
            'reason' => $reason,
            'message' => $message
        ];
    }

    /**
     * Crée une prévision partielle (date trop éloignée)
     */
    private static function createPartialForecast($forecast, $targetDate) {
        $lastForecast = end($forecast['list']);
        $lastDate = date('Y-m-d', $lastForecast['dt']);
        $daysUntil = self::getDaysUntil($targetDate);
        $cityName = isset($forecast['city']) ? $forecast['city']['name'] : '';

        $data = self::extractWeatherData($lastForecast, $cityName);
        $data['partial'] = true;
        $data['forecast_date'] = $lastDate;
        $data['event_date'] = $targetDate;
        $data['days_until'] = $daysUntil;

        return $data;
    }

    /**
     * Trouve la meilleure correspondance de prévision pour une date
     */
    private static function findBestForecastMatch($forecastList, $targetDate) {
        $bestMatch = null;
        $bestTimeDiff = PHP_INT_MAX;
        $targetNoon = strtotime($targetDate . ' 12:00:00');

        // Chercher la prévision la plus proche de midi
        foreach ($forecastList as $item) {
            if (date('Y-m-d', $item['dt']) === $targetDate) {
                $timeDiff = abs($item['dt'] - $targetNoon);
                if ($timeDiff < $bestTimeDiff) {
                    $bestTimeDiff = $timeDiff;
                    $bestMatch = $item;
                }
            }
        }

        // Si pas de match exact, prendre le premier après la date cible
        if (!$bestMatch) {
            foreach ($forecastList as $item) {
                if (date('Y-m-d', $item['dt']) >= $targetDate) {
                    $bestMatch = $item;
                    break;
                }
            }
        }

        // Dernier recours: premier élément disponible
        if (!$bestMatch && !empty($forecastList)) {
            $bestMatch = $forecastList[0];
        }

        return $bestMatch;
    }

    /**
     * Formate les données météo pour le retour
     */
    private static function formatWeatherData($match, $forecast, $city) {
        $cityName = isset($forecast['city']) ? $forecast['city']['name'] : $city;
        return self::extractWeatherData($match, $cityName);
    }

    /**
     * Extrait les données météo d'un élément de prévision
     */
    private static function extractWeatherData($item, $cityName) {
        return [
            'available' => true,
            'temp' => round($item['main']['temp']),
            'temp_min' => round($item['main']['temp_min']),
            'temp_max' => round($item['main']['temp_max']),
            'feels_like' => round($item['main']['feels_like']),
            'humidity' => $item['main']['humidity'],
            'description' => ucfirst($item['weather'][0]['description']),
            'icon' => $item['weather'][0]['icon'],
            'wind_speed' => round($item['wind']['speed'] * 3.6),
            'clouds' => $item['clouds']['all'],
            'main' => $item['weather'][0]['main'],
            'city' => $cityName
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
