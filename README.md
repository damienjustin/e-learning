# Bloomin LMS

CMS spécialisé e-learning par [Bloomin](https://bloomin.agency), en PHP/JS/HTML/CSS natif, conçu comme un template installable (façon WordPress) : un cœur (`core/`), un système de thèmes (`themes/`), une administration (`admin/`), et un installeur web (`install/`).

## Fonctionnalités

- Cours &rarr; Modules &rarr; Leçons (texte/vidéo) &rarr; Quiz (choix unique/multiple, score de réussite, tentatives limitées)
- Comptes utilisateurs avec rôles : administrateur, formateur, étudiant
- Inscription aux cours, suivi de progression des leçons, tentatives de quiz historisées
- Thème personnalisable à 100% : dupliquez `themes/default` pour créer un nouveau thème, puis activez-le dans Administration &rarr; Réglages
- Système de hooks (`core/Hooks.php`) pour étendre le CMS sans toucher au cœur

## Sécurité intégrée

- Toutes les requêtes SQL utilisent des requêtes préparées PDO (pas de concaténation)
- Mots de passe hashés avec `password_hash` (bcrypt/argon selon PHP), re-hash automatique
- Protection CSRF sur tous les formulaires (jeton par session, vérifié via `hash_equals`)
- Sessions sécurisées : cookies `HttpOnly`, `SameSite=Lax`, régénération périodique de l'ID de session, régénération à la connexion
- Limitation des tentatives de connexion (anti brute-force, par IP + email)
- En-têtes de sécurité HTTP (CSP, X-Frame-Options, X-Content-Type-Options, HSTS si HTTPS)
- Accès direct aux dossiers `config/`, `core/`, `includes/`, `database/` bloqué via `.htaccess`
- Contrôle d'accès par rôle sur chaque page d'administration et chaque action sensible

## Installation

1. Copiez l'ensemble du projet sur votre serveur (Apache + `mod_rewrite`, PHP 8.1+, MySQL/MariaDB)
2. Donnez les droits d'écriture sur `config/` et `uploads/` au serveur web
3. Rendez-vous sur `https://votre-site/install/` et suivez l'assistant :
   - informations de connexion à la base de données (elle sera créée automatiquement)
   - nom du site
   - création du compte administrateur
4. Une fois l'installation terminée, supprimez ou protégez le dossier `install/`
5. Connectez-vous sur `/login` avec le compte administrateur créé

## Structure

```
admin/        Interface d'administration (cours, utilisateurs, réglages)
config/       Configuration (config.php généré par l'installeur, ignoré par git)
core/         Classes du cœur (Database, Auth, Security, Hooks, Router, View, Config)
database/     Schéma SQL
includes/     Bootstrap + contrôleurs du site public
install/      Assistant d'installation web
themes/       Thèmes (le thème "default" est fourni, 100% personnalisable)
uploads/      Fichiers uploadés (vidéos, documents de cours)
```

## Mises à jour façon WordPress

Chaque site installé peut se mettre à jour depuis Administration &rarr; Mises à jour (réservé aux administrateurs) :

- Le CMS interroge l'API GitHub (`/repos/damienjustin/e-learning/releases/latest`) pour connaître la dernière version disponible.
- En cliquant sur "Mettre à jour", il télécharge l'archive de la release, et **remplace uniquement** : `core/`, `admin/`, `includes/`, `install/`, `themes/default/`, `database/schema.sql`, `database/migrations/`, `index.php`, `.htaccess`.
- **Jamais touché** : `config/config.php`, `uploads/`, les thèmes personnalisés (tout dossier sous `themes/` autre que `default`), et bien sûr le contenu de la base de données.
- Les évolutions de base de données passent par `database/migrations/*.sql`, exécutées une seule fois et de façon strictement additive (ajout de colonnes/tables, jamais de suppression) — voir `core/Migrator.php`.

### Publier une nouvelle version (côté mainteneur)

1. Faites vos changements sur le cœur du CMS (et si besoin un fichier `database/migrations/000X_description.sql` pour les changements de schéma)
2. Mettez à jour `core/Version.php` (`Version::CURRENT`)
3. Créez une release GitHub avec un tag (ex. `v1.1.0`) sur ce dépôt — la release "latest" est ce que les sites installés iront vérifier
4. Les sites verront la mise à jour disponible la prochaine fois qu'un administrateur ouvre la page Mises à jour

## Créer un nouveau thème

Dupliquez `themes/default` sous `themes/mon-theme`, modifiez les fichiers PHP (`layout.php`, `home.php`, `course_show.php`, etc.) et les assets CSS/JS, puis activez-le depuis Administration &rarr; Réglages.
