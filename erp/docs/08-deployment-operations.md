# Déploiement & Opérations

## Vue d'Ensemble

Ce document couvre le déploiement en production, la surveillance, la maintenance et la résolution des problèmes du SFA ERP.

---

## 1. Architecture de Déploiement

### 1.1 Topologie Recommandée

```
┌─────────────────────────────────────────────────────────────────────────┐
│                              PRODUCTION                                  │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌─────────────────┐                                                    │
│  │  Load Balancer  │  (Nginx / HAProxy)                                 │
│  │  SSL/TLS        │                                                    │
│  └────────┬────────┘                                                    │
│           │                                                              │
│    ┌──────┴──────┐                                                        │
│    │             │                                                        │
│ ┌──▼──┐     ┌──▼──┐                                                  │
│ │Web 1│     │Web 2│  (Laravel Horizon + PHP-FPM)                     │
│ │     │     │     │  - API GraphQL                                     │
│ │4 CPU│     │4 CPU│  - REST Endpoints                                   │
│ │8 GB │     │8 GB │  - Queue Workers                                    │
│ └─────┘     └─────┘                                                  │
│                                                                          │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │                    Data Layer                                   │    │
│  ├────────────────────────────────────────────────────────────────┤    │
│  │                                                                 │    │
│  │  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐         │    │
│  │  │   MySQL     │    │    Redis    │    │   Queue     │         │    │
│  │  │   Primary   │    │   Cache     │    │   Worker    │         │    │
│  │  │   (16 CPU)  │    │   Pub/Sub   │    │   (8 CPU)   │         │    │
│  │  │   64 GB     │    │   Session   │    │             │         │    │
│  │  └──────┬──────┘    └─────────────┘    └─────────────┘         │    │
│  │         │                                                      │    │
│  │         │    Replication                                        │    │
│  │         │    ├──► Replica 1 (Read queries)                     │    │
│  │         │    └──► Replica 2 (Backups)                        │    │
│  │         │                                                      │    │
│  └─────────┼──────────────────────────────────────────────────────┘    │
│            │                                                             │
│  ┌─────────▼────────────────────────────────────────────────────────┐   │
│  │                    Monitoring Stack                               │   │
│  │  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐            │   │
│  │  │Prometheus│  │ Grafana │  │  Loki   │  │ AlertManager│        │   │
│  │  │ Metrics │  │ Dashboards│ │  Logs   │  │  Alerting   │        │   │
│  │  └─────────┘  └─────────┘  └─────────┘  └─────────┘            │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 1.2 Configuration Serveur

**Serveur Application (2x minimum) :**
- 4 vCPU
- 8 GB RAM
- 50 GB SSD
- Ubuntu 22.04 LTS

**Serveur Base de Données :**
- 16 vCPU
- 64 GB RAM
- 500 GB SSD NVMe
- MySQL 8.0 avec Group Replication

---

## 2. Installation

### 2.1 Prérequis

```bash
# Installation système
apt update && apt upgrade -y
apt install -y nginx php8.2-fpm php8.2-mysql php8.2-redis \
    php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip \
    redis-server mysql-server git composer

# Configuration PHP
# /etc/php/8.2/fpm/php.ini
memory_limit = 512M
max_execution_time = 30
upload_max_filesize = 10M
post_max_size = 10M
opcache.enable = 1
opcache.memory_consumption = 256
```

### 2.2 Déploiement Application

```bash
# Clone du repository
git clone https://github.com/entreprise/sfa-erp.git /var/www/sfa
cd /var/www/sfa

# Installation dépendances
composer install --no-dev --optimize-autoloader

# Configuration environnement
cp .env.example .env
php artisan key:generate

# Configuration base de données
DB_CONNECTION=mysql
DB_HOST=10.0.0.10
DB_PORT=3306
DB_DATABASE=sfa_production
DB_USERNAME=sfa_app
DB_PASSWORD="secure_random_password"

# Event Store sharding
EVENT_STORE_PARTITIONS=16
EVENT_STORE_VERIFY_INTEGRITY=true

# Outbox
OUTBOX_BATCH_SIZE=100
OUTBOX_MAX_RETRIES=5

# Cache
CACHE_DRIVER=redis
REDIS_HOST=10.0.0.11
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis
HORIZON_PREFIX=horizon:

# Migrations
php artisan migrate --force

# Optimisations
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 2.3 Configuration Nginx

```nginx
# /etc/nginx/sites-available/sfa
server {
    listen 80;
    server_name api.sfa.entreprise.com;
    root /var/www/sfa/public;

    # SSL
    listen 443 ssl http2;
    ssl_certificate /etc/ssl/certs/sfa.crt;
    ssl_certificate_key /etc/ssl/private/sfa.key;

    # Sécurité
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    # GraphQL endpoint
    location /graphql {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # Timeouts pour longues requêtes
        proxy_read_timeout 300s;
        proxy_connect_timeout 75s;
    }

    # SSE pour temps réel
    location /api/stream {
        proxy_pass http://127.0.0.1:8000;
        proxy_http_version 1.1;
        proxy_set_header Connection '';
        proxy_buffering off;
        proxy_cache off;
        chunked_transfer_encoding off;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

---

## 3. Commandes Opérationnelles

### 3.1 Surveillance Quotidienne

```bash
#!/bin/bash
# /usr/local/bin/sfa-health-check.sh

echo "=== SFA Health Check $(date) ==="

# Vérifier queue
PENDING=$(php artisan tinker --execute="echo \App\Models\DomainOutbox::where('status', 'pending')->count();")
echo "Pending outbox events: $PENDING"
if [ "$PENDING" -gt 1000 ]; then
    echo "WARNING: High pending count!" | mail -s "SFA Alert" ops@entreprise.com
fi

# Vérifier projections
LAG=$(mysql -e "SELECT MAX(es.id - p.last_event_id) FROM event_store es JOIN projection_versions p ON 1=1;" | tail -1)
echo "Projection lag: $LAG events"
if [ "$LAG" -gt 100 ]; then
    echo "WARNING: Projection lag high!"
fi

# Vérifier espace disque
DISK_USAGE=$(df -h /var/lib/mysql | awk 'NR==2 {print $5}' | tr -d '%')
if [ "$DISK_USAGE" -gt 80 ]; then
    echo "WARNING: Disk usage ${DISK_USAGE}%!"
fi

# Vérifier mémoire Redis
REDIS_MEM=$(redis-cli INFO memory | grep used_memory_human | cut -d: -f2 | tr -d '\r')
echo "Redis memory: $REDIS_MEM"
```

### 3.2 Maintenance Planifiée

```bash
# Cron jobs recommandées
# /etc/cron.d/sfa

# Process outbox toutes les minutes
* * * * * www-data /usr/bin/php /var/www/sfa/artisan outbox:process >> /var/log/sfa/outbox.log 2>&1

# Évaluation des alertes toutes les 5 minutes
*/5 * * * * www-data /usr/bin/php /var/www/sfa/artisan alerts:evaluate >> /var/log/sfa/alerts.log 2>&1

# Sync CRDT toutes les 10 minutes
*/10 * * * * www-data /usr/bin/php /var/www/sfa/artisan crdt:sync >> /var/log/sfa/crdt.log 2>&1

# Snapshots toutes les heures
0 * * * * www-data /usr/bin/php /var/www/sfa/artisan projection:snapshot >> /var/log/sfa/snapshots.log 2>&1

# Health check toutes les 5 minutes
*/5 * * * * root /usr/local/bin/sfa-health-check.sh

# Backup quotidien à 2h
0 2 * * * root /usr/local/bin/sfa-backup.sh

# Nettoyage logs hebdomadaire
0 3 * * 0 root find /var/log/sfa -name "*.log" -mtime +7 -delete
```

---

## 4. Backup et Restauration

### 4.1 Stratégie de Backup

```bash
#!/bin/bash
# /usr/local/bin/sfa-backup.sh

BACKUP_DIR="/backup/sfa/$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# 1. Backup MySQL (Event Store et Projections)
mysqldump --single-transaction --routines \
    --databases sfa_production \
    | gzip > "$BACKUP_DIR/mysql.sql.gz"

# 2. Backup Redis
redis-cli BGSAVE
sleep 5
cp /var/lib/redis/dump.rdb "$BACKUP_DIR/redis.rdb"

# 3. Backup configuration
cp -r /var/www/sfa/.env "$BACKUP_DIR/"
cp -r /etc/nginx/sites-available "$BACKUP_DIR/nginx/"

# 4. Snapshot Event Store séparé
php artisan event-store:backup --output="$BACKUP_DIR/event_store"

# Compression et upload S3
tar -czf "$BACKUP_DIR.tar.gz" "$BACKUP_DIR"
aws s3 cp "$BACKUP_DIR.tar.gz" s3://sfa-backups/production/

# Nettoyage local
rm -rf "$BACKUP_DIR" "$BACKUP_DIR.tar.gz"

# Rotation S3 (garder 30 jours)
aws s3 ls s3://sfa-backups/production/ | \
    awk '{print $4}' | \
    sort | \
    head -n -30 | \
    xargs -I {} aws s3 rm s3://sfa-backups/production/{}
```

### 4.2 Restauration

```bash
#!/bin/bash
# /usr/local/bin/sfa-restore.sh

BACKUP_FILE=$1  # s3://sfa-backups/production/20260322_020001.tar.gz

# Télécharger backup
aws s3 cp "$BACKUP_FILE" /tmp/
tar -xzf "/tmp/$(basename $BACKUP_FILE)" -C /tmp/
BACKUP_DIR="/tmp/$(basename $BACKUP_FILE .tar.gz)"

# Arrêter les workers
php artisan horizon:pause

# Restaurer MySQL
gunzip < "$BACKUP_DIR/mysql.sql.gz" | mysql

# Restaurer Redis
systemctl stop redis-server
cp "$BACKUP_DIR/redis.rdb" /var/lib/redis/dump.rdb
systemctl start redis-server

# Reconstruire projections
php artisan projection:rebuild-all

# Reprendre workers
php artisan horizon:continue

# Nettoyage
rm -rf "$BACKUP_DIR" "/tmp/$(basename $BACKUP_FILE)"
```

---

## 5. Monitoring

### 5.1 Métriques Clés

| Métrique | Seuil Warning | Seuil Critical | Action |
|----------|---------------|----------------|--------|
| Outbox pending | > 1000 | > 5000 | Scaler workers |
| Projection lag | > 100 events | > 1000 events | Rebuild projections |
| DB CPU | > 70% | > 90% | Optimiser queries |
| DB Disk | > 80% | > 90% | Purge anciens events |
| Redis memory | > 80% | > 95% | Augmenter RAM |
| HTTP 500 rate | > 0.1% | > 1% | Investigation |

### 5.2 Dashboard Grafana

```yaml
# Exemple de panels Grafana
panels:
  - title: Outbox Processing Rate
    type: graph
    targets:
      - expr: rate(outbox_events_processed_total[5m])
    alert:
      - condition: avg() < 10
        for: 5m

  - title: Projection Lag
    type: singlestat
    targets:
      - expr: event_store_max_id - projection_last_event_id
    thresholds: [100, 1000]

  - title: API Response Time
    type: heatmap
    targets:
      - expr: http_request_duration_seconds_bucket
```

---

## 6. Résolution des Problèmes

### 6.1 Guide de Dépannage

```
PROBLÈME: Outbox accumulation
SYMPTÔMES: Table domain_outbox en croissance
CAUSES POSSIBLES:
  1. Workers Horizon arrêtés
  2. Erreurs dans projectors
  3. Database lock contention

DIAGNOSTIC:
  php artisan outbox:status
  php artisan horizon:list
  mysql -e "SHOW PROCESSLIST;"

RÉSOLUTION:
  1. php artisan horizon:continue
  2. Vérifier logs: grep ERROR storage/logs/laravel.log
  3. Redémarrer workers: php artisan horizon:terminate && php artisan horizon

---

PROBLÈME: Projections désynchronisées
SYMPTÔMES: Données inconsistantes entre event store et projections

RÉSOLUTION:
  # Identifier le projector concerné
  php artisan projection:status

  # Reconstruire
  php artisan projection:rebuild SalesDashboardProjector

  # Vérifier intégrité
  php artisan projection:verify

---

PROBLÈME: High CPU sur Event Store

RÉSOLUTION:
  # Vérifier requêtes lentes
  mysql -e "SELECT * FROM performance_schema.events_statements_summary_by_digest ORDER BY SUM_TIMER_WAIT DESC LIMIT 10;"

  # Ajouter index si manquant
  php artisan migrate --path=database/migrations/..._add_index_to_event_store.php

  # Archiver vieux events si nécessaire
  php artisan event-store:archive --older-than="1 year"
```

### 6.2 Procédures d'Urgence

```bash
# Scénario: Base de données saturée
# Action immédiate:

# 1. Activer mode maintenance
php artisan down --message="Maintenance en cours"

# 2. Archiver events anciens
php artisan event-store:archive --older-than="6 months"

# 3. Optimiser tables
mysql -e "OPTIMIZE TABLE event_store;"
mysql -e "OPTIMIZE TABLE domain_outbox;"

# 4. Redémarrer services
systemctl restart php8.2-fpm
systemctl restart nginx

# 5. Sortie maintenance
php artisan up
```

---

## 7. Scaling Horizontal

### 7.1 Ajout d'un Serveur Application

```bash
# 1. Provisionner nouveau serveur
# 2. Déployer code (même procédure)
# 3. Configurer pour utiliser la même DB et Redis
# 4. Ajouter au load balancer

# Sur le nouveau serveur:
php artisan migrate --force
php artisan config:cache

# Sur le load balancer (Nginx upstream):
upstream sfa_backend {
    server 10.0.0.10:8000;
    server 10.0.0.11:8000;  # Nouveau
}
```

### 7.2 Sharding Additionnel

Si une entreprise dépasse la capacité d'un shard :

```php
# Déplacer entreprise vers shard dédié
php artisan company:migrate-to-shard \
    --company-id=123 \
    --target-shard=shard-02

# Reconstruire projections sur nouveau shard
php artisan projection:rebuild --shard=shard-02
```

---

## 8. Sécurité

### 8.1 Checklist Sécurité

- [ ] SSL/TLS sur tous les endpoints
- [ ] Rate limiting sur API
- [ ] Authentication JWT avec expiration courte
- [ ] Authorization par rôle (RBAC)
- [ ] Sanitization des inputs GraphQL
- [ ] Audit log activé
- [ ] Backup chiffrés
- [ ] Accès SSH par clé uniquement
- [ ] Firewall configuré
- [ ] Updates de sécurité automatiques

### 8.2 Configuration Fail2Ban

```ini
# /etc/fail2ban/jail.d/sfa.conf
[sfa-api]
enabled = true
filter = sfa-auth
logpath = /var/www/sfa/storage/logs/laravel.log
maxretry = 5
findtime = 300
bantime = 3600
```

---

## 9. Guide de Résolution d'Incidents (Troubleshooting)

### 9.1 Événements Bloqués (Dead Letter Log)

Si des événements apparaissent dans la file d'erreur :
1. **Identifier l'erreur** : `SELECT error_message FROM domain_outbox WHERE status = 'failed'`.
2. **Corriger la cause** (souvent un bug dans un projecteur ou une contrainte DB).
3. **Rejouer** : Utiliser la commande `php artisan outbox:replay-failed`.
4. **Vérification** : Comparer `last_event_id` entre l'Event Store et les projections pour s'assurer qu'il n'y a plus de gap.

---

## 8. Prochaines Sections

- [01-architecture-generale.md](./01-architecture-generale.md) - Documentation technique générale
- [02-infrastructure-core.md](./02-infrastructure-core.md) - Services et composants techniques
- [03-domaines-metiers.md](./03-domaines-metiers.md) - Implémentation des 13 macro-domaines
- [04-projections-analytics.md](./04-projections-analytics.md) - Phase C : Dashboards et analytics
- [05-temps-reel-crdt.md](./05-temps-reel-crdt.md) - Phase D : Temps réel et sync mobile
- [06-api-graphql.md](./06-api-graphql.md) - Phase D : API de requête GraphQL
- [07-alerting-observabilite.md](./07-alerting-observabilite.md) - Phase D : Monitoring et alertes
- [08-deployment-operations.md](./08-deployment-operations.md) - Guide de déploiement et maintenance