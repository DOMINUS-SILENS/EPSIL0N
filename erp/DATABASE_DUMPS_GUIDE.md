# EPSILON Database Dumps & Seeders Guide

Cet ensemble de scripts et seeders permet de **générer, remplir et exporter les données de la base EPSILON ERP**.

---

## 📋 Structure

```
database/
├── migrations/       # Structuration des tables
├── seeders/         # Pseudo-données pour tests
│   ├── DatabaseSeeder.php           # Point d'entrée principal
│   ├── EpsilonCoreSeeder.php         # Utilisateurs & config système
│   ├── EntrepriseDepotSeeder.php     # Entreprises et dépôts
│   ├── ProductCatalogSeeder.php      # Produits
│   ├── CustomerOrderSeeder.php       # Clients et commandes
│   ├── InventorySeeder.php           # Mouvements de stock
│   └── EpsilonDemoDataSeeder.php     # (NOUVEAU) Données démo complets
└── dumps/
    ├── epsilon_sqlite_*.sql.gz       # Exports SQLite
    └── epsilon_mysql_*.sql.gz        # Exports MySQL
```

```
scripts/
├── dump-db-sqlite.sh    # Export SQLite en local
└── dump-db-mysql.sh     # Export MySQL en prod
```

---

## 🚀 Utilisation Rapide

### 1. Peupler la base avec données de démo

```bash
# Avec seeders Laravel (reproduisible)
php artisan db:seed

# Ou spécifiquement le nouveau seeder complet
php artisan db:seed --class=EpsilonDemoDataSeeder

# Reset + seed (ATTENTION : supprime les données)
php artisan migrate:fresh --seed
```

### 2. Générer un dump SQL (new command)

```bash
# SQLite dump (auto-détecté)
php artisan db:dump

# MySQL dump explicite
php artisan db:dump --type=mysql

# SQLite avec données incluses
php artisan db:dump --type=sqlite --include-data=1

# Avec compression
php artisan db:dump --compress
```

### 3. Scripts bash directs

```bash
# SQLite (si dans erp/)
./scripts/dump-db-sqlite.sh

# MySQL (si dans erp/)
./scripts/dump-db-mysql.sh
```

---

## 📊 Configuration

### .env actuel
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=crm_db
DB_USERNAME=laravel
DB_PASSWORD=password
```

### Pour utiliser SQLite localement
```bash
# Temporairement pour tests
cp .env .env.sqlite
sed -i 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/' .env.sqlite
sed -i '/DB_HOST=/d' .env.sqlite

# Utiliser pour seeding
APP_ENV=local php artisan db:seed --env=.env.sqlite
```

---

## 💾 Utilisation des Dumps

### Restaurer depuis dump

```bash
# SQLite
sqlite3 database/database.sqlite < database/dumps/epsilon_sqlite_*.sql

# MySQL
mysql -u laravel -p crm_db < database/dumps/epsilon_mysql_*.sql
```

### Distribuer les données

```bash
# Archive et compresse automatiquement
gzip -c database/dumps/epsilon_sqlite_*.sql | base64 > /tmp/epsilon_demo_b64.txt

# Partager le .gz directement pour livraison
ls -lh database/dumps/*.gz
```

---

## 🔐 Sécurité

- **Seeders** : Utilisent `insertOrIgnore()` (non-destructif)
- **Dumps** : Contiennent TOUS les mots de passe bcryptés (NE PAS committer)
- **.gitignore** : Les dumps ne doivent pas être en Git

```bash
# Vérifier que dumps/ est bien ignoré
cat .gitignore | grep dumps
```

---

## 📝 Notes Importantes

### Si MySQL ne fonctionne pas

```bash
# Basculer en SQLite pour tests
php artisan db:seed --database=sqlite

# Vérifier connexion MySQL
mysql -h 127.0.0.1 -u laravel -p crm_db -e "SELECT 1;"
```

### Données de test par défaut

**Utilisateurs** (depuis EpsilonCoreSeeder):
- Admin: `admin@epsilon-erp.local` / `Admin@Epsilon2026`
- Sales: `yves.sales@epsilon-erp.local` / `Sales@Epsilon2026`
- Stock: `marie.stock@epsilon-erp.local` / `Stock@Epsilon2026`
- Delivery: `pierre.delivery@epsilon-erp.local` / `Delivery@Epsilon2026`

**Articles** (depuis ProductCatalogSeeder):
- ART-001, ART-002, ART-003, ART-004, etc.

**Clients** (depuis CustomerOrderSeeder):
- Différents distributeurs et magasins de test

---

## 🛠️ Commandes Artisan Disponibles

```bash
# Lister les seeders
php artisan list seed

# Seeder spécifique
php artisan db:seed --class=ProductCatalogSeeder

# Dump database
php artisan db:dump --help

# Reset complet
php artisan migrate:fresh --seed
```

---

## 📈 Prochaines Étapes

- [ ] Configurer MySQL en local pour tests
- [ ] Ajouter seeder pour CRM (Leads, Activities)
- [ ] Ajouter seeder pour Invoices et Payments
- [ ] Intégrer factoryes pour données volumineuses
- [ ] Créer script d'import massif (CSV → DB)

---

## 🐛 Troubleshooting

**"Database connection failed"**
```bash
# Vérifier .env
cat .env | grep DB_

# Tester MySQL
mysql -h 127.0.0.1 -u laravel -p -e "SELECT VERSION();"
```

**"Table doesn't exist"**
```bash
# Recréer les migrations
php artisan migrate
```

**"Permission denied on dumps/"**
```bash
chmod -R 755 database/dumps
```

---

**Créé pour EPSILON ERP** | Dernière mise à jour: 2026-03-27
