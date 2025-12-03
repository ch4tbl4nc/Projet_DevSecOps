<?php
session_start();
require_once __DIR__ . '/../privée/database.php';
require_once __DIR__ . '/../privée/WeatherService.php';
use Privee\Database;
use Privee\WeatherService;

$pdo = Database::getPdo();
$stmt = $pdo->query("SELECT * FROM events ORDER BY date DESC, start_time DESC");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Vérifier si l'utilisateur est admin
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Événements GUARDIA</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="css/events_form.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  </head>
  <body>
    <!-- Bouton Mode Sombre -->
    <button id="theme-btn" style="position:fixed; top:20px; right:20px; z-index:1000; padding:10px 15px; border-radius:30px; border:none; background:white; cursor:pointer; font-weight:bold; box-shadow:0 4px 10px rgba(0,0,0,0.2);">
      <i class="fas fa-moon"></i> Mode Nuit
    </button>

    <!-- Cercles d'ambiance -->
    <div class="orbit orbit1">
      <div class="cercle1"></div>
    </div>
    <div class="orbit">
      <div class="cercle2"></div>
    </div>
    <div class="orbit orbit3">
      <div class="cercle3"></div>
    </div>

    <div class="header">
      <h1><i class="fas fa-calendar-check"></i> Événements GUARDIA</h1>
      <p>Découvrez tous nos événements à venir</p>
    </div>

    <div class="nav-buttons">
      <?php if($isAdmin): ?>
        <a href="form.php" class="btn-nav"><i class="fas fa-plus"></i> Créer un événement</a>
        <a href="admin.php" class="btn-nav"><i class="fas fa-shield-halved"></i> Administration</a>
      <?php endif; ?>
      <?php if($isLoggedIn): ?>
        <a href="logout.php" class="btn-nav" style="background: #e53e3e;"><i class="fas fa-right-from-bracket"></i> Déconnexion</a>
      <?php else: ?>
        <a href="views/login.html" class="btn-nav"><i class="fas fa-lock"></i> Connexion</a>
      <?php endif; ?>
    </div>

    <div class="main-content">
      <!-- Liste des événements à gauche -->
      <div id="events-list" class="events-container">
        <?php if(count($events) > 0): ?>
          <?php foreach($events as $event): ?>
            <?php
              $fullAddress = htmlspecialchars($event['address'] . ', ' . $event['postal_code'] . ' ' . $event['city'] . ', ' . $event['country']);
              $eventName = htmlspecialchars($event['name']);
              $startTime = date('H:i', strtotime($event['start_time']));
              $endTime = date('H:i', strtotime($event['end_time']));
              
              // Gérer l'affichage selon si c'est un événement d'une journée ou plusieurs
              if ($event['end_date']) {
                // Événement sur plusieurs jours
                $startDate = date('d F Y', strtotime($event['date']));
                $endDate = date('d F Y', strtotime($event['end_date']));
                $dateDisplay = 'Du ' . $startDate . ' au ' . $endDate;
                $timeDisplay = $startTime . ' → ' . $endTime;
              } else {
                // Événement d'une journée
                $eventDate = date('d F Y', strtotime($event['date']));
                $dateDisplay = $eventDate;
                $timeDisplay = $startTime . ' - ' . $endTime;
              }
            ?>
            <div class="event-card" onclick="showOnMap(this)" 
                 data-address="<?= $fullAddress ?>"
                 data-name="<?= $eventName ?>"
                 data-city="<?= htmlspecialchars($event['city']) ?>"
                 data-date="<?= htmlspecialchars($event['date']) ?>"
                 data-end-date="<?= htmlspecialchars($event['end_date'] ?? '') ?>"
                 data-country="<?= $event['country'] === 'France' ? 'FR' : htmlspecialchars($event['country']) ?>">
              <?php if($event['image_path']): ?>
                <div class="event-image">
                  <img src="<?= htmlspecialchars($event['image_path']) ?>" alt="<?= $eventName ?>">
                </div>
              <?php endif; ?>
              <h3><?= $eventName ?></h3>
              <div class="event-info">
                <span class="icon"><i class="fas fa-calendar"></i></span>
                <span><?= $dateDisplay ?></span>
              </div>
              <div class="event-info">
                <span class="icon"><i class="fas fa-clock"></i></span>
                <span><?= $timeDisplay ?></span>
              </div>
              <div class="event-info">
                <span class="icon"><i class="fas fa-location-dot"></i></span>
                <span><?= $fullAddress ?></span>
              </div>
              <?php if($event['description']): ?>
                <div class="event-description">
                  <?= nl2br(htmlspecialchars($event['description'])) ?>
                </div>
              <?php endif; ?>
              <?php if($event['theme']): ?>
                <span class="event-theme"><?= htmlspecialchars($event['theme']) ?></span>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-events">
            <h2><i class="fas fa-face-sad-tear"></i> Aucun événement pour le moment</h2>
          </div>
        <?php endif; ?>
      </div>

      <!-- Carte Google Maps et Météo à droite -->
      <div class="map-weather-container">
        <div class="map-container">
          <div class="info-box" id="map-info"><i class="fas fa-location-dot"></i> Cliquez sur un événement pour voir sa localisation</div>
          <iframe 
            id="map-iframe"
            src=""
            allowfullscreen="" 
            loading="lazy" 
            referrerpolicy="no-referrer-when-downgrade">
          </iframe>
        </div>
        
        <!-- Widget Météo -->
        <div class="weather-widget" id="weather-widget">
          <div class="weather-header">
            <i class="fas fa-cloud-sun"></i>
            <span>Prévisions Météo</span>
          </div>
          <div class="weather-content" id="weather-content">
            <div class="weather-placeholder">
              <i class="fas fa-cloud-sun"></i>
              <p>Sélectionnez un événement pour voir la météo</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script>
      // Fonction pour afficher un événement sur la carte
      async function showOnMap(eventCard) {
        // Retirer la classe "selected" de toutes les cartes
        document.querySelectorAll('.event-card').forEach(card => {
          card.classList.remove('selected');
        });

        // Ajouter la classe "selected" à la carte cliquée
        eventCard.classList.add('selected');

        const address = eventCard.getAttribute('data-address');
        const eventName = eventCard.getAttribute('data-name');
        const city = eventCard.getAttribute('data-city');
        const date = eventCard.getAttribute('data-date'); // Toujours la date de début
        const endDate = eventCard.getAttribute('data-end-date');
        const country = eventCard.getAttribute('data-country');
        const isMultiDay = endDate && endDate !== '';
        
        const encodedAddress = encodeURIComponent(address);
        const iframe = document.getElementById('map-iframe');
        const infoBox = document.getElementById('map-info');
        
        iframe.src = `https://www.google.com/maps?q=${encodedAddress}&output=embed`;
        infoBox.innerHTML = `<i class="fas fa-location-dot"></i> ${eventName} - ${address}`;
        
        // Charger la météo (toujours pour la date de début)
        loadWeather(city, date, country, eventName, isMultiDay);
      }
      
      // Fonction pour charger la météo via AJAX
      async function loadWeather(city, date, country, eventName, isMultiDay = false) {
        const weatherContent = document.getElementById('weather-content');
        
        // Afficher le chargement
        weatherContent.innerHTML = `
          <div class="weather-loading">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Chargement des prévisions...</p>
          </div>
        `;
        
        try {
          const response = await fetch(`get_weather.php?city=${encodeURIComponent(city)}&date=${date}&country=${country}`);
          const data = await response.json();
          
          if (data.error) {
            weatherContent.innerHTML = `
              <div class="weather-error">
                <i class="fas fa-exclamation-triangle"></i>
                <p>${data.error}</p>
              </div>
            `;
            return;
          }
          
          if (!data.available) {
            weatherContent.innerHTML = `
              <div class="weather-unavailable">
                <i class="fas fa-clock"></i>
                <p>${data.message}</p>
              </div>
            `;
            return;
          }
          
          // Afficher la météo
          let partialNotice = '';
          if (data.partial) {
            const forecastDate = new Date(data.forecast_date).toLocaleDateString('fr-FR', {day: 'numeric', month: 'long'});
            partialNotice = `<div class="weather-notice"><i class="fas fa-info-circle"></i> Prévision du ${forecastDate} (la plus proche disponible)</div>`;
          }
          
          // Badge pour événement multi-jours
          let multiDayBadge = '';
          if (isMultiDay) {
            multiDayBadge = `<span class="weather-multiday-badge"><i class="fas fa-calendar-week"></i> Jour 1</span>`;
          }
          
          const dateFormatted = new Date(date).toLocaleDateString('fr-FR', {weekday: 'long', day: 'numeric', month: 'long'});
          
          weatherContent.innerHTML = `
            ${partialNotice}
            <div class="weather-main">
              <div class="weather-icon weather-${data.main.toLowerCase()}">
                <i class="fas ${data.icon_fa}"></i>
              </div>
              <div class="weather-temp">
                <span class="temp-value">${data.temp}°C</span>
                <span class="temp-description">${data.description}</span>
              </div>
            </div>
            <div class="weather-city">
              <i class="fas fa-location-dot"></i>
              <span>${data.city}</span>
              <span class="weather-date">${dateFormatted}</span>
              ${multiDayBadge}
            </div>
            <div class="weather-details">
              <div class="weather-detail">
                <i class="fas fa-temperature-arrow-down"></i>
                <div class="detail-content">
                  <span class="detail-value">${data.temp_min}°C</span>
                  <span class="detail-label">Min</span>
                </div>
              </div>
              <div class="weather-detail">
                <i class="fas fa-temperature-arrow-up"></i>
                <div class="detail-content">
                  <span class="detail-value">${data.temp_max}°C</span>
                  <span class="detail-label">Max</span>
                </div>
              </div>
              <div class="weather-detail">
                <i class="fas fa-droplet"></i>
                <div class="detail-content">
                  <span class="detail-value">${data.humidity}%</span>
                  <span class="detail-label">Humidité</span>
                </div>
              </div>
              <div class="weather-detail">
                <i class="fas fa-wind"></i>
                <div class="detail-content">
                  <span class="detail-value">${data.wind_speed} km/h</span>
                  <span class="detail-label">Vent</span>
                </div>
              </div>
              <div class="weather-detail">
                <i class="fas fa-temperature-half"></i>
                <div class="detail-content">
                  <span class="detail-value">${data.feels_like}°C</span>
                  <span class="detail-label">Ressenti</span>
                </div>
              </div>
              <div class="weather-detail">
                <i class="fas fa-cloud"></i>
                <div class="detail-content">
                  <span class="detail-value">${data.clouds}%</span>
                  <span class="detail-label">Nuages</span>
                </div>
              </div>
            </div>
          `;
        } catch (error) {
          weatherContent.innerHTML = `
            <div class="weather-error">
              <i class="fas fa-exclamation-triangle"></i>
              <p>Impossible de charger la météo</p>
            </div>
          `;
        }
      }

      // Afficher le premier événement par défaut au chargement
      window.addEventListener('DOMContentLoaded', () => {
        const firstEvent = document.querySelector('.event-card');
        if (firstEvent) {
          showOnMap(firstEvent);
        }
      });

      /* === SCRIPT MODE NUIT === */
      const themeBtn = document.getElementById('theme-btn');
      const html = document.documentElement;

      // Charger le thème sauvegardé
      if(localStorage.getItem('theme') === 'dark') {
        html.setAttribute('data-theme', 'dark');
        themeBtn.innerHTML = '<i class="fas fa-sun"></i> Mode Jour';
        themeBtn.style.background = '#1967d2';
        themeBtn.style.color = 'white';
      }

      themeBtn.addEventListener('click', () => {
        if (html.getAttribute('data-theme') === 'dark') {
          html.removeAttribute('data-theme');
          localStorage.setItem('theme', 'light');
          themeBtn.innerHTML = '<i class="fas fa-moon"></i> Mode Nuit';
          themeBtn.style.background = 'white';
          themeBtn.style.color = 'black';
        } else {
          html.setAttribute('data-theme', 'dark');
          localStorage.setItem('theme', 'dark');
          themeBtn.innerHTML = '<i class="fas fa-sun"></i> Mode Jour';
          themeBtn.style.background = '#1967d2';
          themeBtn.style.color = 'white';
        }
      });
    </script>
  </body>
</html>