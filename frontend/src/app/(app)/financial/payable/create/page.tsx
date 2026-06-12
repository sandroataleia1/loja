'use client'

import Link from 'next/link'
import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { ChevronLeft, Plus, Trash2 } from 'lucide-react'
import { toast }            from 'sonner'
import { AppPageHeader }    from '@/components/shared/app-page-header'
import { AppCard }          from '@/components/shared/app-card'
import { Button }           from '@/components/ui/button'
import { Input }            from '@/components/ui/input'
import { Label }            from '@/components/ui/label'
import { useCreateEntry, useFinancialAccounts, useFinancialCategories } from '@/features/financial/hooks'
import { ROUTES }           from '@/constants'

export default function CreatePayablePage() {
  const router  = useRouter()
  const { mutate: createEntry, isPending } = useCreateEntry()
  const { data: accounts   = [] } = useFinancialAccounts()
  const { data: categories = [] } = useFinancialCategories()

  const expenseCategories = categories.filter((c) => c.type === 'expense' && c.is_active)

  const [description,    setDescription]    = useState('')
  const [categoryId,     setCategoryId]     = useState('')
  const [accountId,      setAccountId]      = useState('')
  const [amountStr,      setAmountStr]      = useState('')
  const [dueDate,        setDueDate]        = useState('')
  const [externalRef,    setExternalRef]    = useState('')
  const [useInstallments, setUseInstallments] = useState(false)
  const [installments,   setInstallments]   = useState([{ due_date: '', amount: '' }])

  function addInstallment() {
    setInstallments((prev) => [...prev, { due_date: '', amount: '' }])
  }

  function removeInstallment(i: number) {
    setInstallments((prev) => prev.filter((_, idx) => idx !== i))
  }

  function updateInstallment(i: number, field: 'due_date' | 'amount', value: string) {
    setInstallments((prev) => prev.map((inst, idx) => idx === i ? { ...inst, [field]: value } : inst))
  }

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    const amountCents = Math.round(parseFloat(amountStr.replace(',', '.')) * 100)
    if (isNaN(amountCents) || amountCents <= 0) { toast.error('Valor inválido.'); return }
    if (!dueDate) { toast.error('Informe a data de vencimento.'); return }

    const payload: Parameters<typeof createEntry>[0] = {
      type:                 'expense',
      amount_cents:         amountCents,
      due_date:             dueDate,
      description:          description || undefined,
      category_id:          categoryId  || undefined,
      financial_account_id: accountId   || undefined,
      external_reference:   externalRef || undefined,
    }

    if (useInstallments && installments.length >= 2) {
      const parsed = installments.map((inst) => ({
        due_date:     inst.due_date,
        amount_cents: Math.round(parseFloat(inst.amount.replace(',', '.')) * 100),
      }))
      if (parsed.some((p) => !p.due_date || isNaN(p.amount_cents) || p.amount_cents <= 0)) {
        toast.error('Preencha todas as parcelas corretamente.')
        return
      }
      const sumParcelas = parsed.reduce((s, p) => s + p.amount_cents, 0)
      if (sumParcelas !== amountCents) {
        toast.error(`Soma das parcelas (${(sumParcelas / 100).toFixed(2)}) deve ser igual ao valor total.`)
        return
      }
      payload.installments = parsed
    }

    createEntry(payload, {
      onSuccess: (created) => {
        toast.success('Lançamento criado com sucesso.')
        router.push(`/financial/entries/${created.uuid}`)
      },
      onError: (err) => {
        toast.error(err instanceof Error ? err.message : 'Erro ao criar lançamento.')
      },
    })
  }

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Nova Conta a Pagar"
        description="Registre uma nova despesa ou compromisso financeiro."
        actions={
          <Button variant="outline" asChild>
            <Link href={ROUTES.FINANCIAL_PAYABLE}>
              <ChevronLeft className="mr-1.5 h-4 w-4" />
              Voltar
            </Link>
          </Button>
        }
      />

      <form onSubmit={handleSubmit} className="space-y-6 max-w-2xl">
        <AppCard title="Dados do Lançamento">
          <div className="grid gap-4">
            <div className="grid gap-1.5">
              <Label htmlFor="description">Descrição</Label>
              <Input id="description" value={description} onChange={(e) => setDescription(e.target.value)} placeholder="Ex: Aluguel, Fornecedor, etc." />
            </div>

            <div className="grid sm:grid-cols-2 gap-4">
              <div className="grid gap-1.5">
                <Label htmlFor="amount">Valor (R$) <span className="text-red-500">*</span></Label>
                <Input id="amount" value={amountStr} onChange={(e) => setAmountStr(e.target.value)} placeholder="0,00" required />
              </div>
              <div className="grid gap-1.5">
                <Label htmlFor="due_date">Vencimento <span className="text-red-500">*</span></Label>
                <Input id="due_date" type="date" value={dueDate} onChange={(e) => setDueDate(e.target.value)} required />
              </div>
            </div>

            <div className="grid sm:grid-cols-2 gap-4">
              <div className="grid gap-1.5">
                <Label htmlFor="category">Categoria</Label>
                <select
                  id="category"
                  value={categoryId}
                  onChange={(e) => setCategoryId(e.target.value)}
                  className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                >
                  <option value="">Sem categoria</option>
                  {expenseCategories.map((c) => <option key={c.uuid} value={c.uuid}>{c.name}</option>)}
                </select>
              </div>
              <div className="grid gap-1.5">
                <Label htmlFor="account">Conta</Label>
                <select
                  id="account"
                  value={accountId}
                  onChange={(e) => setAccountId(e.target.value)}
                  className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                >
                  <option value="">Sem conta</option>
                  {accounts.filter((a) => a.is_active).map((a) => <option key={a.uuid} value={a.uuid}>{a.name}</option>)}
                </select>
              </div>
            </div>

            <div className="grid gap-1.5">
              <Label htmlFor="external_ref">Referência Externa</Label>
              <Input id="external_ref" value={externalRef} onChange={(e) => setExternalRef(e.target.value)} placeholder="NF, contrato, número do pedido..." />
            </div>
          </div>
        </AppCard>

        {/* Parcelamento */}
        <AppCard title="Parcelamento">
          <div className="space-y-4">
            <label className="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                checked={useInstallments}
                onChange={(e) => {
                  setUseInstallments(e.target.checked)
                  if (e.target.checked && installments.length < 2) {
                    setInstallments([{ due_date: '', amount: '' }, { due_date: '', amount: '' }])
                  }
                }}
                className="h-4 w-4 rounded border-input"
              />
              <span className="text-sm">Parcelar este lançamento</span>
            </label>

            {useInstallments && (
              <div className="space-y-3">
                {installments.map((inst, i) => (
                  <div key={i} className="flex items-center gap-3">
                    <span className="text-sm text-muted-foreground w-20 shrink-0">Parcela {i + 1}</span>
                    <Input
                      type="date"
                      value={inst.due_date}
                      onChange={(e) => updateInstallment(i, 'due_date', e.target.value)}
                      className="w-40"
                    />
                    <Input
                      value={inst.amount}
                      onChange={(e) => updateInstallment(i, 'amount', e.target.value)}
                      placeholder="0,00"
                      className="w-32"
                    />
                    {installments.length > 2 && (
                      <Button type="button" variant="ghost" size="icon" onClick={() => removeInstallment(i)}>
                        <Trash2 className="h-4 w-4 text-muted-foreground" />
                      </Button>
                    )}
                  </div>
                ))}
                <Button type="button" variant="outline" size="sm" onClick={addInstallment}>
                  <Plus className="h-3 w-3 mr-1.5" />
                  Adicionar parcela
                </Button>
                <p className="text-xs text-muted-foreground">A soma das parcelas deve ser igual ao valor total.</p>
              </div>
            )}
          </div>
        </AppCard>

        <div className="flex gap-3">
          <Button type="submit" disabled={isPending}>{isPending ? 'Salvando...' : 'Criar lançamento'}</Button>
          <Button type="button" variant="outline" asChild>
            <Link href={ROUTES.FINANCIAL_PAYABLE}>Cancelar</Link>
          </Button>
        </div>
      </form>
    </div>
  )
}
