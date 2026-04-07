-- Event Store Schema for EPSILONE Kernel
-- PostgreSQL 14+ required
-- Aligned with PostgreSqlEventStore implementation

-- Simple single-table event store (matches implementation)
CREATE TABLE IF NOT EXISTS event_store (
    id BIGSERIAL PRIMARY KEY,
    event_id VARCHAR(36) NOT NULL,
    tenant_id VARCHAR(36) NOT NULL,
    stream_id VARCHAR(255) NOT NULL,
    stream_version INTEGER NOT NULL,
    event_type VARCHAR(255) NOT NULL,
    event_class_name VARCHAR(512) NOT NULL,
    payload JSONB NOT NULL,
    metadata JSONB NOT NULL,
    occurred_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_stream_version UNIQUE (stream_id, stream_version)
);

-- Indexes for event store queries (tenant isolation + performance)
CREATE INDEX idx_event_store_tenant ON event_store(tenant_id);
CREATE INDEX idx_event_store_stream ON event_store(stream_id);
CREATE INDEX idx_event_store_event_id ON event_store(event_id);
CREATE INDEX idx_event_store_stream_version ON event_store(tenant_id, stream_id, stream_version);
