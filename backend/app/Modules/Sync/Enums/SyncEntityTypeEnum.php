<?php

declare(strict_types=1);

namespace App\Modules\Sync\Enums;

/**
 * Tipos de entidade suportados pelo protocolo de sincronização.
 *
 * Ownership determina estratégia de conflito:
 * - PDV-owned: backend aceita dados do PDV como autoritativos
 * - Backend-owned: operações do PDV são CONFLICT
 *
 * @see ProcessSyncOperationAction::OWNERSHIP_MAP
 */
enum SyncEntityTypeEnum: string
{
    // ── PDV-owned (backend aceita) ─────────────────────────────────────────
    case Sale               = 'sale';
    case PaymentTransaction = 'payment_transaction';
    case CashMovement       = 'cash_movement';
    case Customer           = 'customer';

    // ── Backend-owned (PDV não pode modificar → CONFLICT) ──────────────────
    case Product        = 'product';
    case ProductVariant = 'product_variant';
    case Price          = 'price';
    case Inventory      = 'inventory';

    public function label(): string
    {
        return match($this) {
            self::Sale               => 'Venda',
            self::PaymentTransaction => 'Pagamento',
            self::CashMovement       => 'Movimentação de caixa',
            self::Customer           => 'Cliente',
            self::Product            => 'Produto',
            self::ProductVariant     => 'Variante',
            self::Price              => 'Preço',
            self::Inventory          => 'Estoque',
        };
    }

    /** PDV é o proprietário desta entidade — backend aceita operações. */
    public function isPdvOwned(): bool
    {
        return match($this) {
            self::Sale, self::PaymentTransaction,
            self::CashMovement, self::Customer => true,
            default                            => false,
        };
    }
}
