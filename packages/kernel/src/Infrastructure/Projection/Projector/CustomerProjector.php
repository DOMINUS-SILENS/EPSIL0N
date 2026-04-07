<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Projection\Projector;

use Spiral\Kernel\Domain\Shared\Event\DomainEvent;
use Spiral\Kernel\Infrastructure\Projection\IEventProjector;
use PDO;

class CustomerProjector implements IEventProjector
{
    public function __construct(
        private readonly PDO $db
    ) {}

    public function handledEventTypes(): array
    {
        return [
            'CustomerRegistered',
            'CustomerEmailVerified',
            'CustomerRenamed',
            'CustomerDeactivated',
            'CustomerReactivated',
        ];
    }

    public function project(DomainEvent $event): void
    {
        $data = $event->toArray();
        $tenantId = $event->getTenantId()->toString();
        $customerId = $data['customer_id'] ?? throw new \RuntimeException("Event missing customer_id");

        switch ($event->getEventType()) {
            case 'CustomerRegistered':
                $this->upsertCustomer($tenantId, $customerId, [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'verified' => false,
                    'active' => true,
                ]);
                break;

            case 'CustomerEmailVerified':
                $this->updateCustomer($tenantId, $customerId, ['verified' => true]);
                break;

            case 'CustomerRenamed':
                $this->updateCustomer($tenantId, $customerId, ['name' => $data['new_name']]);
                break;

            case 'CustomerDeactivated':
                $this->updateCustomer($tenantId, $customerId, [
                    'active' => false,
                    'deactivation_reason' => $data['reason'] ?? 'Not specified'
                ]);
                break;

            case 'CustomerReactivated':
                $this->updateCustomer($tenantId, $customerId, [
                    'active' => true,
                    'deactivation_reason' => null
                ]);
                break;
        }
    }

    private function upsertCustomer(string $tenantId, string $customerId, array $fields): void
    {
        $sql = "INSERT INTO projection_customers (id, tenant_id, name, email, verified, active, updated_at)
                VALUES (:id, :tenant_id, :name, :email, :verified, :active, NOW())
                ON CONFLICT (id) DO UPDATE SET
                name = EXCLUDED.name, email = EXCLUDED.email, verified = EXCLUDED.verified,
                active = EXCLUDED.active, updated_at = NOW()";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge([
            'id' => $customerId,
            'tenant_id' => $tenantId,
        ], $fields));
    }

    private function updateCustomer(string $tenantId, string $customerId, array $fields): void
    {
        $set = [];
        foreach ($fields as $key => $value) {
            $set[] = "$key = :$key";
        }

        $sql = "UPDATE projection_customers SET " . implode(', ', $set) . ", updated_at = NOW()
                WHERE id = :id AND tenant_id = :tenant_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge([
            'id' => $customerId,
            'tenant_id' => $tenantId,
        ], $fields));
    }
}
