# 🏦 API Banque - Système Bancaire Complet

## 📋 Présentation Générale

L'**API Banque** est une application web RESTful complète développée avec **Laravel 11.x** pour la gestion d'un système bancaire moderne. Elle offre une architecture sécurisée et scalable permettant de gérer les clients, comptes bancaires et transactions financières avec une authentification robuste basée sur **OAuth2** via Laravel Passport.

### 🎯 Objectif du Projet

Ce système bancaire API vise à fournir une plateforme digitale complète pour :
- **Gestion des clients** : Inscription, authentification et profils utilisateurs
- **Administration des comptes** : Création et gestion de comptes bancaires (chèque, courant, épargne)
- **Suivi des transactions** : Enregistrement et historique des opérations financières
- **Sécurité renforcée** : Authentification OAuth2, autorisation par rôles, rate limiting
- **Architecture API moderne** : RESTful, HATEOAS, pagination, documentation Swagger

### 🏗️ Architecture Technique

- **Framework** : Laravel 11.x (PHP 8.1+)
- **Authentification** : Laravel Passport (OAuth2)
- **Base de données** : MySQL/PostgreSQL avec migrations Eloquent
- **Documentation** : Swagger/OpenAPI (darkaonline/l5-swagger)
- **Tests** : PHPUnit avec factories et seeders
- **Déploiement** : Docker-ready avec nginx et configuration production

---

## 🌟 Fonctionnalités Principales

### 👥 Gestion des Utilisateurs
- **Inscription client** avec validation complète (nom, email, téléphone, mot de passe)
- **Authentification OAuth2** avec tokens d'accès et rafraîchissement
- **Rôles différenciés** : Admin (accès complet) vs Client (accès limité)
- **Middleware de sécurité** pour contrôle d'accès par rôle

### 💳 Gestion des Comptes Bancaires
- **Types de comptes** : Chèque, Courant, Épargne
- **Numéros uniques** générés automatiquement (format : CPT-XXXXXXXX)
- **Gestion des statuts** : Actif, Bloqué, Fermé
- **Soft deletes** pour archivage logique
- **Filtrage automatique** selon le type de compte

### 💸 Gestion des Transactions
- **Types d'opérations** : Dépôt, Retrait, Virement, Transfert
- **Références uniques** générées automatiquement
- **Support multi-devises** : FCFA, EUR, USD
- **Statuts de transaction** : En attente, Validée, Rejetée, Annulée
- **Historique complet** avec timestamps

### 🔐 Sécurité & Performance
- **Rate limiting** : 60 requêtes/minute par défaut
- **Validation stricte** des données entrantes
- **Gestion d'erreurs** structurée avec codes HTTP appropriés
- **Logs détaillés** pour monitoring et debugging
- **Middleware personnalisé** pour autorisation granulaire

---

## 🚀 Installation & Configuration

### Prérequis Système
- **PHP** : 8.1 ou supérieur
- **Composer** : Gestionnaire de dépendances PHP
- **Base de données** : MySQL 8.0+ ou PostgreSQL 13+
- **Serveur web** : Nginx/Apache avec mod_rewrite

### Étapes d'Installation

```bash
# 1. Cloner le repository
git clone <repository-url>
cd banque-api

# 2. Installer les dépendances PHP
composer install

# 3. Configuration de l'environnement
cp .env.example .env
php artisan key:generate

# 4. Configuration base de données (.env)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=banque_api
DB_USERNAME=votre_username
DB_PASSWORD=votre_password

# 5. Migration et seeding de la base
php artisan migrate
php artisan db:seed

# 6. Installation Laravel Passport
php artisan passport:install

# 7. Démarrage du serveur de développement
php artisan serve
```

### Configuration Passport OAuth2

```bash
# Installation des clés OAuth2
php artisan passport:install

# Les clients API sont créés automatiquement
# Récupérer client_id et client_secret dans oauth_clients
```

---

## 📚 Structure de l'Application

```
app/
├── Http/Controllers/Api/V1/
│   ├── AuthController.php          # Authentification
│   ├── CompteController.php        # Gestion comptes
│   └── TransactionController.php   # Gestion transactions
├── Models/
│   ├── User.php                    # Utilisateur principal
│   ├── Client.php                  # Profil client
│   ├── Compte.php                  # Compte bancaire
│   └── Transaction.php             # Transaction financière
├── Http/Middleware/
│   ├── RoleMiddleware.php          # Contrôle des rôles
│   └── RequestLoggerMiddleware.php # Logging des requêtes
├── Http/Resources/
│   ├── ClientResource.php
│   ├── CompteResource.php
│   └── TransactionResource.php
├── Scopes/
│   └── CompteScope.php             # Filtrage comptes actifs
├── Traits/
│   └── ApiResponseTrait.php        # Réponses API standardisées
└── Jobs/
    ├── MigrateBlockedAccountToNeon.php
    └── RestoreAccountFromNeon.php

database/
├── migrations/                     # Schémas base de données
├── factories/                      # Génération données factices
└── seeders/                        # Données de test

routes/
└── api.php                         # Définition routes API
```

---

## 🌐 API Endpoints - Documentation Complète

### Base URL
```
http://localhost:8000/api/v1/zeynab-ba
```

### 🔑 Authentification Requise
Tous les endpoints (sauf inscription/connexion) nécessitent un **Bearer Token** :
```
Authorization: Bearer {access_token}
```

---

## 1. 🔐 AUTHENTIFICATION

### `POST /auth/login` - Connexion Utilisateur
**Accès** : Public  
**Rate Limit** : 60/minute

**Corps de la requête** :
```json
{
  "email": "client@example.com",
  "password": "motdepasse123"
}
```

**Réponse succès (200)** :
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 2,
      "name": "Amadou Diop",
      "email": "amadou.diop@example.com",
      "role": "client"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
  },
  "message": "Connexion réussie"
}
```

### `POST /auth/register` - Inscription Client
**Accès** : Public  
**Rate Limit** : 60/minute

**Corps de la requête** :
```json
{
  "nom": "Diop",
  "prenom": "Amadou",
  "email": "amadou.diop@example.com",
  "telephone": "+221771234567",
  "password": "motdepasse123",
  "password_confirmation": "motdepasse123"
}
```

**Réponse succès (201)** :
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 2,
      "name": "Amadou Diop",
      "email": "amadou.diop@example.com",
      "role": "client"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
  },
  "message": "Inscription réussie"
}
```

### `POST /auth/logout` - Déconnexion
**Accès** : Authentifié  
**Rate Limit** : 60/minute

**Réponse succès (200)** :
```json
{
  "success": true,
  "message": "Déconnexion réussie"
}
```

### `GET /auth/user` - Informations Utilisateur Connecté
**Accès** : Authentifié  
**Rate Limit** : 60/minute

**Réponse succès (200)** :
```json
{
  "success": true,
  "data": {
    "id": 2,
    "name": "Amadou Diop",
    "email": "amadou.diop@example.com",
    "role": "client",
    "client": {
      "id": "uuid-client",
      "nom": "Diop",
      "prenom": "Amadou",
      "telephone": "+221771234567",
      "statut": "actif"
    }
  },
  "message": "Informations utilisateur récupérées"
}
```

---

## 2. 💳 GESTION DES COMPTES

### `GET /comptes` - Liste des Comptes
**Accès** : Authentifié (Admin: tous les comptes actifs chèque/épargne, Client: ses comptes actifs chèque/épargne)
**Rate Limit** : 60/minute

**Paramètres de requête** :
```javascript
{
  page: 1,           // Pagination (défaut: 1)
  limit: 10,         // Éléments par page (max: 100, défaut: 10)
  type: "cheque",    // Filtre: cheque, epargne (courant filtré automatiquement)
  statut: "actif",   // Filtre: actif (autres statuts filtrés automatiquement)
  search: "amadou",  // Recherche: numéro compte, nom/prénom titulaire, email
  sort: "created_at", // Tri: created_at, numero, solde_initial, client.nom
  order: "desc"      // Ordre: asc, desc
}
```

**Note importante** : Le scope global applique automatiquement un filtre pour n'afficher que les comptes de type "cheque" ou "epargne" avec statut "actif".

**Réponse succès (200)** :
```json
{
  "success": true,
  "data": {
    "comptes": {
      "data": [
        {
          "id": "uuid-compte-1",
          "numero": "CPT-A1B2C3D4",
          "solde_initial": 500000,
          "devise": "FCFA",
          "type": "cheque",
          "statut": "actif",
          "client": {
            "id": "uuid-client-1",
            "nom": "Diop",
            "prenom": "Amadou",
            "email": "amadou.diop@example.com",
            "telephone": "+221771234567"
          },
          "created_at": "2025-01-15T10:30:00.000000Z",
          "updated_at": "2025-01-15T10:30:00.000000Z"
        }
      ],
      "current_page": 1,
      "last_page": 3,
      "per_page": 10,
      "total": 25,
      "from": 1,
      "to": 10,
      "links": [
        {
          "url": null,
          "label": "&laquo; Previous",
          "active": false
        },
        {
          "url": "http://localhost:8000/api/v1/zeynab-ba/comptes?page=1",
          "label": "1",
          "active": true
        },
        {
          "url": "http://localhost:8000/api/v1/zeynab-ba/comptes?page=2",
          "label": "2",
          "active": false
        },
        {
          "url": "http://localhost:8000/api/v1/zeynab-ba/comptes?page=3",
          "label": "3",
          "active": false
        },
        {
          "url": "http://localhost:8000/api/v1/zeynab-ba/comptes?page=2",
          "label": "Next &raquo;",
          "active": false
        }
      ]
    }
  },
  "message": "Liste des comptes récupérée avec succès"
}
```

### `GET /comptes/{id}` - Détails d'un Compte
**Accès** : Authentifié avec vérification propriétaire  
**Rate Limit** : 60/minute

**Paramètres** :
- `id` (string, UUID) : Identifiant du compte

**Réponse succès (200)** :
```json
{
  "success": true,
  "data": {
    "id": "uuid-compte-1",
    "numeroCompte": "CPT-CHQ001",
    "titulaire": "Amadou Diop",
    "type": "cheque",
    "solde": 500000,
    "devise": "FCFA",
    "dateCreation": "2025-01-15T10:30:00.000000Z",
    "statut": "actif",
    "motifBlocage": null,
    "_links": {
      "self": {"href": "/api/v1/zeynab-ba/comptes/uuid-compte-1", "method": "GET"},
      "update": {"href": "/api/v1/zeynab-ba/comptes/uuid-compte-1", "method": "PUT"},
      "delete": {"href": "/api/v1/zeynab-ba/comptes/uuid-compte-1", "method": "DELETE"},
      "collection": {"href": "/api/v1/zeynab-ba/comptes", "method": "GET"}
    }
  },
  "message": "Détails du compte récupérés avec succès"
}
```

### `POST /comptes` - Créer un Compte
**Accès** : Admin uniquement  
**Rate Limit** : 60/minute

**Corps de la requête** :
```json
{
  "client_id": "uuid-client-1",
  "type": "cheque",
  "devise": "FCFA",
  "solde_initial": 100000,
  "statut": "actif"
}
```

**Réponse succès (201)** :
```json
{
  "success": true,
  "data": {
    "id": "uuid-nouveau-compte",
    "numeroCompte": "CPT-CHQ025",
    "titulaire": "Amadou Diop",
    "type": "cheque",
    "solde": 100000,
    "devise": "FCFA",
    "dateCreation": "2025-01-25T14:30:00.000000Z",
    "statut": "actif",
    "_links": {
      "self": {"href": "/api/v1/zeynab-ba/comptes/uuid-nouveau-compte", "method": "GET"}
    }
  },
  "message": "Compte créé avec succès"
}
```

### `PUT /comptes/{id}` - Modifier un Compte
**Accès** : Admin uniquement  
**Rate Limit** : 60/minute

**Corps de la requête** :
```json
{
  "type": "courant",
  "statut": "bloque",
  "motif_blocage": "Suspicion de fraude"
}
```

### `DELETE /comptes/{id}` - Supprimer un Compte
**Accès** : Admin uniquement  
**Rate Limit** : 60/minute

**Note** : Soft delete - le compte est archivé, pas supprimé définitivement

### `GET /comptes-archives` - Comptes Archivés (Cloud)
**Accès** : Admin uniquement  
**Rate Limit** : 60/minute

**Description** : Liste des comptes supprimés (soft deleted), simulés comme stockés dans le cloud

---

## 3. 💸 GESTION DES TRANSACTIONS

### `GET /transactions` - Liste des Transactions
**Accès** : Authentifié (Admin: toutes, Client: les siennes)  
**Rate Limit** : 60/minute

**Paramètres de requête** :
```javascript
{
  page: 1,
  limit: 10,
  type: "depot",           // depot, retrait, virement, transfert
  statut: "validee",       // en_attente, validee, rejete, annulee
  compte_id: "uuid-compte",
  search: "salaire",       // Recherche référence/description
  sort: "date_execution",  // date_execution, montant, created_at
  order: "desc"
}
```

**Réponse succès (200)** :
```json
{
  "success": true,
  "data": {
    "_links": {
      "self": {"href": "/api/v1/zeynab-ba/transactions?page=1", "method": "GET"},
      "first": {"href": "/api/v1/zeynab-ba/transactions?page=1", "method": "GET"},
      "last": {"href": "/api/v1/zeynab-ba/transactions?page=5", "method": "GET"}
    },
    "_embedded": {
      "transactions": [
        {
          "id": "uuid-transaction-1",
          "reference": "TXN-DEPOSIT001",
          "type": "depot",
          "montant": 500000,
          "montant_formate": "500 000 FCFA",
          "devise": "FCFA",
          "description": "Dépôt initial de salaire",
          "statut": "validee",
          "date_execution": "2025-01-15T10:30:00.000000Z",
          "compte": {
            "id": "uuid-compte-1",
            "numero": "CPT-CHQ001",
            "type": "cheque",
            "titulaire": "Amadou Diop"
          },
          "_links": {
            "self": {"href": "/api/v1/zeynab-ba/transactions/uuid-transaction-1", "method": "GET"},
            "collection": {"href": "/api/v1/zeynab-ba/transactions", "method": "GET"},
            "compte": {"href": "/api/v1/zeynab-ba/comptes/uuid-compte-1", "method": "GET"}
          }
        }
      ]
    },
    "pagination": {
      "currentPage": 1,
      "totalPages": 5,
      "totalItems": 47,
      "itemsPerPage": 10,
      "hasNext": true,
      "hasPrevious": false
    }
  },
  "message": "Liste des transactions récupérée avec succès"
}
```

### `GET /transactions/{id}` - Détails d'une Transaction
**Accès** : Authentifié avec vérification propriétaire  
**Rate Limit** : 60/minute

### `POST /transactions` - Créer une Transaction
**Accès** : Authentifié (Admin: tous comptes, Client: ses comptes)  
**Rate Limit** : 60/minute

**Corps de la requête** :
```json
{
  "compte_id": "uuid-compte-1",
  "type": "depot",
  "montant": 100000,
  "devise": "FCFA",
  "description": "Dépôt de salaire"
}
```

---

## 🗄️ Schéma Base de Données

### Table `users`
- `id` (BIGINT, Primary Key)
- `name` (VARCHAR)
- `email` (VARCHAR, UNIQUE)
- `email_verified_at` (TIMESTAMP, NULLABLE)
- `password` (VARCHAR)
- `role` (ENUM: admin, client)
- `created_at`, `updated_at` (TIMESTAMPS)

### Table `clients`
- `id` (UUID, Primary Key)
- `user_id` (BIGINT, FOREIGN KEY -> users.id)
- `nom`, `prenom` (VARCHAR)
- `email` (VARCHAR, UNIQUE)
- `telephone` (VARCHAR, UNIQUE)
- `date_naissance` (DATE, NULLABLE)
- `adresse`, `ville`, `pays` (VARCHAR, NULLABLE)
- `statut` (ENUM: actif, inactif, suspendu)
- `metadata` (JSON, NULLABLE)
- `deleted_at` (TIMESTAMP, NULLABLE)
- `created_at`, `updated_at` (TIMESTAMPS)

### Table `comptes`
- `id` (UUID, Primary Key)
- `numero` (VARCHAR, UNIQUE)
- `solde_initial` (DECIMAL 15,2)
- `devise` (ENUM: FCFA, EUR, USD)
- `type` (ENUM: cheque, courant, epargne)
- `statut` (ENUM: actif, bloque, ferme)
- `motif_blocage` (TEXT, NULLABLE)
- `client_id` (BIGINT, FOREIGN KEY -> clients.id)
- `metadata` (JSON, NULLABLE)
- `deleted_at` (TIMESTAMP, NULLABLE)
- `created_at`, `updated_at` (TIMESTAMPS)

### Table `transactions`
- `id` (UUID, Primary Key)
- `reference` (VARCHAR, UNIQUE)
- `type` (ENUM: depot, retrait, virement, transfert)
- `montant` (DECIMAL 15,2)
- `devise` (ENUM: FCFA, EUR, USD)
- `description` (TEXT, NULLABLE)
- `statut` (ENUM: en_attente, validee, rejete, annulee)
- `date_execution` (TIMESTAMP, NULLABLE)
- `compte_id` (BIGINT, FOREIGN KEY -> comptes.id)
- `metadata` (JSON, NULLABLE)
- `deleted_at` (TIMESTAMP, NULLABLE)
- `created_at`, `updated_at` (TIMESTAMPS)

---

## 🔧 Configuration Avancée

### Variables d'Environnement (.env)
```env
# Application
APP_NAME="API Banque"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Base de données
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=banque_api
DB_USERNAME=root
DB_PASSWORD=

# Passport OAuth2
PASSPORT_PERSONAL_ACCESS_CLIENT_ID=1
PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET=your-secret-here

# Rate Limiting
THROTTLE_RATE=60

# Pagination
DEFAULT_PAGINATION_LIMIT=10
MAX_PAGINATION_LIMIT=100

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=debug
```

### Middleware Personnalisé
```php
// app/Http/Kernel.php
protected $middlewareAliases = [
    'auth' => \App\Http\Middleware\Authenticate::class,
    'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
    'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
    'can' => \Illuminate\Auth\Middleware\Authorize::class,
    'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
    'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
    'signed' => \App\Http\Middleware\ValidateSignature::class,
    'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
    'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
    // Middleware personnalisé
    'role' => \App\Http\Middleware\RoleMiddleware::class,
];
```

---

## 🧪 Tests & Données de Développement

### Exécution des Seeders
```bash
# Tous les seeders
php artisan db:seed

# Seeders spécifiques
php artisan db:seed --class=ClientSeeder
php artisan db:seed --class=CompteSeeder
php artisan db:seed --class=TransactionSeeder
```

### Tests Automatisés
```bash
# Exécuter tous les tests
php artisan test

# Tests avec couverture
php artisan test --coverage

# Tests spécifiques
php artisan test tests/Feature/Api/AuthTest.php
```

### Génération de Données Factices
```bash
# Via Tinker
php artisan tinker

>>> App\Models\Client::factory(10)->create()
>>> App\Models\Compte::factory(10)->create()
>>> App\Models\Transaction::factory(10)->create()
```

### Données de Test (Seeders)
```php
// Clients prédéfinis
[
    ['nom' => 'Diop', 'prenom' => 'Amadou', 'email' => 'amadou.diop@example.com'],
    ['nom' => 'Ndiaye', 'prenom' => 'Fatou', 'email' => 'fatou.ndiaye@example.com'],
    ['nom' => 'Sow', 'prenom' => 'Mamadou', 'email' => 'mamadou.sow@example.com'],
]

// Comptes prédéfinis
[
    ['numero' => 'CPT-CHQ001', 'solde_initial' => 500000, 'type' => 'cheque'],
    ['numero' => 'CPT-CRT001', 'solde_initial' => 100000, 'type' => 'courant'],
    ['numero' => 'CPT-EPG001', 'solde_initial' => 200000, 'type' => 'epargne'],
]

// Transactions prédéfinies
[
    ['reference' => 'TXN-DEPOSIT001', 'type' => 'depot', 'montant' => 500000],
    ['reference' => 'TXN-WITHDRAW001', 'type' => 'retrait', 'montant' => 100000],
    ['reference' => 'TXN-TRANSFER001', 'type' => 'virement', 'montant' => 250000],
]
```

---

## 📖 Documentation API

### Swagger/OpenAPI
Accédez à la documentation interactive :
```
http://localhost:8000/api/documentation
```

### Postman Collection
Importez le fichier `postman_collection.json` pour tester l'API avec Postman.

---

## 🚨 Gestion des Erreurs

### Codes HTTP et Significations
- `200` : Succès (GET, PUT, DELETE réussis)
- `201` : Créé (POST réussi)
- `400` : Données invalides (validation échouée)
- `401` : Non authentifié (token manquant/invalide)
- `403` : Accès refusé (permissions insuffisantes)
- `404` : Ressource non trouvée
- `422` : Erreur de validation
- `429` : Limite de taux dépassée
- `500` : Erreur serveur interne

### Structure des Réponses d'Erreur
```json
{
  "success": false,
  "message": "Description de l'erreur",
  "errors": {
    "email": ["L'adresse email est déjà utilisée"],
    "password": ["Le mot de passe doit contenir au moins 8 caractères"]
  }
}
```

---

## 🔍 Monitoring & Logs

### Logs Laravel
```bash
# Suivre les logs en temps réel
tail -f storage/logs/laravel.log

# Logs par date
tail -f storage/logs/laravel-2025-01-25.log
```

### Middleware de Logging
- `RequestLoggerMiddleware` : Log toutes les requêtes API
- Logs incluent : méthode HTTP, URL, IP, user agent, timestamp
- Niveau de log configurable dans `.env`

---

## 🚀 Déploiement en Production

### Préparation
```bash
# Optimisation Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Migrations production
php artisan migrate --force

# Permissions
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

### Variables Production
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.banque.example.com

# Base de données production
DB_CONNECTION=mysql
DB_HOST=production-db-host
DB_DATABASE=banque_prod
DB_USERNAME=prod_user
DB_PASSWORD=prod_password

# Passport production
PASSPORT_PERSONAL_ACCESS_CLIENT_ID=prod-client-id
PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET=prod-client-secret

# Sécurité renforcée
THROTTLE_RATE=120
```

### Docker (Optionnel)
```bash
# Build et démarrage
docker-compose up -d --build

# Accès aux logs
docker-compose logs -f app
```

---

## 🔐 Sécurité

### Mesures Implémentées
- **Authentification OAuth2** avec tokens JWT
- **Hashage des mots de passe** avec bcrypt
- **Rate limiting** pour prévention des attaques par déni de service
- **Validation stricte** des entrées utilisateur
- **Middleware d'autorisation** par rôles
- **Soft deletes** pour protection contre suppression accidentelle
- **Logs détaillés** pour audit et monitoring

### Bonnes Pratiques
- Utilisation d'UUID pour les clés primaires sensibles
- Sanitisation automatique des entrées
- Gestion sécurisée des sessions
- Protection CSRF sur les formulaires
- Headers de sécurité HTTP

---

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/nouvelle-fonctionnalite`)
3. Commit les changements (`git commit -am 'Ajout nouvelle fonctionnalité'`)
4. Push vers la branche (`git push origin feature/nouvelle-fonctionnalite`)
5. Créer une Pull Request

### Standards de Code
- Respect des PSR-12
- Tests unitaires pour nouvelles fonctionnalités
- Documentation des méthodes complexes
- Validation des données selon les règles métier

---

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

---

## 📞 Support & Contact

- **Email** : support@banque-api.com
- **Documentation** : [Lien vers la documentation complète]
- **Issues** : [GitHub Issues pour signaler les bugs]
- **Discussions** : [GitHub Discussions pour les questions générales]

---

## 🙏 Remerciements

Développé avec ❤️ par **Zeynab BA** - API Banque v1.0

**Technologies utilisées :**
- Laravel Framework
- Laravel Passport
- MySQL/PostgreSQL
- Docker
- Swagger/OpenAPI
- PHPUnit

---

*Dernière mise à jour : Janvier 2025*
