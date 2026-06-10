'use client'

import { useState } from 'react'
import Link from 'next/link'
import { Plus, Search } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { ConfirmDialog } from '@/components/shared/confirm-dialog'
import { CustomerTable } from '@/features/customers/components/customer-table'
import { useCustomers, useDeleteCustomer } from '@/features/customers/hooks'
import { ROUTES } from '@/constants'
import type { CustomerFilters } from '@/services/customer.service'

export default function CustomersPage() {
  const [search,   setSearch]   = useState('')
  const [page,     setPage]     = useState(1)
  const [deleteUuid, setDeleteUuid] = useState<string | null>(null)

  // Build filters
  const filters: CustomerFilters = {
    q:        search  || undefined,
    page,
    per_page: 20,
  }

  const { data, isLoading } = useCustomers(filters)
  const { mutate: deleteCustomer, isPending: isDeleting } = useDeleteCustomer()

  const customers = data?.data    ?? []
  const meta      = data?.meta

  function handleDeleteConfirm() {
    if (!deleteUuid) return
    deleteCustomer(deleteUuid, {
      onSuccess: () => {
        toast.success('Cliente excluído com sucesso.')
        setDeleteUuid(null)
      },
      onError: (err) => {
        toast.error(err instanceof Error ? err.message : 'Erro ao excluir cliente.')
        setDeleteUuid(null)
      },
    })
  }

  function handleSearchChange(e: React.ChangeEvent<HTMLInputElement>) {
    setSearch(e.target.value)
    setPage(1)
  }

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Clientes"
        description="Gerencie sua base de clientes."
        actions={
          <Button asChild>
            <Link href={`${ROUTES.CUSTOMERS}/create`}>
              <Plus className="mr-2 h-4 w-4" />
              Novo Cliente
            </Link>
          </Button>
        }
      />

      {/* Search */}
      <div className="relative max-w-sm">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
        <Input
          className="pl-9"
          placeholder="Buscar por nome, documento, e-mail…"
          value={search}
          onChange={handleSearchChange}
        />
      </div>

      {/* Table */}
      <CustomerTable
        customers={customers}
        isLoading={isLoading}
        onDelete={(uuid) => setDeleteUuid(uuid)}
      />

      {/* Pagination */}
      {meta && meta.last_page > 1 && (
        <div className="flex items-center justify-between text-sm text-muted-foreground">
          <span>
            Mostrando {customers.length} de {meta.total} clientes
          </span>
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              disabled={page <= 1}
              onClick={() => setPage((p) => p - 1)}
            >
              Anterior
            </Button>
            <span className="px-2">
              {page} / {meta.last_page}
            </span>
            <Button
              variant="outline"
              size="sm"
              disabled={page >= meta.last_page}
              onClick={() => setPage((p) => p + 1)}
            >
              Próximo
            </Button>
          </div>
        </div>
      )}

      {/* Delete confirmation */}
      <ConfirmDialog
        open={Boolean(deleteUuid)}
        onOpenChange={(open) => { if (!open) setDeleteUuid(null) }}
        onConfirm={handleDeleteConfirm}
        loading={isDeleting}
        title="Excluir cliente?"
        description="Esta ação não pode ser desfeita. Todos os dados do cliente serão removidos permanentemente."
        confirmLabel="Excluir"
      />
    </div>
  )
}
