<?php
require_once __DIR__ . '/security-headers.php';
session_start();
require_once '/var/www/private/database.php';
use Privee\Database;
$pdo = Database::getPdo();

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: events.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Vérification CSRF
  $csrf_token = $_POST['csrf_token'] ?? '';
  if (!verifyCsrfToken($csrf_token)) {
      header('Location: form.php?error=csrf');
      exit;
  }

  // Sanitization des entrées
  $name = sanitizeInput($_POST['name'] ?? '');
  $theme = sanitizeInput($_POST['theme'] ?? '');
  $description = sanitizeInput($_POST['description'] ?? '');
  $address = sanitizeInput($_POST['address'] ?? '');
  $city = sanitizeInput($_POST['city'] ?? '');
  $postal_code = sanitizeInput($_POST['postal_code'] ?? '');
  $country = sanitizeInput($_POST['country'] ?? 'France');
  $duration_type = $_POST['duration_type'] ?? 'single';

  if ($duration_type === 'single') {
    $date = $_POST['date'] ?? '';
    $start_time = $_POST['heure_debut'] ?? '';
    $end_time = $_POST['heure_fin'] ?? '';
    $end_date = null;
  } else {
    $date = $_POST['date_debut_multi'] ?? '';
    $start_time = $_POST['heure_debut_multi'] ?? '';
    $end_time = $_POST['heure_fin_multi'] ?? '';
    $end_date = $_POST['date_fin_multi'] ?? '';
  }

  // Validation des champs requis
  if (empty($name) || empty($date) || empty($start_time) || empty($end_time) || empty($address) || empty($city)) {
      header('Location: form.php?error=empty');
      exit;
  }

  // Validation date et heure
  if (!validateDate($date) || !validateTime($start_time) || !validateTime($end_time)) {
      header('Location: form.php?error=format');
      exit;
  }

  // Gestion de l'upload d'image sécurisé
  $image_path = null;
  if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
    // Vérifier la taille (max 5MB)
    if ($_FILES['event_image']['size'] > 5 * 1024 * 1024) {
        header('Location: form.php?error=filesize');
        exit;
    }
    
    $upload_dir = __DIR__ . '/uploads/events/';
    if (!is_dir($upload_dir)) {
      mkdir($upload_dir, 0755, true);
    }
    
    // Vérifier le type MIME réel
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $_FILES['event_image']['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_extension = strtolower(pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (in_array($mime_type, $allowed_mimes) && in_array($file_extension, $allowed_extensions)) {
      $unique_name = uniqid('event_', true) . '.' . $file_extension;
      $destination = $upload_dir . $unique_name;
      
      if (move_uploaded_file($_FILES['event_image']['tmp_name'], $destination)) {
        $image_path = 'uploads/events/' . $unique_name;
      }
    }
  }

  $stmt = $pdo->prepare("INSERT INTO events (name, theme, description, date, start_time, end_time, end_date, address, city, postal_code, country, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->execute([$name, $theme, $description, $date, $start_time, $end_time, $end_date, $address, $city, $postal_code, $country, $image_path]);

  header('Location: events.php');
  exit;
}

// Générer le token CSRF pour le formulaire
$csrf_token = generateCsrfToken();

$stmt = $pdo->query("SELECT * FROM events ORDER BY date DESC, start_time DESC");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
  <head>
    <title>Créer un Événement - MonAgendaPro</title>
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
        <h1><i class="fas fa-calendar-plus"></i> Créer un Événement</h1>
        <p>Remplissez le formulaire pour ajouter un nouvel événement MonAgendaPro</p>
      </div>

      <div class="form-container">
        <div id="success-message" class="success-message">
          <i class="fas fa-check"></i> Événement créé avec succès !
        </div>

        <form id="event-form" method="POST" action="form.php" enctype="multipart/form-data">
          <!-- Token CSRF -->
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
          
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

          <div class="form-group">
            <label for="event_image">Image de l'événement (optionnel)</label>
            <input type="file" id="event_image" name="event_image" accept="image/*" onchange="previewImage(event)">
            <div id="image-preview" style="margin-top: 15px; display: none;">
              <img id="preview-img" src="" alt="Aperçu" style="max-width: 100%; max-height: 300px; border-radius: 8px; border: 2px solid var(--border-color);">
            </div>
          </div>

          <!-- Durée de l'événement -->
          <fieldset class="form-group" style="border: none; padding: 0; margin: 0;">
            <legend style="font-weight: 500; margin-bottom: 10px;">Durée de l'événement <span class="required">*</span></legend>
            <div style="display: flex; gap: 20px;">
              <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="radio" name="duration_type" value="single" checked onchange="toggleDurationType()">
                <span>Une journée</span>
              </label>
              <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="radio" name="duration_type" value="multi" onchange="toggleDurationType()">
                <span>Plusieurs jours</span>
              </label>
            </div>
          </fieldset>

          <!-- Date et heures pour une journée -->
          <div id="single-day-section">
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
          </div>

          <!-- Date et heures pour plusieurs jours -->
          <div id="multi-day-section" style="display: none;">
            <div class="form-row">
              <div class="form-group">
                <label for="date_debut_multi">Date de début <span class="required">*</span></label>
                <input type="date" id="date_debut_multi" name="date_debut_multi">
              </div>
              <div class="form-group">
                <label for="heure_debut_multi">Heure de début <span class="required">*</span></label>
                <input type="time" id="heure_debut_multi" name="heure_debut_multi">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="date_fin_multi">Date de fin <span class="required">*</span></label>
                <input type="date" id="date_fin_multi" name="date_fin_multi">
              </div>
              <div class="form-group">
                <label for="heure_fin_multi">Heure de fin <span class="required">*</span></label>
                <input type="time" id="heure_fin_multi" name="heure_fin_multi">
              </div>
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
            <iframe id="map-iframe" src="" title="Aperçu de la carte de l'événement"></iframe>
          </div>

          <button type="submit" class="btn-submit">Créer l'événement</button>
        </form>

        <div style="text-align: center; margin-top: 30px;">
            <a href="events.php"><i class="fas fa-list"></i> Voir tous les événements</a>
        </div>
      </div>
    </div>

    <script>
      // Prévisualisation de l'image
      function previewImage(event) {
        const file = event.target.files[0];
        const preview = document.getElementById('image-preview');
        const previewImg = document.getElementById('preview-img');
        
        if (file) {
          const reader = new FileReader();
          reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
          };
          reader.readAsDataURL(file);
        } else {
          preview.style.display = 'none';
        }
      }

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

      // Basculer entre une journée et plusieurs jours
      function toggleDurationType() {
        const durationType = document.querySelector('input[name="duration_type"]:checked').value;
        const singleDay = document.getElementById('single-day-section');
        const multiDay = document.getElementById('multi-day-section');
        
        if (durationType === 'single') {
          singleDay.style.display = 'block';
          multiDay.style.display = 'none';
          // Rendre les champs single obligatoires
          document.getElementById('date').required = true;
          document.getElementById('heure_debut').required = true;
          document.getElementById('heure_fin').required = true;
          // Rendre les champs multi optionnels
          document.getElementById('date_debut_multi').required = false;
          document.getElementById('heure_debut_multi').required = false;
          document.getElementById('date_fin_multi').required = false;
          document.getElementById('heure_fin_multi').required = false;
        } else {
          singleDay.style.display = 'none';
          multiDay.style.display = 'block';
          // Rendre les champs single optionnels
          document.getElementById('date').required = false;
          document.getElementById('heure_debut').required = false;
          document.getElementById('heure_fin').required = false;
          // Rendre les champs multi obligatoires
          document.getElementById('date_debut_multi').required = true;
          document.getElementById('heure_debut_multi').required = true;
          document.getElementById('date_fin_multi').required = true;
          document.getElementById('heure_fin_multi').required = true;
        }
      }

      // Gestion du formulaire
      document.getElementById('event-form').addEventListener('submit', function(e) {
        const durationType = document.querySelector('input[name="duration_type"]:checked').value;
        
        if (durationType === 'single') {
          // Validation des heures pour une journée
          const heureDebut = document.getElementById('heure_debut').value;
          const heureFin = document.getElementById('heure_fin').value;
          
          if (heureDebut && heureFin && heureFin <= heureDebut) {
            alert('L\'heure de fin doit être après l\'heure de début');
            e.preventDefault();
            return false;
          }
        } else {
          // Validation pour plusieurs jours
          const dateDebut = new Date(document.getElementById('date_debut_multi').value + ' ' + document.getElementById('heure_debut_multi').value);
          const dateFin = new Date(document.getElementById('date_fin_multi').value + ' ' + document.getElementById('heure_fin_multi').value);
          
          if (dateFin <= dateDebut) {
            alert('La date/heure de fin doit être après la date/heure de début');
            e.preventDefault();
            return false;
          }
        }
      });

      // Définir la date minimale à aujourd'hui
      const today = new Date().toISOString().split('T')[0];
      document.getElementById('date').setAttribute('min', today);
      document.getElementById('date_debut_multi').setAttribute('min', today);
      document.getElementById('date_fin_multi').setAttribute('min', today);

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
