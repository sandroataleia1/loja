'use client'

import { useState, useMemo } from 'react'
import { Plus, Trash2, X, Loader2, TrendingDown, AlertTriangle, CheckCircle2 } from 'lucide-react'
import { toast }                from 'sonner'
import { AppPageHeader }        from '@/components/shared/app-page-header'
import { Button }               from '@/components/ui/button'
import { Input }                from '@/components/ui/input'
import { Label }                from '@/components/ui/label'
import { Skeleton }             from '@/components/ui/skeleton'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { EntryStatusBadge }     from '@/features/financial/components'
import {
  useFinancialEntries,
  useCreateEntry,
  useFinancialAccounts,
  useFinancialCategories,
} from '@/features/financial/hooks'
import type { FinancialEntryStatus } from '@/types/financial'
import Link from 'next/link'

function fmt(cents: number) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)
}

function fmtDate(iso: string | null) {
  if (!iso) return '—'
  return new Date(iso + 'T00:00:00').toLocaleDateString('pt-BR')
}

function KpiCard({ label, value, icon: Icon, colorClass, isLoading }: {
  label:      string
  value:      string
  icon:       React.ComponentType<{ className?: string }>
  colorClass: string
  isLoading:  boolean
}) {
  return (
    <div className="rounded-lg border bg-card p-5 flex items-center gap-4">
      <div className={`flex h-11 w-11 items-center justify-center rounded-full ${colorClass}`}>
        <Icon className="h-5 w-5" />
      </div>
      <div>
        <p className="text-sm text-muted-foreground">{label}</p>
        {isLoading
          ? <Skeleton className="mt-1 h-6 w-28" />
          : <p className="text-xl font-bold">{value}</p>
        }
      </div>
    </div>
  )
}

// ── Modal ─────────────────────────────────────────────────────────────────────

function PayableDialog({ onClose }: { onClose: () => void }) {
  const { mutate: createEntry, isPending } = useCreateEntry()
  const { data: accounts   = [] } = useFinancialAccounts()
  const { data: categories = [] } = useFinancialCategories()

  const expenseCategories = categories.filter((c) => c.type === 'expense' && c.is_active)

  const [description,     setDescription]     = useState('')
  const [categoryId,      setCategoryId]      = useState('')
  const [accountId,       setAccountId]       = useState('')
  const [amountStr,       setAmountStr]       = useState('')
  const [dueDate,         setDueDate]         = useState('')
  const [externalRef,     setExternalRef]     = useState('')
  const [useInstallments, setUseInstallments] = useState(false)
  const [installments,    setInstallments]    = useState([{ due_date: '', amount: '' }, { due_date: '', amount: '' }])

  function addInstallment() {
    setInstallments((p) => [...p, { due_date: '', amount: '' }])
  }

  function removeInstallment(i: number) {
    setInstallments((p) => p.filter((_, idx) => idx !== i))
  }

  function updateInstallment(i: number, field: 'due_date' | 'amount', value: string) {
    setInstallments((p) => p.map((inst, idx) => idx === i ? { ...inst, [field]: value } : inst))
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
      onSuccess: () => { toast.success('Lançamento criado com sucesso.'); onClose() },
      onError:   (err) => { toast.error(err instanceof Error ? err.message : 'Erro ao criar lançamento.') },
    })
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
      <div className="bg-card border rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col">
        <div className="flex items-center justify-between px-5 py-4 border-b shrink-0">
          <p className="font-bold text-base">Nova Conta a Pagar</p>
          <button onClick={onClose} className="text-muted-foreground hover:text-foreground transition-colors">
            <X className="h-4 w-4" />
          </button>
        </div>

        <form id="payable-form" onSubmit={handleSubmit} className="overflow-y-auto flex-1 p-5 space-y-4">
          <div className="space-y-1.5">
            <Label htmlFor="pdesc">Descrição</Label>
            <Input id="pdesc" value={description} onChange={(e) => setDescription(e.target.value)} placeholder="Ex: Aluguel, fornecedor, boleto…" />
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5">
              <Label htmlFor="pamount">Valor (R$) <span className="text-destructive">*</span></Label>
              <Input id="pamount" value={amountStr} onChange={(e) => setAmountStr(e.target.value)} placeholder="0,00" required />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="pdue">Vencimento <span className="text-destructive">*</span></Label>
              <Input id="pdue" type="date" value={dueDate} onChange={(e) => setDueDate(e.target.value)} required />
            </div>
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5">
              <Label>Categoria</Label>
              <select value={categoryId} onChange={(e) => setCategoryId(e.target.value)}
                className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm">
                <option value="">Sem categoria</option>
                {expenseCategories.map((c) => <option key={c.uuid} value={c.uuid}>{c.name}</option>)}
              </select>
            </div>
            <div className="space-y-1.5">
              <Label>Conta</Label>
              <select value={accountId} onChange={(e) => setAccountId(e.target.value)}
                className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm">
                <option value="">Sem conta</option>
                {accounts.filter((a) => a.is_active).map((a) => <option key={a.uuid} value={a.uuid}>{a.name}</option>)}
              </select>
            </div>
          </div>

          <div className="space-y-1.5">
            <Label htmlFor="pref">Referência externa</Label>
            <Input id="pref" value={externalRef} onChange={(e) => setExternalRef(e.target.value)} placeholder="NF, contrato, nº pedido…" />
          </div>

          <div className="border rounded-xl p-3 space-y-3">
            <label className="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" checked={useInstallments} onChange={(e) => setUseInstallments(e.target.checked)}
                className="h-4 w-4 rounded border-input" />
              <span className="text-sm font-medium">Parcelar este lançamento</span>
            </label>
            {useInstallments && (
              <div className="space-y-2">
                {installments.map((inst, i) => (
                  <div key={i} className="flex items-center gap-2">
                    <span className="text-xs text-muted-foreground w-16 shrink-0">Parcela {i + 1}</span>
                    <Input type="date" value={inst.due_date}
                      onChange={(e) => updateInstallment(i, 'due_date', e.target.value)} className="h-8 text-sm" />
                    <Input value={inst.amount} onChange={(e) => updateInstallment(i, 'amount', e.target.value)}
                      placeholder="0,00" className="h-8 w-28 text-sm" />
                    {installments.length > 2 && (
                      <button type="button" onClick={() => removeInstallment(i)}
                        className="p-1 text-muted-foreground hover:text-destructive">
                        <Trash2 className="h-3.5 w-3.5" />
                      </button>
                    )}
                  </div>
                ))}
                <Button type="button" variant="outline" size="sm" onClick={addInstallment}>
                  <Plus className="h-3 w-3 mr-1.5" />Adicionar parcela
                </Button>
                <p className="text-xs text-muted-foreground">A soma das parcelas deve ser igual ao valor total.</p>
              </div>
            )}
          </div>
        </form>

        <div className="flex justify-end gap-3 px-5 py-4 border-t shrink-0">
          <Button type="button" variant="outline" onClick={onClose} disabled={isPending}>Cancelar</Button>
          <Button type="submit" form="payable-form" disabled={isPending}>
            {isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            Criar lançamento
          </Button>
        </div>
      </div>
    </div>
  )
}

// ── Page ─────────────────────────────────────────────────────────────────────

export default function PayablePage() {
  const [status,     setStatus]     = useState<FinancialEntryStatus | ''>('')
  const [dueFrom,    setDueFrom]    = useState('')
  const [dueTo,      setDueTo]      = useState('')
  const [page,       setPage]       = useState(1)
  const [showCreate, setShowCreate] = useState(false)

  const monthRange = useMemo(() => {
    const now  = new Date()
    const y    = now.getFullYear()
    const m    = String(now.getMonth() + 1).padStart(2, '0')
    const last = new Date(y, now.getMonth() + 1, 0).getDate()
    return { from: `${y}-${m}-01`, to: `${y}-${m}-${last}` }
  }, [])

  const { data, isLoading } = useFinancialEntries({
    type: 'expense', status: status || undefined,
    due_from: dueFrom || undefined, due_to: dueTo || undefined,
    per_page: 20, page,
  })
  const { data: kpiPending,  isLoading: kpiPendingLoading  } = useFinancialEntries({ type: 'expense', status: 'pending',  per_page: 100 })
  const { data: kpiOverdue,  isLoading: kpiOverdueLoading  } = useFinancialEntries({ type: 'expense', status: 'overdue',  per_page: 100 })
  const { data: kpiPaidMth,  isLoading: kpiPaidMthLoading  } = useFinancialEntries({
    type: 'expense', status: 'paid',
    due_from: monthRange.from, due_to: monthRange.to, per_page: 100,
  })

  const totalPending = (kpiPending?.data ?? []).reduce((s, e) => s + e.amount_cents, 0)
  const totalOverdue = (kpiOverdue?.data ?? []).reduce((s, e) => s + e.amount_cents, 0)
  const totalPaidMth = (kpiPaidMth?.data ?? []).reduce((s, e) => s + e.amount_cents, 0)

  const entries = data?.data ?? []
  const meta    = data?.meta

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Contas a Pagar"
        description="Gerencie suas despesas e compromissos financeiros."
        actions={
          <Button onClick={() => setShowCreate(true)}>
            <Plus className="h-4 w-4 mr-1.5" />
            Novo lançamento
          </Button>
        }
      />

      {/* KPIs */}
      <div className="grid gap-4 sm:grid-cols-3">
        <KpiCard label="Total Pendente"   value={fmt(totalPending)} icon={TrendingDown}  colorClass="bg-red-100 text-red-600"    isLoading={kpiPendingLoading} />
        <KpiCard label="Vencidas"         value={fmt(totalOverdue)} icon={AlertTriangle} colorClass="bg-orange-100 text-orange-600" isLoading={kpiOverdueLoading} />
        <KpiCard label="Pagas este mês"   value={fmt(totalPaidMth)} icon={CheckCircle2}  colorClass="bg-green-100 text-green-600" isLoading={kpiPaidMthLoading} />
      </div>

      {/* Filtros */}
      <div className="flex flex-wrap items-center gap-3">
        <select value={status}
          onChange={(e) => { setStatus(e.target.value as FinancialEntryStatus | ''); setPage(1) }}
          className="flex h-9 rounded-md border border-input bg-background px-3 py-1 text-sm">
          <option value="">Todos os status</option>
          <option value="pending">Pendente</option>
          <option value="overdue">Vencido</option>
          <option value="paid">Pago</option>
          <option value="partially_paid">Parcialmente pago</option>
          <option value="cancelled">Cancelado</option>
        </select>
        <div className="flex items-center gap-2">
          <span className="text-sm text-muted-foreground">Venc. de</span>
          <input type="date" value={dueFrom}
            onChange={(e) => { setDueFrom(e.target.value); setPage(1) }}
            className="flex h-9 rounded-md border border-input bg-background px-3 py-1 text-sm" />
          <span className="text-sm text-muted-foreground">até</span>
          <input type="date" value={dueTo}
            onChange={(e) => { setDueTo(e.target.value); setPage(1) }}
            className="flex h-9 rounded-md border border-input bg-background px-3 py-1 text-sm" />
        </div>
        {(status || dueFrom || dueTo) && (
          <Button variant="ghost" size="sm"
            onClick={() => { setStatus(''); setDueFrom(''); setDueTo(''); setPage(1) }}>
            Limpar filtros
          </Button>
        )}
      </div>

      {/* Tabela */}
      <div className="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Descrição</TableHead>
              <TableHead>Categoria</TableHead>
              <TableHead>Conta</TableHead>
              <TableHead>Vencimento</TableHead>
              <TableHead className="text-right">Valor</TableHead>
              <TableHead>Status</TableHead>
              <TableHead className="w-16">Ações</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {isLoading ? (
              Array.from({ length: 5 }).map((_, i) => (
                <TableRow key={i}>
                  {Array.from({ length: 7 }).map((_, j) => (
                    <TableCell key={j}><Skeleton className="h-4 w-full" /></TableCell>
                  ))}
                </TableRow>
              ))
            ) : entries.length === 0 ? (
              <TableRow>
                <TableCell colSpan={7} className="text-center text-muted-foreground py-8">
                  Nenhuma conta a pagar encontrada.
                </TableCell>
              </TableRow>
            ) : entries.map((entry) => (
              <TableRow key={entry.uuid}>
                <TableCell className="font-medium max-w-48 truncate">
                  {entry.description ?? <span className="text-muted-foreground">—</span>}
                </TableCell>
                <TableCell className="text-muted-foreground text-sm">{entry.category?.name ?? '—'}</TableCell>
                <TableCell className="text-muted-foreground text-sm">{entry.account?.name ?? '—'}</TableCell>
                <TableCell className={`text-sm ${entry.status === 'overdue' ? 'text-red-600 font-medium' : ''}`}>
                  {fmtDate(entry.due_date)}
                </TableCell>
                <TableCell className="text-right font-medium text-red-600">{fmt(entry.amount_cents)}</TableCell>
                <TableCell><EntryStatusBadge status={entry.status} /></TableCell>
                <TableCell>
                  <Button variant="ghost" size="sm" asChild>
                    <Link href={`/financial/entries/${entry.uuid}`}>Ver</Link>
                  </Button>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>

      {meta && meta.last_page > 1 && (
        <div className="flex items-center justify-between text-sm text-muted-foreground">
          <span>{entries.length} de {meta.total} lançamentos</span>
          <div className="flex gap-2">
            <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>Anterior</Button>
            <span className="px-2 py-1">{page} / {meta.last_page}</span>
            <Button variant="outline" size="sm" disabled={page >= meta.last_page} onClick={() => setPage((p) => p + 1)}>Próximo</Button>
          </div>
        </div>
      )}

      {showCreate && <PayableDialog onClose={() => setShowCreate(false)} />}
    </div>
  )
}
