# 🏦 API Banque - Gestion des Comptes

Une API REST Laravel pour la gestion complète des comptes bancaires avec authentification, pagination, filtrage et documentation OpenAPI.

## 📋 Table des Matières

- [Fonctionnalités](#-fonctionnalités)
- [Technologies Utilisées](#-technologies-utilisées)
- [Architecture](#-architecture)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [API Endpoints](#-api-endpoints)
- [Authentification](#-authentification)
- [Base de Données](#-base-de-données)
- [Tests](#-tests)
- [Documentation](#-documentation)
- [Sécurité](#-sécurité)

## ✨ Fonctionnalités

### Comptes Bancaires
- ✅ **CRUD complet** des comptes bancaires
- ✅ **UUID comme clé primaire** pour sécurité renforcée
- ✅ **Génération automatique** du numéro de compte (format: CPT-XXXXXXXX)
- ✅ **Soft Deletes** pour archivage des comptes
- ✅ **Statuts** : actif, bloqué, fermé
- ✅ **Types** : chèque, courant, épargne
- ✅ **Devises** : FCFA, EUR, USD
- ✅ **Métadonnées** avec versioning automatique

### API REST
- ✅ **Versionnement** (v1) des endpoints
- ✅ **Pagination** avec métadonnées complètes
- ✅ **Filtrage avancé** par type, statut, recherche
- ✅ **Tri multi-colonnes** (date, solde, titulaire)
- ✅ **Format de réponse standardisé**
- ✅ **Gestion d'erreurs** personnalisée
- ✅ **Rate Limiting** avec logging

### Sécurité & Performance
- ✅ **Authentification OAuth2** (Laravel Passport avec modèle Admin)
- ✅ **CORS configuré** pour les applications frontend
- ✅ **Middleware personnalisé** pour monitoring des rate limits
- ✅ **Validation stricte** des données d'entrée
- ✅ **Logs détaillés** des accès et erreurs

## 🛠 Technologies Utilisées

### Backend
- **Laravel 11** - Framework PHP moderne
- **PHP 8.2+** - Langage de programmation
- **MySQL/PostgreSQL** - Base de données relationnelle

### Authentification & Sécurité
- **Laravel Passport** - OAuth2 server complet pour l'authentification API
- **UUID** - Identifiants uniques sécurisés
- **Rate Limiting** - Protection contre les abus

### Architecture API
- **RESTful Design** - Architecture REST standard
- **API Versioning** - Gestion des versions (v1)
- **OpenAPI 3.0** - Documentation automatique
- **JSON:API** - Format de réponse standardisé

### Outils de Développement
- **Composer** - Gestionnaire de dépendances PHP
- **Artisan** - Interface en ligne de commande Laravel
- **Migrations** - Gestion du schéma de base de données
- **Seeders & Factories** - Génération de données de test

## 🏗 Architecture

### Structure des Dossiers
```
app/
├── Exceptions/           # Exceptions personnalisées
│   └── CompteException.php
├── Http/
│   ├── Controllers/Api/V1/  # Controllers API versionnés
│   │   └── CompteController.php
│   ├── Middleware/       # Middlewares personnalisés
│   │   └── RatingMiddleware.php
│   ├── Requests/         # Classes de validation
│   │   └── StoreCompteRequest.php
│   └── Resources/        # Transformation des données
│       └── CompteResource.php
├── Models/               # Modèles Eloquent
│   └── Compte.php
├── Scopes/               # Scopes de requête globaux
│   └── CompteScope.php
└── Traits/               # Traits réutilisables
    └── ApiResponseTrait.php

database/
├── factories/            # Factories pour tests
│   └── CompteFactory.php
├── migrations/           # Migrations de base de données
│   └── create_comptes_table.php
└── seeders/              # Seeders de données
    └── CompteSeeder.php

routes/
└── api.php               # Routes API versionnées

storage/api-docs/
└── api-docs.json         # Documentation OpenAPI
```

### Patterns Architecturaux

#### 1. **Repository Pattern** (Implicite via Eloquent)
- Utilisation directe d'Eloquent ORM
- Requêtes optimisées avec eager loading
- Scopes pour logique métier réutilisable

#### 2. **API Resource Pattern**
- Transformation des données via `CompteResource`
- Format de réponse standardisé
- Séparation claire entre logique métier et présentation

#### 3. **Middleware Pattern**
- `RatingMiddleware` pour monitoring des rate limits
- Gestion centralisée des préoccupations transversales
- Logging automatique des événements de sécurité

#### 4. **Exception Handling Pattern**
- `CompteException` avec méthodes statiques
- Gestion d'erreurs métier spécifique
- Messages d'erreur contextualisés

#### 5. **Trait Pattern**
- `ApiResponseTrait` pour format de réponse uniforme
- Réutilisabilité du code de réponse API
- Méthodes helper pour succès/erreur/pagination

## 🚀 Installation

### Prérequis
- PHP 8.2 ou supérieur
- Composer
- MySQL/PostgreSQL
- Node.js & npm (pour assets frontend)

### Étapes d'Installation

1. **Cloner le repository**
```bash
git clone <repository-url>
cd banque
```

2. **Installer les dépendances PHP**
```bash
composer install
```

3. **Configuration de l'environnement**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configuration de la base de données**
```bash
# Modifier .env avec vos credentials DB
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=banque
DB_USERNAME=votre_username
DB_PASSWORD=votre_password
```

5. **Migration et seeding**
```bash
php artisan migrate
php artisan db:seed
```

6. **Démarrer le serveur**
```bash
php artisan serve
```

## ⚙️ Configuration

### Variables d'Environnement (.env)

```env
# Application
APP_NAME="API Banque"
APP_ENV=local
APP_KEY=base64:generated-key
APP_DEBUG=true
APP_URL=http://localhost

# Base de Données
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=banque
DB_USERNAME=user
DB_PASSWORD=password

# Cache & Session
CACHE_DRIVER=file
SESSION_DRIVER=file

# Sanctum (Authentification API)
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1

# CORS
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:8080
```

### Configuration CORS (config/cors.php)

```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins' => ['http://localhost:3000', 'http://localhost:8080', 'https://banque.example.com'],
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept', 'Origin'],
    'exposed_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining', 'X-RateLimit-Reset'],
    'max_age' => 86400,
    'supports_credentials' => true,
];
```

## 📡 API Endpoints

### Base URL
```
http://localhost:8000/api/v1
```

### Authentication
Tous les endpoints nécessitent un token Bearer :
```
Authorization: Bearer {token}
```

### Comptes - Lister tous les comptes

**GET** `/api/v1/comptes`

#### Paramètres de requête
| Paramètre | Type | Défaut | Description |
|-----------|------|--------|-------------|
| `page` | integer | 1 | Numéro de page |
| `limit` | integer | 10 | Éléments par page (max: 100) |
| `type` | string | - | Filtre par type (cheque, courant, epargne) |
| `statut` | string | - | Filtre par statut (actif, bloque, ferme) |
| `search` | string | - | Recherche par titulaire ou numéro |
| `sort` | string | dateCreation | Tri (dateCreation, solde, titulaire) |
| `order` | string | desc | Ordre (asc, desc) |

#### Exemple de requête
```bash
GET /api/v1/comptes?page=1&limit=10&type=epargne&statut=actif&sort=dateCreation&order=desc
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
```

#### Réponse de succès (200)
```json
{
  "success": true,
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "numeroCompte": "CPT-CHQ001",
      "titulaire": "Amadou Diallo",
      "type": "epargne",
      "solde": 500000,
      "devise": "FCFA",
      "dateCreation": "2023-03-15T00:00:00Z",
      "statut": "actif",
      "motifBlocage": null,
      "metadata": {
        "derniereModification": "2023-06-10T14:30:00Z",
        "version": 1
      }
    }
  ],
  "pagination": {
    "currentPage": 1,
    "totalPages": 3,
    "totalItems": 25,
    "itemsPerPage": 10,
    "hasNext": true,
    "hasPrevious": false
  },
  "links": {
    "self": "/api/v1/comptes?page=1&limit=10",
    "next": "/api/v1/comptes?page=2&limit=10",
    "first": "/api/v1/comptes?page=1&limit=10",
    "last": "/api/v1/comptes?page=3&limit=10"
  }
}
```

## 🔐 Authentification

### Laravel Passport
L'API utilise Laravel Passport pour l'authentification OAuth2 complète.

#### Obtenir un Personal Access Token
```bash
# Via Tinker
php artisan tinker
>>> $user = App\Models\User::find(1);
>>> $token = $user->createToken('API Token')->accessToken;

# Ou via API (nécessite une route dédiée)
POST /oauth/token
Content-Type: application/json

{
  "grant_type": "password",
  "client_id": "your-client-id",
  "client_secret": "your-client-secret",
  "username": "user@example.com",
  "password": "password",
  "scope": "*"
}
```

#### Utiliser le token
```bash
Authorization: Bearer {access_token}
```

## 🗄️ Base de Données

### Schéma des tables

#### Table `comptes`
```sql
CREATE TABLE comptes (
  id CHAR(36) PRIMARY KEY,
  numero VARCHAR(255) UNIQUE NOT NULL,
  solde_initial DECIMAL(15,2) DEFAULT 0,
  devise VARCHAR(255) DEFAULT 'FCFA',
  type VARCHAR(255) DEFAULT 'cheque',
  statut ENUM('actif', 'bloque', 'ferme') DEFAULT 'actif',
  motif_blocage TEXT NULL,
  metadata JSON NULL,
  user_id CHAR(36) NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  deleted_at TIMESTAMP NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_type (user_id, type),
  INDEX idx_numero (numero),
  INDEX idx_devise (devise),
  INDEX idx_statut (statut)
);
```

### Migrations
```bash
# Créer une nouvelle migration
php artisan make:migration add_new_field_to_comptes_table

# Exécuter les migrations
php artisan migrate

# Rollback
php artisan migrate:rollback
```

### Seeders & Factories

#### Générer des données de test
```bash
# Seeder spécifique
php artisan db:seed --class=CompteSeeder

# Tous les seeders
php artisan db:seed

# Factory pour tests
php artisan tinker
>>> App\Models\Compte::factory()->count(10)->create();
```

## 🧪 Tests

### Tests unitaires et fonctionnels
```bash
# Exécuter tous les tests
php artisan test

# Tests spécifiques
php artisan test --filter=CompteTest

# Tests avec couverture
php artisan test --coverage
```

### Structure des tests
```
tests/
├── Feature/
│   ├── Api/
│   │   └── CompteApiTest.php
│   └── CompteTest.php
└── Unit/
    ├── Models/
    │   └── CompteTest.php
    └── Services/
        └── CompteServiceTest.php
```

## 📚 Documentation

### OpenAPI/Swagger
La documentation API est générée automatiquement et disponible dans :
- **Fichier JSON** : `storage/api-docs/api-docs.json`
- **Interface web** : Via Swagger UI (si configuré)

### Points d'entrée documentés
- ✅ Endpoints RESTful
- ✅ Paramètres de requête
- ✅ Schémas de réponse
- ✅ Codes d'erreur
- ✅ Exemples d'utilisation

## 🔒 Sécurité

### Mesures implémentées

#### 1. **Authentification**
- **OAuth2 via Laravel Passport**
- **Personal Access Tokens** pour l'API
- **Password Grant** pour l'authentification classique
- **Client Credentials** pour les applications tierces
- **Refresh Tokens** avec expiration automatique

#### 2. **Autorisation**
- Middleware d'authentification
- Vérification des rôles (extensible)
- Contrôle d'accès aux ressources

#### 3. **Validation**
- Validation stricte des entrées
- Sanitisation automatique
- Messages d'erreur personnalisés

#### 4. **Rate Limiting**
- Limite de 60 requêtes/minute par défaut
- Logging automatique des dépassements
- Headers exposés pour monitoring client

#### 5. **Sécurité des données**
- UUID comme clés primaires
- Soft deletes pour archivage
- Chiffrement des données sensibles (extensible)

### Headers de sécurité
```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1640995200
```

## 📈 Performance

### Optimisations implémentées

#### 1. **Base de données**
- Indexes stratégiques sur colonnes fréquemment filtrées
- Eager loading des relations
- Pagination efficace

#### 2. **Cache**
- Cache des requêtes fréquentes (extensible)
- Cache des configurations
- Cache des tokens JWT

#### 3. **API**
- Réponses JSON optimisées
- Compression GZIP
- Headers appropriés pour cache

## 🔧 Commandes Artisan Utiles

```bash
# Générer des clés
php artisan key:generate

# Migrations
php artisan migrate
php artisan migrate:status
php artisan migrate:rollback

# Seeders
php artisan db:seed
php artisan make:seeder NouveauSeeder

# Factories
php artisan make:factory NouveauFactory

# Controllers
php artisan make:controller Api/V1/NouveauController --api

# Resources
php artisan make:resource NouveauResource

# Requests
php artisan make:request NouveauRequest

# Middleware
php artisan make:middleware NouveauMiddleware

# Tests
php artisan make:test NouveauTest
php artisan test

# Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Nettoyer le cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## 🚀 Déploiement

### Variables de production
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.banque.example.com

# Base de données production
DB_CONNECTION=mysql
DB_HOST=production-host
DB_DATABASE=banque_prod
DB_USERNAME=prod_user
DB_PASSWORD=secure_password

# Cache et sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=redis-server
```

### Commandes de déploiement
```bash
# Installation des dépendances
composer install --optimize-autoloader --no-dev

# Génération des clés
php artisan key:generate

# Cache des configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Migrations
php artisan migrate --force

# Permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/nouvelle-fonctionnalite`)
3. Commit les changements (`git commit -am 'Ajout nouvelle fonctionnalité'`)
4. Push la branche (`git push origin feature/nouvelle-fonctionnalite`)
5. Créer une Pull Request

## 📝 Licence

Ce projet est sous licence MIT - voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 📞 Support

Pour toute question ou problème :
- 📧 Email: support@banque.example.com
- 📖 Documentation: [API Docs](storage/api-docs/api-docs.json)
- 🐛 Issues: [GitHub Issues](https://github.com/username/banque/issues)

---

**Développé avec ❤️ par l'équipe de développement Laravel**

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
