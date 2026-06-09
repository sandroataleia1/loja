'use client'

import { cn } from '@/lib/utils'
import type { ConditionalStatus, ConditionalStatusHistory } from '@store/shared-types'

// ── Helpers ───────────────────────────────────────────────────────────────────

function getDotColor(status: ConditionalStatus): string {
  switch (status) {
    case 'open':                return 'bg-blue-500'
    case 'partially_returned':  return 'bg-yellow-500'
    case 'returned':            return 'bg-gray-400'
    case 'partially_converted': return 'bg-orange-500'
    case 'converted':           return 'bg-green-500'
    case 'overdue':             return 'bg-red-500'
    case 'cancelled':           return 'bg-gray-400'
    default:                    return 'bg-gray-400'
  }
}

const STATUS_LABEL: Record<ConditionalStatus, string> = {
  open:                'Aberto',
  partially_returned:  'Devolução Parcial',
  returned:            'Devolvido',
  partially_converted: 'Conversão Parcial',
  converted:           'Convertido',
  overdue:             'Vencido',
  cancelled:           'Cancelado',
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

interface ConditionalTimelineProps {
  history: ConditionalStatusHistory[]
}

export function ConditionalTimeline({ history }: ConditionalTimelineProps) {
  if (history.length === 0) {
    return (
      <div className="flex items-center justify-center py-12 text-muted-foreground text-sm">
        Nenhum histórico de status encontrado.
      </div>
    )
  }

  return (
    <div className="space-y-0">
      {history.map((entry, index) => {
        const dotColor = getDotColor(entry.current_status)
        const isLast   = index === history.length - 1
        const label    = STATUS_LABEL[entry.current_status] ?? entry.current_status

        return (
          <div key={entry.uuid} className="flex gap-4">
            {/* Timeline line + dot */}
            <div className="flex flex-col items-center pt-1.5">
              <span className={cn('h-2.5 w-2.5 rounded-full shrink-0', dotColor)} />
              {!isLast && <div className="mt-1 w-px flex-1 min-h-4 bg-border" />}
            </div>

            {/* Content */}
            <div className={cn('flex-1 pb-5', isLast && 'pb-0')}>
              <div className="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                <span className="text-sm font-medium">{label}</span>
                {entry.previous_status && (
                  <span className="text-xs text-muted-foreground">
                    ← {STATUS_LABEL[entry.previous_status] ?? entry.previous_status}
                  </span>
                )}
              </div>
              <div className="mt-0.5 flex flex-wrap items-center gap-x-2 text-xs text-muted-foreground">
                <span>{formatRelativeDate(entry.changed_at)}</span>
                {entry.changed_by && (
                  <span>por {entry.changed_by}</span>
                )}
              </div>
            </div>
          </div>
        )
      })}
    </div>
  )
}
