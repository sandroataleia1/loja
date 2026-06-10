'use client'

import { cn } from '@/lib/utils'

interface StockBadgeProps {
  quantity:  number
  reserved?: number
  variant?:  'default' | 'compact'
}

export function StockBadge({ quantity, reserved = 0, variant = 'default' }: StockBadgeProps) {
  const available = quantity - reserved

  const color =
    available <= 0 ? 'bg-red-100 text-red-700 border-red-200'
    : available <= 5 ? 'bg-yellow-100 text-yellow-700 border-yellow-200'
    : 'bg-green-100 text-green-700 border-green-200'

  if (variant === 'compact') {
    return (
      <span
        className={cn(
          'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium border',
          color,
        )}
      >
        {available}
      </span>
    )
  }

  return (
    <span
      className={cn(
        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium border',
        color,
      )}
    >
      {available} disp.{reserved > 0 ? ` (${reserved} res.)` : ''}
    </span>
  )
}
