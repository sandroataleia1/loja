'use client'

import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Plus, Pencil, Trash2, Check, X, Lock, Calculator } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard } from '@/components/shared/app-card'
import {
  paymentService,
  type PaymentCondition,
  type CreatePaymentConditionRequest,
  type InstallmentPreview,
} from '@/services/payment.service'

const QK = ['payment-conditions'] as const

const CONDITION_TYPES = [
  { value: 'a_vista',         label: 'À vista' },
  { value: 'parcelado',       label: 'Parcelado' },
  { value: 'entrada_parcelas',label: 'Entrada + Parcelas' },
  { value: 'variavel',        label: 'Parcelamento variável' },
]

const DISCOUNT_TYPES  = [
  { value: 'none',    label: 'Sem desconto' },
  { value: 'percent', label: 'Percentual (%)' },
  { value: 'fixed',   label: 'Fixo (R$)' },
]

const INTEREST_TYPES = [
  { value: 'none',                 label: 'Sem juros' },
  { value: 'percent_month',        label: '% ao mês' },
  { value: 'percent_total',        label: '% sobre total' },
  { value: 'fixed_per_installment',label: 'Fixo por parcela (R$)' },
  { value: 'fixed_total',          label: 'Fixo total (R$)' },
]

type FormState = Omit<CreatePaymentConditionRequest, 'name'> & { name: string }

const defaultForm = (): FormState => ({
  name:               '',
  type:               'a_vista',
  discount_type:      'none',
  discount_value:     0,
  interest_type:      'none',
  interest_value:     0,
  fine_percent:       0,
  fine_after_days:    0,
  grace_days:         0,
  installment_count:  1,
  first_due_days:     0,
  interval_days:      30,
  has_entry:          false,
  entry_percent:      0,
  is_variable:        false,
  is_active:          true,
})

function formatBRL(cents: number) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)
}

function conditionSummary(c: PaymentCondition): string {
  if (c.is_variable) return 'Parcelamento variável'
  if (c.type === 'a_vista') {
    const suffix = c.first_due_days > 0 ? ` em ${c.first_due_days} dias` : ' imediato'
    return `À vista${suffix}`
  }
  const prefix = c.has_entry ? 'Entrada + ' : ''
  const rest = c.installment_count - (c.has_entry ? 1 : 0)
  return `${prefix}${rest}x de ${c.interval_days} dias`
}

export default function PaymentConditionsPage() {
  const qc = useQueryClient()

  const { data: items = [], isLoading } = useQuery({
    queryKey: QK,
    queryFn:  () => paymentService.listConditions(),
  })

  const createMutation = useMutation({
    mutationFn: (data: CreatePaymentConditionRequest) => paymentService.createCondition(data),
    onSuccess:  () => { qc.invalidateQueries({ queryKey: QK }); toast.success('Condição adicionada.') },
    onError:    () => toast.error('Erro ao adicionar condição.'),
  })

  const updateMutation = useMutation({
    mutationFn: ({ uuid, data }: { uuid: string; data: CreatePaymentConditionRequest }) =>
      paymentService.updateCondition(uuid, data),
    onSuccess: () => { qc.invalidateQueries({ queryKey: QK }); toast.success('Condição atualizada.') },
    onError:   () => toast.error('Erro ao atualizar condição.'),
  })

  const deleteMutation = useMutation({
    mutationFn: (uuid: string) => paymentService.deleteCondition(uuid),
    onSuccess:  () => { qc.invalidateQueries({ queryKey: QK }); toast.success('Condição removida.') },
    onError:    () => toast.error('Erro ao remover condição.'),
  })

  const [form,            setForm]            = useState<FormState>(defaultForm())
  const [editingUuid,     setEditingUuid]     = useState<string | null>(null)
  const [showForm,        setShowForm]        = useState(false)
  const [previewAmount,   setPreviewAmount]   = useState(100000)
  const [previewResult,   setPreviewResult]   = useState<{ installments: InstallmentPreview[]; discount_cents: number; interest_cents: number; total_amount_cents: number } | null>(null)
  const [previewing,      setPreviewing]      = useState(false)

  function startEdit(item: PaymentCondition) {
    setEditingUuid(item.uuid)
    setForm({
      name:              item.name,
      type:              item.type,
      discount_type:     item.discount_type,
      discount_value:    parseFloat(String(item.discount_value)) || 0,
      interest_type:     item.interest_type,
      interest_value:    parseFloat(String(item.interest_value)) || 0,
      fine_percent:      parseFloat(String(item.fine_percent)) || 0,
      fine_after_days:   item.fine_after_days,
      grace_days:        item.grace_days,
      installment_count: item.installment_count,
      first_due_days:    item.first_due_days,
      interval_days:     item.interval_days,
      has_entry:         item.has_entry,
      entry_percent:     parseFloat(String(item.entry_percent)) || 0,
      is_variable:       item.is_variable,
      is_active:         item.is_active,
    })
    setShowForm(true)
    setPreviewResult(null)
  }

  function cancelEdit() {
    setEditingUuid(null)
    setForm(defaultForm())
    setShowForm(false)
    setPreviewResult(null)
  }

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    if (!form.name.trim()) return

    if (editingUuid) {
      updateMutation.mutate({ uuid: editingUuid, data: form }, { onSuccess: () => cancelEdit() })
    } else {
      createMutation.mutate(form, { onSuccess: () => { setForm(defaultForm()); setShowForm(false); setPreviewResult(null) } })
    }
  }

  async function handlePreview() {
    if (!editingUuid) return
    setPreviewing(true)
    try {
      const result = await paymentService.calculateInstallments(editingUuid, previewAmount)
      setPreviewResult(result)
    } catch {
      toast.error('Erro ao calcular parcelas.')
    } finally {
      setPreviewing(false)
    }
  }

  const isBusy = createMutation.isPending || updateMutation.isPending || deleteMutation.isPending

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Condições de Pagamento"
        description="Gerencie condições com desconto, juros, multa e parcelamento."
        actions={
          <Button onClick={() => { setShowForm(!showForm); setEditingUuid(null); setForm(defaultForm()); setPreviewResult(null) }}>
            <Plus className="mr-1.5 h-4 w-4" />
            Nova Condição
          </Button>
        }
      />

      {showForm && (
        <AppCard title={editingUuid ? 'Editar Condição' : 'Nova Condição de Pagamento'}>
          <form onSubmit={handleSubmit} className="space-y-6">

            {/* Nome e Tipo */}
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label htmlFor="cond-name">Nome *</Label>
                <Input
                  id="cond-name"
                  value={form.name}
                  onChange={(e) => setForm({ ...form, name: e.target.value })}
                  placeholder="Ex: 30/60/90 dias"
                  required
                  disabled={isBusy}
                />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="cond-type">Tipo</Label>
                <select
                  id="cond-type"
                  value={form.type}
                  onChange={(e) => setForm({ ...form, type: e.target.value as FormState['type'] })}
                  disabled={isBusy}
                  className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                >
                  {CONDITION_TYPES.map((t) => (
                    <option key={t.value} value={t.value}>{t.label}</option>
                  ))}
                </select>
              </div>
            </div>

            {/* Parcelamento */}
            <fieldset className="rounded-md border p-4">
              <legend className="px-1 text-xs font-medium text-muted-foreground">Parcelamento</legend>
              <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div className="space-y-1.5">
                  <Label htmlFor="inst-count">Nº Parcelas</Label>
                  <Input
                    id="inst-count"
                    type="number"
                    min={0}
                    max={360}
                    value={form.installment_count}
                    onChange={(e) => setForm({ ...form, installment_count: parseInt(e.target.value) || 0 })}
                    disabled={isBusy || !!form.is_variable}
                  />
                </div>
                <div className="space-y-1.5">
                  <Label htmlFor="first-due">Primeiro vencimento (dias)</Label>
                  <Input
                    id="first-due"
                    type="number"
                    min={0}
                    value={form.first_due_days}
                    onChange={(e) => setForm({ ...form, first_due_days: parseInt(e.target.value) || 0 })}
                    disabled={isBusy}
                  />
                </div>
                <div className="space-y-1.5">
                  <Label htmlFor="interval">Intervalo entre parcelas (dias)</Label>
                  <Input
                    id="interval"
                    type="number"
                    min={1}
                    value={form.interval_days}
                    onChange={(e) => setForm({ ...form, interval_days: parseInt(e.target.value) || 30 })}
                    disabled={isBusy}
                  />
                </div>
                <div className="space-y-1.5">
                  <Label htmlFor="entry-pct">% de Entrada</Label>
                  <Input
                    id="entry-pct"
                    type="number"
                    min={0}
                    max={100}
                    step={0.01}
                    value={form.entry_percent}
                    onChange={(e) => setForm({ ...form, entry_percent: parseFloat(e.target.value) || 0 })}
                    disabled={isBusy || !form.has_entry}
                  />
                </div>
              </div>
              <div className="mt-3 flex flex-wrap gap-4">
                <label className="flex cursor-pointer items-center gap-2 text-sm">
                  <input
                    type="checkbox"
                    checked={!!form.has_entry}
                    onChange={(e) => setForm({ ...form, has_entry: e.target.checked })}
                    disabled={isBusy}
                    className="h-4 w-4 rounded border-gray-300"
                  />
                  Entrada obrigatória no ato
                </label>
                <label className="flex cursor-pointer items-center gap-2 text-sm">
                  <input
                    type="checkbox"
                    checked={!!form.is_variable}
                    onChange={(e) => setForm({ ...form, is_variable: e.target.checked })}
                    disabled={isBusy}
                    className="h-4 w-4 rounded border-gray-300"
                  />
                  Número de parcelas variável
                </label>
              </div>
            </fieldset>

            {/* Desconto */}
            <fieldset className="rounded-md border p-4">
              <legend className="px-1 text-xs font-medium text-muted-foreground">Desconto</legend>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-1.5">
                  <Label>Tipo de desconto</Label>
                  <select
                    value={form.discount_type}
                    onChange={(e) => setForm({ ...form, discount_type: e.target.value as FormState['discount_type'] })}
                    disabled={isBusy}
                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                  >
                    {DISCOUNT_TYPES.map((t) => (
                      <option key={t.value} value={t.value}>{t.label}</option>
                    ))}
                  </select>
                </div>
                <div className="space-y-1.5">
                  <Label htmlFor="disc-value">Valor do desconto</Label>
                  <Input
                    id="disc-value"
                    type="number"
                    min={0}
                    step={0.01}
                    value={form.discount_value}
                    onChange={(e) => setForm({ ...form, discount_value: parseFloat(e.target.value) || 0 })}
                    disabled={isBusy || form.discount_type === 'none'}
                  />
                </div>
              </div>
            </fieldset>

            {/* Juros */}
            <fieldset className="rounded-md border p-4">
              <legend className="px-1 text-xs font-medium text-muted-foreground">Juros de Parcelamento</legend>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-1.5">
                  <Label>Tipo de juros</Label>
                  <select
                    value={form.interest_type}
                    onChange={(e) => setForm({ ...form, interest_type: e.target.value as FormState['interest_type'] })}
                    disabled={isBusy}
                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                  >
                    {INTEREST_TYPES.map((t) => (
                      <option key={t.value} value={t.value}>{t.label}</option>
                    ))}
                  </select>
                </div>
                <div className="space-y-1.5">
                  <Label htmlFor="int-value">Valor dos juros</Label>
                  <Input
                    id="int-value"
                    type="number"
                    min={0}
                    step={0.01}
                    value={form.interest_value}
                    onChange={(e) => setForm({ ...form, interest_value: parseFloat(e.target.value) || 0 })}
                    disabled={isBusy || form.interest_type === 'none'}
                  />
                </div>
              </div>
            </fieldset>

            {/* Multa e Carência */}
            <fieldset className="rounded-md border p-4">
              <legend className="px-1 text-xs font-medium text-muted-foreground">Multa e Carência</legend>
              <div className="grid grid-cols-2 gap-4 sm:grid-cols-3">
                <div className="space-y-1.5">
                  <Label htmlFor="fine-pct">Multa por atraso (%)</Label>
                  <Input
                    id="fine-pct"
                    type="number"
                    min={0}
                    max={100}
                    step={0.01}
                    value={form.fine_percent}
                    onChange={(e) => setForm({ ...form, fine_percent: parseFloat(e.target.value) || 0 })}
                    disabled={isBusy}
                  />
                </div>
                <div className="space-y-1.5">
                  <Label htmlFor="fine-days">Carência da multa (dias)</Label>
                  <Input
                    id="fine-days"
                    type="number"
                    min={0}
                    value={form.fine_after_days}
                    onChange={(e) => setForm({ ...form, fine_after_days: parseInt(e.target.value) || 0 })}
                    disabled={isBusy}
                  />
                </div>
                <div className="space-y-1.5">
                  <Label htmlFor="grace">Carência geral (dias)</Label>
                  <Input
                    id="grace"
                    type="number"
                    min={0}
                    value={form.grace_days}
                    onChange={(e) => setForm({ ...form, grace_days: parseInt(e.target.value) || 0 })}
                    disabled={isBusy}
                  />
                </div>
              </div>
            </fieldset>

            {/* Preview de Parcelas */}
            {editingUuid && (
              <div className="rounded-md border p-4 space-y-3">
                <p className="text-xs font-medium text-muted-foreground">Preview de Parcelas</p>
                <div className="flex items-center gap-3">
                  <div className="space-y-1">
                    <Label htmlFor="preview-amount">Valor (R$)</Label>
                    <Input
                      id="preview-amount"
                      type="number"
                      min={1}
                      step={0.01}
                      value={previewAmount / 100}
                      onChange={(e) => setPreviewAmount(Math.round((parseFloat(e.target.value) || 0) * 100))}
                      className="w-40"
                    />
                  </div>
                  <Button
                    type="button"
                    variant="outline"
                    onClick={handlePreview}
                    disabled={previewing}
                    className="mt-6"
                  >
                    <Calculator className="mr-1.5 h-4 w-4" />
                    Calcular
                  </Button>
                </div>

                {previewResult && (
                  <div className="space-y-2">
                    <div className="flex gap-6 text-sm">
                      <span>Desconto: <strong>{formatBRL(previewResult.discount_cents)}</strong></span>
                      <span>Juros: <strong>{formatBRL(previewResult.interest_cents)}</strong></span>
                      <span>Total: <strong>{formatBRL(previewResult.total_amount_cents)}</strong></span>
                    </div>
                    <div className="overflow-auto rounded-md border">
                      <table className="w-full text-sm">
                        <thead className="border-b bg-muted/40">
                          <tr>
                            <th className="px-3 py-2 text-left font-medium">Parcela</th>
                            <th className="px-3 py-2 text-left font-medium">Vencimento</th>
                            <th className="px-3 py-2 text-right font-medium">Valor</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y">
                          {previewResult.installments.map((inst) => (
                            <tr key={inst.number}>
                              <td className="px-3 py-2">{inst.number}ª</td>
                              <td className="px-3 py-2">{new Date(inst.due_date + 'T00:00:00').toLocaleDateString('pt-BR')}</td>
                              <td className="px-3 py-2 text-right">{formatBRL(inst.amount_cents)}</td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  </div>
                )}
              </div>
            )}

            <div className="flex gap-2 border-t pt-4">
              <Button type="submit" disabled={!form.name.trim() || isBusy}>
                <Check className="mr-1.5 h-4 w-4" />
                {editingUuid ? 'Salvar' : 'Adicionar'}
              </Button>
              <Button type="button" variant="outline" onClick={cancelEdit}>
                <X className="mr-1.5 h-4 w-4" />
                Cancelar
              </Button>
            </div>
          </form>
        </AppCard>
      )}

      <AppCard>
        {isLoading ? (
          <p className="text-sm text-muted-foreground">Carregando...</p>
        ) : (
          <div className="divide-y">
            {items.map((item) => (
              <div key={item.uuid} className="flex items-center gap-3 py-3">
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2">
                    <span className="text-sm font-medium">{item.name}</span>
                    {item.is_system && (
                      <span className="flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-[11px] text-muted-foreground">
                        <Lock className="h-3 w-3" />
                        Padrão
                      </span>
                    )}
                  </div>
                  <p className="mt-0.5 text-[11px] text-muted-foreground">{conditionSummary(item)}</p>
                  <div className="mt-1 flex flex-wrap gap-3 text-[11px] text-muted-foreground">
                    {item.discount_type !== 'none' && (
                      <span>
                        Desc. {item.discount_type === 'percent' ? `${item.discount_value}%` : formatBRL(Number(item.discount_value) * 100)}
                      </span>
                    )}
                    {item.interest_type !== 'none' && (
                      <span>Juros {item.interest_value}{item.interest_type.startsWith('percent') ? '%' : ' R$'}</span>
                    )}
                    {Number(item.fine_percent) > 0 && <span>Multa {item.fine_percent}%</span>}
                  </div>
                </div>
                {!item.is_system && (
                  <div className="flex gap-1">
                    <Button size="icon" variant="ghost" className="h-8 w-8" onClick={() => startEdit(item)} disabled={isBusy}>
                      <Pencil className="h-3.5 w-3.5" />
                    </Button>
                    <Button
                      size="icon"
                      variant="ghost"
                      className="h-8 w-8 text-destructive hover:text-destructive"
                      onClick={() => deleteMutation.mutate(item.uuid)}
                      disabled={isBusy}
                    >
                      <Trash2 className="h-3.5 w-3.5" />
                    </Button>
                  </div>
                )}
              </div>
            ))}

            {items.length === 0 && (
              <p className="py-4 text-sm text-muted-foreground">Nenhuma condição cadastrada.</p>
            )}
          </div>
        )}
      </AppCard>
    </div>
  )
}
