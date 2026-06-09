'use client'

import Link from 'next/link'
import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { useInventoryAdjustments } from '@/features/inventory/hooks'
import { ROUTES } from '@/constants'
import { cn } from '@/lib/utils'

function TableHead() {
  return (
    <tr className="border-b bg-muted/50 text-left">
      <th className="px-4 py-3 font-medium text-muted-foreground">Data</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Produto / SKU</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Loja</th>
      <th className="px-4 py-3 font-medium text-muted-foreground text-right">Anterior → Novo</th>
      <th className="px-4 py-3 font-medium text-muted-foreground text-right">Diferença</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Motivo</th>
    </tr>
  )
}

export default function AdjustmentsPage() {
  const { data: adjustments = [], isLoading } = useInventoryAdjustments()

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Ajustes de Estoque"
        description="Histórico de ajustes manuais de estoque."
        actions={
          <Button asChild>
            <Link href={`${ROUTES.INVENTORY_ADJUSTMENTS}/create`}>
              <Plus className="mr-2 h-4 w-4" />
              Novo Ajuste
            </Link>
          </Button>
        }
      />

      <div className="rounded-md border overflow-x-auto">
        <table className="w-full text-sm">
          <thead><TableHead /></thead>
          <tbody>
            {isLoading && Array.from({ length: 5 }).map((_, i) => (
              <tr key={i} className="border-b last:border-0">
                {Array.from({ length: 6 }).map((__, j) => (
                  <td key={j} className="px-4 py-3">
                    <Skeleton className="h-4 w-full" />
                  </td>
                ))}
              </tr>
            ))}

            {!isLoading && adjustments.length === 0 && (
              <tr>
                <td colSpan={6} className="px-4 py-10 text-center text-muted-foreground">
                  Nenhum ajuste encontrado.
                </td>
              </tr>
            )}

            {!isLoading && adjustments.map((adj) => {
              const isPositive = adj.difference > 0
              const isNegative = adj.difference < 0

              return (
                <tr key={adj.uuid} className="border-b last:border-0 hover:bg-muted/50 transition-colors">
                  <td className="px-4 py-3 text-muted-foreground whitespace-nowrap">
                    {new Date(adj.created_at).toLocaleDateString('pt-BR')}
                  </td>
                  <td className="px-4 py-3">
                    {adj.variant ? (
                      <span className="font-mono text-xs">{adj.variant.sku}</span>
                    ) : (
                      <span className="text-muted-foreground">—</span>
                    )}
                  </td>
                  <td className="px-4 py-3 text-muted-foreground">
                    {adj.store?.name ?? adj.store_id}
                  </td>
                  <td className="px-4 py-3 text-right tabular-nums text-muted-foreground whitespace-nowrap">
                    {adj.previous_quantity} → {adj.new_quantity}
                  </td>
                  <td className="px-4 py-3 text-right tabular-nums font-semibold whitespace-nowrap">
                    <span
                      className={cn(
                        isPositive && 'text-green-600',
                        isNegative && 'text-red-600',
                      )}
                    >
                      {isPositive ? '+' : ''}{adj.difference}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-muted-foreground max-w-48 truncate">
                    {adj.reason}
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
