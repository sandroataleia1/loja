'use client'

import type { FinancialEntryStatus } from '@/types/financial'

const STATUS_MAP: Record<FinancialEntryStatus, { label: string; className: string }> = {
  pending:        { label: 'Pendente',            className: 'bg-yellow-100 text-yellow-700 border-yellow-200' },
  paid:           { label: 'Pago',                className: 'bg-green-100 text-green-700 border-green-200'   },
  overdue:        { label: 'Vencido',             className: 'bg-red-100 text-red-700 border-red-200'         },
  cancelled:      { label: 'Cancelado',           className: 'bg-gray-100 text-gray-600 border-gray-200'      },
  partially_paid: { label: 'Parcialmente pago',   className: 'bg-orange-100 text-orange-700 border-orange-200'},
}

export function EntryStatusBadge({ status }: { status: FinancialEntryStatus }) {
  const { label, className } = STATUS_MAP[status] ?? { label: status, className: 'bg-gray-100 text-gray-700' }
  return (
    <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium border ${className}`}>
      {label}
    </span>
  )
}
