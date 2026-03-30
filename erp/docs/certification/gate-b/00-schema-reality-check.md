# Phase B0: Schema Reality Check (Automated Audit)

## Objective
Establish absolute truth of physical DB schema compared against application logic.

## Migration Status (Summary)
```text

  Migration name .............................................. Batch / Status  
  0001_01_01_000000_create_users_table ............................... [1] Ran  
  0001_01_01_000001_create_cache_table ............................... [1] Ran  
  0001_01_01_000002_create_jobs_table ................................ [1] Ran  
  2025_03_21_000000_create_entreprise_table .......................... [1] Ran  
  2025_03_21_000001_create_article_table ............................. [1] Ran  
  2025_03_21_000002_create_article_unite_table ....................... [1] Ran  
  2025_03_21_000003_create_article_groupe_prix_table ................. [1] Ran  
  2025_03_21_000004_create_article_article_unite_article_groupe_prix_table  [1] Ran  
  2025_03_21_000005_create_depot_table ............................... [1] Ran  
  2025_03_21_000006_create_article_unite_depot_table ................. [1] Ran  
  2025_03_21_000007_create_article_mouvement_table ................... [1] Ran  
  2025_03_21_000008_create_article_famille_table ..................... [1] Ran  
  2025_03_21_000009_create_article_marque_table ...................... [1] Ran  
  2025_03_21_000010_create_mouvement_table ........................... [1] Ran  
  2025_03_21_000011_create_mouvement_ligne_table ..................... [1] Ran  
  2025_03_21_000012_create_article_mouvement_mouvement_ligne_table ... [1] Ran  
  2025_03_21_000013_create_balance_stock_table ....................... [1] Ran  
  2025_03_21_000014_create_article_mouvement_triggers ................ [1] Ran  
  2025_03_21_000015_create_calculate_balance_stock_procedure ......... [1] Ran  
  2025_03_21_000017_register_stock_movement_event_schema ............. [1] Ran  
  2026_03_20_183720_create_aggregate_sequences_table ................. [1] Ran  
  2026_03_20_183816_create_domain_outbox_table ....................... [1] Ran  
  2026_03_20_183908_create_integration_outbox_table .................. [1] Ran  
  2026_03_20_184008_create_credit_reservations_table ................. [1] Ran  
  2026_03_20_184113_create_stock_reservations_table .................. [1] Ran  
  2026_03_20_184147_create_audit_logs_table .......................... [1] Ran  
  2026_03_20_212618_create_customers_table ........................... [1] Ran  
  2026_03_20_212632_create_stock_moves_table ......................... [1] Ran  
  2026_03_21_122330_create_journal_tables ............................ [1] Ran  
  2026_03_21_122405_create_customer_balance_projections_table ........ [1] Ran  
  2026_03_21_122658_create_projection_versions_table ................. [1] Ran  
  2026_03_21_132028_create_event_store_tables ........................ [1] Ran  
  2026_03_21_132459_create_event_shard_sequences_table ............... [1] Ran  
  2026_03_21_135037_create_contracts_table ........................... [1] Ran  
  2026_03_21_135331_create_intents_table ............................. [1] Ran  
  2026_03_21_135553_create_anomalies_table ........................... [1] Ran  
  2026_03_21_135711_create_system_modes_table ........................ [1] Ran  
  2026_03_21_145224_create_sagas_table ............................... [1] Ran  
  2026_03_21_145255_create_saga_steps_table .......................... [1] Ran  
  2026_03_21_160813_create_stock_quants_view ......................... [1] Ran  
  2026_03_21_162418_create_projection_snapshots_table ................ [1] Ran  
  2026_03_21_164803_add_signature_to_event_store ..................... [1] Ran  
  2026_03_21_165101_create_merkle_nodes_table ........................ [1] Ran  
  2026_03_21_170753_create_decision_audit_table ...................... [1] Ran  
  2026_03_22_140000_create_analytics_dashboards_tables ............... [1] Ran  
  2026_03_22_150000_create_stock_balances_table ...................... [1] Ran  
  2026_03_22_160000_create_alert_and_crdt_tables ..................... [1] Ran  
  2026_03_22_164500_update_domain_outbox_for_worker_determinism ...... [1] Ran  
  2026_03_22_164800_update_saga_steps_for_determinism ................ [1] Ran  
  2026_03_22_172200_create_domain_events_and_refactor_outbox ......... [1] Ran  
  2026_03_22_174000_enforce_axiomatic_closures ....................... [1] Ran  
  2026_03_22_191435_add_date_columns_to_article_article_unite_article_groupe_prix_table  [1] Ran  
  2026_03_25_130000_migrate_event_store_aggregate_id_to_varchar ...... [1] Ran  
  2026_03_25_143125_create_employees_table ........................... [1] Ran  
  2026_03_25_143500_create_vehicles_table ............................ [1] Ran  
  2026_03_25_144000_create_purchase_orders_table ..................... [1] Ran  
  2026_03_25_144500_create_projects_table ............................ [1] Ran  
  2026_03_25_150000_create_aggregate_snapshots_table ................. [1] Ran  
  2026_03_25_151000_create_failed_outbox_events_table ................ [1] Ran  
  2026_03_25_223000_create_offline_sync_tables_for_sfa ............... [1] Ran  
  2026_03_26_140000_create_saga_state_table .......................... [1] Ran  
  2026_03_27_090000_upgrade_projection_checkpoints ................... [1] Ran  
  2026_03_27_100000_upgrade_failed_outbox_events ..................... [1] Ran  
  2026_03_27_101000_create_quarantine_tables ......................... [1] Ran  
  2026_03_27_102000_upgrade_snapshots_table .......................... [1] Ran  
  2026_03_27_110000_enforce_enterprise_snapshots_schema .............. [1] Ran  
  2026_03_27_111000_create_sequence_heads_table ...................... [1] Ran  
  2026_03_27_112000_add_versioning_to_events ......................... [1] Ran  
  2026_03_27_113000_add_failure_class_to_dead_letters ................ [1] Ran  
  2026_03_27_115000_add_causal_blocks_to_saga_states ................. [1] Ran  
  2026_03_27_200000_create_orders_table .............................. [1] Ran  
  2026_03_29_000000_add_sfa_performance_indexes ...................... [2] Ran  
  2026_03_29_000001_add_generated_columns_for_json_metadata .......... [3] Ran  
  2026_03_29_000002_standardize_field_naming ......................... [3] Ran  
  2026_03_29_100000_create_api_idempotency_keys_table ................ [3] Ran  
  2026_03_29_100000_create_device_sync_state_table ................... [3] Ran  
  2026_03_29_100001_add_gate_a_unique_constraints .................... [4] Ran  
  2026_03_29_100001_create_sync_conflicts_table ...................... [4] Ran  
  2026_03_29_110000_normalize_tenant_columns ......................... [5] Ran  
  2026_03_29_120000_add_entreprise_id_to_device_sync_state ........... [5] Ran  
  2026_03_29_130000_mass_rename_company_id_to_entreprise_id .......... [5] Ran  
  2026_03_29_210000_create_canonical_tables .......................... [7] Ran  
  2026_03_29_210000_create_canonical_tables_parallel ................. [6] Ran  
  2026_03_29_211000_migrate_legacy_data .............................. Pending  
  2026_03_29_212000_add_canonical_constraints ........................ Pending  
  2026_03_29_300000_mass_master_normalization ........................ Pending  
  2026_03_30_090000_create_replay_guard_table ........................ Pending  
  2026_03_30_100000_create_canonical_projection_events_table ......... Pending  


```

## Table Reality Inventory

### Table: `event_store`
- **Status:** ✅ EXISTS
- **Row Count:** 0

#### Physical Schema:
```sql
CREATE TABLE `event_store` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shard_id` tinyint(3) unsigned NOT NULL,
  `local_sequence` bigint(20) unsigned NOT NULL,
  `global_sequence` bigint(20) unsigned DEFAULT NULL,
  `event_type` varchar(100) NOT NULL,
  `event_version` int(10) unsigned NOT NULL DEFAULT 1,
  `aggregate_type` varchar(100) NOT NULL,
  `aggregate_id` varchar(255) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `entreprise_id` bigint(20) unsigned GENERATED ALWAYS AS (coalesce(json_unquote(json_extract(`metadata`,'$.tenant_id')),1)) VIRTUAL,
  `previous_hash` varchar(64) NOT NULL,
  `merkle_root` varchar(64) NOT NULL,
  `signature` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `correlation_id` varchar(36) DEFAULT NULL,
  `causation_id` char(36) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_store_shard_id_local_sequence_unique` (`shard_id`,`local_sequence`),
  UNIQUE KEY `uniq_aggregate_version` (`aggregate_id`,`event_version`),
  KEY `event_store_aggregate_type_aggregate_id_index` (`aggregate_type`,`aggregate_id`),
  KEY `event_store_global_sequence_index` (`global_sequence`),
  KEY `idx_ev_aggregate_lookup` (`aggregate_type`,`aggregate_id`,`id`),
  KEY `idx_ev_shard_sequence` (`shard_id`,`local_sequence`),
  KEY `idx_ev_type_time` (`event_type`,`created_at`),
  KEY `idx_ev_correlation` (`correlation_id`,`created_at`),
  KEY `idx_ev_tenant` (`entreprise_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
```

### Table: `domain_outbox`
- **Status:** ✅ EXISTS
- **Row Count:** 0

#### Physical Schema:
```sql
CREATE TABLE `domain_outbox` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entreprise_id` bigint(20) unsigned NOT NULL DEFAULT 1,
  `event_id` bigint(20) unsigned NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `attempts` int(10) unsigned NOT NULL DEFAULT 0,
  `max_attempts` int(10) unsigned NOT NULL DEFAULT 5,
  `next_retry_at` timestamp NULL DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_do_status_retry` (`status`,`next_retry_at`),
  KEY `idx_do_event` (`event_id`),
  KEY `domain_outbox_entreprise_id_index` (`entreprise_id`),
  CONSTRAINT `domain_outbox_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `domain_events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
```

### Table: `orders`
- **Status:** ✅ EXISTS
- **Row Count:** 0

#### Physical Schema:
```sql
CREATE TABLE `orders` (
  `id` char(36) NOT NULL,
  `entreprise_id` bigint(20) unsigned NOT NULL DEFAULT 1,
  `reference` varchar(255) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `total_ht` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `total_ttc` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_reference_unique` (`reference`),
  KEY `orders_customer_id_index` (`customer_id`),
  KEY `orders_status_index` (`status`),
  KEY `idx_ord_customer_status` (`customer_id`,`status`,`created_at`),
  KEY `idx_ord_creator_time` (`created_by`,`created_at`),
  KEY `idx_ord_status_time` (`status`,`created_at`),
  KEY `idx_ord_tenant` (`entreprise_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
```

### Table: `commandes`
- **Status:** ❌ TABLE MISSING

### Table: `article`
- **Status:** ✅ EXISTS
- **Row Count:** 0

#### Physical Schema:
```sql
CREATE TABLE `article` (
  `article_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entreprise_id` bigint(20) unsigned NOT NULL,
  `article_designation` text NOT NULL,
  `article_abreviation` varchar(10) DEFAULT NULL,
  `article_lang_id` bigint(20) unsigned DEFAULT NULL,
  `article_designation_lang_text_id` bigint(20) unsigned DEFAULT NULL,
  `article_famille_id` bigint(20) unsigned DEFAULT NULL,
  `article_marque_id` bigint(20) unsigned DEFAULT NULL,
  `article_classe_id` bigint(20) unsigned DEFAULT NULL,
  `article_nature_id` bigint(20) unsigned DEFAULT NULL,
  `article_type_id` bigint(20) unsigned DEFAULT NULL,
  `article_sous_famille_id` bigint(20) unsigned DEFAULT NULL,
  `article_parfume_id` bigint(20) unsigned DEFAULT NULL,
  `article_contenance_id` bigint(20) unsigned DEFAULT NULL,
  `article_modele_id` bigint(20) unsigned DEFAULT NULL,
  `article_matricule` varchar(22) DEFAULT NULL,
  `article_description` varchar(100) DEFAULT NULL,
  `article_product_number` varchar(30) DEFAULT NULL,
  `article_serial_number` varchar(30) DEFAULT NULL,
  `article_created_by` bigint(20) unsigned DEFAULT NULL,
  `article_created_date` datetime DEFAULT NULL,
  `article_updated_by` bigint(20) unsigned DEFAULT NULL,
  `article_updated_date` datetime DEFAULT NULL,
  `article_ean13` varchar(15) DEFAULT NULL,
  `article_bar_code` varchar(30) DEFAULT NULL,
  `article_qr_code` varchar(30) DEFAULT NULL,
  `article_quantite_stock` double DEFAULT NULL,
  `article_quantite_optimale` double DEFAULT NULL,
  `article_quantite_theorique` double DEFAULT NULL,
  `article_quantite_min` double DEFAULT NULL,
  `article_project_id` bigint(20) unsigned DEFAULT NULL,
  `article_project_modele_quantite` double DEFAULT NULL,
  `article_comptable_compte_id_achat` bigint(20) unsigned DEFAULT NULL,
  `article_comptable_compte_id_vente` bigint(20) unsigned DEFAULT NULL,
  `taxe_tva_status_id` tinyint(3) unsigned DEFAULT NULL,
  `article_online_show` tinyint(3) unsigned DEFAULT NULL,
  `article_online_reference` varchar(22) DEFAULT NULL,
  `article_online_description` text DEFAULT NULL,
  `article_online_page_id` bigint(20) unsigned DEFAULT NULL,
  `article_online_famille_id` bigint(20) unsigned DEFAULT NULL,
  `active` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `is_active` tinyint(4) GENERATED ALWAYS AS (`active`) VIRTUAL,
  `is_stock_managed` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `archive` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `taxe_id` bigint(20) unsigned DEFAULT NULL,
  `article_manage_stock` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `article_default_photo` varchar(256) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`article_id`),
  KEY `article_entreprise_id_index` (`entreprise_id`),
  KEY `article_article_famille_id_index` (`article_famille_id`),
  KEY `article_article_marque_id_index` (`article_marque_id`),
  KEY `article_taxe_tva_status_id_index` (`taxe_tva_status_id`),
  KEY `article_article_online_show_index` (`article_online_show`),
  KEY `article_active_index` (`active`),
  KEY `article_article_created_date_index` (`article_created_date`),
  KEY `article_article_updated_date_index` (`article_updated_date`),
  KEY `idx_art_ent_active_fam` (`entreprise_id`,`active`,`article_famille_id`),
  KEY `idx_art_ean` (`article_ean13`,`active`),
  KEY `idx_art_barcode` (`article_bar_code`,`active`),
  KEY `idx_art_marque` (`article_marque_id`,`active`)
) ENGINE=InnoDB AUTO_INCREMENT=5001 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
```

### Table: `balance_stock`
- **Status:** ✅ EXISTS
- **Row Count:** 0

#### Physical Schema:
```sql
CREATE TABLE `balance_stock` (
  `article_id` bigint(20) unsigned NOT NULL,
  `entreprise_id` bigint(20) unsigned DEFAULT NULL,
  `date_day` date NOT NULL,
  `quantite_new` double DEFAULT NULL,
  `quantite_old` double DEFAULT NULL,
  `quantite_entre` double DEFAULT NULL,
  `quantite_retour` double DEFAULT NULL,
  `quantite_sortie` double DEFAULT NULL,
  `sorties` double NOT NULL DEFAULT 0,
  `quantite_physique` double DEFAULT NULL,
  `quantite_theorique` double DEFAULT NULL,
  `ecart_jour` double NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`article_id`,`date_day`),
  KEY `idx_bs_date_article` (`date_day`,`article_id`),
  KEY `idx_bs_company` (`entreprise_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
```

### Table: `device_sync_state`
- **Status:** ✅ EXISTS
- **Row Count:** 0

#### Physical Schema:
```sql
CREATE TABLE `device_sync_state` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entreprise_id` bigint(20) unsigned NOT NULL,
  `device_id` varchar(255) NOT NULL,
  `entity_type` varchar(100) NOT NULL,
  `last_sync_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_sync_sequence` varchar(255) DEFAULT NULL,
  `sync_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_device_sync_lookup` (`entreprise_id`,`device_id`,`entity_type`),
  KEY `device_sync_state_company_id_index` (`entreprise_id`),
  KEY `device_sync_state_device_id_index` (`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
```

### Table: `projector_processed_events`
- **Status:** ✅ EXISTS
- **Row Count:** 0

#### Physical Schema:
```sql
CREATE TABLE `projector_processed_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `projector_id` varchar(150) NOT NULL,
  `event_id` bigint(20) unsigned NOT NULL,
  `processed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `projector_processed_events_projector_id_event_id_unique` (`projector_id`,`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
```

