'use client'

import { Badge } from '@/components/ui/badge'
import type { SaleStatus } from '@store/shared-types'

const STATUS_MAP: Record<SaleStatus, { label: string; className: string }> = {
  draft:               { label: 'Rascunho',    className: 'bg-gray-100 text-gray-700 border-gray-200' },
  pending:             { label: 'Pendente',     className: 'bg-yellow-100 text-yellow-700 border-yellow-200' },
  completed:           { label: 'Concluída',   className: 'bg-green-100 text-green-700 border-green-200' },
  cancelled:           { label: 'Cancelada',   className: 'bg-red-100 text-red-700 border-red-200' },
  partially_refunded:  { label: 'Parcialmente Estornada', className: 'bg-orange-100 text-orange-700 border-orange-200' },
  refunded:            { label: 'Estornada',   className: 'bg-gray-100 text-gray-600 border-gray-200' },
}

export function SaleStatusBadge({ status }: { status: SaleStatus }) {
  const { label, className } = STATUS_MAP[status] ?? { label: status, className: 'bg-gray-100 text-gray-700' }
  return (
    <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium border ${className}`}>
      {label}
    </span>
  )
}
