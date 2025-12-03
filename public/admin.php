<?php
session_start();
require_once __DIR__ . '/../privée/database.php';
use Privee\Database;

$pdo = Database::getPdo();

// Vérifier si l'utilisateur est connecté et admin
if (!isset($_SESSION['user_id'])) {
    header('Location: views/login.html');
    exit;
}

$stmt = $pdo->prepare('SELECT is_admin FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch();

if (!$currentUser || $currentUser['is_admin'] != 1) {
    header('Location: events.php');
    exit;
}

// Traitement des actions admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Changer le statut admin d'un utilisateur
    if (isset($_POST['toggle_admin'])) {
        $userId = $_POST['user_id'];
        $newStatus = $_POST['new_status'];
        
        // Empêcher de se retirer ses propres droits admin
        if ($userId != $_SESSION['user_id']) {
            $stmt = $pdo->prepare('UPDATE users SET is_admin = ? WHERE id = ?');
            $stmt->execute([$newStatus, $userId]);
        }
    }
    
    // Supprimer un utilisateur
    if (isset($_POST['delete_user'])) {
        $userId = $_POST['user_id'];
        
        // Empêcher de se supprimer soi-même
        if ($userId != $_SESSION['user_id']) {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$userId]);
        }
    }
    
    // Supprimer un événement
    if (isset($_POST['delete_event'])) {
        $eventId = $_POST['event_id'];
        
        // Récupérer le chemin de l'image avant suppression
        $stmt = $pdo->prepare('SELECT image_path FROM events WHERE id = ?');
        $stmt->execute([$eventId]);
        $event = $stmt->fetch();
        
        // Supprimer l'image si elle existe
        if ($event && $event['image_path'] && file_exists(__DIR__ . '/' . $event['image_path'])) {
            unlink(__DIR__ . '/' . $event['image_path']);
        }
        
        $stmt = $pdo->prepare('DELETE FROM events WHERE id = ?');
        $stmt->execute([$eventId]);
    }
    
    // Redirection pour éviter la resoumission
    header('Location: admin.php');
    exit;
}

// Récupérer les statistiques
$totalUsers = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalAdmins = $pdo->query('SELECT COUNT(*) FROM users WHERE is_admin = 1')->fetchColumn();
$totalEvents = $pdo->query('SELECT COUNT(*) FROM events')->fetchColumn();
$upcomingEvents = $pdo->query('SELECT COUNT(*) FROM events WHERE date >= CURDATE()')->fetchColumn();
$pastEvents = $pdo->query('SELECT COUNT(*) FROM events WHERE date < CURDATE()')->fetchColumn();

// Récupérer tous les utilisateurs
$users = $pdo->query('SELECT id, username, email, is_admin, created_at FROM users ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

// Récupérer tous les événements
$events = $pdo->query('SELECT id, name, date, city, theme, created_at FROM events ORDER BY date DESC')->fetchAll(PDO::FETCH_ASSOC);

// Statistiques par mois (6 derniers mois)
$monthlyStats = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM events 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Administration - GUARDIA</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="css/admin.css">
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

    <div class="admin-container">
      <!-- Header -->
      <div class="header">
        <h1><i class="fas fa-shield-halved"></i> Dashboard Administration</h1>
        <p>Gérez les utilisateurs et les événements GUARDIA</p>
      </div>

      <!-- Navigation -->
      <div class="nav-buttons">
        <a href="events.php" class="btn-nav"><i class="fas fa-list"></i> Voir les événements</a>
        <a href="form.php" class="btn-nav"><i class="fas fa-plus"></i> Créer un événement</a>
        <a href="logout.php" class="btn-nav btn-logout"><i class="fas fa-right-from-bracket"></i> Déconnexion</a>
      </div>

      <!-- Statistiques -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon"><i class="fas fa-users"></i></div>
          <div class="stat-info">
            <span class="stat-number"><?= $totalUsers ?></span>
            <span class="stat-label">Utilisateurs</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon"><i class="fas fa-user-shield"></i></div>
          <div class="stat-info">
            <span class="stat-number"><?= $totalAdmins ?></span>
            <span class="stat-label">Administrateurs</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
          <div class="stat-info">
            <span class="stat-number"><?= $totalEvents ?></span>
            <span class="stat-label">Événements total</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon"><i class="fas fa-calendar-days"></i></div>
          <div class="stat-info">
            <span class="stat-number"><?= $upcomingEvents ?></span>
            <span class="stat-label">À venir</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon"><i class="fas fa-calendar-xmark"></i></div>
          <div class="stat-info">
            <span class="stat-number"><?= $pastEvents ?></span>
            <span class="stat-label">Passés</span>
          </div>
        </div>
      </div>

      <!-- Tabs Navigation -->
      <div class="tabs">
        <button class="tab-btn active" onclick="showTab('users')"><i class="fas fa-users"></i> Utilisateurs (<?= $totalUsers ?>)</button>
        <button class="tab-btn" onclick="showTab('events')"><i class="fas fa-calendar-check"></i> Événements (<?= $totalEvents ?>)</button>
      </div>

      <!-- Tab Content: Utilisateurs -->
      <div id="users-tab" class="tab-content active">
        <div class="section-card">
          <h2><i class="fas fa-users"></i> Gestion des Utilisateurs</h2>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Nom d'utilisateur</th>
                  <th>Email</th>
                  <th>Rôle</th>
                  <th>Inscrit le</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($users as $user): ?>
                  <tr class="<?= $user['is_admin'] ? 'admin-row' : '' ?>">
                    <td><?= $user['id'] ?></td>
                    <td>
                      <span class="username"><?= htmlspecialchars($user['username']) ?></span>
                      <?php if($user['id'] == $_SESSION['user_id']): ?>
                        <span class="badge badge-you">Vous</span>
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                      <?php if($user['is_admin']): ?>
                        <span class="badge badge-admin"><i class="fas fa-shield-halved"></i> Admin</span>
                      <?php else: ?>
                        <span class="badge badge-user"><i class="fas fa-user"></i> Utilisateur</span>
                      <?php endif; ?>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                    <td class="actions">
                      <?php if($user['id'] != $_SESSION['user_id']): ?>
                        <form method="POST" style="display:inline;">
                          <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                          <input type="hidden" name="new_status" value="<?= $user['is_admin'] ? 0 : 1 ?>">
                          <button type="submit" name="toggle_admin" class="btn-action <?= $user['is_admin'] ? 'btn-demote' : 'btn-promote' ?>">
                            <?= $user['is_admin'] ? '<i class="fas fa-arrow-down"></i> Rétrograder' : '<i class="fas fa-arrow-up"></i> Promouvoir' ?>
                          </button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cet utilisateur ?');">
                          <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                          <button type="submit" name="delete_user" class="btn-action btn-delete"><i class="fas fa-trash"></i> Supprimer</button>
                        </form>
                      <?php else: ?>
                        <span class="no-action">-</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Tab Content: Événements -->
      <div id="events-tab" class="tab-content">
        <div class="section-card">
          <h2><i class="fas fa-calendar-check"></i> Gestion des Événements</h2>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Nom</th>
                  <th>Date</th>
                  <th>Ville</th>
                  <th>Thème</th>
                  <th>Statut</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($events as $event): ?>
                  <?php 
                    $isPast = strtotime($event['date']) < strtotime('today');
                  ?>
                  <tr class="<?= $isPast ? 'past-event' : 'upcoming-event' ?>">
                    <td><?= $event['id'] ?></td>
                    <td><span class="event-name"><?= htmlspecialchars($event['name']) ?></span></td>
                    <td><?= date('d/m/Y', strtotime($event['date'])) ?></td>
                    <td><?= htmlspecialchars($event['city']) ?></td>
                    <td>
                      <?php if($event['theme']): ?>
                        <span class="badge badge-theme"><?= htmlspecialchars($event['theme']) ?></span>
                      <?php else: ?>
                        <span class="no-theme">-</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if($isPast): ?>
                        <span class="badge badge-past"><i class="fas fa-calendar-xmark"></i> Passé</span>
                      <?php else: ?>
                        <span class="badge badge-upcoming"><i class="fas fa-calendar-days"></i> À venir</span>
                      <?php endif; ?>
                    </td>
                    <td class="actions">
                      <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cet événement ?');">
                        <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                        <button type="submit" name="delete_event" class="btn-action btn-delete"><i class="fas fa-trash"></i> Supprimer</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <script>
      // Gestion des onglets
      function showTab(tabName) {
        // Cacher tous les contenus
        document.querySelectorAll('.tab-content').forEach(content => {
          content.classList.remove('active');
        });
        document.querySelectorAll('.tab-btn').forEach(btn => {
          btn.classList.remove('active');
        });

        // Afficher l'onglet sélectionné
        document.getElementById(tabName + '-tab').classList.add('active');
        event.target.classList.add('active');
      }

      /* === SCRIPT MODE NUIT === */
      const themeBtn = document.getElementById('theme-btn');
      const html = document.documentElement;

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
