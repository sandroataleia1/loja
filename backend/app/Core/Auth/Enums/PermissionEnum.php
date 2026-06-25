<?php

declare(strict_types=1);

namespace App\Core\Auth\Enums;

enum PermissionEnum: string
{
    // ── Dashboard ─────────────────────────────────────────────────────────────
    case DashboardView = 'dashboard.view';

    // ── Customers ─────────────────────────────────────────────────────────────
    case CustomersView   = 'customers.view';
    case CustomersCreate = 'customers.create';
    case CustomersUpdate = 'customers.update';
    case CustomersDelete = 'customers.delete';

    // ── Products ──────────────────────────────────────────────────────────────
    case ProductsView   = 'products.view';
    case ProductsCreate = 'products.create';
    case ProductsUpdate = 'products.update';
    case ProductsDelete = 'products.delete';

    // ── Inventory ─────────────────────────────────────────────────────────────
    case InventoryView     = 'inventory.view';
    case InventoryAdjust   = 'inventory.adjust';
    case InventoryTransfer = 'inventory.transfer';

    // ── Sales ─────────────────────────────────────────────────────────────────
    case SalesView     = 'sales.view';
    case SalesCreate   = 'sales.create';
    case SalesCancel   = 'sales.cancel';
    case SalesDiscount = 'sales.discount';

    // ── Cashier ───────────────────────────────────────────────────────────────
    case CashierOpen   = 'cashier.open';
    case CashierClose  = 'cashier.close';
    case CashierReopen = 'cashier.reopen';

    // ── Financial ─────────────────────────────────────────────────────────────
    case FinancialView   = 'financial.view';
    case FinancialCreate = 'financial.create';
    case FinancialUpdate = 'financial.update';
    case FinancialDelete = 'financial.delete';

    // ── Fiscal ────────────────────────────────────────────────────────────────
    case FiscalView      = 'fiscal.view';
    case FiscalIssue     = 'fiscal.issue';
    case FiscalCancel    = 'fiscal.cancel';
    case FiscalReprocess = 'fiscal.reprocess';

    // ── Users ─────────────────────────────────────────────────────────────────
    case UsersView   = 'users.view';
    case UsersCreate = 'users.create';
    case UsersUpdate = 'users.update';
    case UsersDelete = 'users.delete';

    // ── Settings ──────────────────────────────────────────────────────────────
    case SettingsView    = 'settings.view';
    case SettingsUpdate  = 'settings.update';
    case SettingsHistory = 'settings.history';

    // ── Suppliers ─────────────────────────────────────────────────────────────
    case SuppliersView   = 'suppliers.view';
    case SuppliersCreate = 'suppliers.create';
    case SuppliersUpdate = 'suppliers.update';
    case SuppliersDelete = 'suppliers.delete';

    // ── Purchasing ────────────────────────────────────────────────────────────
    case PurchaseOrdersView      = 'purchase_orders.view';
    case PurchaseOrdersCreate    = 'purchase_orders.create';
    case PurchaseOrdersApprove   = 'purchase_orders.approve';
    case PurchaseReceiptsReceive = 'purchase_receipts.receive';

    // ── Carriers ──────────────────────────────────────────────────────────────
    case CarriersView   = 'carriers.view';
    case CarriersCreate = 'carriers.create';
    case CarriersUpdate = 'carriers.update';
    case CarriersDelete = 'carriers.delete';

    // ── Sellers ───────────────────────────────────────────────────────────────
    case SellersView   = 'sellers.view';
    case SellersCreate = 'sellers.create';
    case SellersUpdate = 'sellers.update';
    case SellersDelete = 'sellers.delete';

    // ── Partners ──────────────────────────────────────────────────────────────
    case PartnersView   = 'partners.view';
    case PartnersCreate = 'partners.create';
    case PartnersUpdate = 'partners.update';
    case PartnersDelete = 'partners.delete';

    // ── Cost Centers ──────────────────────────────────────────────────────────
    case CostCentersView   = 'cost_centers.view';
    case CostCentersCreate = 'cost_centers.create';
    case CostCentersUpdate = 'cost_centers.update';
    case CostCentersDelete = 'cost_centers.delete';

    public function module(): string
    {
        return match ($this) {
            self::DashboardView                                                             => 'dashboard',
            self::CustomersView, self::CustomersCreate, self::CustomersUpdate,
            self::CustomersDelete                                                           => 'customers',
            self::ProductsView, self::ProductsCreate, self::ProductsUpdate,
            self::ProductsDelete                                                            => 'products',
            self::InventoryView, self::InventoryAdjust, self::InventoryTransfer            => 'inventory',
            self::SalesView, self::SalesCreate, self::SalesCancel, self::SalesDiscount     => 'sales',
            self::CashierOpen, self::CashierClose, self::CashierReopen                     => 'cashier',
            self::FinancialView, self::FinancialCreate, self::FinancialUpdate,
            self::FinancialDelete                                                           => 'financial',
            self::FiscalView, self::FiscalIssue, self::FiscalCancel,
            self::FiscalReprocess                                                           => 'fiscal',
            self::UsersView, self::UsersCreate, self::UsersUpdate, self::UsersDelete       => 'users',
            self::SettingsView, self::SettingsUpdate, self::SettingsHistory                  => 'settings',
            self::SuppliersView, self::SuppliersCreate, self::SuppliersUpdate,
            self::SuppliersDelete                                                           => 'suppliers',
            self::PurchaseOrdersView, self::PurchaseOrdersCreate,
            self::PurchaseOrdersApprove, self::PurchaseReceiptsReceive                     => 'purchasing',
            self::CarriersView, self::CarriersCreate, self::CarriersUpdate,
            self::CarriersDelete                                                            => 'carriers',
            self::SellersView, self::SellersCreate, self::SellersUpdate,
            self::SellersDelete                                                             => 'sellers',
            self::PartnersView, self::PartnersCreate, self::PartnersUpdate,
            self::PartnersDelete                                                            => 'partners',
            self::CostCentersView, self::CostCentersCreate, self::CostCentersUpdate,
            self::CostCentersDelete                                                         => 'cost_centers',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::DashboardView          => 'Visualizar painel',
            self::CustomersView          => 'Visualizar clientes',
            self::CustomersCreate        => 'Criar clientes',
            self::CustomersUpdate        => 'Editar clientes',
            self::CustomersDelete        => 'Excluir clientes',
            self::ProductsView           => 'Visualizar produtos',
            self::ProductsCreate         => 'Criar produtos',
            self::ProductsUpdate         => 'Editar produtos',
            self::ProductsDelete         => 'Excluir produtos',
            self::InventoryView          => 'Visualizar estoque',
            self::InventoryAdjust        => 'Ajustar estoque',
            self::InventoryTransfer      => 'Transferir estoque',
            self::SalesView              => 'Visualizar vendas',
            self::SalesCreate            => 'Realizar vendas',
            self::SalesCancel            => 'Cancelar vendas',
            self::SalesDiscount          => 'Aplicar desconto em vendas',
            self::CashierOpen            => 'Abrir caixa',
            self::CashierClose           => 'Fechar caixa',
            self::CashierReopen          => 'Reabrir caixa',
            self::FinancialView          => 'Visualizar financeiro',
            self::FinancialCreate        => 'Criar lançamentos financeiros',
            self::FinancialUpdate        => 'Editar lançamentos financeiros',
            self::FinancialDelete        => 'Excluir lançamentos financeiros',
            self::FiscalView             => 'Visualizar documentos fiscais',
            self::FiscalIssue            => 'Emitir documentos fiscais',
            self::FiscalCancel           => 'Cancelar documentos fiscais',
            self::FiscalReprocess        => 'Reprocessar documentos fiscais rejeitados',
            self::UsersView              => 'Visualizar usuários',
            self::UsersCreate            => 'Criar usuários',
            self::UsersUpdate            => 'Editar usuários',
            self::UsersDelete            => 'Excluir usuários',
            self::SettingsView           => 'Visualizar configurações',
            self::SettingsUpdate         => 'Editar configurações',
            self::SettingsHistory        => 'Visualizar histórico de configurações',
            self::SuppliersView          => 'Visualizar fornecedores',
            self::SuppliersCreate        => 'Criar fornecedores',
            self::SuppliersUpdate        => 'Editar fornecedores',
            self::SuppliersDelete        => 'Excluir fornecedores',
            self::PurchaseOrdersView     => 'Visualizar pedidos de compra',
            self::PurchaseOrdersCreate   => 'Criar pedidos de compra',
            self::PurchaseOrdersApprove  => 'Aprovar pedidos de compra',
            self::PurchaseReceiptsReceive => 'Registrar recebimentos',
            self::CarriersView           => 'Visualizar transportadoras',
            self::CarriersCreate         => 'Criar transportadoras',
            self::CarriersUpdate         => 'Editar transportadoras',
            self::CarriersDelete         => 'Excluir transportadoras',
            self::SellersView            => 'Visualizar vendedores',
            self::SellersCreate          => 'Criar vendedores',
            self::SellersUpdate          => 'Editar vendedores',
            self::SellersDelete          => 'Excluir vendedores',
            self::PartnersView           => 'Visualizar profissionais parceiros',
            self::PartnersCreate         => 'Criar profissionais parceiros',
            self::PartnersUpdate         => 'Editar profissionais parceiros',
            self::PartnersDelete         => 'Excluir profissionais parceiros',
            self::CostCentersView        => 'Visualizar centros de custo',
            self::CostCentersCreate      => 'Criar centros de custo',
            self::CostCentersUpdate      => 'Editar centros de custo',
            self::CostCentersDelete      => 'Excluir centros de custo',
        };
    }
}
