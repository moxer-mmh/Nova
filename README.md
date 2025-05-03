# Nova Book Store - Projet e-Commerce

## Description

Nova est une plateforme e-commerce de vente de livres développée en PHP avec une base de données Oracle. Cette application permet aux utilisateurs de parcourir un catalogue de livres, de les ajouter à leur panier et de passer des commandes. Les administrateurs peuvent gérer les livres, les commandes et les utilisateurs.

## Fonctionnalités

### Utilisateurs

- Parcourir le catalogue de livres
- Rechercher des livres (filtres par titre, auteur, catégorie)
- Consulter les détails d'un livre
- Créer un compte utilisateur
- Se connecter/déconnecter
- Ajouter des livres au panier
- Gérer le contenu du panier
- Passer des commandes
- Consulter l'historique des commandes
- Modifier les informations du profil

### Administrateurs

- Gérer le catalogue de livres (ajouter, modifier, supprimer)
- Gérer les commandes (visualiser, modifier le statut)
- Consulter les statistiques de vente

## Technologies utilisées

- PHP 8.0+
- Oracle Database
- HTML5, CSS3, JavaScript
- Architecture MVC personnalisée

## Prérequis

- PHP 8.0 ou supérieur avec extension Oracle OCI8
- Oracle Database 11g ou supérieur
- Serveur web (Apache, Nginx, etc.)

## Installation

1. Cloner le dépôt

   ```
   git clone https://github.com/votre-username/Nova.git
   ```
2. Configurer la base de données Oracle

   - Créer un utilisateur Oracle nommé 'Nova'
   - Exécuter les scripts SQL du dossier `database/`
3. Configurer l'application

   - Copier le fichier `backend/config/config.sample.php` vers `backend/config/config.local.php`
   - Modifier les informations de connexion dans `config.local.php`
4. Configurer le serveur web

   - Configurer le document root vers le dossier du projet
   - Activer la réécriture d'URL si nécessaire
5. Accéder à l'application

   - URL publique : `http://votre-domaine/`
   - Admin : `http://votre-domaine/pages/admin/dashboard.php`
   - Identifiants admin par défaut : `admin / admin123`

## Structure du projet

```
/
├── backend/           # Code serveur
│   ├── api/           # API endpoints
│   ├── config/        # Configuration
│   ├── includes/      # Fragments réutilisables
│   └── models/        # Modèles de données
├── database/          # Scripts SQL
├── frontend/          # Assets client
│   ├── assets/
│   │   ├── css/       # Feuilles de style
│   │   ├── js/        # Scripts JavaScript
│   │   └── images/    # Images
├── logs/              # Journaux
├── pages/             # Pages de l'application
│   ├── admin/         # Administration
│   └── ...            # Autres pages
└── utils/             # Utilitaires
```
