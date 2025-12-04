<?php
namespace Privee;

class Config {
    // Clé API OpenWeatherMap depuis les variables d'environnement
    public static function getApiKey(): string {
        return getenv('OPENWEATHER_API_KEY') ?: '';
    }
    
    // Langue des résultats météo
    const WEATHER_LANG = 'fr';
    
    // Unités (metric = Celsius, imperial = Fahrenheit)
    const WEATHER_UNITS = 'metric';
}
