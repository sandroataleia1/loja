'use client'

import { useState, useEffect } from 'react'
import { Plus, Search, Building2, Phone, Mail, Edit2, Trash2 } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { ConfirmDialog } from '@/components/shared/confirm-dialog'
import { useSuppliers, useDeleteSupplier } from '@/features/purchasing/hooks'
import type { SupplierFilters } from '@/services/purchasing.service'
import { SupplierFormDialog } from './supplier-form-dialog'

export default function SuppliersPage() {
  const [search,          setSearch]          = useState('')
  const [debouncedSearch, setDebouncedSearch] = useState('')
  const [page,            setPage]            = useState(1)
  const [deleteUuid,      setDeleteUuid]      = useState<string | null>(null)
  const [editUuid,        setEditUuid]        = useState<string | null>(null)
  const [showCreate,      setShowCreate]      = useState(false)

  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(search), 300)
    return () => clearTimeout(t)
  }, [search])

  const filters: SupplierFilters = { q: debouncedSearch || undefined, page, per_page: 20 }
  const { data, isLoading } = useSuppliers(filters)
  const { mutate: deleteFn, isPending: isDeleting } = useDeleteSupplier()

  const suppliers = data?.data ?? []
  const meta      = data?.meta

  function handleDeleteConfirm() {
    if (!deleteUuid) return
    deleteFn(deleteUuid, {
      onSuccess: () => setDeleteUuid(null),
      onError:   () => toast.error('Erro ao remover fornecedor.'),
    })
  }

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Fornecedores"
        description="Gerencie os fornecedores da sua empresa."
        actions={
          <Button onClick={() => setShowCreate(true)}>
            <Plus className="mr-2 h-4 w-4" />
            Novo Fornecedor
          </Button>
        }
      />

      <div className="flex items-center gap-3">
        <div className="relative flex-1 max-w-sm">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Buscar por nome, CNPJ ou código..."
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1) }}
            className="pl-9"
          />
        </div>
      </div>

      <div className="rounded-lg border bg-card">
        {isLoading ? (
          <div className="p-8 text-center text-sm text-muted-foreground">Carregando...</div>
        ) : suppliers.length === 0 ? (
          <div className="p-8 text-center text-sm text-muted-foreground">
            {search ? 'Nenhum fornecedor encontrado.' : 'Nenhum fornecedor cadastrado.'}
          </div>
        ) : (
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b text-left text-xs text-muted-foreground">
                <th className="px-4 py-3 font-medium">Fornecedor</th>
                <th className="px-4 py-3 font-medium">Documento</th>
                <th className="px-4 py-3 font-medium">Contato</th>
                <th className="px-4 py-3 font-medium">Status</th>
                <th className="px-4 py-3 font-medium w-20"></th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {suppliers.map((s) => (
                <tr key={s.uuid} className="hover:bg-muted/50 transition-colors">
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-2">
                      <Building2 className="h-4 w-4 text-muted-foreground shrink-0" />
                      <div>
                        <p className="font-medium">{s.name}</p>
                        {s.trade_name && <p className="text-xs text-muted-foreground">{s.trade_name}</p>}
                      </div>
                    </div>
                  </td>
                  <td className="px-4 py-3 text-muted-foreground">{s.document ?? '—'}</td>
                  <td className="px-4 py-3">
                    <div className="flex flex-col gap-0.5">
                      {s.phone && (
                        <span className="flex items-center gap-1 text-xs text-muted-foreground">
                          <Phone className="h-3 w-3" /> {s.phone}
                        </span>
                      )}
                      {s.email && (
                        <span className="flex items-center gap-1 text-xs text-muted-foreground">
                          <Mail className="h-3 w-3" /> {s.email}
                        </span>
                      )}
                    </div>
                  </td>
                  <td className="px-4 py-3">
                    <Badge variant={s.is_active ? 'default' : 'secondary'}>
                      {s.is_active ? 'Ativo' : 'Inativo'}
                    </Badge>
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-1">
                      <Button size="icon" variant="ghost" className="h-7 w-7" onClick={() => setEditUuid(s.uuid)}>
                        <Edit2 className="h-3.5 w-3.5" />
                      </Button>
                      <Button size="icon" variant="ghost" className="h-7 w-7 text-destructive hover:text-destructive" onClick={() => setDeleteUuid(s.uuid)}>
                        <Trash2 className="h-3.5 w-3.5" />
                      </Button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      {meta && meta.last_page > 1 && (
        <div className="flex items-center justify-between text-sm">
          <span className="text-muted-foreground">
            {meta.total} fornecedor{meta.total !== 1 ? 'es' : ''}
          </span>
          <div className="flex gap-2">
            <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage(p => p - 1)}>
              Anterior
            </Button>
            <Button variant="outline" size="sm" disabled={page >= meta.last_page} onClick={() => setPage(p => p + 1)}>
              Próxima
            </Button>
          </div>
        </div>
      )}

      {(showCreate || editUuid !== null) && (
        <SupplierFormDialog
          open
          supplierUuid={editUuid}
          onClose={() => { setShowCreate(false); setEditUuid(null) }}
        />
      )}

      <ConfirmDialog
        open={!!deleteUuid}
        onOpenChange={(v) => { if (!v) setDeleteUuid(null) }}
        title="Remover Fornecedor"
        description="Esta ação não pode ser desfeita. O fornecedor será removido permanentemente."
        confirmLabel="Remover"
        loading={isDeleting}
        onConfirm={handleDeleteConfirm}
      />
    </div>
  )
}
