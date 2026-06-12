'use client'

import { useState } from 'react'
import { Plus, Pencil, Trash2 } from 'lucide-react'
import { toast }             from 'sonner'
import { AppPageHeader }     from '@/components/shared/app-page-header'
import { AppCard }           from '@/components/shared/app-card'
import { Button }            from '@/components/ui/button'
import { Input }             from '@/components/ui/input'
import { Label }             from '@/components/ui/label'
import { Skeleton }          from '@/components/ui/skeleton'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { useFinancialAccounts, useCreateAccount, useUpdateAccount, useDeleteAccount } from '@/features/financial/hooks'
import type { FinancialAccount, FinancialAccountType } from '@/types/financial'

function fmt(cents: number) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)
}

const ACCOUNT_TYPE_LABELS: Record<FinancialAccountType, string> = {
  cash:           'Dinheiro/Caixa',
  bank:           'Conta Bancária',
  digital_wallet: 'Carteira Digital',
}

interface FormState {
  name:             string
  code:             string
  type:             FinancialAccountType
  initial_balance:  string
  is_active:        boolean
}

const EMPTY_FORM: FormState = { name: '', code: '', type: 'bank', initial_balance: '0', is_active: true }

export default function AccountsPage() {
  const { data: accounts = [], isLoading }   = useFinancialAccounts()
  const { mutate: createAccount, isPending: creating } = useCreateAccount()
  const { mutate: updateAccount, isPending: updating } = useUpdateAccount()
  const { mutate: deleteAccount, isPending: deleting } = useDeleteAccount()

  const [showDialog,    setShowDialog]    = useState(false)
  const [editTarget,    setEditTarget]    = useState<FinancialAccount | null>(null)
  const [form,          setForm]          = useState<FormState>(EMPTY_FORM)
  const [deleteTarget,  setDeleteTarget]  = useState<FinancialAccount | null>(null)

  function openCreate() {
    setEditTarget(null)
    setForm(EMPTY_FORM)
    setShowDialog(true)
  }

  function openEdit(acc: FinancialAccount) {
    setEditTarget(acc)
    setForm({
      name:            acc.name,
      code:            acc.code ?? '',
      type:            acc.type,
      initial_balance: '0',
      is_active:       acc.is_active,
    })
    setShowDialog(true)
  }

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    const payload = {
      name:     form.name,
      code:     form.code || undefined,
      type:     form.type,
      is_active: form.is_active,
      ...(editTarget ? {} : {
        initial_balance_cents: Math.round(parseFloat(form.initial_balance.replace(',', '.') || '0') * 100),
      }),
    }

    if (editTarget) {
      updateAccount({ uuid: editTarget.uuid, data: payload }, {
        onSuccess: () => { toast.success('Conta atualizada.'); setShowDialog(false) },
        onError:   (err) => toast.error(err instanceof Error ? err.message : 'Erro ao atualizar conta.'),
      })
    } else {
      createAccount(payload, {
        onSuccess: () => { toast.success('Conta criada.'); setShowDialog(false) },
        onError:   (err) => toast.error(err instanceof Error ? err.message : 'Erro ao criar conta.'),
      })
    }
  }

  function handleDelete() {
    if (!deleteTarget) return
    deleteAccount(deleteTarget.uuid, {
      onSuccess: () => { toast.success('Conta excluída.'); setDeleteTarget(null) },
      onError:   (err) => toast.error(err instanceof Error ? err.message : 'Erro ao excluir conta.'),
    })
  }

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Contas Bancárias"
        description="Gerencie caixas, contas bancárias e carteiras digitais."
        actions={
          <Button onClick={openCreate}>
            <Plus className="h-4 w-4 mr-1.5" />
            Nova conta
          </Button>
        }
      />

      <AppCard>
        <div className="rounded-md border">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Código</TableHead>
                <TableHead>Nome</TableHead>
                <TableHead>Tipo</TableHead>
                <TableHead className="text-right">Saldo Atual</TableHead>
                <TableHead>Status</TableHead>
                <TableHead className="w-24">Ações</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {isLoading ? (
                Array.from({ length: 3 }).map((_, i) => (
                  <TableRow key={i}>
                    {Array.from({ length: 6 }).map((_, j) => (
                      <TableCell key={j}><Skeleton className="h-4 w-full" /></TableCell>
                    ))}
                  </TableRow>
                ))
              ) : accounts.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={6} className="text-center text-muted-foreground py-8">
                    Nenhuma conta cadastrada.
                  </TableCell>
                </TableRow>
              ) : accounts.map((acc) => (
                <TableRow key={acc.uuid}>
                  <TableCell className="font-mono text-sm">{acc.code ?? '—'}</TableCell>
                  <TableCell className="font-medium">{acc.name}</TableCell>
                  <TableCell className="text-sm text-muted-foreground">{ACCOUNT_TYPE_LABELS[acc.type]}</TableCell>
                  <TableCell className={`text-right font-medium ${(acc.current_balance_cents ?? 0) < 0 ? 'text-red-600' : 'text-green-600'}`}>
                    {fmt(acc.current_balance_cents ?? 0)}
                  </TableCell>
                  <TableCell>
                    <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium border ${
                      acc.is_active ? 'bg-green-100 text-green-700 border-green-200' : 'bg-gray-100 text-gray-600 border-gray-200'
                    }`}>
                      {acc.is_active ? 'Ativa' : 'Inativa'}
                    </span>
                  </TableCell>
                  <TableCell>
                    <div className="flex gap-1">
                      <Button variant="ghost" size="icon" onClick={() => openEdit(acc)}>
                        <Pencil className="h-4 w-4" />
                      </Button>
                      <Button variant="ghost" size="icon" onClick={() => setDeleteTarget(acc)}>
                        <Trash2 className="h-4 w-4 text-destructive" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>
      </AppCard>

      {/* Dialog: criar / editar */}
      {showDialog && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
          <div className="w-full max-w-md rounded-lg border bg-background p-6 shadow-lg">
            <h2 className="text-lg font-semibold mb-4">{editTarget ? 'Editar Conta' : 'Nova Conta'}</h2>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid gap-1.5">
                <Label htmlFor="acc_name">Nome <span className="text-red-500">*</span></Label>
                <Input id="acc_name" value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} placeholder="Ex: Caixa Principal, Banco Bradesco..." required />
              </div>
              <div className="grid gap-1.5">
                <Label htmlFor="acc_code">Código (opcional)</Label>
                <Input id="acc_code" value={form.code} onChange={(e) => setForm((f) => ({ ...f, code: e.target.value }))} placeholder="001, CAIXA01..." />
              </div>
              <div className="grid gap-1.5">
                <Label htmlFor="acc_type">Tipo</Label>
                <select
                  id="acc_type"
                  value={form.type}
                  onChange={(e) => setForm((f) => ({ ...f, type: e.target.value as FinancialAccountType }))}
                  className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                >
                  <option value="cash">Dinheiro/Caixa</option>
                  <option value="bank">Conta Bancária</option>
                  <option value="digital_wallet">Carteira Digital</option>
                </select>
              </div>
              {!editTarget && (
                <div className="grid gap-1.5">
                  <Label htmlFor="acc_balance">Saldo inicial (R$)</Label>
                  <Input id="acc_balance" value={form.initial_balance} onChange={(e) => setForm((f) => ({ ...f, initial_balance: e.target.value }))} placeholder="0,00" />
                </div>
              )}
              <label className="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" checked={form.is_active} onChange={(e) => setForm((f) => ({ ...f, is_active: e.target.checked }))} className="h-4 w-4 rounded border-input" />
                <span className="text-sm">Conta ativa</span>
              </label>
              <div className="flex gap-3 pt-2">
                <Button type="submit" disabled={creating || updating}>{(creating || updating) ? 'Salvando...' : 'Salvar'}</Button>
                <Button type="button" variant="outline" onClick={() => setShowDialog(false)}>Cancelar</Button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Dialog: confirmar exclusão */}
      {deleteTarget && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
          <div className="w-full max-w-sm rounded-lg border bg-background p-6 shadow-lg">
            <h2 className="text-lg font-semibold mb-2">Excluir conta</h2>
            <p className="text-sm text-muted-foreground mb-4">
              Tem certeza que deseja excluir <strong>{deleteTarget.name}</strong>? Esta ação não pode ser desfeita.
            </p>
            <div className="flex gap-3">
              <Button variant="destructive" disabled={deleting} onClick={handleDelete}>{deleting ? 'Excluindo...' : 'Excluir'}</Button>
              <Button variant="outline" onClick={() => setDeleteTarget(null)}>Cancelar</Button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
