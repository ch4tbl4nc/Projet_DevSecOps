# 🔒 Configuration ZapProxy (OWASP ZAP)

## Vue d'ensemble

ZapProxy est un outil de scanning de sécurité automatisé qui détecte les vulnérabilités web courantes :
- ✅ Injections SQL
- ✅ XSS (Cross-Site Scripting)
- ✅ CSRF (Cross-Site Request Forgery)
- ✅ Authentification cassée
- ✅ Exposition de données sensibles
- ✅ Et plus...

## Configuration dans le CI/CD

### Fichiers de configuration
- **rules.tsv** : Règles de scan ZapProxy
- **CD.yml** : Configuration GitHub Actions avec deux types de scan :
  1. **Baseline Scan** : À chaque push (rapide, ~2-3 min)
  2. **Full Scan** : Une fois par nuit (complet, ~10-30 min)

### Étapes du scan

1. **Démarrage du serveur PHP** sur `http://127.0.0.1:8080`
2. **Scan ZapProxy Baseline** pour les vulnérabilités critiques
3. **Upload du rapport** en artifacts
4. **Création automatique d'une Issue** si des vulnérabilités sont trouvées

## Utilisation locale

### Installation de ZapProxy

```bash
# Sur Windows
choco install zaproxy

# Sur macOS
brew install zaproxy

# Sur Linux
sudo apt-get install zaproxy
```

### Scan local

```bash
# Démarrer votre serveur
php -S localhost:8080 -t ./public

# Dans un autre terminal
zaproxy.sh -cmd \
  -quickurl http://localhost:8080 \
  -quickout ./zap-report.html
```

## Comprendre les résultats

### Severité des alertes
- **FAIL** : Critique, doit être corrigé avant le déploiement
- **WARN** : Attention, devrait être vérifié
- **PASS** : Info, aucune action requise

### Exemple de rapport
Les rapports sont disponibles dans :
- **GitHub Actions** → Artifacts → `zap-scan-report`
- **Issues GitHub** : Créées automatiquement si vulnérabilités critiques

## Amélioration progressive

### Ajouter des exclusions
Modifiez `.zap/rules.tsv` pour :
- Ignorer des faux positifs
- Adapter le scan à votre contexte

### Intégration avec SonarCloud
Combinez avec SonarCloud pour une couverture SAST + DAST complète

### Pipeline de sécurité complet
1. **CI (ci.yml)** : Tests unitaires + Linting PHP
2. **SAST (SonarCloud)** : Analyse statique du code
3. **DAST (ZapProxy)** : Test dynamique du site web

## Ressources

- [Documentation ZapProxy](https://www.zaproxy.org/docs/)
- [GitHub Action ZapProxy](https://github.com/zaproxy/action-baseline)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)

## Troubleshooting

### Serveur PHP ne démarre pas
```bash
# Vérifier les logs
cat /tmp/php.log

# Vérifier le port
lsof -i :8080
```

### Faux positifs
Modifiez `rules.tsv` pour ignorer les alertes non pertinentes :
```tsv
10021 | X-Content-Type-Options Header Missing | IGNORE
```

### Scan trop lent
Utilisez le Baseline Scan au lieu du Full Scan pour les tests rapides.
