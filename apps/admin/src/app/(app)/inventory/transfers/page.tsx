'use client'

import { useState } from 'react'
import Link from 'next/link'
import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { useTransfers } from '@/features/inventory/hooks'
import { ROUTES } from '@/constants'
import { cn } from '@/lib/utils'
import type { TransferStatus } from '@store/shared-types'

const STATUS_LABELS: Record<TransferStatus, string> = {
  pending:    'Pendente',
  in_transit: 'Em trânsito',
  received:   'Recebido',
  cancelled:  'Cancelado',
}

const STATUS_COLORS: Record<TransferStatus, string> = {
  pending:    'bg-gray-100 text-gray-700 border-gray-200',
  in_transit: 'bg-blue-100 text-blue-700 border-blue-200',
  received:   'bg-green-100 text-green-700 border-green-200',
  cancelled:  'bg-red-100 text-red-700 border-red-200',
}

function TableHead() {
  return (
    <tr className="border-b bg-muted/50 text-left">
      <th className="px-4 py-3 font-medium text-muted-foreground">Código</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Origem → Destino</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Status</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Data</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Ações</th>
    </tr>
  )
}

export default function TransfersPage() {
  const [status, setStatus] = useState('')

  const { data: transfers = [], isLoading } = useTransfers(
    status ? { status } : undefined,
  )

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Transferências"
        description="Gerencie transferências de estoque entre lojas."
        actions={
          <Button asChild>
            <Link href={`${ROUTES.INVENTORY_TRANSFERS}/create`}>
              <Plus className="mr-2 h-4 w-4" />
              Nova Transferência
            </Link>
          </Button>
        }
      />

      {/* Filter */}
      <div>
        <select
          className="rounded-md border bg-background px-3 py-2 text-sm outline-none ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2"
          value={status}
          onChange={(e) => setStatus(e.target.value)}
        >
          <option value="">Todos os status</option>
          {(Object.entries(STATUS_LABELS) as [TransferStatus, string][]).map(([val, label]) => (
            <option key={val} value={val}>{label}</option>
          ))}
        </select>
      </div>

      {/* Table */}
      <div className="rounded-md border overflow-x-auto">
        <table className="w-full text-sm">
          <thead><TableHead /></thead>
          <tbody>
            {isLoading && Array.from({ length: 5 }).map((_, i) => (
              <tr key={i} className="border-b last:border-0">
                {Array.from({ length: 5 }).map((__, j) => (
                  <td key={j} className="px-4 py-3">
                    <Skeleton className="h-4 w-full" />
                  </td>
                ))}
              </tr>
            ))}

            {!isLoading && transfers.length === 0 && (
              <tr>
                <td colSpan={5} className="px-4 py-10 text-center text-muted-foreground">
                  Nenhuma transferência encontrada.
                </td>
              </tr>
            )}

            {!isLoading && transfers.map((transfer) => {
              const statusColor = STATUS_COLORS[transfer.status] ?? 'bg-gray-100 text-gray-700 border-gray-200'

              return (
                <tr key={transfer.uuid} className="border-b last:border-0 hover:bg-muted/50 transition-colors">
                  <td className="px-4 py-3 font-mono text-xs text-muted-foreground">
                    {transfer.code ?? transfer.uuid.slice(0, 8)}
                  </td>
                  <td className="px-4 py-3">
                    <span className="font-medium">
                      {transfer.origin_store?.name ?? transfer.origin_store_id}
                    </span>
                    <span className="mx-2 text-muted-foreground">→</span>
                    <span className="font-medium">
                      {transfer.destination_store?.name ?? transfer.destination_store_id}
                    </span>
                  </td>
                  <td className="px-4 py-3">
                    <span className={cn(
                      'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium border',
                      statusColor,
                    )}>
                      {transfer.status_label ?? STATUS_LABELS[transfer.status] ?? transfer.status}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-muted-foreground whitespace-nowrap">
                    {new Date(transfer.created_at).toLocaleDateString('pt-BR')}
                  </td>
                  <td className="px-4 py-3">
                    <Button variant="ghost" size="sm" asChild>
                      <Link href={`${ROUTES.INVENTORY_TRANSFERS}/${transfer.uuid}`}>
                        Ver detalhes
                      </Link>
                    </Button>
                  </td>
                </tr>
              )
            })}
          </tbody>
        </table>
      </div>
    </div>
  )
}
