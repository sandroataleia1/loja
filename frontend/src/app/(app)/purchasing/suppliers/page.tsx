'use client'

import { useState } from 'react'
import Link from 'next/link'
import { Plus, Pencil, Trash2, ChevronLeft, Loader2, Building2, AlertCircle, X } from 'lucide-react'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard }       from '@/components/shared/app-card'
import { Skeleton }      from '@/components/ui/skeleton'
import { Button }        from '@/components/ui/button'
import { Input }         from '@/components/ui/input'
import { Label }         from '@/components/ui/label'
import {
  useSuppliers,
  useCreateSupplier,
  useUpdateSupplier,
  useDeleteSupplier,
} from '@/features/purchasing/hooks'
import { ROUTES } from '@/constants'
import type { Supplier } from '@/types/shared-types'
import type { CreateSupplierPayload } from '@/services/purchasing.service'

// ── Form Dialog ───────────────────────────────────────────────────────────────

function SupplierDialog({
  supplier,
  onClose,
}: {
  supplier?: Supplier
  onClose: () => void
}) {
  const create = useCreateSupplier()
  const update = useUpdateSupplier()
  const isEdit = !!supplier

  const [form, setForm] = useState<CreateSupplierPayload>({
    person_type: supplier?.person_type ?? 'COMPANY',
    name:        supplier?.name        ?? '',
    trade_name:  supplier?.trade_name  ?? '',
    document:    supplier?.document    ?? '',
    email:       supplier?.email       ?? '',
    phone:       supplier?.phone       ?? '',
    notes:       supplier?.notes       ?? '',
  })

  function set(field: keyof CreateSupplierPayload, value: string) {
    setForm((p) => ({ ...p, [field]: value }))
  }

  const isPending = create.isPending || update.isPending

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    if (!form.name.trim()) return

    const payload = { ...form, trade_name: form.trade_name || undefined, document: form.document || undefined, email: form.email || undefined, phone: form.phone || undefined, notes: form.notes || undefined }

    if (isEdit) {
      update.mutate({ uuid: supplier.uuid, data: payload }, { onSuccess: onClose })
    } else {
      create.mutate(payload, { onSuccess: onClose })
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
      <div className="bg-card border rounded-2xl shadow-2xl w-full max-w-lg">
        <div className="flex items-center justify-between px-5 py-4 border-b">
          <p className="font-bold">{isEdit ? 'Editar Fornecedor' : 'Novo Fornecedor'}</p>
          <button onClick={onClose} className="text-muted-foreground hover:text-foreground transition-colors">
            <X className="w-4 h-4" />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-5 space-y-4">
          <div className="space-y-1.5">
            <Label>Tipo de pessoa *</Label>
            <div className="flex gap-3">
              {(['COMPANY', 'INDIVIDUAL'] as const).map((t) => (
                <label key={t} className="flex items-center gap-1.5 cursor-pointer">
                  <input
                    type="radio"
                    value={t}
                    checked={form.person_type === t}
                    onChange={() => set('person_type', t)}
                    className="accent-primary"
                  />
                  <span className="text-sm">{t === 'COMPANY' ? 'Pessoa Jurídica' : 'Pessoa Física'}</span>
                </label>
              ))}
            </div>
          </div>

          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div className="space-y-1.5 sm:col-span-2">
              <Label>Razão social / Nome *</Label>
              <Input value={form.name} onChange={(e) => set('name', e.target.value)} placeholder="Nome do fornecedor" required />
            </div>
            <div className="space-y-1.5">
              <Label>Nome fantasia</Label>
              <Input value={form.trade_name ?? ''} onChange={(e) => set('trade_name', e.target.value)} placeholder="Fantasia" />
            </div>
            <div className="space-y-1.5">
              <Label>{form.person_type === 'COMPANY' ? 'CNPJ' : 'CPF'}</Label>
              <Input value={form.document ?? ''} onChange={(e) => set('document', e.target.value)} placeholder={form.person_type === 'COMPANY' ? '00.000.000/0000-00' : '000.000.000-00'} />
            </div>
            <div className="space-y-1.5">
              <Label>E-mail</Label>
              <Input type="email" value={form.email ?? ''} onChange={(e) => set('email', e.target.value)} placeholder="email@fornecedor.com.br" />
            </div>
            <div className="space-y-1.5">
              <Label>Telefone</Label>
              <Input value={form.phone ?? ''} onChange={(e) => set('phone', e.target.value)} placeholder="(11) 99999-9999" />
            </div>
            <div className="space-y-1.5 sm:col-span-2">
              <Label>Observações</Label>
              <textarea
                value={form.notes ?? ''}
                onChange={(e) => set('notes', e.target.value)}
                rows={2}
                className="w-full rounded-lg border bg-background px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-primary/30"
                placeholder="Condições comerciais, prazo de pagamento…"
              />
            </div>
          </div>

          <div className="flex justify-end gap-3 pt-1">
            <Button type="button" variant="outline" onClick={onClose} disabled={isPending}>Cancelar</Button>
            <Button type="submit" disabled={isPending || !form.name.trim()}>
              {isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              {isEdit ? 'Salvar alterações' : 'Criar fornecedor'}
            </Button>
          </div>
        </form>
      </div>
    </div>
  )
}

// ── Page ──────────────────────────────────────────────────────────────────────

export default function SuppliersPage() {
  const [q,          setQ]          = useState('')
  const [page,       setPage]       = useState(1)
  const [dialog,     setDialog]     = useState<'create' | Supplier | null>(null)

  const { data, isLoading, isError } = useSuppliers({ q: q || undefined, per_page: 20, page })
  const deleteSupplier = useDeleteSupplier()

  const suppliers = data?.data ?? []
  const meta      = data?.meta

  function handleDelete(s: Supplier) {
    if (!confirm(`Remover fornecedor "${s.name}"?`)) return
    deleteSupplier.mutate(s.uuid)
  }

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Fornecedores"
        description="Gerencie os fornecedores da loja."
        actions={
          <div className="flex gap-2">
            <Button variant="outline" size="sm" asChild>
              <Link href={ROUTES.PURCHASING}>
                <ChevronLeft className="mr-1 h-4 w-4" />
                Pedidos
              </Link>
            </Button>
            <Button size="sm" onClick={() => setDialog('create')}>
              <Plus className="mr-1.5 h-3.5 w-3.5" />
              Novo fornecedor
            </Button>
          </div>
        }
      />

      <AppCard className="overflow-hidden">
        {/* Busca */}
        <div className="flex items-center gap-3 px-4 py-3 border-b">
          <Input
            placeholder="Buscar por nome, CNPJ…"
            value={q}
            onChange={(e) => { setQ(e.target.value); setPage(1) }}
            className="h-8 max-w-xs"
          />
          <span className="ml-auto text-xs text-muted-foreground">
            {meta ? `${meta.total} fornecedor${meta.total !== 1 ? 'es' : ''}` : ''}
          </span>
        </div>

        {/* Tabela */}
        {isLoading ? (
          <div className="space-y-3 p-4">
            {Array.from({ length: 8 }).map((_, i) => <Skeleton key={i} className="h-10 w-full rounded-lg" />)}
          </div>
        ) : isError ? (
          <div className="flex items-center gap-2 p-6 text-destructive text-sm">
            <AlertCircle className="w-4 h-4 shrink-0" />
            Erro ao carregar fornecedores.
          </div>
        ) : suppliers.length === 0 ? (
          <div className="flex flex-col items-center gap-3 py-16 text-muted-foreground">
            <Building2 className="w-10 h-10 opacity-30" />
            <p className="text-sm">Nenhum fornecedor encontrado.</p>
            <Button size="sm" onClick={() => setDialog('create')}>Criar primeiro fornecedor</Button>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b bg-muted/30">
                  {['Nome', 'Documento', 'E-mail', 'Telefone', 'Status', ''].map((h) => (
                    <th key={h} className="px-4 py-2.5 text-left text-xs font-medium text-muted-foreground">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {suppliers.map((s) => (
                  <tr key={s.uuid} className="border-b last:border-0 hover:bg-muted/30 transition-colors">
                    <td className="px-4 py-3">
                      <p className="font-medium">{s.name}</p>
                      {s.trade_name && <p className="text-xs text-muted-foreground">{s.trade_name}</p>}
                    </td>
                    <td className="px-4 py-3 text-muted-foreground font-mono text-xs">{s.document ?? '—'}</td>
                    <td className="px-4 py-3 text-muted-foreground">{s.email ?? '—'}</td>
                    <td className="px-4 py-3 text-muted-foreground">{s.phone ?? '—'}</td>
                    <td className="px-4 py-3">
                      <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                        s.is_active
                          ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'
                          : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                      }`}>
                        {s.is_active ? 'Ativo' : 'Inativo'}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-1 justify-end">
                        <button
                          onClick={() => setDialog(s)}
                          className="p-1.5 rounded-lg text-muted-foreground hover:text-foreground hover:bg-accent transition-colors"
                          title="Editar"
                        >
                          <Pencil className="w-3.5 h-3.5" />
                        </button>
                        <button
                          onClick={() => handleDelete(s)}
                          disabled={deleteSupplier.isPending}
                          className="p-1.5 rounded-lg text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors"
                          title="Remover"
                        >
                          <Trash2 className="w-3.5 h-3.5" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {/* Paginação */}
        {meta && meta.last_page > 1 && (
          <div className="flex items-center justify-between px-4 py-3 border-t text-sm">
            <button onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={page <= 1} className="px-3 py-1 rounded-lg border disabled:opacity-40 hover:bg-accent transition-colors">Anterior</button>
            <span className="text-muted-foreground">Página {meta.current_page} de {meta.last_page}</span>
            <button onClick={() => setPage((p) => Math.min(meta.last_page, p + 1))} disabled={page >= meta.last_page} className="px-3 py-1 rounded-lg border disabled:opacity-40 hover:bg-accent transition-colors">Próxima</button>
          </div>
        )}
      </AppCard>

      {dialog && (
        <SupplierDialog
          supplier={dialog === 'create' ? undefined : dialog}
          onClose={() => setDialog(null)}
        />
      )}
    </div>
  )
}
