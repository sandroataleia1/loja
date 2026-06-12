'use client'

import type { FinancialEntryType } from '@/types/financial'

const TYPE_MAP: Record<FinancialEntryType, { label: string; className: string }> = {
  income:  { label: 'Receita', className: 'bg-green-100 text-green-700 border-green-200' },
  expense: { label: 'Despesa', className: 'bg-red-100 text-red-700 border-red-200'       },
}

export function EntryTypeBadge({ type }: { type: FinancialEntryType }) {
  const { label, className } = TYPE_MAP[type] ?? { label: type, className: 'bg-gray-100 text-gray-700' }
  return (
    <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium border ${className}`}>
      {label}
    </span>
  )
}
