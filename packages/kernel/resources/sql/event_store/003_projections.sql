-- Projections & Mobile Sync Feed Schema
-- PostgreSQL 14+ required

-- 1. Customer Read Model
-- Maintained by CustomerProjector
CREATE TABLE IF NOT EXISTS projection_customers (
    id UUID PRIMARY KEY,
    tenant_id UUID NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    verified BOOLEAN NOT NULL DEFAULT FALSE,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    deactivation_reason TEXT,
    version INTEGER NOT NULL DEFAULT 0,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_customer_email UNIQUE (tenant_id, email)
);

CREATE INDEX idx_projection_customers_tenant ON projection_customers(tenant_id);

-- 2. Mobile Sync Feed
-- Implements Symmetric Feed Architecture (SFA)
CREATE TABLE IF NOT EXISTS mobile_sync_feed (
    sync_id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    device_id UUID, -- Optional for global feed, populated on distribution
    aggregate_type VARCHAR(100) NOT NULL,
    aggregate_id UUID NOT NULL,
    event_type VARCHAR(255) NOT NULL,
    event_id UUID NOT NULL,
    payload JSONB NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    acknowledged_at TIMESTAMPTZ,
    CONSTRAINT uq_event_sync UNIQUE (event_id)
);

CREATE INDEX idx_mobile_sync_feed_tenant_sync ON mobile_sync_feed(tenant_id, sync_id);
CREATE INDEX idx_mobile_sync_feed_device_sync ON mobile_sync_feed(device_id, sync_id);

-- 3. Device Offsets
-- Tracks the cursor for each mobile device
CREATE TABLE IF NOT EXISTS device_offsets (
    tenant_id UUID NOT NULL,
    device_id UUID PRIMARY KEY,
    last_sync_id BIGINT NOT NULL DEFAULT 0,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_device_tenant UNIQUE (device_id, tenant_id)
);

CREATE INDEX idx_device_offsets_tenant ON device_offsets(tenant_id);
