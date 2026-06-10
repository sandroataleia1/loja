'use client'

import { TrendingUp, TrendingDown, Minus } from 'lucide-react'
import { cn } from '@/lib/utils'
import { DashboardTableCard } from './dashboard-table-card'
import { useTopProducts } from '../hooks'
import type { DashboardTopProduct } from '../types'

function fmtCurrency(v: number) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', maximumFractionDigits: 0 }).format(v)
}

function ProductRow({ product, rank }: { product: DashboardTopProduct; rank: number }) {
  const TrendIcon =
    product.trend === 'up'
      ? TrendingUp
      : product.trend === 'down'
        ? TrendingDown
        : Minus

  return (
    <div className="flex items-center gap-3 py-2.5 border-b last:border-0">
      <span className="w-5 shrink-0 text-center text-xs font-bold text-muted-foreground">{rank}</span>
      <div className="min-w-0 flex-1">
        <p className="truncate text-xs font-medium">{product.name}</p>
        <p className="text-xs text-muted-foreground">{product.sku} &middot; {product.quantity} un.</p>
      </div>
      <div className="flex shrink-0 items-center gap-1">
        <TrendIcon
          className={cn(
            'h-3 w-3',
            product.trend === 'up'   && 'text-emerald-500',
            product.trend === 'down' && 'text-red-500',
            product.trend === 'neutral' && 'text-muted-foreground',
          )}
        />
        <span className="text-xs font-semibold">{fmtCurrency(product.revenue)}</span>
      </div>
    </div>
  )
}

export function TopProductsWidget() {
  const { topProducts, isLoading } = useTopProducts()

  return (
    <DashboardTableCard title="Top Produtos" subtitle="Maior receita no mês" isLoading={isLoading}>
      {topProducts.map((product, i) => (
        <ProductRow key={product.id} product={product} rank={i + 1} />
      ))}
    </DashboardTableCard>
  )
}
