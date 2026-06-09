<?php

declare(strict_types=1);

namespace App\Core\Auth\DTOs;

use App\Core\Auth\Enums\PermissionEnum;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Snapshot das permissões de um usuário em um tenant num dado instante.
 *
 * Serializable para:
 * - Resposta de login → UserResource → frontend armazena em memória
 * - Sync pull → user_context → PDV armazena em SQLite para uso offline
 *
 * Validação offline no PDV:
 *   $set->hasPermission('give_discount')
 *   $set->canAccessStore($storeId)
 */
final readonly class PermissionSetDTO
{
    public function __construct(
        public readonly string            $userId,
        public readonly string            $tenantId,
        public readonly array             $permissions,     // string[] de slugs
        public readonly ?array            $allowedStoreIds, // null = irrestrito, [] = sem acesso
        public readonly DateTimeImmutable $issuedAt,
    ) {}

    public function hasPermission(string|PermissionEnum $permission): bool
    {
        $slug = $permission instanceof PermissionEnum ? $permission->value : $permission;

        return in_array($slug, $this->permissions, true);
    }

    public function canAccessStore(string $storeId): bool
    {
        return $this->allowedStoreIds === null
            || in_array($storeId, $this->allowedStoreIds, true);
    }

    public function toArray(): array
    {
        return [
            'user_id'           => $this->userId,
            'tenant_id'         => $this->tenantId,
            'permissions'       => $this->permissions,
            'allowed_store_ids' => $this->allowedStoreIds,
            'issued_at'         => $this->issuedAt->format(DateTimeInterface::ATOM),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            userId:          $data['user_id'],
            tenantId:        $data['tenant_id'],
            permissions:     $data['permissions'],
            allowedStoreIds: $data['allowed_store_ids'],
            issuedAt:        new DateTimeImmutable($data['issued_at']),
        );
    }
}
