<?php

declare(strict_types=1);

namespace App\Core\Audit\Enums;

/**
 * Ações de negócio rastreadas no audit_logs.
 *
 * Convenção de nomenclatura:
 * - Verbos no passado simples (completed, cancelled, adjusted)
 * - Separador underscore
 * - Módulo implícito no nome quando necessário
 */
enum AuditActionEnum: string
{
    // ── Vendas ────────────────────────────────────────────────────────────────
    case CompleteSale    = 'sale.completed';
    case CancelSale      = 'sale.cancelled';
    case ApplyDiscount   = 'sale.discount_applied';
    case SaleReturn      = 'sale.returned';

    // ── Caixa ─────────────────────────────────────────────────────────────────
    case OpenCashRegister  = 'cash_register.opened';
    case CloseCashRegister = 'cash_register.closed';

    // ── Estoque ───────────────────────────────────────────────────────────────
    case AdjustStock   = 'inventory.stock_adjusted';
    case TransferStock = 'inventory.stock_transferred';
    case StockCounted  = 'inventory.stock_counted';

    // ── Preços ────────────────────────────────────────────────────────────────
    case PriceChanged = 'catalog.price_changed';

    // ── Financeiro ────────────────────────────────────────────────────────────
    case PayExpense            = 'financial.expense_paid';
    case ReverseFinancialEntry = 'financial.entry_reversed';

    // ── Fiscal ────────────────────────────────────────────────────────────────
    case EmitNfce                  = 'fiscal.nfce_emitted';
    case CancelNfce                = 'fiscal.nfce_cancelled';
    case FiscalError               = 'fiscal.error';
    case FiscalRetried             = 'fiscal.retried';
    case FiscalEnvironmentChanged  = 'fiscal.environment_changed';

    // ── Cadastros Mestres ─────────────────────────────────────────────────────
    case CustomerCreated             = 'customer.created';
    case CustomerUpdated             = 'customer.updated';
    case CustomerDeleted             = 'customer.deleted';
    case CustomerBlocked             = 'customer.blocked';
    case CustomerCreditAnalyzed      = 'customer.credit_analyzed';
    case CustomerSensitiveDataViewed = 'customer.sensitive_data_viewed';
    case CustomerDataExported        = 'customer.data_exported';
    case CustomerDeletionRequested   = 'customer.deletion_requested';
    case SupplierCreated   = 'supplier.created';
    case SupplierUpdated   = 'supplier.updated';
    case SupplierDeleted   = 'supplier.deleted';
    case SupplierSuspended = 'supplier.suspended';
    case CarrierCreated    = 'carrier.created';
    case CarrierUpdated    = 'carrier.updated';
    case CarrierDeleted    = 'carrier.deleted';
    case SellerCreated     = 'seller.created';
    case SellerUpdated     = 'seller.updated';
    case SellerDeleted     = 'seller.deleted';

    // ── Configurações ─────────────────────────────────────────────────────────
    case SettingsUpdated = 'settings.updated';

    // ── RBAC / Permissões ─────────────────────────────────────────────────────
    case RoleAssigned       = 'rbac.role_assigned';
    case RoleRevoked        = 'rbac.role_revoked';
    case RoleCreated        = 'rbac.role_created';
    case RoleUpdated        = 'rbac.role_updated';
    case RoleDeleted        = 'rbac.role_deleted';
    case StoreAccessGranted = 'rbac.store_access_granted';
    case StoreAccessRevoked = 'rbac.store_access_revoked';
    case PermissionsSynced  = 'rbac.permissions_synced';

    // ── Aprovações ────────────────────────────────────────────────────────────
    case ApprovalApproved = 'approval.approved';
    case ApprovalRejected = 'approval.rejected';
    case ApprovalPinFailed = 'approval.pin_failed';

    // ── Autenticação / Segurança ──────────────────────────────────────────────
    case Login              = 'auth.login';
    case Logout             = 'auth.logout';
    case FailedLogin        = 'auth.failed_login';
    case TokenRevoked       = 'auth.token_revoked';
    case AuthPinLogin       = 'auth.pin_login';
    case AuthPinFailed      = 'auth.pin_failed';
    case AuthPinChanged     = 'auth.pin_changed';
    case AuthPasswordReset  = 'auth.password_reset';
    case AuthAccountLocked  = 'auth.account_locked';

    public function label(): string
    {
        return match ($this) {
            self::CompleteSale          => 'Venda concluída',
            self::CancelSale            => 'Venda cancelada',
            self::ApplyDiscount         => 'Desconto aplicado',
            self::SaleReturn            => 'Devolução realizada',
            self::OpenCashRegister      => 'Caixa aberto',
            self::CloseCashRegister     => 'Caixa fechado',
            self::AdjustStock           => 'Estoque ajustado',
            self::TransferStock         => 'Estoque transferido',
            self::StockCounted          => 'Contagem de estoque',
            self::PriceChanged          => 'Preço alterado',
            self::PayExpense            => 'Despesa paga',
            self::ReverseFinancialEntry => 'Lançamento estornado',
            self::EmitNfce                 => 'NFC-e emitida',
            self::CancelNfce               => 'NFC-e cancelada',
            self::FiscalError              => 'Erro fiscal',
            self::FiscalRetried            => 'Fiscal reprocessado',
            self::FiscalEnvironmentChanged => 'Ambiente SEFAZ alterado',
            self::CustomerCreated             => 'Cliente criado',
            self::CustomerUpdated             => 'Cliente atualizado',
            self::CustomerDeleted             => 'Cliente excluído',
            self::CustomerBlocked             => 'Cliente bloqueado',
            self::CustomerSensitiveDataViewed => 'Dado sensível visualizado',
            self::CustomerDataExported        => 'Dados exportados (LGPD)',
            self::CustomerDeletionRequested   => 'Exclusão de dados solicitada',
            self::SupplierCreated   => 'Fornecedor criado',
            self::SupplierUpdated   => 'Fornecedor atualizado',
            self::SupplierDeleted   => 'Fornecedor excluído',
            self::SupplierSuspended => 'Fornecedor suspenso',
            self::CarrierCreated    => 'Transportadora criada',
            self::CarrierUpdated    => 'Transportadora atualizada',
            self::CarrierDeleted    => 'Transportadora excluída',
            self::SellerCreated     => 'Vendedor criado',
            self::SellerUpdated     => 'Vendedor atualizado',
            self::SellerDeleted     => 'Vendedor excluído',
            self::SettingsUpdated          => 'Configurações atualizadas',
            self::RoleAssigned          => 'Role atribuída',
            self::RoleRevoked           => 'Role revogada',
            self::RoleCreated           => 'Role criada',
            self::RoleUpdated           => 'Role atualizada',
            self::RoleDeleted           => 'Role excluída',
            self::StoreAccessGranted    => 'Acesso à loja concedido',
            self::StoreAccessRevoked    => 'Acesso à loja revogado',
            self::PermissionsSynced     => 'Permissões sincronizadas',
            self::ApprovalApproved      => 'Aprovação concedida',
            self::ApprovalRejected      => 'Aprovação rejeitada',
            self::ApprovalPinFailed     => 'Tentativa de PIN de aprovação inválida',
            self::Login                 => 'Login realizado',
            self::Logout                => 'Logout realizado',
            self::FailedLogin           => 'Tentativa de login inválida',
            self::TokenRevoked          => 'Token revogado',
            self::AuthPinLogin          => 'Login por PIN realizado',
            self::AuthPinFailed         => 'Tentativa de PIN de login inválida',
            self::AuthPinChanged        => 'PIN alterado',
            self::AuthPasswordReset     => 'Senha redefinida',
            self::AuthAccountLocked     => 'Conta bloqueada por excesso de tentativas',
        };
    }

    /** Ações de alto risco que precisam de revisão extra em compliance. */
    public function isHighRisk(): bool
    {
        return in_array($this, [
            self::CancelSale,
            self::ReverseFinancialEntry,
            self::CancelNfce,
            self::FiscalEnvironmentChanged,
            self::RoleAssigned,
            self::RoleRevoked,
            self::RoleDeleted,
            self::FailedLogin,
            self::ApprovalApproved,
            self::ApprovalRejected,
            self::AuthAccountLocked,
        ], true);
    }
}
