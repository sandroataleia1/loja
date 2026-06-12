'use client'

import { useState } from 'react'
import { Plus, ArrowRight } from 'lucide-react'
import { toast }          from 'sonner'
import { AppPageHeader }  from '@/components/shared/app-page-header'
import { Button }         from '@/components/ui/button'
import { Input }          from '@/components/ui/input'
import { Label }          from '@/components/ui/label'
import { Skeleton }       from '@/components/ui/skeleton'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { useFinancialTransfers, useCreateTransfer, useFinancialAccounts } from '@/features/financial/hooks'

function fmt(cents: number) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)
}

function fmtDate(iso: string | null) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('pt-BR')
}

export default function TransfersPage() {
  const [page, setPage] = useState(1)

  const { data, isLoading }              = useFinancialTransfers({ per_page: 20, page })
  const { data: accounts = [] }          = useFinancialAccounts()
  const { mutate: createTransfer, isPending: creating } = useCreateTransfer()

  const transfers    = data?.data ?? []
  const meta         = data?.meta
  const activeAccounts = accounts.filter((a) => a.is_active)

  const [showDialog,   setShowDialog]   = useState(false)
  const [originId,     setOriginId]     = useState('')
  const [destId,       setDestId]       = useState('')
  const [amountStr,    setAmountStr]    = useState('')
  const [transferDate, setTransferDate] = useState(new Date().toISOString().split('T')[0])
  const [description,  setDescription]  = useState('')

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    if (!originId || !destId) { toast.error('Selecione as contas de origem e destino.'); return }
    if (originId === destId)  { toast.error('Origem e destino devem ser contas diferentes.'); return }
    const amountCents = Math.round(parseFloat(amountStr.replace(',', '.')) * 100)
    if (isNaN(amountCents) || amountCents <= 0) { toast.error('Valor inválido.'); return }

    createTransfer({
      origin_account_id:      originId,
      destination_account_id: destId,
      amount_cents:           amountCents,
      transferred_at:         transferDate,
      description:            description || undefined,
    }, {
      onSuccess: () => {
        toast.success('Transferência registrada com sucesso.')
        setShowDialog(false)
        setOriginId(''); setDestId(''); setAmountStr(''); setDescription('')
        setTransferDate(new Date().toISOString().split('T')[0])
      },
      onError: (err) => toast.error(err instanceof Error ? err.message : 'Erro ao registrar transferência.'),
    })
  }

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Transferências"
        description="Movimentações entre contas bancárias e caixas."
        actions={
          <Button onClick={() => setShowDialog(true)}>
            <Plus className="h-4 w-4 mr-1.5" />
            Nova transferência
          </Button>
        }
      />

      <div className="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Data</TableHead>
              <TableHead>Origem</TableHead>
              <TableHead className="w-6"></TableHead>
              <TableHead>Destino</TableHead>
              <TableHead className="text-right">Valor</TableHead>
              <TableHead>Descrição</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {isLoading ? (
              Array.from({ length: 5 }).map((_, i) => (
                <TableRow key={i}>
                  {Array.from({ length: 6 }).map((_, j) => (
                    <TableCell key={j}><Skeleton className="h-4 w-full" /></TableCell>
                  ))}
                </TableRow>
              ))
            ) : transfers.length === 0 ? (
              <TableRow>
                <TableCell colSpan={6} className="text-center text-muted-foreground py-8">
                  Nenhuma transferência registrada.
                </TableCell>
              </TableRow>
            ) : transfers.map((tr) => (
              <TableRow key={tr.uuid}>
                <TableCell className="text-sm">{fmtDate(tr.transferred_at)}</TableCell>
                <TableCell className="font-medium">{tr.origin_account?.name ?? '—'}</TableCell>
                <TableCell><ArrowRight className="h-4 w-4 text-muted-foreground" /></TableCell>
                <TableCell className="font-medium">{tr.destination_account?.name ?? '—'}</TableCell>
                <TableCell className="text-right font-medium text-blue-600">{fmt(tr.amount_cents)}</TableCell>
                <TableCell className="text-sm text-muted-foreground max-w-40 truncate">{tr.description ?? '—'}</TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>

      {meta && meta.last_page > 1 && (
        <div className="flex items-center justify-between text-sm text-muted-foreground">
          <span>{transfers.length} de {meta.total} transferências</span>
          <div className="flex gap-2">
            <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>Anterior</Button>
            <span className="px-2 py-1">{page} / {meta.last_page}</span>
            <Button variant="outline" size="sm" disabled={page >= meta.last_page} onClick={() => setPage((p) => p + 1)}>Próximo</Button>
          </div>
        </div>
      )}

      {/* Dialog: nova transferência */}
      {showDialog && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
          <div className="w-full max-w-md rounded-lg border bg-background p-6 shadow-lg">
            <h2 className="text-lg font-semibold mb-4">Nova Transferência</h2>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid gap-1.5">
                <Label htmlFor="origin">Conta de Origem <span className="text-red-500">*</span></Label>
                <select
                  id="origin"
                  value={originId}
                  onChange={(e) => setOriginId(e.target.value)}
                  className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                  required
                >
                  <option value="">Selecionar...</option>
                  {activeAccounts.map((a) => <option key={a.uuid} value={a.uuid}>{a.name}</option>)}
                </select>
              </div>
              <div className="grid gap-1.5">
                <Label htmlFor="dest">Conta de Destino <span className="text-red-500">*</span></Label>
                <select
                  id="dest"
                  value={destId}
                  onChange={(e) => setDestId(e.target.value)}
                  className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                  required
                >
                  <option value="">Selecionar...</option>
                  {activeAccounts.filter((a) => a.uuid !== originId).map((a) => <option key={a.uuid} value={a.uuid}>{a.name}</option>)}
                </select>
              </div>
              <div className="grid sm:grid-cols-2 gap-4">
                <div className="grid gap-1.5">
                  <Label htmlFor="tr_amount">Valor (R$) <span className="text-red-500">*</span></Label>
                  <Input id="tr_amount" value={amountStr} onChange={(e) => setAmountStr(e.target.value)} placeholder="0,00" required />
                </div>
                <div className="grid gap-1.5">
                  <Label htmlFor="tr_date">Data</Label>
                  <Input id="tr_date" type="date" value={transferDate} onChange={(e) => setTransferDate(e.target.value)} />
                </div>
              </div>
              <div className="grid gap-1.5">
                <Label htmlFor="tr_desc">Descrição (opcional)</Label>
                <Input id="tr_desc" value={description} onChange={(e) => setDescription(e.target.value)} placeholder="Motivo ou observação..." />
              </div>
              <div className="flex gap-3 pt-2">
                <Button type="submit" disabled={creating}>{creating ? 'Salvando...' : 'Registrar transferência'}</Button>
                <Button type="button" variant="outline" onClick={() => setShowDialog(false)}>Cancelar</Button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}
