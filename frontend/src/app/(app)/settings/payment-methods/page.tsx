'use client'

import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Plus, Pencil, Trash2, Check, X, Lock, ChevronDown, ChevronUp } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard } from '@/components/shared/app-card'
import {
  paymentService,
  type TenantPaymentMethod,
  type CreatePaymentMethodRequest,
} from '@/services/payment.service'

const QK = ['payment-methods'] as const

const METHOD_TYPES = [
  { value: 'cash',          label: 'Dinheiro' },
  { value: 'pix',           label: 'PIX' },
  { value: 'credit_card',   label: 'Cartão de Crédito' },
  { value: 'debit_card',    label: 'Cartão de Débito' },
  { value: 'boleto',        label: 'Boleto Bancário' },
  { value: 'bank_transfer', label: 'Transferência Bancária' },
  { value: 'check',         label: 'Cheque' },
  { value: 'store_credit',  label: 'Crediário' },
  { value: 'convention',    label: 'Convênio' },
  { value: 'voucher',       label: 'Vale / Voucher' },
]

interface FormState {
  name:                        string
  type:                        string
  accepts_change:              boolean
  allow_installments:          boolean
  max_installments:            number
  min_installment_value_cents: number
  requires_authorization:      boolean
  integrates_financial:        boolean
}

const defaultForm = (): FormState => ({
  name:                        '',
  type:                        'cash',
  accepts_change:              false,
  allow_installments:          false,
  max_installments:            1,
  min_installment_value_cents: 0,
  requires_authorization:      false,
  integrates_financial:        true,
})

export default function PaymentMethodsPage() {
  const qc = useQueryClient()

  const { data: items = [], isLoading } = useQuery({
    queryKey: QK,
    queryFn:  () => paymentService.listMethods(),
  })

  const createMutation = useMutation({
    mutationFn: (data: CreatePaymentMethodRequest) => paymentService.createMethod(data),
    onSuccess:  () => { qc.invalidateQueries({ queryKey: QK }); toast.success('Forma adicionada.') },
    onError:    () => toast.error('Erro ao adicionar forma de pagamento.'),
  })

  const updateMutation = useMutation({
    mutationFn: ({ uuid, data }: { uuid: string; data: CreatePaymentMethodRequest }) =>
      paymentService.updateMethod(uuid, data),
    onSuccess: () => { qc.invalidateQueries({ queryKey: QK }); toast.success('Forma atualizada.') },
    onError:   () => toast.error('Erro ao atualizar forma de pagamento.'),
  })

  const deleteMutation = useMutation({
    mutationFn: (uuid: string) => paymentService.deleteMethod(uuid),
    onSuccess:  () => { qc.invalidateQueries({ queryKey: QK }); toast.success('Forma removida.') },
    onError:    () => toast.error('Erro ao remover forma de pagamento.'),
  })

  const [form,        setForm]        = useState<FormState>(defaultForm())
  const [editingUuid, setEditingUuid] = useState<string | null>(null)
  const [showForm,    setShowForm]    = useState(false)

  function startEdit(item: TenantPaymentMethod) {
    setEditingUuid(item.uuid)
    setForm({
      name:                        item.name,
      type:                        item.type ?? 'cash',
      accepts_change:              item.accepts_change,
      allow_installments:          item.allow_installments,
      max_installments:            item.max_installments,
      min_installment_value_cents: item.min_installment_value_cents,
      requires_authorization:      item.requires_authorization,
      integrates_financial:        item.integrates_financial,
    })
    setShowForm(true)
  }

  function cancelEdit() {
    setEditingUuid(null)
    setForm(defaultForm())
    setShowForm(false)
  }

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    if (!form.name.trim()) return

    if (editingUuid) {
      updateMutation.mutate({ uuid: editingUuid, data: form }, { onSuccess: () => cancelEdit() })
    } else {
      createMutation.mutate(form, { onSuccess: () => { setForm(defaultForm()); setShowForm(false) } })
    }
  }

  const isBusy = createMutation.isPending || updateMutation.isPending || deleteMutation.isPending

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Formas de Pagamento"
        description="Gerencie as formas de pagamento disponíveis nos documentos."
        actions={
          <Button onClick={() => { setShowForm(!showForm); setEditingUuid(null); setForm(defaultForm()) }}>
            <Plus className="mr-1.5 h-4 w-4" />
            Nova Forma
          </Button>
        }
      />

      {showForm && (
        <AppCard title={editingUuid ? 'Editar Forma de Pagamento' : 'Nova Forma de Pagamento'}>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
              <div className="space-y-1.5">
                <Label htmlFor="method-name">Nome *</Label>
                <Input
                  id="method-name"
                  value={form.name}
                  onChange={(e) => setForm({ ...form, name: e.target.value })}
                  placeholder="Ex: Crédito da Loja"
                  required
                  disabled={isBusy}
                />
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="method-type">Tipo</Label>
                <select
                  id="method-type"
                  value={form.type}
                  onChange={(e) => setForm({ ...form, type: e.target.value })}
                  disabled={isBusy}
                  className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                >
                  {METHOD_TYPES.map((t) => (
                    <option key={t.value} value={t.value}>{t.label}</option>
                  ))}
                </select>
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="max-inst">Máx. Parcelas</Label>
                <Input
                  id="max-inst"
                  type="number"
                  min={1}
                  max={72}
                  value={form.max_installments}
                  onChange={(e) => setForm({ ...form, max_installments: parseInt(e.target.value) || 1 })}
                  disabled={isBusy || !form.allow_installments}
                />
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="min-inst-value">Parcela Mínima (R$)</Label>
                <Input
                  id="min-inst-value"
                  type="number"
                  min={0}
                  step={0.01}
                  value={form.min_installment_value_cents / 100}
                  onChange={(e) => setForm({ ...form, min_installment_value_cents: Math.round((parseFloat(e.target.value) || 0) * 100) })}
                  disabled={isBusy || !form.allow_installments}
                />
              </div>
            </div>

            <div className="flex flex-wrap gap-6">
              {([
                ['accepts_change',        'Permite troco'],
                ['allow_installments',    'Permite parcelamento'],
                ['requires_authorization','Exige autorização'],
                ['integrates_financial',  'Integra financeiro'],
              ] as const).map(([field, label]) => (
                <label key={field} className="flex cursor-pointer items-center gap-2 text-sm">
                  <input
                    type="checkbox"
                    checked={form[field]}
                    onChange={(e) => setForm({ ...form, [field]: e.target.checked })}
                    disabled={isBusy}
                    className="h-4 w-4 rounded border-gray-300"
                  />
                  {label}
                </label>
              ))}
            </div>

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
                    {item.type && (
                      <span className="rounded-full bg-muted px-2 py-0.5 text-[10px] text-muted-foreground">
                        {METHOD_TYPES.find((t) => t.value === item.type)?.label ?? item.type}
                      </span>
                    )}
                    {item.is_system && (
                      <span className="flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-[11px] text-muted-foreground">
                        <Lock className="h-3 w-3" />
                        Padrão
                      </span>
                    )}
                  </div>
                  <div className="mt-1 flex flex-wrap gap-3 text-[11px] text-muted-foreground">
                    {item.accepts_change        && <span>Troco</span>}
                    {item.allow_installments    && <span>Até {item.max_installments}x</span>}
                    {item.requires_authorization && <span>Autorização</span>}
                    {!item.integrates_financial  && <span>Sem integração financeira</span>}
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
              <p className="py-4 text-sm text-muted-foreground">Nenhuma forma de pagamento cadastrada.</p>
            )}
          </div>
        )}
      </AppCard>
    </div>
  )
}
