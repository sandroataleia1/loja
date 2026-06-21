import { cn } from '@/lib/utils'
import type { QuoteStatus, OrderStatus } from '@store/shared-types'

const QUOTE_COLORS: Record<QuoteStatus, string> = {
  draft:     'bg-gray-100 text-gray-700',
  sent:      'bg-blue-100 text-blue-700',
  viewed:    'bg-indigo-100 text-indigo-700',
  accepted:  'bg-green-100 text-green-700',
  rejected:  'bg-red-100 text-red-700',
  expired:   'bg-orange-100 text-orange-700',
  converted: 'bg-purple-100 text-purple-700',
  cancelled: 'bg-gray-100 text-gray-500',
}

const ORDER_COLORS: Record<OrderStatus, string> = {
  pending:     'bg-yellow-100 text-yellow-700',
  confirmed:   'bg-blue-100 text-blue-700',
  in_progress: 'bg-indigo-100 text-indigo-700',
  ready:       'bg-cyan-100 text-cyan-700',
  delivered:   'bg-green-100 text-green-700',
  completed:   'bg-emerald-100 text-emerald-700',
  cancelled:   'bg-gray-100 text-gray-500',
}

interface Props {
  status: QuoteStatus | OrderStatus
  label:  string
  type:   'quote' | 'order'
}

export function DocumentStatusBadge({ status, label, type }: Props) {
  const color = type === 'quote'
    ? QUOTE_COLORS[status as QuoteStatus] ?? 'bg-gray-100 text-gray-700'
    : ORDER_COLORS[status as OrderStatus] ?? 'bg-gray-100 text-gray-700'

  return (
    <span className={cn('inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium', color)}>
      {label}
    </span>
  )
}
