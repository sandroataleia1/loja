<?php

declare(strict_types=1);

namespace App\Core\Audit\Enums;

/**
 * Políticas de retenção de dados por categoria.
 *
 * Baseia-se em: LGPD, CFO (5 anos para financeiro), RFB (5-10 anos para fiscal).
 * Fundação para futuro serviço de cleanup automático.
 */
enum DataRetentionPolicyEnum: string
{
    case AuditLogs      = 'audit_logs';       // Auditoria operacional
    case DomainEvents   = 'domain_events';    // Eventos de domínio (analytics/IA)
    case TransactionsFinancial = 'transactions_financial'; // Legislação fiscal/contábil
    case FiscalDocuments = 'fiscal_documents'; // Obrigação fiscal RFB
    case PersonalData   = 'personal_data';    // Dados pessoais de clientes
    case AuthLogs       = 'auth_logs';        // Logs de autenticação
    case SyncLogs       = 'sync_logs';        // Logs de sync PDV

    /** Número de dias de retenção. */
    public function retentionDays(): int
    {
        return match ($this) {
            self::AuditLogs             => 365,       // 1 ano
            self::DomainEvents          => 730,       // 2 anos (analytics)
            self::TransactionsFinancial => 1825,      // 5 anos (CFO)
            self::FiscalDocuments       => 3650,      // 10 anos (RFB NF-e)
            self::PersonalData          => 1825,      // 5 anos (LGPD proporcional)
            self::AuthLogs              => 180,       // 6 meses
            self::SyncLogs              => 90,        // 3 meses
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::AuditLogs             => 'Logs de auditoria',
            self::DomainEvents          => 'Eventos de domínio',
            self::TransactionsFinancial => 'Transações financeiras',
            self::FiscalDocuments       => 'Documentos fiscais',
            self::PersonalData          => 'Dados pessoais',
            self::AuthLogs              => 'Logs de autenticação',
            self::SyncLogs              => 'Logs de sincronização',
        };
    }
}
