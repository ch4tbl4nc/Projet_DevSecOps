<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: views/login.php');
    exit;
}

require_once '/var/www/private/database.php';
require_once '/var/www/private/WeatherService.php';
use Privee\Database;
use Privee\WeatherService;

$pdo = Database::getPdo();
$stmt = $pdo->query("SELECT * FROM events ORDER BY date DESC, start_time DESC");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Vérifier si l'utilisateur est admin
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
$username = $_SESSION['username'] ?? 'Utilisateur';
?>
<!DOCTYPE html>
<html lang="fr">
  <head>
    <title>Événements MonAgendaPro</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
      /* Styles spécifiques à la page events */
      body {
        min-height: 100vh;
        padding-top: 100px;
      }
      
      .page-container {
        max-width: 1600px;
        margin: 0 auto;
        padding: 0 30px 50px;
      }
      
      .page-header {
        text-align: center;
        margin-bottom: 40px;
      }
      
      .page-header h1 {
        font-family: 'Orbitron', sans-serif;
        font-size: 42px;
        color: #fff;
        text-shadow: 0 0 20px rgba(25, 103, 210, 0.5);
        margin-bottom: 10px;
      }
      
      .page-header h1 i {
        color: #1967d2;
        margin-right: 15px;
      }
      
      .page-header p {
        color: #a0aec0;
        font-size: 16px;
      }
      
      .welcome-badge {
        display: inline-block;
        background: rgba(25, 103, 210, 0.2);
        border: 1px solid rgba(25, 103, 210, 0.4);
        padding: 8px 20px;
        border-radius: 30px;
        color: #64b5f6;
        font-size: 14px;
        margin-bottom: 20px;
      }
      
      .welcome-badge i {
        margin-right: 8px;
      }
      
      /* Boutons d'action */
      .action-buttons {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-bottom: 40px;
        flex-wrap: wrap;
      }
      
      .btn-action {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 14px 28px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: bold;
        font-size: 15px;
        transition: all 0.3s;
        border: 2px solid transparent;
      }
      
      .btn-action.primary {
        background: linear-gradient(135deg, #1967d2 0%, #1557b0 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(25, 103, 210, 0.4);
      }
      
      .btn-action.primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(25, 103, 210, 0.6);
      }
      
      .btn-action.secondary {
        background: rgba(25, 103, 210, 0.15);
        color: #64b5f6;
        border-color: rgba(25, 103, 210, 0.4);
      }
      
      .btn-action.secondary:hover {
        background: rgba(25, 103, 210, 0.25);
        transform: translateY(-3px);
      }
      
      .btn-action.danger {
        background: rgba(220, 38, 38, 0.15);
        color: #f87171;
        border-color: rgba(220, 38, 38, 0.4);
      }
      
      .btn-action.danger:hover {
        background: rgba(220, 38, 38, 0.25);
        transform: translateY(-3px);
      }
      
      /* Layout principal */
      .main-content {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 30px;
      }
      
      @media (max-width: 1100px) {
        .main-content {
          grid-template-columns: 1fr;
        }
      }
      
      /* Liste des événements */
      .events-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
      }
      
      .event-card {
        background: rgba(20, 30, 50, 0.9);
        border: 2px solid rgba(25, 103, 210, 0.3);
        border-radius: 16px;
        padding: 0;
        cursor: pointer;
        transition: all 0.3s;
        text-align: left;
        overflow: hidden;
        color: #fff;
        width: 100%;
        font-family: inherit;
      }
      
      .event-card:hover {
        border-color: #1967d2;
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(25, 103, 210, 0.3);
      }
      
      .event-card.selected {
        border-color: #1967d2;
        box-shadow: 0 0 25px rgba(25, 103, 210, 0.5);
      }
      
      .event-card .event-image {
        width: 100%;
        height: 160px;
        overflow: hidden;
      }
      
      .event-card .event-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }
      
      .event-card-content {
        padding: 20px;
      }
      
      .event-card h3 {
        font-family: 'Orbitron', sans-serif;
        font-size: 18px;
        margin: 0 0 15px;
        color: #fff;
        text-shadow: 0 0 10px rgba(25, 103, 210, 0.3);
      }
      
      .event-info {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 10px;
        font-size: 14px;
        color: #a0aec0;
      }
      
      .event-info .icon {
        color: #1967d2;
        width: 18px;
        text-align: center;
        flex-shrink: 0;
      }
      
      .event-description {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid rgba(25, 103, 210, 0.2);
        font-size: 13px;
        color: #718096;
        line-height: 1.5;
      }
      
      .event-theme {
        display: inline-block;
        margin-top: 15px;
        padding: 6px 14px;
        background: rgba(25, 103, 210, 0.2);
        border: 1px solid rgba(25, 103, 210, 0.4);
        border-radius: 20px;
        font-size: 12px;
        color: #64b5f6;
      }
      
      /* Message si pas d'événements */
      .no-events {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 20px;
        background: rgba(20, 30, 50, 0.8);
        border: 2px dashed rgba(25, 103, 210, 0.3);
        border-radius: 16px;
      }
      
      .no-events h2 {
        color: #64b5f6;
        font-family: 'Orbitron', sans-serif;
        font-size: 22px;
      }
      
      .no-events i {
        margin-right: 10px;
      }
      
      /* Sidebar droite */
      .sidebar {
        display: flex;
        flex-direction: column;
        gap: 20px;
      }
      
      /* Carte */
      .map-container {
        background: rgba(20, 30, 50, 0.9);
        border: 2px solid rgba(25, 103, 210, 0.3);
        border-radius: 16px;
        overflow: hidden;
      }
      
      .map-header {
        background: linear-gradient(135deg, #1967d2 0%, #1557b0 100%);
        padding: 15px 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: bold;
        font-size: 14px;
      }
      
      .map-header i {
        font-size: 16px;
      }
      
      #map-info {
        padding: 12px 20px;
        background: rgba(0, 0, 0, 0.3);
        font-size: 13px;
        color: #a0aec0;
        border-bottom: 1px solid rgba(25, 103, 210, 0.2);
      }
      
      #map-info i {
        color: #1967d2;
        margin-right: 8px;
      }
      
      #map-iframe {
        width: 100%;
        height: 220px;
        border: none;
      }
      
      /* Widget météo */
      .weather-widget {
        background: rgba(20, 30, 50, 0.9);
        border: 2px solid rgba(25, 103, 210, 0.3);
        border-radius: 16px;
        overflow: hidden;
      }
      
      .weather-header {
        background: linear-gradient(135deg, #1967d2 0%, #1557b0 100%);
        padding: 15px 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: bold;
        font-size: 14px;
      }
      
      .weather-content {
        padding: 20px;
      }
      
      .weather-placeholder, .weather-loading, .weather-error, .weather-unavailable {
        text-align: center;
        padding: 30px 20px;
        color: #718096;
      }
      
      .weather-placeholder i, .weather-loading i, .weather-error i, .weather-unavailable i {
        font-size: 40px;
        margin-bottom: 15px;
        display: block;
        color: #4a5568;
      }
      
      .weather-loading i {
        color: #1967d2;
      }
      
      .weather-error i {
        color: #f87171;
      }
      
      .weather-main {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 20px;
      }
      
      .weather-icon {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background: rgba(25, 103, 210, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        color: #64b5f6;
      }
      
      .weather-temp {
        flex: 1;
      }
      
      .temp-value {
        font-size: 36px;
        font-weight: bold;
        color: #fff;
        font-family: 'Orbitron', sans-serif;
      }
      
      .temp-description {
        display: block;
        color: #a0aec0;
        font-size: 14px;
        text-transform: capitalize;
        margin-top: 5px;
      }
      
      .weather-city {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        padding: 12px 15px;
        background: rgba(0, 0, 0, 0.2);
        border-radius: 10px;
        margin-bottom: 20px;
        font-size: 13px;
        color: #a0aec0;
      }
      
      .weather-city i {
        color: #1967d2;
      }
      
      .weather-date {
        margin-left: auto;
        color: #64b5f6;
      }
      
      .weather-details {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
      }
      
      .weather-detail {
        background: rgba(0, 0, 0, 0.2);
        padding: 12px;
        border-radius: 10px;
        text-align: center;
      }
      
      .weather-detail i {
        color: #1967d2;
        font-size: 16px;
        margin-bottom: 8px;
        display: block;
      }
      
      .detail-value {
        font-weight: bold;
        color: #fff;
        font-size: 14px;
      }
      
      .detail-label {
        display: block;
        font-size: 11px;
        color: #718096;
        margin-top: 4px;
      }
      
      .weather-notice {
        background: rgba(25, 103, 210, 0.15);
        border: 1px solid rgba(25, 103, 210, 0.3);
        padding: 10px 15px;
        border-radius: 8px;
        margin-bottom: 15px;
        font-size: 12px;
        color: #64b5f6;
      }
      
      .weather-notice i {
        margin-right: 8px;
      }
      
      .weather-multiday-badge {
        background: rgba(139, 92, 246, 0.2);
        border: 1px solid rgba(139, 92, 246, 0.4);
        padding: 4px 10px;
        border-radius: 15px;
        font-size: 11px;
        color: #a78bfa;
      }
    </style>
  </head>
  <body>
    <!-- Navbar -->
    <nav class="navbar">
      <a href="views/index.html" class="nav-logo">MonAgendaPro</a>
      <div class="nav-links">
        <a href="views/index.html" class="nav-item">Accueil</a>
        <a href="events.php" class="nav-item" style="color: #1967d2;">Événements</a>
        <?php if($isAdmin): ?>
          <a href="admin.php" class="nav-item">Administration</a>
        <?php endif; ?>
        <a href="logout.php" class="nav-btn-connexion" style="background: #dc2626;">Déconnexion</a>
      </div>
    </nav>

    <!-- Animation d'arrière-plan -->
    <div class="background-animation">
      <div class="orbit orbit1"><div class="cercle1"></div></div>
      <div class="cercle2"></div>
      <div class="orbit orbit3"><div class="cercle3"></div></div>
    </div>

    <div class="page-container">
      <!-- Header de la page -->
      <div class="page-header">
        <div class="welcome-badge">
          <i class="fas fa-user"></i> Bienvenue, <?= htmlspecialchars($username) ?>
        </div>
        <h1><i class="fas fa-calendar-check"></i> Mes Événements</h1>
        <p>Découvrez et gérez tous vos événements à venir</p>
      </div>

      <!-- Boutons d'action -->
      <div class="action-buttons">
        <?php if($isAdmin): ?>
          <a href="form.php" class="btn-action primary">
            <i class="fas fa-plus"></i> Créer un événement
          </a>
          <a href="admin.php" class="btn-action secondary">
            <i class="fas fa-shield-halved"></i> Administration
          </a>
        <?php endif; ?>
        <a href="logout.php" class="btn-action danger">
          <i class="fas fa-right-from-bracket"></i> Déconnexion
        </a>
      </div>

      <div class="main-content">
        <!-- Liste des événements -->
        <div class="events-container">
          <?php if(count($events) > 0): ?>
            <?php
            define('DATE_FORMAT_FR', 'd F Y');
            foreach($events as $event):
              $fullAddress = htmlspecialchars($event['address'] . ', ' . $event['postal_code'] . ' ' . $event['city'] . ', ' . $event['country']);
              $eventName = htmlspecialchars($event['name']);
              $startTime = date('H:i', strtotime($event['start_time']));
              $endTime = date('H:i', strtotime($event['end_time']));

              if ($event['end_date']) {
                $startDate = date(DATE_FORMAT_FR, strtotime($event['date']));
                $endDate = date(DATE_FORMAT_FR, strtotime($event['end_date']));
                $dateDisplay = 'Du ' . $startDate . ' au ' . $endDate;
                $timeDisplay = $startTime . ' → ' . $endTime;
              } else {
                $eventDate = date(DATE_FORMAT_FR, strtotime($event['date']));
                $dateDisplay = $eventDate;
                $timeDisplay = $startTime . ' - ' . $endTime;
              }
            ?>
              <button type="button" class="event-card" onclick="showOnMap(this)"
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
                <div class="event-card-content">
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
              </button>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="no-events">
              <h2><i class="fas fa-calendar-xmark"></i> Aucun événement pour le moment</h2>
              <p style="color: #718096; margin-top: 10px;">Créez votre premier événement pour commencer !</p>
            </div>
          <?php endif; ?>
        </div>

        <!-- Sidebar droite -->
        <div class="sidebar">
          <!-- Carte Google Maps -->
          <div class="map-container">
            <div class="map-header">
              <i class="fas fa-map-location-dot"></i>
              <span>Localisation</span>
            </div>
            <div id="map-info"><i class="fas fa-location-dot"></i> Cliquez sur un événement pour voir sa localisation</div>
            <iframe
              id="map-iframe"
              src=""
              title="Carte de localisation de l'événement"
              allowfullscreen=""
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade">
            </iframe>
          </div>
          
          <!-- Widget Météo -->
          <div class="weather-widget">
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
    </div>

    <script type="module" src="https://unpkg.com/@splinetool/viewer@1.0.28/build/spline-viewer.js"></script>
    <script>
      // Fonction pour afficher un événement sur la carte
      async function showOnMap(eventCard) {
        document.querySelectorAll('.event-card').forEach(card => {
          card.classList.remove('selected');
        });
        eventCard.classList.add('selected');

        const address = eventCard.getAttribute('data-address');
        const eventName = eventCard.getAttribute('data-name');
        const city = eventCard.getAttribute('data-city');
        const date = eventCard.getAttribute('data-date');
        const endDate = eventCard.getAttribute('data-end-date');
        const country = eventCard.getAttribute('data-country');
        const isMultiDay = endDate && endDate !== '';
        
        const encodedAddress = encodeURIComponent(address);
        const iframe = document.getElementById('map-iframe');
        const infoBox = document.getElementById('map-info');
        
        iframe.src = `https://www.google.com/maps?q=${encodedAddress}&output=embed`;
        infoBox.innerHTML = `<i class="fas fa-location-dot"></i> ${eventName} - ${address}`;
        
        loadWeather(city, date, country, eventName, isMultiDay);
      }
      
      // Fonction pour charger la météo via AJAX
      async function loadWeather(city, date, country, eventName, isMultiDay = false) {
        const weatherContent = document.getElementById('weather-content');
        
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
          
          let partialNotice = '';
          if (data.partial) {
            const forecastDate = new Date(data.forecast_date).toLocaleDateString('fr-FR', {day: 'numeric', month: 'long'});
            partialNotice = `<div class="weather-notice"><i class="fas fa-info-circle"></i> Prévision du ${forecastDate} (la plus proche disponible)</div>`;
          }
          
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
    </script>
  </body>
</html>
