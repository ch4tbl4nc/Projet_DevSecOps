<?php
require_once __DIR__ . '/../privée/database.php';
use Privee\Database;
$pdo = Database::getPdo();
$stmt = $pdo->query("SELECT * FROM events ORDER BY date DESC, start_time DESC");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Événements GUARDIA</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="css/events_form.css">
  </head>
  <body>
    <!-- Bouton Mode Sombre -->
    <button id="theme-btn" style="position:fixed; top:20px; right:20px; z-index:1000; padding:10px 15px; border-radius:30px; border:none; background:white; cursor:pointer; font-weight:bold; box-shadow:0 4px 10px rgba(0,0,0,0.2);">
      🌙 Mode Nuit
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
      <h1>🎉 Événements GUARDIA</h1>
      <p>Découvrez tous nos événements à venir</p>
    </div>

    <div class="nav-buttons">
      <a href="form.php" class="btn-nav">➕ Créer un événement</a>
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
                 data-name="<?= $eventName ?>">
              <?php if($event['image_path']): ?>
                <div class="event-image">
                  <img src="<?= htmlspecialchars($event['image_path']) ?>" alt="<?= $eventName ?>">
                </div>
              <?php endif; ?>
              <h3><?= $eventName ?></h3>
              <div class="event-info">
                <span class="icon">📅</span>
                <span><?= $dateDisplay ?></span>
              </div>
              <div class="event-info">
                <span class="icon">⏰</span>
                <span><?= $timeDisplay ?></span>
              </div>
              <div class="event-info">
                <span class="icon">📍</span>
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
            <h2>😔 Aucun événement pour le moment</h2>
          </div>
        <?php endif; ?>
      </div>

      <!-- Carte Google Maps à droite -->
      <div class="map-container">
        <div class="info-box" id="map-info">📍 Cliquez sur un événement pour voir sa localisation</div>
        <iframe 
          id="map-iframe"
          src=""
          allowfullscreen="" 
          loading="lazy" 
          referrerpolicy="no-referrer-when-downgrade">
        </iframe>
      </div>
    </div>

    <script>
      // Fonction pour afficher un événement sur la carte
      function showOnMap(eventCard) {
        // Retirer la classe "selected" de toutes les cartes
        document.querySelectorAll('.event-card').forEach(card => {
          card.classList.remove('selected');
        });

        // Ajouter la classe "selected" à la carte cliquée
        eventCard.classList.add('selected');

        const address = eventCard.getAttribute('data-address');
        const eventName = eventCard.getAttribute('data-name');
        
        const encodedAddress = encodeURIComponent(address);
        const iframe = document.getElementById('map-iframe');
        const infoBox = document.getElementById('map-info');
        
        iframe.src = `https://www.google.com/maps?q=${encodedAddress}&output=embed`;
        infoBox.textContent = `📍 ${eventName} - ${address}`;
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
        themeBtn.textContent = '☀️ Mode Jour';
        themeBtn.style.background = '#1967d2';
        themeBtn.style.color = 'white';
      }

      themeBtn.addEventListener('click', () => {
        if (html.getAttribute('data-theme') === 'dark') {
          html.removeAttribute('data-theme');
          localStorage.setItem('theme', 'light');
          themeBtn.textContent = '🌙 Mode Nuit';
          themeBtn.style.background = 'white';
          themeBtn.style.color = 'black';
        } else {
          html.setAttribute('data-theme', 'dark');
          localStorage.setItem('theme', 'dark');
          themeBtn.textContent = '☀️ Mode Jour';
          themeBtn.style.background = '#1967d2';
          themeBtn.style.color = 'white';
        }
      });
    </script>
  </body>
</html>