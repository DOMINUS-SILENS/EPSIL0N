<?php

namespace App\Services;

use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class CanonicalIdentityService
{
    /**
     * Epsilon Canonical Namespace (UUIDv4 converted to bytes for namespace)
     * e0f8e0b0-a5e1-4c3a-8b3a-5f3a5f3a5f3a
     */
    private const NAMESPACE = 'e0f8e0b0-a5e1-4c3a-8b3a-5f3a5f3a5f3a';

    /**
     * Generate a deterministic UUIDv5 for a canonical entity.
     */
    public function generateId(string $entityType, int $entrepriseId, int|string $legacyId, array $context = []): string
    {
        $name = match ($entityType) {
            'order' => "enterprise:{$entrepriseId}:order:{$legacyId}",
            'order_line' => "enterprise:{$entrepriseId}:order:{$context['legacy_order_id']}:line:{$legacyId}",
            'article' => "enterprise:{$entrepriseId}:article:{$legacyId}",
            'customer' => "enterprise:{$entrepriseId}:customer:{$legacyId}",
            'depot' => "enterprise:{$entrepriseId}:depot:{$legacyId}",
            default => "enterprise:{$entrepriseId}:{$entityType}:{$legacyId}",
        };

        return Uuid::uuid5(self::NAMESPACE, $name)->toString();
    }
}
