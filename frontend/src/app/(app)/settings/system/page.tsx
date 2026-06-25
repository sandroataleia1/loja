'use client'

import { useState } from 'react'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard } from '@/components/shared/app-card'
import { useSystemSettings, useUpdateSection, useFeatureFlags, useUpdateFeatures } from '@/features/system-settings/hooks'
import type {
  CommercialSettings,
  FinancialSettings,
  CreditSettings,
  InventorySettings,
  BillingSettings,
  CommissionSettings,
  LogisticsSettings,
  FeatureFlag,
} from '@/services/system-settings.service'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Skeleton } from '@/components/ui/skeleton'
import { Loader2, Save } from 'lucide-react'
import { toast } from 'sonner'

// ── Abas ─────────────────────────────────────────────────────────────────────

const TABS = [
  { id: 'commercial',  label: 'Comercial'   },
  { id: 'financial',   label: 'Financeiro'  },
  { id: 'credit',      label: 'Crédito'     },
  { id: 'inventory',   label: 'Estoque'     },
  { id: 'billing',     label: 'Faturamento' },
  { id: 'commission',  label: 'Comissão'    },
  { id: 'logistics',   label: 'Logística'   },
  { id: 'features',    label: 'Recursos'    },
] as const

type TabId = (typeof TABS)[number]['id']

// ── Helper components ─────────────────────────────────────────────────────────

function FieldRow({ children }: { children: React.ReactNode }) {
  return <div className="flex flex-col sm:flex-row sm:items-center gap-3 py-3 border-b last:border-0">{children}</div>
}

function FieldLabel({ htmlFor, children, hint }: { htmlFor?: string; children: React.ReactNode; hint?: string }) {
  return (
    <div className="sm:w-64 shrink-0">
      <Label htmlFor={htmlFor} className="text-sm font-medium">{children}</Label>
      {hint && <p className="text-xs text-muted-foreground mt-0.5">{hint}</p>}
    </div>
  )
}

function Toggle({ id, checked, onChange, disabled }: { id: string; checked: boolean; onChange: (v: boolean) => void; disabled?: boolean }) {
  return (
    <button
      id={id}
      type="button"
      role="switch"
      aria-checked={checked}
      disabled={disabled}
      onClick={() => onChange(!checked)}
      className={`relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 ${checked ? 'bg-primary' : 'bg-input'}`}
    >
      <span className={`pointer-events-none inline-block h-5 w-5 rounded-full bg-background shadow-lg ring-0 transition-transform ${checked ? 'translate-x-5' : 'translate-x-0'}`} />
    </button>
  )
}

function SectionSkeleton() {
  return (
    <div className="space-y-4 py-2">
      {Array.from({ length: 5 }).map((_, i) => (
        <div key={i} className="flex items-center gap-4 py-3 border-b">
          <Skeleton className="h-4 w-48" />
          <Skeleton className="h-6 w-11 rounded-full" />
        </div>
      ))}
    </div>
  )
}

// ── Abas de configuração ──────────────────────────────────────────────────────

function CommercialTab({ data, onSave, isPending }: {
  data:      CommercialSettings
  onSave:    (v: Partial<CommercialSettings>) => void
  isPending: boolean
}) {
  const [form, setForm] = useState<CommercialSettings>(data)
  const set = <K extends keyof CommercialSettings>(k: K, v: CommercialSettings[K]) => setForm((f) => ({ ...f, [k]: v }))

  return (
    <div className="space-y-1">
      <FieldRow>
        <FieldLabel htmlFor="require_salesperson" hint="Obriga vincular um vendedor em cada venda">Exigir vendedor</FieldLabel>
        <Toggle id="require_salesperson" checked={form.require_salesperson} onChange={(v) => set('require_salesperson', v)} />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="require_quote_before_order" hint="Pedido só pode ser gerado a partir de um orçamento">Exigir orçamento antes do pedido</FieldLabel>
        <Toggle id="require_quote_before_order" checked={form.require_quote_before_order} onChange={(v) => set('require_quote_before_order', v)} />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="allow_order_without_stock" hint="Permite criar pedidos mesmo sem saldo em estoque">Permitir pedido sem estoque</FieldLabel>
        <Toggle id="allow_order_without_stock" checked={form.allow_order_without_stock} onChange={(v) => set('allow_order_without_stock', v)} />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="allow_free_discount" hint="Vendedor pode aplicar qualquer percentual de desconto">Permitir desconto livre</FieldLabel>
        <Toggle id="allow_free_discount" checked={form.allow_free_discount} onChange={(v) => set('allow_free_discount', v)} />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="default_discount_limit" hint="Limite padrão de desconto sem aprovação (0–100%)">Limite padrão de desconto (%)</FieldLabel>
        <Input
          id="default_discount_limit"
          type="number"
          min={0} max={100} step={0.5}
          className="w-28"
          value={form.default_discount_limit}
          onChange={(e) => set('default_discount_limit', parseFloat(e.target.value) || 0)}
        />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="require_discount_approval" hint="Descontos acima do limite exigem aprovação via PIN">Aprovação de desconto obrigatória</FieldLabel>
        <Toggle id="require_discount_approval" checked={form.require_discount_approval} onChange={(v) => set('require_discount_approval', v)} />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="require_price_approval" hint="Alteração de preço unitário exige aprovação via PIN">Aprovação de alteração de preço</FieldLabel>
        <Toggle id="require_price_approval" checked={form.require_price_approval} onChange={(v) => set('require_price_approval', v)} />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="use_price_table" hint="Preços são definidos por tabelas de preço por cliente/grupo">Utilizar tabela de preços</FieldLabel>
        <Toggle id="use_price_table" checked={form.use_price_table} onChange={(v) => set('use_price_table', v)} />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="quote_validity_days" hint="Quantidade de dias padrão para expiração do orçamento">Validade padrão do orçamento (dias)</FieldLabel>
        <Input
          id="quote_validity_days"
          type="number"
          min={1} max={3650}
          className="w-28"
          value={form.quote_validity_days}
          onChange={(e) => set('quote_validity_days', parseInt(e.target.value) || 30)}
        />
      </FieldRow>
      <SaveButton onClick={() => onSave(form)} isPending={isPending} />
    </div>
  )
}

function FinancialTab({ data, onSave, isPending }: {
  data:      FinancialSettings
  onSave:    (v: Partial<FinancialSettings>) => void
  isPending: boolean
}) {
  const [form, setForm] = useState<FinancialSettings>(data)
  const set = <K extends keyof FinancialSettings>(k: K, v: FinancialSettings[K]) => setForm((f) => ({ ...f, [k]: v }))

  return (
    <div className="space-y-1">
      <FieldRow>
        <FieldLabel htmlFor="default_interest_rate" hint="Juros mensais padrão para parcelas (%)">Juros padrão (%/mês)</FieldLabel>
        <Input id="default_interest_rate" type="number" min={0} max={100} step={0.1} className="w-28"
          value={form.default_interest_rate} onChange={(e) => set('default_interest_rate', parseFloat(e.target.value) || 0)} />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="default_fine_rate" hint="Multa aplicada em atraso (%)">Multa padrão (%)</FieldLabel>
        <Input id="default_fine_rate" type="number" min={0} max={100} step={0.1} className="w-28"
          value={form.default_fine_rate} onChange={(e) => set('default_fine_rate', parseFloat(e.target.value) || 0)} />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="default_discount_rate" hint="Desconto financeiro para pagamento antecipado (%)">Desconto padrão (%)</FieldLabel>
        <Input id="default_discount_rate" type="number" min={0} max={100} step={0.1} className="w-28"
          value={form.default_discount_rate} onChange={(e) => set('default_discount_rate', parseFloat(e.target.value) || 0)} />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="tolerance_days" hint="Dias de carência antes de aplicar multa e juros">Dias de tolerância</FieldLabel>
        <Input id="tolerance_days" type="number" min={0} max={365} className="w-28"
          value={form.tolerance_days} onChange={(e) => set('tolerance_days', parseInt(e.target.value) || 0)} />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="rounding_mode" hint="Regra de arredondamento para valores monetários">Arredondamento</FieldLabel>
        <select
          id="rounding_mode"
          value={form.rounding_mode}
          onChange={(e) => set('rounding_mode', e.target.value as FinancialSettings['rounding_mode'])}
          className="rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring w-44"
        >
          <option value="half_up">Arredondar para cima</option>
          <option value="half_down">Arredondar para baixo</option>
          <option value="ceil">Sempre para cima</option>
          <option value="floor">Sempre para baixo</option>
        </select>
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="auto_generate_bills" hint="Gera automaticamente os títulos financeiros ao fechar uma venda">Geração automática de títulos</FieldLabel>
        <Toggle id="auto_generate_bills" checked={form.auto_generate_bills} onChange={(v) => set('auto_generate_bills', v)} />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="auto_apply_customer_credit" hint="Utiliza automaticamente o crédito disponível do cliente">Utilizar crédito do cliente automaticamente</FieldLabel>
        <Toggle id="auto_apply_customer_credit" checked={form.auto_apply_customer_credit} onChange={(v) => set('auto_apply_customer_credit', v)} />
      </FieldRow>
      <SaveButton onClick={() => onSave(form)} isPending={isPending} />
    </div>
  )
}

function CreditTab({ data, onSave, isPending }: {
  data:      CreditSettings
  onSave:    (v: Partial<CreditSettings>) => void
  isPending: boolean
}) {
  const [form, setForm] = useState<CreditSettings>(data)
  const set = <K extends keyof CreditSettings>(k: K, v: CreditSettings[K]) => setForm((f) => ({ ...f, [k]: v }))

  return (
    <div className="space-y-1">
      <FieldRow>
        <FieldLabel htmlFor="default_credit_limit" hint="Limite de crédito padrão para novos clientes (R$)">Limite de crédito padrão (R$)</FieldLabel>
        <Input id="default_credit_limit" type="number" min={0} step={0.01} className="w-36"
          value={form.default_credit_limit} onChange={(e) => set('default_credit_limit', parseFloat(e.target.value) || 0)} />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="block_sale_without_credit" hint="Impede venda quando o cliente está sem crédito disponível">Bloquear venda sem crédito</FieldLabel>
        <Toggle id="block_sale_without_credit" checked={form.block_sale_without_credit} onChange={(v) => set('block_sale_without_credit', v)} />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="allow_exceed_limit" hint="Permite que o cliente ultrapasse o limite de crédito">Permitir ultrapassar limite</FieldLabel>
        <Toggle id="allow_exceed_limit" checked={form.allow_exceed_limit} onChange={(v) => set('allow_exceed_limit', v)} />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="require_approval_to_exceed" hint="Exige aprovação via PIN para ultrapassar o limite de crédito">Exigir aprovação para ultrapassar limite</FieldLabel>
        <Toggle id="require_approval_to_exceed" checked={form.require_approval_to_exceed} onChange={(v) => set('require_approval_to_exceed', v)} />
      </FieldRow>
      <SaveButton onClick={() => onSave(form)} isPending={isPending} />
    </div>
  )
}

function InventoryTab({ data, onSave, isPending }: {
  data:      InventorySettings
  onSave:    (v: Partial<InventorySettings>) => void
  isPending: boolean
}) {
  const [form, setForm] = useState<InventorySettings>(data)
  const set = <K extends keyof InventorySettings>(k: K, v: InventorySettings[K]) => setForm((f) => ({ ...f, [k]: v }))

  return (
    <div className="space-y-1">
      <FieldRow>
        <FieldLabel htmlFor="allow_negative_stock" hint="Permite que o saldo de estoque fique negativo">Permitir estoque negativo</FieldLabel>
        <Toggle id="allow_negative_stock" checked={form.allow_negative_stock} onChange={(v) => set('allow_negative_stock', v)} />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="auto_reserve" hint="Reserva automaticamente o estoque ao confirmar um pedido">Reserva automática</FieldLabel>
        <Toggle id="auto_reserve" checked={form.auto_reserve} onChange={(v) => set('auto_reserve', v)} />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="auto_deduct" hint="Baixa o estoque automaticamente ao faturar">Baixa automática</FieldLabel>
        <Toggle id="auto_deduct" checked={form.auto_deduct} onChange={(v) => set('auto_deduct', v)} />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="require_picking" hint="Exige registro de separação antes do faturamento">Separação obrigatória</FieldLabel>
        <Toggle id="require_picking" checked={form.require_picking} onChange={(v) => set('require_picking', v)} />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="require_shipping" hint="Exige registro de expedição antes da entrega">Expedição obrigatória</FieldLabel>
        <Toggle id="require_shipping" checked={form.require_shipping} onChange={(v) => set('require_shipping', v)} />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="require_counting" hint="Exige conferência de itens no recebimento">Conferência obrigatória</FieldLabel>
        <Toggle id="require_counting" checked={form.require_counting} onChange={(v) => set('require_counting', v)} />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="auto_update_cost" hint="Atualiza automaticamente o custo médio ao receber mercadorias">Atualização automática de custo</FieldLabel>
        <Toggle id="auto_update_cost" checked={form.auto_update_cost} onChange={(v) => set('auto_update_cost', v)} />
      </FieldRow>
      <SaveButton onClick={() => onSave(form)} isPending={isPending} />
    </div>
  )
}

function BillingTab({ data, onSave, isPending }: {
  data:      BillingSettings
  onSave:    (v: Partial<BillingSettings>) => void
  isPending: boolean
}) {
  const [form, setForm] = useState<BillingSettings>(data)

  return (
    <div className="space-y-1">
      <FieldRow>
        <FieldLabel htmlFor="billing_mode" hint="Define o momento e a origem do faturamento das vendas">Modo de Faturamento</FieldLabel>
        <div className="flex flex-col gap-2">
          {([
            { value: 'by_order',   label: 'Pelo Pedido',       desc: 'Fatura imediatamente ao confirmar o pedido' },
            { value: 'by_invoice', label: 'Pela Nota Fiscal',  desc: 'Fatura somente após emissão de NF-e' },
            { value: 'future',     label: 'Faturamento Futuro', desc: 'Pedido confirmado, faturamento agendado para data futura' },
          ] as const).map((opt) => (
            <label key={opt.value} className="flex items-start gap-3 cursor-pointer rounded-lg border p-3 hover:bg-muted/50 transition-colors">
              <input
                type="radio"
                name="billing_mode"
                value={opt.value}
                checked={form.billing_mode === opt.value}
                onChange={() => setForm({ billing_mode: opt.value })}
                className="mt-0.5"
              />
              <div>
                <p className="text-sm font-medium">{opt.label}</p>
                <p className="text-xs text-muted-foreground">{opt.desc}</p>
              </div>
            </label>
          ))}
        </div>
      </FieldRow>
      <SaveButton onClick={() => onSave(form)} isPending={isPending} />
    </div>
  )
}

function CommissionTab({ data, onSave, isPending }: {
  data:      CommissionSettings
  onSave:    (v: Partial<CommissionSettings>) => void
  isPending: boolean
}) {
  const [form, setForm] = useState<CommissionSettings>(data)
  const set = <K extends keyof CommissionSettings>(k: K, v: CommissionSettings[K]) => setForm((f) => ({ ...f, [k]: v }))

  return (
    <div className="space-y-1">
      <FieldRow>
        <FieldLabel htmlFor="commission_on_sale" hint="Comissão gerada no momento da venda">Comissão por Venda</FieldLabel>
        <Toggle id="commission_on_sale" checked={form.commission_on_sale} onChange={(v) => set('commission_on_sale', v)} />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="commission_on_payment" hint="Comissão gerada somente após recebimento">Comissão por Recebimento</FieldLabel>
        <Toggle id="commission_on_payment" checked={form.commission_on_payment} onChange={(v) => set('commission_on_payment', v)} />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="proportional_commission" hint="Comissão calculada proporcionalmente ao valor recebido">Comissão Proporcional</FieldLabel>
        <Toggle id="proportional_commission" checked={form.proportional_commission} onChange={(v) => set('proportional_commission', v)} />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="commission_by_margin" hint="Comissão calculada sobre a margem de lucro da venda">Comissão por Margem</FieldLabel>
        <Toggle id="commission_by_margin" checked={form.commission_by_margin} onChange={(v) => set('commission_by_margin', v)} />
      </FieldRow>
      <SaveButton onClick={() => onSave(form)} isPending={isPending} />
    </div>
  )
}

function LogisticsTab({ data, onSave, isPending }: {
  data:      LogisticsSettings
  onSave:    (v: Partial<LogisticsSettings>) => void
  isPending: boolean
}) {
  const [form, setForm] = useState<LogisticsSettings>(data)
  const set = <K extends keyof LogisticsSettings>(k: K, v: LogisticsSettings[K]) => setForm((f) => ({ ...f, [k]: v }))

  return (
    <div className="space-y-1">
      <FieldRow>
        <FieldLabel htmlFor="ls_require_picking" hint="Processo de separação de itens deve ser registrado">Separação Obrigatória</FieldLabel>
        <Toggle id="ls_require_picking" checked={form.require_picking} onChange={(v) => set('require_picking', v)} />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="ls_require_shipping" hint="Processo de expedição deve ser registrado antes da entrega">Expedição Obrigatória</FieldLabel>
        <Toggle id="ls_require_shipping" checked={form.require_shipping} onChange={(v) => set('require_shipping', v)} />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="require_packing_list" hint="Romaneio de carga deve ser emitido antes do envio">Romaneio Obrigatório</FieldLabel>
        <Toggle id="require_packing_list" checked={form.require_packing_list} onChange={(v) => set('require_packing_list', v)} />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="require_delivery" hint="Confirmação de entrega deve ser registrada">Entrega Obrigatória</FieldLabel>
        <Toggle id="require_delivery" checked={form.require_delivery} onChange={(v) => set('require_delivery', v)} />
      </FieldRow>
      <FieldRow>
        <FieldLabel htmlFor="require_delivery_receipt" hint="Comprovante de entrega assinado pelo cliente é obrigatório">Comprovante de Entrega Obrigatório</FieldLabel>
        <Toggle id="require_delivery_receipt" checked={form.require_delivery_receipt} onChange={(v) => set('require_delivery_receipt', v)} />
      </FieldRow>
      <SaveButton onClick={() => onSave(form)} isPending={isPending} />
    </div>
  )
}

function FeaturesTab({ features, onToggle, isPending }: {
  features:  FeatureFlag[]
  onToggle:  (feature: string, enabled: boolean) => void
  isPending: boolean
}) {
  return (
    <div className="space-y-1">
      <p className="text-sm text-muted-foreground pb-3">
        Ative ou desative módulos do sistema. Módulos desativados ficam ocultos para todos os usuários.
      </p>
      {features.map((f) => (
        <FieldRow key={f.feature}>
          <FieldLabel hint={f.description}>{f.label}</FieldLabel>
          <Toggle
            id={`feature_${f.feature}`}
            checked={f.is_enabled}
            onChange={(v) => onToggle(f.feature, v)}
            disabled={isPending}
          />
        </FieldRow>
      ))}
    </div>
  )
}

function SaveButton({ onClick, isPending }: { onClick: () => void; isPending: boolean }) {
  return (
    <div className="flex justify-end pt-4">
      <Button type="button" onClick={onClick} disabled={isPending}>
        {isPending
          ? <><Loader2 className="h-4 w-4 animate-spin mr-2" />Salvando...</>
          : <><Save className="h-4 w-4 mr-2" />Salvar alterações</>}
      </Button>
    </div>
  )
}

// ── Page ──────────────────────────────────────────────────────────────────────

export default function SystemSettingsPage() {
  const [activeTab, setActiveTab] = useState<TabId>('commercial')

  const { data: settings, isLoading } = useSystemSettings()
  const { data: featureFlags, isLoading: isLoadingFeatures } = useFeatureFlags()

  const { mutate: updateCommercial,  isPending: savingCommercial  } = useUpdateSection('commercial')
  const { mutate: updateFinancial,   isPending: savingFinancial   } = useUpdateSection('financial')
  const { mutate: updateCredit,      isPending: savingCredit      } = useUpdateSection('credit')
  const { mutate: updateInventory,   isPending: savingInventory   } = useUpdateSection('inventory')
  const { mutate: updateBilling,     isPending: savingBilling     } = useUpdateSection('billing')
  const { mutate: updateCommission,  isPending: savingCommission  } = useUpdateSection('commission')
  const { mutate: updateLogistics,   isPending: savingLogistics   } = useUpdateSection('logistics')
  const { mutate: updateFeatures,    isPending: savingFeatures    } = useUpdateFeatures()

  function handleSave<T>(
    mutate: (data: T, opts?: { onSuccess?: () => void; onError?: (e: unknown) => void }) => void,
  ) {
    return (data: T) => {
      mutate(data, {
        onSuccess: () => toast.success('Configurações salvas com sucesso.'),
        onError:   () => toast.error('Erro ao salvar configurações.'),
      })
    }
  }

  function handleFeatureToggle(feature: string, enabled: boolean) {
    updateFeatures({ [feature]: enabled }, {
      onSuccess: () => toast.success(`${enabled ? 'Módulo ativado' : 'Módulo desativado'} com sucesso.`),
      onError:   () => toast.error('Erro ao atualizar recurso.'),
    })
  }

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Configurações do Sistema"
        description="Gerencie todas as configurações operacionais do ERP."
      />

      <AppCard>
        {/* Tabs navigation */}
        <div className="flex gap-1 border-b mb-6 overflow-x-auto pb-0 -mx-1 px-1">
          {TABS.map((tab) => (
            <button
              key={tab.id}
              type="button"
              onClick={() => setActiveTab(tab.id)}
              className={`px-4 py-2.5 text-sm font-medium whitespace-nowrap border-b-2 transition-colors ${
                activeTab === tab.id
                  ? 'border-primary text-primary'
                  : 'border-transparent text-muted-foreground hover:text-foreground hover:border-muted'
              }`}
            >
              {tab.label}
            </button>
          ))}
        </div>

        {/* Tab content */}
        {(isLoading || isLoadingFeatures) && <SectionSkeleton />}

        {!isLoading && settings && (
          <>
            {activeTab === 'commercial' && (
              <CommercialTab
                data={settings.commercial}
                onSave={handleSave(updateCommercial)}
                isPending={savingCommercial}
              />
            )}
            {activeTab === 'financial' && (
              <FinancialTab
                data={settings.financial}
                onSave={handleSave(updateFinancial)}
                isPending={savingFinancial}
              />
            )}
            {activeTab === 'credit' && (
              <CreditTab
                data={settings.credit}
                onSave={handleSave(updateCredit)}
                isPending={savingCredit}
              />
            )}
            {activeTab === 'inventory' && (
              <InventoryTab
                data={settings.inventory}
                onSave={handleSave(updateInventory)}
                isPending={savingInventory}
              />
            )}
            {activeTab === 'billing' && (
              <BillingTab
                data={settings.billing}
                onSave={handleSave(updateBilling)}
                isPending={savingBilling}
              />
            )}
            {activeTab === 'commission' && (
              <CommissionTab
                data={settings.commission}
                onSave={handleSave(updateCommission)}
                isPending={savingCommission}
              />
            )}
            {activeTab === 'logistics' && (
              <LogisticsTab
                data={settings.logistics}
                onSave={handleSave(updateLogistics)}
                isPending={savingLogistics}
              />
            )}
          </>
        )}

        {activeTab === 'features' && !isLoadingFeatures && featureFlags && (
          <FeaturesTab
            features={featureFlags}
            onToggle={handleFeatureToggle}
            isPending={savingFeatures}
          />
        )}
      </AppCard>
    </div>
  )
}
