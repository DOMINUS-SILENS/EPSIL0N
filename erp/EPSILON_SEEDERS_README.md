# 📊 EPSILON Database Seeders & Dump Package

**Une solution complète** pour générer des données de test et des dumps SQL exploitables.

---

## 🎯 Ce que tu as reçu

### Seeders Créés

1. **EpsilonCoreSeeder.php** - Users & authentication
1. **EntrepriseDepotSeeder.php** - Companies & warehouse hierarchy
3. **ProductCatalogSeeder.php** - Product families, brands, articles, units
4. **CustomerOrderSeeder.php** - Customers & sales orders
5. **InventorySeeder.php** - Stock initialization & movements

### Scripts

- **erp/scripts/dump-db.sh** - Dump automatisé avec compression et restore

---

## ⚡ Quick Start

### 1. Rendre le script exécutable

```bash
chmod +x erp/scripts/dump-db.sh
```

### 2. Modifier DatabaseSeeder.php

Ouvre `erp/database/seeders/DatabaseSeeder.php` et remplace le contenu par:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            EpsilonCoreSeeder::class,
            EntrepriseDepotSeeder::class,
            ProductCatalogSeeder::class,
            CustomerOrderSeeder::class,
            InventorySeeder::class,
        ]);
    }
}
```

### 3. Lancer les seeders

```bash
cd /home/badji/EPSILON/erp

# Seed seulement (garder les données existantes)
php artisan db:seed

# Reset complet + seed
php artisan migrate:fresh --seed
```

---

## 📦 Générer des Dumps

### Dump Simple (SQL)

```bash
./scripts/dump-db.sh
# Crée: database/dumps/epsilon_erp_20260327_120000.sql
```

### Dump Compressé (Gzip)

```bash
./scripts/dump-db.sh --compress
# Crée: database/dumps/epsilon_erp_20260327_120000.sql.gz (~80% plus petit)
```

### Lister les dumps

```bash
./scripts/dump-db.sh --list
```

### Restaurer un dump

```bash
./scripts/dump-db.sh --restore database/dumps/epsilon_erp_20260327_120000.sql.gz
```

---

## 📋 Données Générées

### Users (4)
- Admin EPSILON (admin@epsilon-erp.local)
- Yves Sales (yves.sales@epsilon-erp.local)
- Marie Stock (marie.stock@epsilon-erp.local)
- Pierre Delivery (pierre.delivery@epsilon-erp.local)

**Mot de passe pour tous**: `Admin@Epsilon2026` (à changer en production!)

### Enterprise (1)
- EPSILON SARL (Paris, France)

### Depots (5) - Hiérarchie
```
Centre Principal (Paris)
├─ Zone Stockage Produits (Niveau 1)
├─ Zone Réfrigérée (Frais)
└─ Zone Expédition

Depot Régional (Lyon)
```

### Product Families (5)
- Électronique
  - Informatique
  - Accessoires
- Alimentaire
  - Produits Frais

### Brands (3)
- EPSILON Pro
- TechFlow
- FreshFood Co

### Articles (5) avec SKUs
1. **Laptop EPSILON Pro 15** - 50 en stock
2. **Souris Sans Fil EPSILON** - 200 en stock
3. **Clavier Mécanique RGB** - 120 en stock
4. **Fromage Frais Camembert** - 80 en stock (réfrigéré)
5. **Lait Entier 1L** - 150 en stock (réfrigéré)

### Customers (4)
- Acme Corporation (Limite: 50k)
- TechStore SARL (Limite: 30k)
- LogiShop France (Limite: 25k)
- FreshMarket Lyon (Limite: 15k)

### Sales Orders (4)
- SO-2026-0001: Acme (Confirmed)
- SO-2026-0002: TechStore (Confirmed)
- SO-2026-0003: LogiShop (Draft)
- SO-2026-0004: FreshMarket (Confirmed)

### Stock Movements (9)
- 5 entrées (réceptions de fournisseurs)
- 4 sorties (ventes/expéditions)

---

## 🔧 Configuration

### Variables d'Environnement

Le script respecte `.env`:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=epsilon_erp
DB_USERNAME=root
DB_PASSWORD=***
```

### Overrides

Tu peux aussi passer via CLI:

```bash
DB_DATABASE=test_db DB_USERNAME=user ./scripts/dump-db.sh --compress
```

---

## 💡 Cas d'Usage

### Cas 1: Développement Local

```bash
# Reset complet avec données fraîches
php artisan migrate:fresh --seed

# Travailler...

# Créer un backup avant de faire des tests
./scripts/dump-db.sh --compress

# Restaurer si besoin
./scripts/dump-db.sh --restore epsilon_erp_*.sql.gz
```

### Cas 2: Livraison Client

```bash
# 1. Préparer les données
php artisan migrate:fresh --seed

# 2. Ajouter données client-spécifiques
# ... (custom seeders ou import CSV)

# 3. Créer le dump pour livraison
./scripts/dump-db.sh --compress

# 4. Livrer: database/dumps/epsilon_erp_*.sql.gz
```

### Cas 3: Environnement de Staging

```bash
# Sur le serveur
cd /opt/epsilon/erp

# Restaurer depuis backup local
scp user@dev:/path/to/epsilon_erp_*.sql.gz .
./scripts/dump-db.sh --restore epsilon_erp_*.sql.gz

# Exécuter migrations éventuelles
php artisan migrate
```

---

## 🛡️ Sécurité & Bonnes Pratiques

### ⚠️ À FAIRE

✅ Modifier les mots de passe des seeders après premier seed
✅ Compresser les dumps pour la transmission
✅ Maintenir .gitignore - dumps ne doivent pas être versionnés
✅ Sauvegarder les dumps régulièrement
✅ Documenter les données sensibles

### ⛔ À NE PAS FAIRE

❌ Committer les dumps générés dans Git
❌ Envoyer les dumps en HTTP non-chiffré
❌ Utiliser les même identifiants de test en production
❌ Laisser traces de données sensibles

### .gitignore

```bash
# À ajouter dans .gitignore
database/dumps/
database/dumps/*.sql
database/dumps/*.sql.gz
```

---

## 🎓 Extension: Créer tes propres Seeders

### Template

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Tes données
        DB::table('table_name')->insertOrIgnore([
            'field' => 'value',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
```

### L'ajouter à DatabaseSeeder

```php
// Dans DatabaseSeeder.php
$this->call([
    // ... autres
    CustomSeeder::class,  // ← ajouter ici
]);
```

---

## 📊 Vérifier les données seedées

### Vue d'ensemble

```bash
php artisan tinker

# Dans Tinker:
>>> DB::table('users')->count()
=> 4

>>> DB::table('article')->count()
=> 5

>>> DB::table('orders')->count()
=> 4

>>> DB::table('article_mouvement')->count()
=> 9
```

### Requête SQL

```sql
SELECT 'users' as table_name, COUNT(*) as count FROM users
UNION ALL
SELECT 'customers', COUNT(*) FROM customers
UNION ALL
SELECT 'orders', COUNT(*) FROM orders
UNION ALL
SELECT 'order_lines', COUNT(*) FROM order_lines
UNION ALL
SELECT 'article', COUNT(*) FROM article
UNION ALL
SELECT 'article_mouvement', COUNT(*) FROM article_mouvement;
```

---

## 🐛 Troubleshooting

### "Dump file not found"

```bash
# Utiliser le chemin complet
./scripts/dump-db.sh --restore /home/badji/EPSILON/erp/database/dumps/epsilon_erp_*.sql.gz
```

### "Access denied for user 'root'"

```bash
# Vérifier .env
cat .env | grep DB_

# Actualiser vars
source .env
./scripts/dump-db.sh --compress
```

### "Table already exists"

```bash
# Utiliser --compress pour force rebuild
php artisan migrate:fresh --seed --force
```

### Dump trop volumineux

```bash
# Compresser tous les dumps existants
gzip -f database/dumps/*.sql

# Nettoyer old dumps (keep 5 latest)
ls -lt database/dumps/*.sql.gz | tail -n +6 | awk '{print $NF}' | xargs rm -f
```

---

## 📈 Performance Considerations

### Pour de gros volumes

```bash
# Insérer sans contraintes
php artisan db:seed --class=BulkSeeder

# Ajouter indexes après
php artisan tinker
>>> DB::statement('OPTIMIZE TABLE article_mouvement');
>>> DB::statement('OPTIMIZE TABLE stock_balances');
```

### Dump volumineux

```bash
# Compression maximale
gzip -9 database/dumps/epsilon_erp_*.sql

# Backup incrémental (derniers changements)
mysqldump --incremental -u root -p epsilon_erp > dumps/incremental.sql
```

---

## 🎁 Fichiers Fournis

```
erp/
├── database/
│   ├── seeders/
│   │   ├── DatabaseSeeder.php (À MODIFIER)
│   │   ├── EpsilonCoreSeeder.php ✓
│   │   ├── EntrepriseDepotSeeder.php ✓
│   │   ├── ProductCatalogSeeder.php ✓
│   │   ├── CustomerOrderSeeder.php ✓
│   │   ├── InventorySeeder.php ✓
│   │   └── dumps/ (Créé automatiquement)
│   │       └── epsilon_erp_YYYYMMDD_HHMMSS.sql(.gz)
│   └── ...
├── scripts/
│   └── dump-db.sh ✓                    (Rendre exécutable!)
└── ...
```

---

## 📞 Questions Fréquentes

**Q: Les seeders écrasent mes données?**
A: Non! Les seeders utilisent `insertOrIgnore()` qui ne crée que si n'existe pas.

**Q: Puis-je runner les seeders plusieurs fois?**
A: Oui, en toute sécurité. Les doublons sont ignorés.

**Q: Où sont stockés les dumps?**
A: Dans `database/dumps/` avec timestamp automatique.

**Q: Comment versionner les dumps?**
A: NE PAS versionner! Mettre dans `.gitignore`. Utiliser pour backups seulement.

**Q: Les taxes et sauf sont inclus?**
A: Non, on a créé que le socle de test. À ajouter selon tes besoins.

---

## ✅ Checklist d'Installation

- [ ] Copier tous les seeders dans `database/seeders/`
- [ ] Modifier `DatabaseSeeder.php` pour inclure les nouveaux seeders
- [ ] Rendre `scripts/dump-db.sh` exécutable: `chmod +x`
- [ ] Vérifier `.env` (DB credentials)
- [ ] Exécuter `php artisan migrate:fresh --seed`
- [ ] Vérifier avec `php artisan tinker` ou DBeaver
- [ ] Créer un dump: `./scripts/dump-db.sh --compress`
- [ ] Tester la restauration

---

## 🚀 Next Steps

1. **Ajouter tes données spécifiques**: Étendre les seeders avec tes besoins
2. **Setup CI/CD**: Utiliser seeders dans tests automatisés
3. **Backup policy**: Schedule dumps réguliers
4. **Anonymization**: Créer versoin "public" sans données sensibles

---

**Paquet Seeder EPSILON - Prêt à Utiliser** ✓
