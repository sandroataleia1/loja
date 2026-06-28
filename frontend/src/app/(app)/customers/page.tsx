'use client'

import { useState, useEffect } from 'react'
import Link from 'next/link'
import { Plus, Search, Filter, X } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { ConfirmDialog } from '@/components/shared/confirm-dialog'
import { CustomerTable } from '@/features/customers/components/customer-table'
import {
  useCustomers,
  useDeleteCustomer,
  useBlockCustomer,
  useUnblockCustomer,
} from '@/features/customers/hooks'
import { ROUTES } from '@/constants'
import type { CustomerFilters } from '@/services/customer.service'

const SELECT_CLASS =
  'flex h-9 rounded-md border border-input bg-background px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30'

export default function CustomersPage() {
  const [search,           setSearch]           = useState('')
  const [debouncedSearch,  setDebouncedSearch]  = useState('')
  const [page,             setPage]             = useState(1)
  const [status,           setStatus]           = useState<string>('')
  const [personType,       setPersonType]       = useState<string>('')
  const [daysWithoutSale,  setDaysWithoutSale]  = useState<number | undefined>()
  const [deleteUuid,       setDeleteUuid]       = useState<string | null>(null)
  const [blockUuid,        setBlockUuid]        = useState<string | null>(null)
  const [blockReason,      setBlockReason]      = useState('')
  const [showFilterSheet,  setShowFilterSheet]  = useState(false)

  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(search), 300)
    return () => clearTimeout(t)
  }, [search])

  // Build filters
  const filters: CustomerFilters = {
    q:        debouncedSearch || undefined,
    page,
    per_page: 20,
    status:   (status as CustomerFilters['status'])   || undefined,
    person_type: (personType as CustomerFilters['person_type']) || undefined,
    days_without_sale: daysWithoutSale,
  }

  const { data, isLoading }                             = useCustomers(filters)
  const { mutate: deleteCustomer,  isPending: isDeleting  } = useDeleteCustomer()
  const { mutate: blockCustomer,   isPending: isBlocking   } = useBlockCustomer()
  const { mutate: unblockCustomer                          } = useUnblockCustomer()

  const customers = data?.data ?? []
  const meta      = data?.meta

  function handleFilterChange(setter: () => void) {
    setter()
    setPage(1)
  }

  function handleDeleteConfirm() {
    if (!deleteUuid) return
    deleteCustomer(deleteUuid, {
      onSuccess: () => {
        toast.success('Cliente excluído com sucesso.')
        setDeleteUuid(null)
      },
      onError: (err) => {
        toast.error(err instanceof Error ? err.message : 'Erro ao excluir cliente.')
      },
    })
  }

  function handleBlockConfirm() {
    if (!blockUuid || !blockReason.trim()) return
    blockCustomer(
      { uuid: blockUuid, reason: blockReason.trim() },
      {
        onSuccess: () => {
          toast.success('Cliente bloqueado com sucesso.')
          setBlockUuid(null)
          setBlockReason('')
        },
        onError: (err) => {
          toast.error(err instanceof Error ? err.message : 'Erro ao bloquear cliente.')
        },
      },
    )
  }

  // Shared filter controls (used in both inline bar and filter sheet)
  const filterControls = (
    <>
      <select
        className={SELECT_CLASS}
        value={status}
        onChange={(e) => handleFilterChange(() => setStatus(e.target.value))}
      >
        <option value="">Todos os status</option>
        <option value="active">Ativo</option>
        <option value="blocked">Bloqueado</option>
        <option value="inactive">Inativo</option>
      </select>

      <select
        className={SELECT_CLASS}
        value={personType}
        onChange={(e) => handleFilterChange(() => setPersonType(e.target.value))}
      >
        <option value="">PF e PJ</option>
        <option value="INDIVIDUAL">Pessoa Física</option>
        <option value="COMPANY">Pessoa Jurídica</option>
      </select>

      <select
        className={SELECT_CLASS}
        value={daysWithoutSale ?? ''}
        onChange={(e) =>
          handleFilterChange(() =>
            setDaysWithoutSale(e.target.value ? Number(e.target.value) : undefined),
          )
        }
      >
        <option value="">Todos os períodos</option>
        <option value="30">&gt; 30 dias sem venda</option>
        <option value="60">&gt; 60 dias sem venda</option>
        <option value="90">&gt; 90 dias sem venda</option>
      </select>
    </>
  )

  return (
    <>
      {/* Print styles */}
      <style>{`
        @media print {
          .no-print { display: none !important; }
          body { font-size: 11px; }
          table { width: 100%; }
        }
      `}</style>

      <div className="space-y-6">
        <div className="no-print">
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
        </div>

        {/* Filter bar */}
        <div className="no-print">
          {/* Mobile: search + filter button */}
          <div className="flex items-center gap-2 md:hidden">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
              <Input
                className="pl-9"
                placeholder="Buscar por nome, documento, e-mail…"
                value={search}
                onChange={(e) => handleFilterChange(() => setSearch(e.target.value))}
              />
            </div>
            <Button
              variant="outline"
              size="sm"
              onClick={() => setShowFilterSheet(true)}
              className="shrink-0"
            >
              <Filter className="h-4 w-4 mr-1.5" />
              Filtros
              {(status || personType || daysWithoutSale) && (
                <span className="ml-1.5 h-1.5 w-1.5 rounded-full bg-primary" />
              )}
            </Button>
          </div>

          {/* Desktop: all filters inline */}
          <div className="hidden md:flex flex-wrap items-center gap-2">
            <div className="relative flex-1 min-w-48 max-w-sm">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
              <Input
                className="pl-9"
                placeholder="Buscar por nome, documento, e-mail…"
                value={search}
                onChange={(e) => handleFilterChange(() => setSearch(e.target.value))}
              />
            </div>
            {filterControls}
          </div>
        </div>

        {/* Table */}
        <CustomerTable
          customers={customers}
          isLoading={isLoading}
          onDelete={(uuid) => setDeleteUuid(uuid)}
          onBlock={(uuid) => {
            setBlockUuid(uuid)
            setBlockReason('')
          }}
          onUnblock={(uuid) =>
            unblockCustomer(uuid, {
              onSuccess: () => toast.success('Cliente desbloqueado com sucesso.'),
              onError: (err) =>
                toast.error(err instanceof Error ? err.message : 'Erro ao desbloquear cliente.'),
            })
          }
        />

        {/* Pagination */}
        {meta && meta.last_page > 1 && (
          <div className="no-print flex items-center justify-between text-sm text-muted-foreground">
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
          onOpenChange={(open) => {
            if (!open) setDeleteUuid(null)
          }}
          onConfirm={handleDeleteConfirm}
          loading={isDeleting}
          title="Excluir cliente?"
          description="Esta ação não pode ser desfeita. Todos os dados do cliente serão removidos permanentemente."
          confirmLabel="Excluir"
        />

        {/* Block modal */}
        {blockUuid && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div className="bg-card border rounded-2xl shadow-2xl w-full max-w-md flex flex-col">
              <div className="flex items-center justify-between px-5 py-4 border-b">
                <p className="font-bold">Bloquear cliente</p>
                <button
                  type="button"
                  onClick={() => setBlockUuid(null)}
                  className="text-muted-foreground hover:text-foreground text-xl leading-none"
                >
                  &times;
                </button>
              </div>
              <div className="p-5 space-y-4">
                <p className="text-sm text-muted-foreground">
                  Informe o motivo do bloqueio. O cliente não poderá realizar compras enquanto estiver bloqueado.
                </p>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium" htmlFor="block-reason">
                    Motivo do bloqueio *
                  </label>
                  <textarea
                    id="block-reason"
                    className="w-full min-h-24 rounded-md border bg-background px-3 py-2 text-sm outline-none ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 resize-y"
                    placeholder="Descreva o motivo do bloqueio…"
                    value={blockReason}
                    onChange={(e) => setBlockReason(e.target.value)}
                  />
                </div>
              </div>
              <div className="flex justify-end gap-3 px-5 py-4 border-t">
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => setBlockUuid(null)}
                >
                  Cancelar
                </Button>
                <Button
                  type="button"
                  variant="destructive"
                  disabled={!blockReason.trim() || isBlocking}
                  onClick={handleBlockConfirm}
                >
                  {isBlocking ? 'Bloqueando…' : 'Bloquear'}
                </Button>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* ── Filter sheet (mobile) ── */}
      {showFilterSheet && (
        <div className="md:hidden fixed inset-0 z-40">
          {/* Backdrop */}
          <div
            className="absolute inset-0 bg-black/50"
            onClick={() => setShowFilterSheet(false)}
          />
          {/* Sheet */}
          <div className="absolute top-0 right-0 bottom-0 w-72 bg-background border-l shadow-xl flex flex-col">
            <div className="flex items-center justify-between px-4 py-3 border-b">
              <p className="font-semibold">Filtros</p>
              <button
                type="button"
                onClick={() => setShowFilterSheet(false)}
                className="text-muted-foreground hover:text-foreground"
              >
                <X className="h-5 w-5" />
              </button>
            </div>
            <div className="flex-1 overflow-y-auto p-4 space-y-4">
              <div className="space-y-1.5">
                <label className="text-sm font-medium">Status</label>
                <select
                  className={`${SELECT_CLASS} w-full`}
                  value={status}
                  onChange={(e) => handleFilterChange(() => setStatus(e.target.value))}
                >
                  <option value="">Todos os status</option>
                  <option value="active">Ativo</option>
                  <option value="blocked">Bloqueado</option>
                  <option value="inactive">Inativo</option>
                </select>
              </div>
              <div className="space-y-1.5">
                <label className="text-sm font-medium">Tipo de Pessoa</label>
                <select
                  className={`${SELECT_CLASS} w-full`}
                  value={personType}
                  onChange={(e) => handleFilterChange(() => setPersonType(e.target.value))}
                >
                  <option value="">PF e PJ</option>
                  <option value="INDIVIDUAL">Pessoa Física</option>
                  <option value="COMPANY">Pessoa Jurídica</option>
                </select>
              </div>
              <div className="space-y-1.5">
                <label className="text-sm font-medium">Dias sem venda</label>
                <select
                  className={`${SELECT_CLASS} w-full`}
                  value={daysWithoutSale ?? ''}
                  onChange={(e) =>
                    handleFilterChange(() =>
                      setDaysWithoutSale(e.target.value ? Number(e.target.value) : undefined),
                    )
                  }
                >
                  <option value="">Todos os períodos</option>
                  <option value="30">&gt; 30 dias sem venda</option>
                  <option value="60">&gt; 60 dias sem venda</option>
                  <option value="90">&gt; 90 dias sem venda</option>
                </select>
              </div>
            </div>
            <div className="p-4 border-t">
              <Button
                className="w-full"
                onClick={() => setShowFilterSheet(false)}
              >
                Aplicar filtros
              </Button>
            </div>
          </div>
        </div>
      )}
    </>
  )
}
