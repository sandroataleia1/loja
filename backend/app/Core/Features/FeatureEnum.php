<?php

declare(strict_types=1);

namespace App\Core\Features;

/**
 * Feature flags granulares por tenant.
 *
 * Convenção de nomenclatura: domínio.funcionalidade (ponto como separador).
 * Verificação: FeatureManager::isEnabled($tenantId, FeatureEnum::CustomerGuarantor)
 * Middleware: ->middleware('feature:customer.guarantor')
 */
enum FeatureEnum: string
{
    // ── Cadastros ─────────────────────────────────────────────────────────────
    case CustomerGuarantor      = 'customer.guarantor';          // Avalista/fiador
    case CustomerSpouse         = 'customer.spouse';             // Dados do cônjuge
    case CustomerCreditAnalysis = 'customer.credit_analysis';    // Análise de crédito
    case CustomerSpcConsult     = 'customer.spc_consult';        // Consulta SPC/Serasa
    case CustomerRegistrationCard = 'customer.registration_card'; // Ficha impressa
    case CustomerMultipleWorks  = 'customer.multiple_works';     // Múltiplas obras por cliente

    // ── Vendas ────────────────────────────────────────────────────────────────
    case SalesInstallment       = 'sales.installment';           // Crediário próprio
    case SalesCheck             = 'sales.check';                 // Cheque pré-datado
    case SalesPartialOrder      = 'sales.partial_order';         // Pedido fracionado (obra)
    case SalesBackorder         = 'sales.backorder';             // Venda sem estoque (encomenda)
    case SalesKit               = 'sales.kit';                   // Venda de kits/compostos
    case SalesWhatsapp          = 'sales.whatsapp';              // Venda por WhatsApp
    case SalesCommissionPartner = 'sales.commission_partner';    // Comissão para parceiros externos

    // ── Estoque ───────────────────────────────────────────────────────────────
    case InventoryLotControl    = 'inventory.lot_control';       // Controle por lote
    case InventoryExpiryControl = 'inventory.expiry_control';    // Controle de validade
    case InventoryMultiWarehouse = 'inventory.multi_warehouse';  // Múltiplos depósitos
    case InventoryAddress       = 'inventory.address';           // Endereçamento de prateleira

    // ── Financeiro ────────────────────────────────────────────────────────────
    case FinancialBoleto        = 'financial.boleto';            // Emissão de boleto
    case FinancialPixAuto       = 'financial.pix_auto';          // PIX automático/API
    case FinancialCheckControl  = 'financial.check_control';     // Controle de cheques
    case FinancialBankReconcile = 'financial.bank_reconcile';    // Conciliação bancária
    case FinancialCollectionRule = 'financial.collection_rule';  // Régua de cobrança

    // ── Fiscal ────────────────────────────────────────────────────────────────
    case FiscalNfe              = 'fiscal.nfe';                  // Emissão NF-e
    case FiscalNfce             = 'fiscal.nfce';                 // Emissão NFC-e
    case FiscalNfse             = 'fiscal.nfse';                 // NF de serviço
    case FiscalCte              = 'fiscal.cte';                  // Conhecimento de transporte
    case FiscalSped             = 'fiscal.sped';                 // SPED e obrigações
    case FiscalSt               = 'fiscal.st';                   // Substituição tributária
    case FiscalDifal            = 'fiscal.difal';                // DIFAL interestadual

    // ── Operacional ───────────────────────────────────────────────────────────
    case OpsWorksManagement     = 'ops.works_management';        // Gestão de obras
    case OpsDeliveryRoutes      = 'ops.delivery_routes';         // Rotas de entrega
    case OpsDeliveryProof       = 'ops.delivery_proof';          // Comprovante de entrega
    case OpsWhatsappBot         = 'ops.whatsapp_bot';            // Atendimento IA WhatsApp
    case OpsMultiBranch         = 'ops.multi_branch';            // Multi-filial
    case OpsRepresentative      = 'ops.representative';          // Portal representante
    case OpsEcommerce           = 'ops.ecommerce';               // E-commerce integrado

    public function label(): string
    {
        return match ($this) {
            // Cadastros
            self::CustomerGuarantor       => 'Avalista/fiador',
            self::CustomerSpouse          => 'Dados do cônjuge',
            self::CustomerCreditAnalysis  => 'Análise de crédito',
            self::CustomerSpcConsult      => 'Consulta SPC/Serasa',
            self::CustomerRegistrationCard => 'Ficha de cadastro impressa',
            self::CustomerMultipleWorks   => 'Múltiplas obras por cliente',
            // Vendas
            self::SalesInstallment        => 'Crediário próprio',
            self::SalesCheck              => 'Cheque pré-datado',
            self::SalesPartialOrder       => 'Pedido fracionado (obra)',
            self::SalesBackorder          => 'Venda sem estoque (encomenda)',
            self::SalesKit                => 'Venda de kits/compostos',
            self::SalesWhatsapp           => 'Venda por WhatsApp',
            self::SalesCommissionPartner  => 'Comissão para parceiros externos',
            // Estoque
            self::InventoryLotControl     => 'Controle por lote',
            self::InventoryExpiryControl  => 'Controle de validade',
            self::InventoryMultiWarehouse => 'Múltiplos depósitos',
            self::InventoryAddress        => 'Endereçamento de prateleira',
            // Financeiro
            self::FinancialBoleto         => 'Emissão de boleto',
            self::FinancialPixAuto        => 'PIX automático/API',
            self::FinancialCheckControl   => 'Controle de cheques',
            self::FinancialBankReconcile  => 'Conciliação bancária',
            self::FinancialCollectionRule => 'Régua de cobrança automática',
            // Fiscal
            self::FiscalNfe              => 'Emissão NF-e',
            self::FiscalNfce             => 'Emissão NFC-e',
            self::FiscalNfse             => 'NF de serviço',
            self::FiscalCte              => 'Conhecimento de transporte',
            self::FiscalSped             => 'SPED e obrigações acessórias',
            self::FiscalSt               => 'Substituição tributária',
            self::FiscalDifal            => 'DIFAL interestadual',
            // Operacional
            self::OpsWorksManagement     => 'Gestão de obras',
            self::OpsDeliveryRoutes      => 'Rotas de entrega',
            self::OpsDeliveryProof       => 'Comprovante de entrega',
            self::OpsWhatsappBot         => 'Atendimento IA WhatsApp',
            self::OpsMultiBranch         => 'Multi-filial',
            self::OpsRepresentative      => 'Portal representante',
            self::OpsEcommerce           => 'E-commerce integrado',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::CustomerGuarantor       => 'Permite vincular avalistas/fiadores a clientes',
            self::CustomerSpouse          => 'Habilita campos de dados do cônjuge no cadastro',
            self::CustomerCreditAnalysis  => 'Análise de crédito automatizada para clientes',
            self::CustomerSpcConsult      => 'Consulta SPC/Serasa integrada ao cadastro',
            self::CustomerRegistrationCard => 'Gera ficha de cadastro para impressão',
            self::CustomerMultipleWorks   => 'Associa múltiplas obras ao mesmo cliente',
            self::SalesInstallment        => 'Parcelamento no crediário próprio da loja',
            self::SalesCheck              => 'Recebimento com cheque pré-datado',
            self::SalesPartialOrder       => 'Pedidos fracionados por etapas de obra',
            self::SalesBackorder          => 'Confirmar venda mesmo sem estoque disponível',
            self::SalesKit                => 'Venda de produtos compostos/kits',
            self::SalesWhatsapp           => 'Iniciar e fechar vendas pelo WhatsApp',
            self::SalesCommissionPartner  => 'Comissão para parceiros/representantes externos',
            self::InventoryLotControl     => 'Rastreio de produtos por número de lote',
            self::InventoryExpiryControl  => 'Alerta e bloqueio de produtos vencidos',
            self::InventoryMultiWarehouse => 'Controle de estoque em múltiplos depósitos',
            self::InventoryAddress        => 'Localização de produtos por endereço de prateleira',
            self::FinancialBoleto         => 'Emissão de boleto bancário integrado',
            self::FinancialPixAuto        => 'Geração de cobranças PIX via API bancária',
            self::FinancialCheckControl   => 'Gestão de cheques recebidos e compensação',
            self::FinancialBankReconcile  => 'Conciliação automática com extrato bancário',
            self::FinancialCollectionRule => 'Régua automática de cobrança por dias de atraso',
            self::FiscalNfe              => 'Emissão de NF-e para vendas B2B',
            self::FiscalNfce             => 'Emissão de NFC-e para vendas no balcão',
            self::FiscalNfse             => 'Nota Fiscal de Serviço eletrônica',
            self::FiscalCte              => 'Conhecimento de Transporte Eletrônico',
            self::FiscalSped             => 'Geração dos arquivos SPED Fiscal e Contribuições',
            self::FiscalSt               => 'Cálculo e destaque de Substituição Tributária',
            self::FiscalDifal            => 'Cálculo do DIFAL para vendas interestaduais',
            self::OpsWorksManagement     => 'Gestão completa de obras e projetos',
            self::OpsDeliveryRoutes      => 'Otimização e roteirização de entregas',
            self::OpsDeliveryProof       => 'Comprovante de entrega com foto/assinatura',
            self::OpsWhatsappBot         => 'Atendimento automatizado via WhatsApp com IA',
            self::OpsMultiBranch         => 'Operação com múltiplas filiais',
            self::OpsRepresentative      => 'Portal de pedidos para representantes',
            self::OpsEcommerce           => 'Loja virtual integrada ao ERP',
        };
    }
}
