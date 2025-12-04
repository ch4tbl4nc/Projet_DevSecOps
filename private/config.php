<?php
namespace Privee;

class Config {
    // Clé API OpenWeatherMap chargée depuis l'environnement
    // Ne JAMAIS mettre de clé API en dur dans le code
    
    public static function getApiKey(): string {
        $key = getenv('OPENWEATHER_API_KEY');
        if (empty($key)) {
            throw new \RuntimeException('OPENWEATHER_API_KEY non définie dans l\'environnement');
        }
        return $key;
    }
    
    // Langue des résultats météo
    const WEATHER_LANG = 'fr';
    
    // Unités (metric = Celsius, imperial = Fahrenheit)
    const WEATHER_UNITS = 'metric';
}
