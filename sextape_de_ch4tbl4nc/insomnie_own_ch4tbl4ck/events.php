<?php
include 'database.php';

$stmt = $pdo->query("SELECT * FROM events ORDER BY date DESC, start_time DESC");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Événements GUARDIA</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="css/styles.css">
  </head>
  <body>
    <div class="header">
      <h1>🎉 Événements GUARDIA</h1>
      <p>Découvrez tous nos événements à venir</p>
    </div>

    <div class="nav-buttons">
      <a href="index.php" class="btn-nav">➕ Créer un événement</a>
    </div>

    <div class="main-content">
      <div id="events-list" class="events-container">
        <?php if(count($events) > 0): ?>
          <?php foreach($events as $event): ?>
            <?php
              $fullAddress = htmlspecialchars($event['address'] . ', ' . $event['postal_code'] . ' ' . $event['city'] . ', ' . $event['country']);
              $eventName = htmlspecialchars($event['name']);
              $eventDate = date('d F Y', strtotime($event['date']));
              $startTime = date('H:i', strtotime($event['start_time']));
              $endTime = date('H:i', strtotime($event['end_time']));
            ?>
            <div class="event-card" onclick="showOnMap(this)" 
                 data-address="<?= $fullAddress ?>"
                 data-name="<?= $eventName ?>">
              <h3><?= $eventName ?></h3>
              <div class="event-info">
                <span class="icon">📅</span>
                <span><?= $eventDate ?></span>
              </div>
              <div class="event-info">
                <span class="icon">⏰</span>
                <span><?= $startTime ?> - <?= $endTime ?></span>
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

      <!-- Carte Google Maps -->
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
    </script>
  </body>
</html>