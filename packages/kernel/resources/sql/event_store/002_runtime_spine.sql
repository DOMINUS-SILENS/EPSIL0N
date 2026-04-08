-- Event Store Runtime Spine Schema
-- PostgreSQL 14+ required
-- This migration replaces the simple single-table event store with a structured
-- stream-based store to guarantee atomic appends and structural versioning.

-- 1. Event Streams: Tracks the current version and ownership of each stream.
CREATE TABLE IF NOT EXISTS event_streams (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    stream_id VARCHAR(255) NOT NULL,
    version INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_tenant_stream UNIQUE (tenant_id, stream_id)
);

-- 2. Domain Events: The immutable log of all state changes.
CREATE TABLE IF NOT EXISTS domain_events (
    id BIGSERIAL PRIMARY KEY,
    global_position BIGSERIAL NOT NULL UNIQUE,
    tenant_id UUID NOT NULL,
    stream_id VARCHAR(255) NOT NULL,
    stream_version INTEGER NOT NULL,
    event_id UUID NOT NULL,
    event_type VARCHAR(255) NOT NULL,
    correlation_id UUID,
    causation_id UUID,
    occurred_at TIMESTAMPTZ NOT NULL,
    schema_version VARCHAR(50) NOT NULL,
    payload JSONB NOT NULL,
    metadata JSONB NOT NULL,
    CONSTRAINT uq_tenant_stream_version UNIQUE (tenant_id, stream_id, stream_version),
    CONSTRAINT uq_event_id UNIQUE (event_id)
);

-- Indexes for performance and isolation
CREATE INDEX idx_domain_events_tenant_stream ON domain_events(tenant_id, stream_id);
CREATE INDEX idx_domain_events_correlation ON domain_events(correlation_id);
CREATE INDEX idx_domain_events_occurred_at ON domain_events(occurred_at);
CREATE INDEX idx_domain_events_global_position ON domain_events(global_position);

-- Cleanup legacy table if it exists (optional, but recommended for a clean transition)
-- DROP TABLE IF EXISTS event_store;
