'use client'

import { useState } from 'react'
import Link from 'next/link'
import { Plus, Search } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { InventoryTable } from '@/features/inventory/components/inventory-table'
import { useInventoryBalances, useStores } from '@/features/inventory/hooks'
import { ROUTES } from '@/constants'
import type { InventoryFilters } from '@/services/inventory.service'

export default function InventoryPage() {
  const [search,   setSearch]   = useState('')
  const [storeId,  setStoreId]  = useState('')
  const [lowStock, setLowStock] = useState(false)
  const [page,     setPage]     = useState(1)

  const { data: stores = [] } = useStores()

  const filters: InventoryFilters = {
    q:         search   || undefined,
    store_id:  storeId  || undefined,
    low_stock: lowStock || undefined,
    page,
    per_page:  20,
  }

  const { data, isLoading } = useInventoryBalances(filters)
  const balances = data?.data ?? []
  const meta     = data?.meta

  function handleSearchChange(e: React.ChangeEvent<HTMLInputElement>) {
    setSearch(e.target.value)
    setPage(1)
  }

  function handleStoreChange(e: React.ChangeEvent<HTMLSelectElement>) {
    setStoreId(e.target.value)
    setPage(1)
  }

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Estoque"
        description="Saldos de estoque por loja e variante."
        actions={
          <Button asChild>
            <Link href={`${ROUTES.INVENTORY_ADJUSTMENTS}/create`}>
              <Plus className="mr-2 h-4 w-4" />
              Novo Ajuste
            </Link>
          </Button>
        }
      />

      {/* Filters */}
      <div className="flex flex-wrap gap-3">
        <div className="relative w-64">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
          <Input
            className="pl-9"
            placeholder="Buscar por SKU ou produto…"
            value={search}
            onChange={handleSearchChange}
          />
        </div>

        <select
          className="rounded-md border bg-background px-3 py-2 text-sm outline-none ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2"
          value={storeId}
          onChange={handleStoreChange}
        >
          <option value="">Todas as lojas</option>
          {stores.map((s) => (
            <option key={s.uuid} value={s.uuid}>{s.name}</option>
          ))}
        </select>

        <label className="flex items-center gap-2 cursor-pointer text-sm">
          <input
            type="checkbox"
            className="accent-primary"
            checked={lowStock}
            onChange={(e) => { setLowStock(e.target.checked); setPage(1) }}
          />
          Apenas estoque baixo
        </label>
      </div>

      {/* Table */}
      <InventoryTable balances={balances} isLoading={isLoading} />

      {/* Pagination */}
      {meta && meta.last_page > 1 && (
        <div className="flex items-center justify-between text-sm text-muted-foreground">
          <span>
            Mostrando {balances.length} de {meta.total} itens
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
            <span className="px-2">{page} / {meta.last_page}</span>
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
    </div>
  )
}
