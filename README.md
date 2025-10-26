# 🏦 API Banque - Système Bancaire Complet

## 📋 Description

Une API REST complète pour un système bancaire moderne développée avec Laravel 11.x, utilisant Laravel Passport pour l'authentification OAuth2. L'API gère les clients, comptes bancaires et transactions avec une architecture sécurisée et scalable.

## 🚀 Fonctionnalités Principales

### 👥 Gestion des Clients
- **CRUD complet** : Création, lecture, mise à jour, suppression
- **UUID comme clé primaire** pour sécurité renforcée
- **Soft deletes** pour archivage logique
- **Profils complets** : nom, prénom, email, téléphone, adresse, etc.
- **Statuts** : actif, inactif, suspendu

### 💳 Gestion des Comptes
- **Types de comptes** : Chèque, Courant, Épargne
- **Filtrage automatique** : Comptes actifs uniquement (type chèque/épargne)
- **Numéros uniques** générés automatiquement (format: CPT-XXXXXXXX)
- **Gestion des statuts** : actif, bloqué, fermé
- **Soft deletes** avec archivage
- **Liens avec clients** via relations Eloquent

### 💸 Gestion des Transactions
- **Types** : Dépôt, Retrait, Virement, Transfert
- **Références uniques** générées automatiquement
- **Montants avec devises** multiples (FCFA, EUR, USD)
- **Statuts** : En attente, Validée, Rejetée, Annulée
- **Historique complet** avec dates d'exécution

### 🔐 Authentification & Autorisation
- **Laravel Passport** pour OAuth2
- **Rôles différenciés** :
  - **Admin** : Accès complet à toutes les ressources
  - **Client** : Accès limité à ses propres données
- **Middleware personnalisé** pour contrôle des rôles
- **Rate limiting** : 60 requêtes/minute

### 📊 API Avancée
- **Pagination HATEOAS** avec liens de navigation
- **Filtrage et recherche** avancés
- **Tri multiple** sur tous les champs
- **Réponses JSON structurées**
- **Gestion d'erreurs** complète
- **Documentation Swagger/OpenAPI**

---

## 🛠️ Installation & Configuration

### Prérequis
- PHP 8.1+
- Composer
- MySQL/PostgreSQL
- Laravel 11.x

### Installation

```bash
# Cloner le repository
git clone <repository-url>
cd banque-api

# Installer les dépendances
composer install

# Configuration de l'environnement
cp .env.example .env
php artisan key:generate

# Configuration de la base de données dans .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=banque_api
DB_USERNAME=votre_username
DB_PASSWORD=votre_password

# Migration et seeding
php artisan migrate
php artisan db:seed

# Installation de Passport
php artisan passport:install

# Démarrage du serveur
php artisan serve
```

### Configuration Passport

```bash
# Créer les clés OAuth2
php artisan passport:install

# Les clients API seront créés automatiquement
# Client ID et Secret disponibles dans la table oauth_clients
```

---

## 📚 Architecture & Structure

### 📁 Structure des Dossiers

```
app/
├── Http/
│   ├── Controllers/Api/V1/
│   │   ├── CompteController.php
│   │   └── TransactionController.php
│   ├── Middleware/
│   │   └── RoleMiddleware.php
│   ├── Requests/
│   │   └── StoreClientRequest.php
│   └── Resources/
│       ├── CompteResource.php
│       └── TransactionResource.php
├── Models/
│   ├── Client.php
│   ├── Compte.php
│   └── Transaction.php
├── Scopes/
│   └── CompteScope.php
└── Traits/
    └── ApiResponseTrait.php

database/
├── factories/
│   ├── ClientFactory.php
│   ├── CompteFactory.php
│   └── TransactionFactory.php
├── migrations/
│   ├── create_clients_table.php
│   ├── create_comptes_table.php
│   ├── create_transactions_table.php
│   └── modify_comptes_table_change_foreign_key_to_client.php
└── seeders/
    ├── ClientSeeder.php
    ├── CompteSeeder.php
    └── TransactionSeeder.php

routes/
└── api.php
```

### 🗄️ Schéma Base de Données

#### Table `clients`
```sql
- id (UUID, Primary Key)
- nom (VARCHAR)
- prenom (VARCHAR)
- email (VARCHAR, UNIQUE)
- telephone (VARCHAR, UNIQUE)
- date_naissance (DATE, NULLABLE)
- adresse (VARCHAR, NULLABLE)
- ville (VARCHAR, NULLABLE)
- pays (VARCHAR, DEFAULT 'Sénégal')
- statut (ENUM: actif, inactif, suspendu)
- metadata (JSON, NULLABLE)
- deleted_at (TIMESTAMP, NULLABLE)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

INDEXES: (nom, prenom), email, telephone, statut
```

#### Table `comptes`
```sql
- id (UUID, Primary Key)
- numero (VARCHAR, UNIQUE)
- solde_initial (DECIMAL 15,2)
- devise (VARCHAR, DEFAULT 'FCFA')
- type (ENUM: cheque, courant, epargne)
- statut (ENUM: actif, bloque, ferme)
- motif_blocage (TEXT, NULLABLE)
- metadata (JSON, NULLABLE)
- client_id (BIGINT, FOREIGN KEY -> clients.id)
- deleted_at (TIMESTAMP, NULLABLE)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

INDEXES: (client_id, type), numero, devise, statut
```

#### Table `transactions`
```sql
- id (UUID, Primary Key)
- reference (VARCHAR, UNIQUE)
- type (ENUM: depot, retrait, virement, transfert)
- montant (DECIMAL 15,2)
- devise (VARCHAR, DEFAULT 'FCFA')
- description (TEXT, NULLABLE)
- statut (ENUM: en_attente, validee, rejete, annulee)
- date_execution (TIMESTAMP, NULLABLE)
- metadata (JSON, NULLABLE)
- compte_id (BIGINT, FOREIGN KEY -> comptes.id)
- deleted_at (TIMESTAMP, NULLABLE)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

INDEXES: (compte_id, type), reference, statut, date_execution
```

---

## 🌐 API Endpoints

### Base URL
```
http://localhost:8000/api/v1/zeynab-ba
```

### 🔑 Authentification
Tous les endpoints nécessitent un token Bearer OAuth2 :
```
Authorization: Bearer {access_token}
```

---

## 💳 1. GESTION DES COMPTES

### `GET /comptes` - Liste des comptes actifs
**Description** : Récupère la liste des comptes actifs (filtrés automatiquement)

**Authentification** : Requise
- **Admin** : Voit tous les comptes
- **Client** : Voit uniquement ses comptes

**Paramètres de requête** :
```javascript
{
  "page": 1,           // Numéro de page (défaut: 1)
  "limit": 10,         // Éléments par page (max: 100, défaut: 10)
  "type": "cheque",    // Filtrer par type (cheque, epargne)
  "statut": "actif",   // Filtrer par statut (actif, bloque, ferme)
  "search": "amadou",  // Recherche par titulaire ou numéro
  "sort": "created_at", // Tri (dateCreation, solde, titulaire)
  "order": "desc"      // Ordre (asc, desc)
}
```

**Réponse** :
```json
{
  "success": true,
  "data": {
    "_links": {
      "self": {"href": "http://localhost:8000/api/v1/zeynab-ba/comptes?page=1", "method": "GET"},
      "first": {"href": "http://localhost:8000/api/v1/zeynab-ba/comptes?page=1", "method": "GET"},
      "last": {"href": "http://localhost:8000/api/v1/zeynab-ba/comptes?page=3", "method": "GET"}
    },
    "_embedded": {
      "comptes": [
        {
          "id": "uuid-compte-1",
          "numeroCompte": "CPT-CHQ001",
          "titulaire": "Amadou Diop",
          "type": "cheque",
          "solde": 500000,
          "devise": "FCFA",
          "dateCreation": "2025-01-15T10:30:00.000000Z",
          "statut": "actif",
          "motifBlocage": null,
          "metadata": {"version": 1},
          "_links": {
            "self": {"href": "http://localhost:8000/api/v1/zeynab-ba/comptes/uuid-compte-1", "method": "GET"},
            "update": {"href": "http://localhost:8000/api/v1/zeynab-ba/comptes/uuid-compte-1", "method": "PUT"},
            "delete": {"href": "http://localhost:8000/api/v1/zeynab-ba/comptes/uuid-compte-1", "method": "DELETE"}
          }
        }
      ]
    },
    "pagination": {
      "currentPage": 1,
      "totalPages": 3,
      "totalItems": 25,
      "itemsPerPage": 10,
      "hasNext": true,
      "hasPrevious": false
    }
  },
  "message": "Liste des comptes récupérée avec succès"
}
```

### `GET /comptes/{id}` - Détails d'un compte
**Description** : Récupère les détails d'un compte spécifique

**Authentification** : Requise avec vérification propriétaire

**Paramètres** :
- `id` (string, UUID) : ID du compte

**Réponse** :
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
    "metadata": {"version": 1},
    "_links": {
      "self": {"href": "http://localhost:8000/api/v1/zeynab-ba/comptes/uuid-compte-1", "method": "GET"},
      "update": {"href": "http://localhost:8000/api/v1/zeynab-ba/comptes/uuid-compte-1", "method": "PUT"},
      "delete": {"href": "http://localhost:8000/api/v1/zeynab-ba/comptes/uuid-compte-1", "method": "DELETE"},
      "collection": {"href": "http://localhost:8000/api/v1/zeynab-ba/comptes", "method": "GET"}
    }
  },
  "message": "Détails du compte récupérés avec succès"
}
```

### `POST /comptes` - Créer un compte
**Description** : Crée un nouveau compte bancaire

**Authentification** : Admin seulement

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

**Réponse** :
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
      "self": {"href": "http://localhost:8000/api/v1/zeynab-ba/comptes/uuid-nouveau-compte", "method": "GET"}
    }
  },
  "message": "Compte créé avec succès"
}
```

### `PUT /comptes/{id}` - Modifier un compte
**Description** : Met à jour les informations d'un compte

**Authentification** : Admin seulement

### `DELETE /comptes/{id}` - Supprimer un compte
**Description** : Supprime logiquement un compte (soft delete)

**Authentification** : Admin seulement

### `GET /comptes-archives` - Comptes archivés (Cloud)
**Description** : Liste des comptes supprimés (soft deleted)

**Authentification** : Admin seulement

**Note** : Simule un accès aux données archivées dans le cloud

---

## 💸 2. GESTION DES TRANSACTIONS

### `GET /transactions` - Liste des transactions
**Description** : Récupère la liste des transactions

**Authentification** : Requise
- **Admin** : Voit toutes les transactions
- **Client** : Voit uniquement les transactions de ses comptes

**Paramètres de requête** :
```javascript
{
  "page": 1,
  "limit": 10,
  "type": "depot",           // depot, retrait, virement, transfert
  "statut": "validee",       // en_attente, validee, rejete, annulee
  "compte_id": "uuid-compte",
  "search": "salaire",       // Recherche dans référence ou description
  "sort": "date_execution",  // date_execution, montant, created_at
  "order": "desc"
}
```

**Réponse** :
```json
{
  "success": true,
  "data": {
    "_links": {
      "self": {"href": "http://localhost:8000/api/v1/zeynab-ba/transactions?page=1", "method": "GET"},
      "first": {"href": "http://localhost:8000/api/v1/zeynab-ba/transactions?page=1", "method": "GET"},
      "last": {"href": "http://localhost:8000/api/v1/zeynab-ba/transactions?page=5", "method": "GET"}
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
          "date_creation": "2025-01-15T10:25:00.000000Z",
          "derniere_modification": "2025-01-15T10:30:00.000000Z",
          "metadata": {"version": 1},
          "compte": {
            "id": "uuid-compte-1",
            "numero": "CPT-CHQ001",
            "type": "cheque",
            "solde_initial": 500000,
            "devise": "FCFA",
            "titulaire": "Amadou Diop"
          },
          "_links": {
            "self": {"href": "http://localhost:8000/api/v1/zeynab-ba/transactions/uuid-transaction-1", "method": "GET"},
            "collection": {"href": "http://localhost:8000/api/v1/zeynab-ba/transactions", "method": "GET"},
            "compte": {"href": "http://localhost:8000/api/v1/zeynab-ba/comptes/uuid-compte-1", "method": "GET"}
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

---

## 👥 3. GESTION DES CLIENTS (FUTUR)

> **Note** : Les endpoints clients ne sont pas encore implémentés dans cette version, mais la structure est prête.

### Endpoints prévus :
- `GET /clients` - Liste des clients (Admin)
- `POST /clients` - Créer un client (Admin)
- `GET /clients/{id}` - Détails client (Admin)
- `PUT /clients/{id}` - Modifier client (Admin)
- `DELETE /clients/{id}` - Supprimer client (Admin)

---

## 🔐 Authentification OAuth2

### Obtenir un token d'accès

```bash
# Via cURL
curl -X POST http://localhost:8000/oauth/token \
  -H "Content-Type: application/json" \
  -d '{
    "grant_type": "password",
    "client_id": "your-client-id",
    "client_secret": "your-client-secret",
    "username": "user@example.com",
    "password": "password",
    "scope": "*"
  }'
```

### Utiliser le token

```bash
curl -X GET http://localhost:8000/api/v1/zeynab-ba/comptes \
  -H "Authorization: Bearer your-access-token" \
  -H "Accept: application/json"
```

---

## 📊 Données de Test

### Clients prédéfinis (Seeder)
```php
[
    ['nom' => 'Diop', 'prenom' => 'Amadou', 'email' => 'amadou.diop@example.com', 'telephone' => '+221771234567'],
    ['nom' => 'Ndiaye', 'prenom' => 'Fatou', 'email' => 'fatou.ndiaye@example.com', 'telephone' => '+221772345678'],
    ['nom' => 'Sow', 'prenom' => 'Mamadou', 'email' => 'mamadou.sow@example.com', 'telephone' => '+221773456789'],
    ['nom' => 'Ba', 'prenom' => 'Aissatou', 'email' => 'aissatou.ba@example.com', 'telephone' => '+221774567890'],
    ['nom' => 'Gueye', 'prenom' => 'Ibrahima', 'email' => 'ibrahima.gueye@example.com', 'telephone' => '+221775678901']
]
```

### Comptes prédéfinis (Seeder)
```php
[
    ['numero' => 'CPT-CHQ001', 'solde_initial' => 500000, 'devise' => 'FCFA', 'type' => 'cheque'],
    ['numero' => 'CPT-CHQ002', 'solde_initial' => 750000, 'devise' => 'FCFA', 'type' => 'cheque'],
    ['numero' => 'CPT-CRT001', 'solde_initial' => 100000, 'devise' => 'EUR', 'type' => 'courant'],
    ['numero' => 'CPT-EPG001', 'solde_initial' => 200000, 'devise' => 'FCFA', 'type' => 'epargne'],
    ['numero' => 'CPT-CHQ003', 'solde_initial' => 300000, 'devise' => 'USD', 'type' => 'cheque']
]
```

### Transactions prédéfinies (Seeder)
```php
[
    ['reference' => 'TXN-DEPOSIT001', 'type' => 'depot', 'montant' => 500000, 'devise' => 'FCFA', 'statut' => 'validee'],
    ['reference' => 'TXN-WITHDRAW001', 'type' => 'retrait', 'montant' => 100000, 'devise' => 'FCFA', 'statut' => 'validee'],
    ['reference' => 'TXN-TRANSFER001', 'type' => 'virement', 'montant' => 250000, 'devise' => 'FCFA', 'statut' => 'validee'],
    ['reference' => 'TXN-DEPOSIT002', 'type' => 'depot', 'montant' => 75000, 'devise' => 'EUR', 'statut' => 'validee'],
    ['reference' => 'TXN-PENDING001', 'type' => 'retrait', 'montant' => 50000, 'devise' => 'FCFA', 'statut' => 'en_attente']
]
```

---

## 🧪 Tests & Développement

### Exécution des seeders
```bash
# Peupler la base avec des données de test
php artisan db:seed

# Peupler seulement certains seeders
php artisan db:seed --class=ClientSeeder
php artisan db:seed --class=CompteSeeder
php artisan db:seed --class=TransactionSeeder
```

### Tests automatisés
```bash
# Exécuter tous les tests
php artisan test

# Tests avec couverture
php artisan test --coverage
```

### Génération de données factices
```bash
# Créer 10 clients factices
php artisan tinker
>>> App\Models\Client::factory(10)->create()
>>> App\Models\Compte::factory(10)->create()
>>> App\Models\Transaction::factory(10)->create()
```

---

## 📖 Documentation API

### Swagger/OpenAPI
L'API est documentée avec Swagger. Accédez à :
```
http://localhost:8000/api/documentation
```

### Postman Collection
Importez le fichier `postman_collection.json` pour tester l'API.

---

## 🔧 Configuration Avancée

### Variables d'environnement (.env)
```env
# Base de données
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=banque_api
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Passport
PASSPORT_PERSONAL_ACCESS_CLIENT_ID=1
PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET=your-secret

# Rate Limiting
THROTTLE_RATE=60

# Pagination
DEFAULT_PAGINATION_LIMIT=10
MAX_PAGINATION_LIMIT=100
```

### Middleware personnalisé
```php
// app/Http/Kernel.php
protected $middlewareAliases = [
    // ... autres middlewares
    'role' => \App\Http\Middleware\RoleMiddleware::class,
];
```

---

## 🚨 Gestion des Erreurs

### Codes d'erreur HTTP
- `200` : Succès
- `201` : Créé avec succès
- `400` : Données invalides
- `401` : Non authentifié
- `403` : Accès refusé
- `404` : Ressource non trouvée
- `422` : Erreur de validation
- `429` : Limite de taux dépassée
- `500` : Erreur serveur

### Structure des erreurs
```json
{
  "success": false,
  "message": "Description de l'erreur",
  "errors": {
    "champ1": ["Erreur 1", "Erreur 2"],
    "champ2": ["Erreur 3"]
  }
}
```

---

## 🔍 Monitoring & Logs

### Logs Laravel
```bash
# Voir les logs
tail -f storage/logs/laravel.log

# Logs par date
tail -f storage/logs/laravel-2025-01-25.log
```

### Rate Limiting
- Surveillance automatique des dépassements
- Logs des IPs suspectes
- Possibilité de blocage automatique

---

## 🚀 Déploiement

### Préparation pour la production
```bash
# Optimisation
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Migrations en production
php artisan migrate --force

# Permissions
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

### Variables de production
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.banque.example.com

# Base de données production
DB_CONNECTION=mysql
DB_HOST=production-host
DB_DATABASE=banque_prod

# Passport production
PASSPORT_PERSONAL_ACCESS_CLIENT_ID=prod-client-id
PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET=prod-client-secret
```

---

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/nouvelle-fonctionnalite`)
3. Commit les changements (`git commit -am 'Ajout nouvelle fonctionnalité'`)
4. Push la branche (`git push origin feature/nouvelle-fonctionnalite`)
5. Créer une Pull Request

---

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

---

## 📞 Support

Pour toute question ou problème :
- 📧 Email : support@banque-api.com
- 📚 Documentation : [Lien vers la doc complète]
- 🐛 Issues : [Lien vers GitHub Issues]

---

*Développé avec ❤️ par Zeynab BA - API Banque v1.0*