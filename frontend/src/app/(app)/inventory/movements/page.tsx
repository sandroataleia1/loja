'use client'

import { useState } from 'react'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { MovementTimeline } from '@/features/inventory/components/movement-timeline'
import { useStockMovements, useStores } from '@/features/inventory/hooks'
import type { MovementFilters } from '@/services/inventory.service'
import type { MovementType } from '@store/shared-types'

const MOVEMENT_TYPE_LABELS: Array<{ value: MovementType | ''; label: string }> = [
  { value: '',                  label: 'Todos os tipos'   },
  { value: 'sale',              label: 'Venda'            },
  { value: 'purchase',          label: 'Compra'           },
  { value: 'transfer',          label: 'Transferência'    },
  { value: 'return',            label: 'Devolução'        },
  { value: 'loss',              label: 'Perda'            },
  { value: 'adjustment',        label: 'Ajuste'           },
  { value: 'inventory',         label: 'Inventário'       },
  { value: 'reserve',           label: 'Reserva'          },
  { value: 'release',           label: 'Liberação'        },
  { value: 'in',                label: 'Entrada'          },
  { value: 'out',               label: 'Saída'            },
]

export default function MovementsPage() {
  const [storeId,  setStoreId]  = useState('')
  const [type,     setType]     = useState('')
  const [dateFrom, setDateFrom] = useState('')
  const [dateTo,   setDateTo]   = useState('')
  const [page,     setPage]     = useState(1)

  const { data: stores = [] } = useStores()

  const filters: MovementFilters = {
    store_id:  storeId  || undefined,
    type:      type     || undefined,
    date_from: dateFrom || undefined,
    date_to:   dateTo   || undefined,
    page,
    per_page:  30,
  }

  const { data, isLoading } = useStockMovements(filters)
  const movements = data?.data ?? []
  const meta      = data?.meta

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Movimentações"
        description="Histórico de todas as movimentações de estoque."
      />

      {/* Filters */}
      <div className="flex flex-wrap gap-3 items-end">
        <div className="space-y-1">
          <Label className="text-xs">Loja</Label>
          <select
            className="rounded-md border bg-background px-3 py-2 text-sm outline-none ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2"
            value={storeId}
            onChange={(e) => { setStoreId(e.target.value); setPage(1) }}
          >
            <option value="">Todas as lojas</option>
            {stores.map((s) => (
              <option key={s.uuid} value={s.uuid}>{s.name}</option>
            ))}
          </select>
        </div>

        <div className="space-y-1">
          <Label className="text-xs">Tipo</Label>
          <select
            className="rounded-md border bg-background px-3 py-2 text-sm outline-none ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2"
            value={type}
            onChange={(e) => { setType(e.target.value); setPage(1) }}
          >
            {MOVEMENT_TYPE_LABELS.map((t) => (
              <option key={t.value} value={t.value}>{t.label}</option>
            ))}
          </select>
        </div>

        <div className="space-y-1">
          <Label className="text-xs">De</Label>
          <Input
            type="date"
            className="w-38"
            value={dateFrom}
            onChange={(e) => { setDateFrom(e.target.value); setPage(1) }}
          />
        </div>

        <div className="space-y-1">
          <Label className="text-xs">Até</Label>
          <Input
            type="date"
            className="w-38"
            value={dateTo}
            onChange={(e) => { setDateTo(e.target.value); setPage(1) }}
          />
        </div>
      </div>

      {/* Timeline */}
      <MovementTimeline movements={movements} isLoading={isLoading} />

      {/* Pagination */}
      {meta && meta.last_page > 1 && (
        <div className="flex items-center justify-between text-sm text-muted-foreground">
          <span>
            Mostrando {movements.length} de {meta.total} movimentações
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
