<?php

declare(strict_types=1);

namespace App\Core\Audit\Enums;

enum AuditEntityTypeEnum: string
{
    case Sale             = 'sale';
    case SaleItem         = 'sale_item';
    case SaleReturn       = 'sale_return';
    case Payment          = 'payment';
    case CashRegister     = 'cash_register';
    case CashMovement     = 'cash_movement';
    case InventoryBalance = 'inventory_balance';
    case StockMovement    = 'stock_movement';
    case FinancialEntry   = 'financial_entry';
    case FinancialAccount = 'financial_account';
    case FiscalDocument   = 'fiscal_document';
    case Product          = 'product';
    case ProductVariant   = 'product_variant';
    case Price            = 'price';
    case TenantUser       = 'tenant_user';
    case User             = 'user';
    case Customer         = 'customer';
    case Store            = 'store';
    case Role             = 'role';
    // Omnichannel
    case Channel          = 'channel';
    case ChannelProduct   = 'channel_product';
    case Order            = 'order';
    // Auth / Aprovações
    case ApprovalRequest  = 'approval_request';
    // Cadastros Mestres
    case Supplier         = 'supplier';
    case Carrier          = 'carrier';
    case Seller           = 'seller';
    // Configurações
    case TenantSettings   = 'tenant_settings';
    case FiscalSettings   = 'fiscal_settings';
}
