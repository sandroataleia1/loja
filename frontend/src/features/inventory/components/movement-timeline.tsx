'use client'

import { Skeleton } from '@/components/ui/skeleton'
import { cn } from '@/lib/utils'
import type { StockMovement, MovementType } from '@store/shared-types'

// ── Helpers ───────────────────────────────────────────────────────────────────

function getDotColor(type: MovementType): string {
  if (['in', 'return', 'conditional_return', 'release'].includes(type)) {
    return 'bg-green-500'
  }
  if (['out', 'sale', 'loss', 'conditional_out'].includes(type)) {
    return 'bg-red-500'
  }
  if (['adjustment', 'inventory'].includes(type)) {
    return 'bg-blue-500'
  }
  return 'bg-gray-400'
}

function formatQuantityDelta(type: MovementType, quantity: number): string {
  const isIn = ['in', 'return', 'purchase', 'conditional_return', 'release'].includes(type)
  const isOut = ['out', 'sale', 'loss', 'conditional_out'].includes(type)
  if (isIn)  return `+${quantity}`
  if (isOut) return `-${quantity}`
  return quantity > 0 ? `+${quantity}` : String(quantity)
}

function formatRelativeDate(iso: string): string {
  const now  = Date.now()
  const then = new Date(iso).getTime()
  const diff = now - then

  const minutes = Math.floor(diff / 60_000)
  const hours   = Math.floor(diff / 3_600_000)
  const days    = Math.floor(diff / 86_400_000)

  if (minutes < 1)  return 'agora mesmo'
  if (minutes < 60) return `há ${minutes} min`
  if (hours < 24)   return `há ${hours}h`
  if (days === 1)   return 'ontem'
  if (days < 30)    return `há ${days} dias`
  return new Date(iso).toLocaleDateString('pt-BR')
}

// ── Component ─────────────────────────────────────────────────────────────────

interface MovementTimelineProps {
  movements: StockMovement[]
  isLoading: boolean
}

export function MovementTimeline({ movements, isLoading }: MovementTimelineProps) {
  if (isLoading) {
    return (
      <div className="space-y-4">
        {Array.from({ length: 5 }).map((_, i) => (
          <div key={i} className="flex gap-4">
            <div className="flex flex-col items-center pt-1">
              <Skeleton className="h-3 w-3 rounded-full" />
              {i < 4 && <div className="mt-1 w-px flex-1 bg-border" />}
            </div>
            <div className="flex-1 pb-6 space-y-1.5">
              <Skeleton className="h-4 w-48" />
              <Skeleton className="h-3 w-64" />
            </div>
          </div>
        ))}
      </div>
    )
  }

  if (movements.length === 0) {
    return (
      <div className="flex items-center justify-center py-12 text-muted-foreground text-sm">
        Nenhuma movimentação encontrada.
      </div>
    )
  }

  return (
    <div className="space-y-0">
      {movements.map((movement, index) => {
        const dotColor = getDotColor(movement.type)
        const delta    = formatQuantityDelta(movement.type, movement.quantity)
        const isLast   = index === movements.length - 1
        const deltaPositive = delta.startsWith('+')
        const deltaNegative = delta.startsWith('-')

        return (
          <div key={movement.uuid} className="flex gap-4">
            {/* Timeline line + dot */}
            <div className="flex flex-col items-center pt-1.5">
              <span className={cn('h-2.5 w-2.5 rounded-full shrink-0', dotColor)} />
              {!isLast && <div className="mt-1 w-px flex-1 min-h-4 bg-border" />}
            </div>

            {/* Content */}
            <div className={cn('flex-1 pb-5', isLast && 'pb-0')}>
              <div className="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                <span className="text-sm font-medium">
                  {movement.type_label ?? movement.type}
                </span>
                <span
                  className={cn(
                    'text-sm font-semibold tabular-nums',
                    deltaPositive && 'text-green-600',
                    deltaNegative && 'text-red-600',
                    !deltaPositive && !deltaNegative && 'text-blue-600',
                  )}
                >
                  {delta}
                </span>
              </div>
              <div className="mt-0.5 flex flex-wrap items-center gap-x-2 text-xs text-muted-foreground">
                {movement.variant?.sku && (
                  <span className="font-mono">{movement.variant.sku}</span>
                )}
                {movement.store?.name && (
                  <span>{movement.store.name}</span>
                )}
                <span>{formatRelativeDate(movement.created_at)}</span>
              </div>
              {movement.notes && (
                <p className="mt-1 text-xs text-muted-foreground italic">{movement.notes}</p>
              )}
            </div>
          </div>
        )
      })}
    </div>
  )
}
