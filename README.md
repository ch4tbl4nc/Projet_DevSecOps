# 🗓️ MonAgendaPro

> **Plateforme de gestion d'événements moderne et sécurisée**

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=flat-square&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=flat-square&logo=docker)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

---

## 📋 Table des Matières

- [Présentation](#-présentation)
- [Fonctionnalités](#-fonctionnalités)
- [Architecture](#-architecture)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Utilisation](#-utilisation)
- [Sécurité](#-sécurité)
- [API Météo](#-api-météo)
- [Structure du Projet](#-structure-du-projet)
- [Technologies](#-technologies)

---

## 🎯 Présentation

**MonAgendaPro** est une application web complète permettant de créer, gérer et visualiser des événements. Elle offre une interface moderne avec un design futuriste, une intégration météo en temps réel et une géolocalisation via Google Maps.

### Points Forts
- 🎨 Design futuriste avec thème sombre
- 🔐 Sécurité renforcée (CSRF, XSS, SQL Injection)
- 🌤️ Prévisions météo intégrées
- 🗺️ Localisation Google Maps
- 🐳 Déploiement Docker simplifié
- 📱 Interface responsive

---

## ✨ Fonctionnalités

### 👤 Gestion des Utilisateurs
| Fonctionnalité | Description |
|----------------|-------------|
| **Inscription** | Création de compte avec validation email |
| **Connexion** | Authentification sécurisée avec hashage bcrypt |
| **Déconnexion** | Destruction complète de session |
| **Rôles** | Utilisateur standard / Administrateur |

### 📅 Gestion des Événements
| Fonctionnalité | Description |
|----------------|-------------|
| **Création** | Formulaire complet (nom, date, lieu, description, image) |
| **Événements multi-jours** | Support des événements sur plusieurs jours |
| **Upload d'images** | Images d'événements (JPG, PNG, GIF, WebP - max 5MB) |
| **Thématiques** | Catégorisation par thème |
| **Localisation** | Adresse complète avec code postal et pays |

### 🛠️ Panel Administration
| Fonctionnalité | Description |
|----------------|-------------|
| **Dashboard** | Statistiques globales (utilisateurs, événements) |
| **Gestion utilisateurs** | Promotion admin, suppression |
| **Gestion événements** | Suppression d'événements |
| **Statistiques** | Graphiques mensuels |

### 🌤️ Intégration Météo
- Prévisions sur 5 jours via OpenWeatherMap
- Affichage température, humidité, vent, ressenti
- Icônes météo dynamiques
- Support multi-pays

### 🗺️ Géolocalisation
- Carte Google Maps intégrée
- Localisation automatique des événements
- Adresse complète affichée

---

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        NAVIGATEUR                            │
│                    (HTML/CSS/JavaScript)                     │
└─────────────────────────┬───────────────────────────────────┘
                          │ HTTP/HTTPS
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                    DOCKER COMPOSE                            │
│  ┌─────────────────┐  ┌─────────────┐  ┌─────────────────┐  │
│  │    APACHE       │  │   MySQL 8   │  │   phpMyAdmin    │  │
│  │    PHP 8.2      │  │             │  │                 │  │
│  │    Port 8080    │  │  Port 3306  │  │   Port 8081     │  │
│  └────────┬────────┘  └──────┬──────┘  └─────────────────┘  │
│           │                  │                               │
│           └──────────────────┘                               │
│                    PDO/MySQL                                 │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼ API REST
┌─────────────────────────────────────────────────────────────┐
│                   OPENWEATHERMAP API                         │
│                  (Prévisions météo)                          │
└─────────────────────────────────────────────────────────────┘
```

---

## 🚀 Installation

### Prérequis
- [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- [Git](https://git-scm.com/)
- Clé API [OpenWeatherMap](https://openweathermap.org/api) (gratuite)

### Étapes d'installation

#### 1. Cloner le projet
```bash
git clone https://github.com/ch4tbl4nc/Projet_DevSecOps.git
cd Projet_DevSecOps
```

#### 2. Configurer l'environnement
```bash
# Copier le fichier d'exemple (ou créer .env)
cp .env.example .env

# Éditer .env avec vos paramètres
```

#### 3. Lancer avec Docker
```bash
docker-compose up -d --build
```

#### 4. Accéder à l'application
| Service | URL |
|---------|-----|
| **Site Web** | http://localhost:8080 |
| **phpMyAdmin** | http://localhost:8081 |

#### 5. Initialiser la base de données
- Accéder à phpMyAdmin (http://localhost:8081)
- Utilisateur : `root` / Mot de passe : `rootpassword`
- Importer le fichier `private/accounts.sql`

---

## ⚙️ Configuration

### Variables d'environnement (.env)

```env
# Base de données
MYSQL_ROOT_PASSWORD=rootpassword
MYSQL_DATABASE=monagendapro_db

# API Météo (obligatoire)
OPENWEATHER_API_KEY=votre_cle_api_ici
```

### docker-compose.yml

```yaml
services:
  website:
    build: .
    ports:
      - "8080:80"
    volumes:
      - ./public:/var/www/html
      - ./private:/var/www/private
    environment:
      - OPENWEATHER_API_KEY=${OPENWEATHER_API_KEY}

  db:
    image: mysql:8.0
    environment:
      - MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
      - MYSQL_DATABASE=${MYSQL_DATABASE}

  phpmyadmin:
    image: phpmyadmin
    ports:
      - "8081:80"
```

---

## 📖 Utilisation

### Première utilisation

1. **Créer un compte** : Cliquer sur "S'inscrire" depuis la page d'accueil
2. **Se connecter** : Utiliser vos identifiants
3. **Voir les événements** : Page principale après connexion

### Devenir Administrateur

1. Accéder à phpMyAdmin (http://localhost:8081)
2. Ouvrir la table `users`
3. Modifier `is_admin` à `1` pour votre utilisateur

### Créer un événement (Admin)

1. Cliquer sur "Créer un événement"
2. Remplir le formulaire :
   - Nom de l'événement
   - Date(s) et heures
   - Adresse complète
   - Description (optionnel)
   - Image (optionnel)
3. Valider

### Panel Administration

Accessible uniquement aux admins :
- Voir les statistiques
- Gérer les utilisateurs (promouvoir/supprimer)
- Supprimer des événements

---

## 🔐 Sécurité

### Mesures implémentées

| Protection | Implémentation |
|------------|----------------|
| **Injection SQL** | Requêtes préparées PDO |
| **XSS** | `htmlspecialchars()` avec ENT_QUOTES |
| **CSRF** | Tokens 64 caractères + `hash_equals()` |
| **Hashage MDP** | `password_hash()` avec bcrypt |
| **Sessions** | HttpOnly, Secure, SameSite=Strict |
| **Upload** | Vérification MIME réel + extension + taille |

### Headers de sécurité

```php
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Content-Security-Policy: [...]
```

### Fichiers sensibles

Les fichiers sensibles sont dans `/private/` (hors du document root) :
- `database.php` - Connexion BDD
- `config.php` - Configuration API
- `WeatherService.php` - Service météo

---

## 🌤️ API Météo

### Configuration

1. Créer un compte sur [OpenWeatherMap](https://openweathermap.org/)
2. Générer une clé API (gratuite)
3. Ajouter dans `.env` :
   ```env
   OPENWEATHER_API_KEY=votre_cle_api
   ```

### Fonctionnement

- **Endpoint** : `get_weather.php`
- **Méthode** : GET
- **Paramètres** :
  - `city` : Nom de la ville
  - `date` : Date (YYYY-MM-DD)
  - `country` : Code pays (FR, US, etc.)

### Exemple de réponse

```json
{
  "available": true,
  "city": "Paris",
  "temp": 12,
  "temp_min": 8,
  "temp_max": 15,
  "humidity": 75,
  "wind_speed": 12,
  "feels_like": 10,
  "description": "nuageux",
  "main": "Clouds",
  "clouds": 80,
  "icon_fa": "fa-cloud"
}
```

---

## 📁 Structure du Projet

```
Projet_DevSecOps/
├── 📄 docker-compose.yml      # Configuration Docker
├── 📄 Dockerfile              # Image PHP/Apache
├── 📄 .env                    # Variables d'environnement
├── 📄 README.md               # Documentation
│
├── 📁 private/                # Fichiers sensibles (hors web)
│   ├── accounts.sql           # Script création BDD
│   ├── config.php             # Configuration (API key)
│   ├── database.php           # Connexion PDO
│   └── WeatherService.php     # Service météo
│
└── 📁 public/                 # Document root Apache
    ├── index.php              # Point d'entrée
    ├── login.php              # Traitement connexion
    ├── register.php           # Traitement inscription
    ├── logout.php             # Déconnexion
    ├── events.php             # Liste des événements
    ├── form.php               # Création d'événement
    ├── admin.php              # Panel administration
    ├── get_weather.php        # API météo interne
    ├── security-headers.php   # Configuration sécurité
    │
    ├── 📁 views/              # Pages HTML/PHP
    │   ├── index.html         # Page d'accueil
    │   ├── login.php          # Formulaire connexion
    │   ├── register.php       # Formulaire inscription
    │   └── founder.html       # Page fondateurs
    │
    ├── 📁 css/                # Feuilles de style
    │   ├── style.css          # Style page d'accueil
    │   ├── events_form.css    # Style événements/formulaire
    │   └── admin.css          # Style administration
    │
    ├── 📁 img/                # Images statiques
    │
    └── 📁 uploads/            # Fichiers uploadés
        └── events/            # Images des événements
```

---

## 🛠️ Technologies

### Backend
| Technologie | Version | Usage |
|-------------|---------|-------|
| PHP | 8.2 | Langage serveur |
| Apache | 2.4 | Serveur web |
| MySQL | 8.0 | Base de données |
| PDO | - | Abstraction BDD |

### Frontend
| Technologie | Usage |
|-------------|-------|
| HTML5 | Structure |
| CSS3 | Styles + animations |
| JavaScript | Interactivité |
| Font Awesome | Icônes |
| Google Fonts (Orbitron) | Typographie |

### Infrastructure
| Technologie | Usage |
|-------------|-------|
| Docker | Conteneurisation |
| Docker Compose | Orchestration |
| phpMyAdmin | Administration BDD |

### APIs Externes
| Service | Usage |
|---------|-------|
| OpenWeatherMap | Prévisions météo |
| Google Maps | Géolocalisation |
| Spline | Animation 3D (accueil) |

---

## 📊 Base de Données

### Schéma

```sql
-- Table des utilisateurs
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des événements
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    theme VARCHAR(50),
    description TEXT,
    date DATE NOT NULL,
    end_date DATE,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    postal_code VARCHAR(10),
    country VARCHAR(50) DEFAULT 'France',
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 🔧 Commandes Utiles

```bash
# Démarrer les conteneurs
docker-compose up -d

# Reconstruire après modifications
docker-compose up -d --build

# Voir les logs
docker-compose logs -f website

# Arrêter les conteneurs
docker-compose down

# Supprimer tout (volumes inclus)
docker-compose down -v

# Accéder au conteneur PHP
docker exec -it MonAgendaPro_website bash
```

---

## 👥 Auteurs

Projet réalisé dans le cadre du cours **DevSecOps**.

---

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

<p align="center">
  <strong>MonAgendaPro</strong> - L'avenir de la gestion d'événements 🚀
</p>
