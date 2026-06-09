'use client'

import { cn } from '@/lib/utils'
import type { ConditionalStatus } from '@store/shared-types'

// ── Color map ─────────────────────────────────────────────────────────────────

const STATUS_COLORS: Record<ConditionalStatus, string> = {
  open:                 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950 dark:text-blue-300 dark:border-blue-800',
  partially_returned:   'bg-yellow-50 text-yellow-700 border-yellow-200 dark:bg-yellow-950 dark:text-yellow-300 dark:border-yellow-800',
  returned:             'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
  partially_converted:  'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-950 dark:text-orange-300 dark:border-orange-800',
  converted:            'bg-green-50 text-green-700 border-green-200 dark:bg-green-950 dark:text-green-300 dark:border-green-800',
  overdue:              'bg-red-50 text-red-700 border-red-200 dark:bg-red-950 dark:text-red-300 dark:border-red-800',
  cancelled:            'bg-gray-100 text-gray-500 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700',
}

const STATUS_LABEL_FALLBACK: Record<ConditionalStatus, string> = {
  open:                'Aberto',
  partially_returned:  'Dev. Parcial',
  returned:            'Devolvido',
  partially_converted: 'Conv. Parcial',
  converted:           'Convertido',
  overdue:             'Vencido',
  cancelled:           'Cancelado',
}

// ── Component ─────────────────────────────────────────────────────────────────

interface ConditionalStatusBadgeProps {
  status: ConditionalStatus
  label?: string
}

export function ConditionalStatusBadge({ status, label }: ConditionalStatusBadgeProps) {
  const colorClass = STATUS_COLORS[status] ?? 'bg-gray-100 text-gray-600 border-gray-200'
  const displayLabel = label ?? STATUS_LABEL_FALLBACK[status] ?? status

  return (
    <span
      className={cn(
        'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium',
        colorClass,
      )}
    >
      {displayLabel}
    </span>
  )
}
