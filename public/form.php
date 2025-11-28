<?php
use Privee\Database;
$pdo = Database::getPdo();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $name = $_POST['name'];
  $theme = $_POST['theme'];
  $description = $_POST['description'];
  $date = $_POST['date'];
  $start_time = $_POST['heure_debut'];
  $end_time = $_POST['heure_fin'];
  $address = $_POST['address'];
  $city = $_POST['city'];
  $postal_code = $_POST['postal_code'];
  $country = $_POST['country'];

  $stmt = $pdo->prepare("INSERT INTO events (name, theme, description, date, start_time, end_time, address, city, postal_code, country) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->execute([$name, $theme, $description, $date, $start_time, $end_time, $address, $city, $postal_code, $country]);

  header('Location: events.php');
  exit;
}

$stmt = $pdo->query("SELECT * FROM events ORDER BY date DESC, start_time DESC");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Créer un Événement - GUARDIA</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="css/events_form.css">
  </head>
  <body>
    <!-- Bouton Mode Sombre -->
    <button id="theme-btn" style="position:fixed; top:20px; right:20px; z-index:1000; padding:10px 15px; border-radius:30px; border:none; background:white; cursor:pointer; font-weight:bold; box-shadow:0 4px 10px rgba(0,0,0,0.2);">
      🌙 Mode Nuit
    </button>

    <!-- Cercles d'ambiance animés -->
    <div class="orbit orbit1">
      <div class="cercle1"></div>
    </div>
    <div class="orbit">
      <div class="cercle2"></div>
    </div>
    <div class="orbit orbit3">
      <div class="cercle3"></div>
    </div>

    <div class="container">
      <div class="header">
        <h1>📅 Créer un Événement</h1>
        <p>Remplissez le formulaire pour ajouter un nouvel événement GUARDIA</p>
      </div>

      <div class="form-container">
        <div id="success-message" class="success-message">
          ✓ Événement créé avec succès !
        </div>

        <form id="event-form" method="POST" action="form.php">
          <!-- Informations générales -->
          <div class="form-group">
            <label for="name">Nom de l'événement <span class="required">*</span></label>
            <input type="text" id="name" name="name" required placeholder="Ex: Conférence Cybersécurité 2025">
          </div>

          <div class="form-group">
            <label for="theme">Thème</label>
            <input type="text" id="theme" name="theme" maxlength="25" placeholder="Ex: Sécurité, Workshop, Hackathon">
          </div>

          <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" placeholder="Décrivez votre événement..."></textarea>
          </div>

          <!-- Date et heures -->
          <div class="form-row">
            <div class="form-group">
              <label for="date">Date <span class="required">*</span></label>
              <input type="date" id="date" name="date" required>
            </div>
            <div class="form-group">
              <label for="heure_debut">Heure de début <span class="required">*</span></label>
              <input type="time" id="heure_debut" name="heure_debut" required>
            </div>
            <div class="form-group">
              <label for="heure_fin">Heure de fin <span class="required">*</span></label>
              <input type="time" id="heure_fin" name="heure_fin" required>
            </div>
          </div>

          

          <!-- Adresse -->
          <div class="form-group">
            <label for="address">Adresse <span class="required">*</span></label>
            <input type="text" id="address" name="address" required placeholder="Ex: 22 Terr. Bellini">
          </div>

          <div class="form-row-3">
            <div class="form-group">
              <label for="city">Ville <span class="required">*</span></label>
              <input type="text" id="city" name="city" required placeholder="Ex: Puteaux">
            </div>
            <div class="form-group">
              <label for="postal_code">Code postal <span class="required">*</span></label>
              <input type="text" id="postal_code" name="postal_code" required placeholder="Ex: 92800">
            </div>
          </div>

          <div class="form-group">
            <label for="country">Pays</label>
            <input type="text" id="country" name="country" value="France" placeholder="France">
          </div>

          <!-- Aperçu de la carte -->
          <div class="map-preview">
            <iframe id="map-iframe" src=""></iframe>
          </div>

          <button type="submit" class="btn-submit">Créer l'événement</button>
        </form>

        <div style="text-align: center; margin-top: 30px;">
            <a href="events.php">📋 Voir tous les événements</a>
        </div>
      </div>
    </div>

    <script>
      // Mise à jour de la carte en temps réel
      const addressInput = document.getElementById('address');
      const cityInput = document.getElementById('city');
      const postalCodeInput = document.getElementById('postal_code');
      const countryInput = document.getElementById('country');
      const mapIframe = document.getElementById('map-iframe');

      function updateMap() {
        const address = addressInput.value;
        const city = cityInput.value;
        const postalCode = postalCodeInput.value;
        const country = countryInput.value || 'France';

        if (address && city && postalCode) {
          const fullAddress = `${address}, ${postalCode} ${city}, ${country}`;
          const encodedAddress = encodeURIComponent(fullAddress);
          mapIframe.src = `https://www.google.com/maps?q=${encodedAddress}&output=embed`;
        }
      }

      // Écouteurs d'événements pour mettre à jour la carte
      addressInput.addEventListener('input', updateMap);
      cityInput.addEventListener('input', updateMap);
      postalCodeInput.addEventListener('input', updateMap);
      countryInput.addEventListener('input', updateMap);

      // Gestion du formulaire
      document.getElementById('event-form').addEventListener('submit', function(e) {
        // Pour tester sans PHP, décommentez la ligne suivante
        // e.preventDefault();
        
        // Validation des heures
        const heureDebut = document.getElementById('heure_debut').value;
        const heureFin = document.getElementById('heure_fin').value;
        
        if (heureDebut && heureFin && heureFin <= heureDebut) {
          alert('L\'heure de fin doit être après l\'heure de début');
          e.preventDefault();
          return false;
        }

        // Afficher un message de succès (pour test sans PHP)
        // document.getElementById('success-message').style.display = 'block';
        // setTimeout(() => {
        //   document.getElementById('event-form').reset();
        //   mapIframe.src = '';
        //   document.getElementById('success-message').style.display = 'none';
        // }, 3000);
      });

      // Définir la date minimale à aujourd'hui
      const today = new Date().toISOString().split('T')[0];
      document.getElementById('date').setAttribute('min', today);

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