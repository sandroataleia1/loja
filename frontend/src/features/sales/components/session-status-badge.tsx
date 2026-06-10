'use client'

import type { CashSessionStatus } from '@store/shared-types'

const STATUS_MAP: Record<CashSessionStatus, { label: string; className: string }> = {
  open:      { label: 'Aberta',     className: 'bg-green-100 text-green-700 border-green-200' },
  closed:    { label: 'Fechada',    className: 'bg-gray-100 text-gray-600 border-gray-200' },
  cancelled: { label: 'Cancelada',  className: 'bg-red-100 text-red-700 border-red-200' },
}

export function SessionStatusBadge({ status }: { status: CashSessionStatus }) {
  const { label, className } = STATUS_MAP[status] ?? { label: status, className: 'bg-gray-100 text-gray-700' }
  return (
    <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium border ${className}`}>
      {label}
    </span>
  )
}
