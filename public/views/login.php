<?php
require_once __DIR__ . '/../security-headers.php';
session_start();

// Si déjà connecté, rediriger vers events
if (isset($_SESSION['user_id'])) {
    header('Location: ../events.php');
    exit;
}

$error = $_GET['error'] ?? null;
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - MonAgendaPro</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .auth-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 120px 20px 40px;
        }
        .auth-container {
            max-width: 450px;
            width: 100%;
            padding: 40px;
            background: rgba(20, 30, 50, 0.95);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(25, 103, 210, 0.3);
            border: 2px solid rgba(25, 103, 210, 0.4);
            backdrop-filter: blur(10px);
        }
        .auth-container h1 {
            text-align: center;
            color: #ffffff;
            margin-bottom: 10px;
            font-family: 'Orbitron', sans-serif;
            text-shadow: 0 0 10px rgba(25, 103, 210, 0.5);
        }
        .auth-container h1 i {
            margin-right: 10px;
            color: #1967d2;
        }
        .auth-subtitle {
            text-align: center;
            color: #a0aec0;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            color: #e2e8f0;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid rgba(25, 103, 210, 0.3);
            border-radius: 8px;
            background: rgba(15, 23, 42, 0.8);
            color: #ffffff;
            font-size: 16px;
            transition: all 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #1967d2;
            box-shadow: 0 0 15px rgba(25, 103, 210, 0.4);
        }
        .form-group input::placeholder {
            color: #64748b;
        }
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1967d2 0%, #1557b0 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(25, 103, 210, 0.4);
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(25, 103, 210, 0.6);
        }
        .btn-submit i {
            margin-right: 8px;
        }
        .auth-footer {
            text-align: center;
            margin-top: 25px;
            color: #a0aec0;
        }
        .auth-footer a {
            color: #1967d2;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }
        .auth-footer a:hover {
            text-shadow: 0 0 10px rgba(25, 103, 210, 0.8);
        }
        .error-message {
            background: rgba(220, 38, 38, 0.2);
            border: 1px solid rgba(220, 38, 38, 0.5);
            color: #fca5a5;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>
    <!-- Navbar identique à index.html -->
    <nav class="navbar">
        <a href="index.html" class="nav-logo">MonAgendaPro</a>
        <div class="nav-links">
            <a href="index.html" class="nav-item">Accueil</a>
            <a href="founder.html" class="nav-item">Les Fondateurs</a>
            <a href="register.php" class="nav-btn-connexion">S'inscrire</a>
        </div>
    </nav>

    <!-- Animation d'arrière-plan -->
    <div class="background-animation">
        <div class="orbit orbit1"><div class="cercle1"></div></div>
        <div class="cercle2"></div>
        <div class="orbit orbit3"><div class="cercle3"></div></div>
    </div>

    <div class="auth-wrapper">

    <div class="auth-container">
        <h1><i class="fas fa-calendar-check"></i> Connexion</h1>
        <p class="auth-subtitle">Accédez à votre espace MonAgendaPro</p>

        <?php if ($error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <span>
            <?php
                switch($error) {
                    case 'csrf': echo 'Session expirée. Veuillez réessayer.'; break;
                    case 'invalid': echo 'Identifiants incorrects.'; break;
                    case 'empty': echo 'Veuillez remplir tous les champs.'; break;
                    default: echo 'Une erreur est survenue.';
                }
            ?>
            </span>
        </div>
        <?php endif; ?>

        <form method="POST" action="../login.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" required placeholder="Votre pseudo">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required placeholder="Votre mot de passe">
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-sign-in-alt"></i> Se connecter
            </button>
        </form>

        <div class="auth-footer">
            Pas encore inscrit ? <a href="register.php">Créer un compte</a>
        </div>
    </div>
    </div>

    <script type="module" src="https://unpkg.com/@splinetool/viewer@1.0.28/build/spline-viewer.js"></script>
</body>
</html>
