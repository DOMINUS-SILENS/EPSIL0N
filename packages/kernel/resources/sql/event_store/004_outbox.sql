-- Create the outbox table for the Transactional Outbox Pattern
-- This table ensures that events are reliably dispatched to projections
CREATE TABLE outbox (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    event_id UUID NOT NULL,
    stream_id VARCHAR(255) NOT NULL,
    stream_version INTEGER NOT NULL,
    event_type VARCHAR(255) NOT NULL,
    payload JSONB NOT NULL,
    metadata JSONB NOT NULL,
    occurred_at TIMESTAMP NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending', -- 'pending', 'processed', 'failed'
    processed_at TIMESTAMP,
    attempts INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE(event_id)
);

CREATE INDEX idx_outbox_status_created ON outbox(status, created_at);
CREATE INDEX idx_outbox_tenant ON outbox(tenant_id);
