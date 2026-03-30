# 🚀 EPSILON Database - Quick Start Guide

**Prêt à utiliser!** Voici exactement ce qu'il faut faire.

---

## 1️⃣ Configuration Initiale (2 min)

### Étape 1: Modifier DatabaseSeeder.php

Ouvre `erp/database/seeders/DatabaseSeeder.php` et remplace le contenu complet par:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with EPSILON demo data.
     */
    public function run(): void
    {
        // Core system
        $this->call([
            EpsilonCoreSeeder::class,           // Users (4)
            EntrepriseDepotSeeder::class,       // Company + Depots (5)
            ProductCatalogSeeder::class,        // Products + Families
            CustomerOrderSeeder::class,         // Clients + Orders
            InventorySeeder::class,             // Stock + Movements
        ]);
    }
}
```

### Étape 2: Vérifier la config

```bash
cd /home/badji/EPSILON/erp

# Vérifier .env
cat .env | grep "^DB_"
```

✅ Résultat attendu:
```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=epsilon_erp
DB_USERNAME=root
```

---

## 2️⃣ Générer les Données (5 min)

### Option A: Seed Seulement (garder données existantes)

```bash
php artisan db:seed
```

✅ Résultat:
```
Seeding: EpsilonCoreSeeder
Seeding: EntrepriseDepotSeeder
Seeding: ProductCatalogSeeder
Seeding: CustomerOrderSeeder
Seeding: InventorySeeder
Database seeded successfully.
```

### Option B: Reset Complet (recommandé pour démarrer propre)

```bash
php artisan migrate:fresh --seed
```

✅ Cela va:
1. Reset complètement la BD
2. Rejouer les migrations
3. Lancer les seeders

---

## 3️⃣ Vérifier les Données (2 min)

### Vérification Rapide

```bash
php artisan tinker

# Dans Tinker, taper:
>>> DB::table('users')->count()
=> 4

>>> DB::table('article')->count()
=> 5

>>> DB::table('orders')->count()
=> 4

>>> DB::table('article_mouvement')->count()
=> 9

>>> DB::table('customers')->count()
=> 4

>>> exit
```

✅ Si tu vois les bons chiffres, c'est ok!

### Vérification Complète (via SQL)

```bash
mysql -u root -p epsilon_erp

# Dans MySQL:
SELECT
    'users' as table_name, COUNT(*) as count FROM users UNION ALL
    SELECT 'customers', COUNT(*) FROM customers UNION ALL
    SELECT 'orders', COUNT(*) FROM orders UNION ALL
    SELECT 'article', COUNT(*) FROM article UNION ALL
    SELECT 'article_mouvement', COUNT(*) FROM article_mouvement;
```

---

## 4️⃣ Générer un Dump (2 min)

### Dump Simple

```bash
cd /home/badji/EPSILON/erp

./scripts/dump-db.sh
```

✅ Crée: `database/dumps/epsilon_erp_20260327_183600.sql`

### Dump Compressé (Recommandé!)

```bash
./scripts/dump-db.sh --compress
```

✅ Crée: `database/dumps/epsilon_erp_20260327_183600.sql.gz` (~80% plus petit)

---

## 5️⃣ Se Connecter aux Données (5 min)

### Utilisateurs Créés

| Email | Mot de passe | Rôle |
|-------|--------------|------|
| admin@epsilon-erp.local | Admin@Epsilon2026 | Admin |
| yves.sales@epsilon-erp.local | Sales@Epsilon2026 | Sales Manager |
| marie.stock@epsilon-erp.local | Stock@Epsilon2026 | Warehouse Manager |
| pierre.delivery@epsilon-erp.local | Delivery@Epsilon2026 | Delivery Manager |

### Clients Créés

- Acme Corporation (Limite: 50k)
- TechStore SARL (Limite: 30k)
- LogiShop France (Limite: 25k)
- FreshMarket Lyon (Limite: 15k)

### Produits Créés

- Laptop EPSILON Pro 15 (1299€)
- Souris Sans Fil (49.99€)
- Clavier Mécanique RGB (149.99€)
- Fromage Frais Camembert (7.99€)
- Lait Entier 1L (1.49€)

---

## 6️⃣ Restaurer un Dump (si besoin)

```bash
# List tous les dumps
./scripts/dump-db.sh --list

# Restaurer un dump
./scripts/dump-db.sh --restore database/dumps/epsilon_erp_20260327_183600.sql.gz
```

---

## 📊 Données Générées - Vue d'ensemble

```
EPSILON SARL (Entreprise)
│
├─ 5 Users (Admin, Sales, Warehouse, Delivery, + dev)
│
├─ 5 Depots (Hiérarchie Paris + Lyon)
│   ├─ Centre Principal (Paris)
│   ├─ Zone Stockage
│   ├─ Zone Réfrigérée
│   ├─ Zone Expédition
│   └─ Depot Lyon
│
├─ 5 Product Families (Électronique, Alimentaire, etc.)
│
├─ 5 Products
│   ├─ 3 Électronique (Laptop, Mouse, Keyboard)
│   └─ 2 Alimentaire (Fromage, Lait)
│
├─ 4 Customers (Acme, TechStore, LogiShop, FreshMarket)
│
├─ 4 Sales Orders
│   ├─ 2x Confirmed
│   ├─ 1x Draft
│   └─ 1x autre statut
│
└─ 9 Stock Movements
    ├─ 5x Inbound (Receipts)
    └─ 4x Outbound (Sales/Shipments)
```

---

## ✅ Checklist Complète

- [ ] Modifié `DatabaseSeeder.php`
- [ ] Vérifié `.env` (DB config)
- [ ] Exécuté `php artisan migrate:fresh --seed`
- [ ] Vérifié les données avec `php artisan tinker`
- [ ] Généré un dump: `./scripts/dump-db.sh --compress`
- [ ] Testé la restauration
- [ ] Notée l'URL API: `http://localhost:8000/api`

---

## 🐛 Si ça ne marche pas

### Erreur: "Seeder not found"

```bash
# Vérifier que les fichiers sont dans le bon dossier
ls -la erp/database/seeders/Epsilon*Seeder.php
ls -la erp/database/seeders/ProductCatalog*
```

### Erreur: "Table doesn't exist"

```bash
# Relancer les migrations
php artisan migrate:fresh

# Puis seed
php artisan db:seed
```

### Erreur: "Access denied for user"

```bash
# Vérifier .env
cat erp/.env | grep DB_

# Tester connexion manuel
mysql -u root -p -e "SELECT DATABASE();"
```

### Script dump pas exécutable

```bash
chmod +x erp/scripts/dump-db.sh
```

---

## 🎓 Exemples d'Utilisation

### Workflow 1: Dev Local

```bash
cd /home/badji/EPSILON/erp

# 1. Reset propre
php artisan migrate:fresh --seed

# 2. Développer... faire des tests...

# 3. Créer backup avant changements risqués
./scripts/dump-db.sh --compress

# 4. Continuer...

# 5. Besoin de revenir?
./scripts/dump-db.sh --restore database/dumps/epsilon_erp_*.sql.gz

# 6. Relancer
php artisan migrate:fresh --seed
```

### Workflow 2: Livraison Client

```bash
# 1. Préparer
php artisan migrate:fresh --seed

# 2. Ajouter données client spécifiques
# ... importer CSV ou ajouter avec admin UI

# 3. Créer dump pour livraison
./scripts/dump-db.sh --compress

# 4. Livrer file:
# database/dumps/epsilon_erp_20260327_*.sql.gz
```

### Workflow 3: Staging/Prod

```bash
# Sur serveur distant:
cd /opt/epsilon/erp

# Restaurer depuis backup
gunzip -c epsilon_erp_latest.sql.gz | mysql -u root -p epsilon_erp

# Vérifier
php artisan tinker
>>> DB::table('users')->count()
```

---

## 💡 Tips & Tricks

### Créer un alias

```bash
# Dans ~/.bashrc ou ~/.zshrc
alias dump-epsilon="cd /home/badji/EPSILON/erp && ./scripts/dump-db.sh"
alias restore-epsilon="cd /home/badji/EPSILON/erp && ./scripts/dump-db.sh --restore"

# Reload
source ~/.bashrc (ou ~/.zshrc)

# Utiliser
dump-epsilon --compress
```

### Scheduler un dump régulier

```bash
# Ajouter à crontab
crontab -e

# Ajouter:
# Dump every day at 2:00 AM
0 2 * * * cd /home/badji/EPSILON/erp && ./scripts/dump-db.sh --compress >> logs/dump.log 2>&1
```

### Sanity check avant commit

```bash
# Script pre-commit
#!/bin/bash
cd erp
php artisan migrate:fresh --seed --force
php artisan test
```

---

## 📞 Questions?

**Q: Combien de données?**
A: ~50 enregistrements au total. Parfait pour tests, petite démo.

**Q: Puis-je ajouter mes données?**
A: Oui! Créer un nouveau Seeder, l'ajouter à DatabaseSeeder.

**Q: Impact performance?**
A: Aucun. Seeders rapides (<1s).

**Q: Sécurité?**
A: Mots de passe de démo seulement. À changer en prod!

---

## 🎁 Fichiers Fournís

```✓ Tous les seeders
✓ Scripts dump automatisé
✓ Ce guide
✓ Gitignore pour dumps
```

---

**Status**: ✅ **PRÊT À UTILISER**

Première commande:
```bash
cd /home/badji/EPSILON/erp
php artisan migrate:fresh --seed
```

Good luck! 🚀
