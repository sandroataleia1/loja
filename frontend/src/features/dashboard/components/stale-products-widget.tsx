'use client'

import { cn } from '@/lib/utils'
import { DashboardTableCard } from './dashboard-table-card'
import { useStaleProducts } from '../hooks'
import type { DashboardStaleProduct } from '../types'

function urgencyClass(days: number): string {
  if (days >= 90) return 'text-red-500'
  if (days >= 60) return 'text-amber-500'
  return 'text-muted-foreground'
}

function ProductRow({ product }: { product: DashboardStaleProduct }) {
  return (
    <div className="flex items-center justify-between py-2.5 border-b last:border-0">
      <div className="min-w-0">
        <p className="truncate text-xs font-medium">{product.name}</p>
        <p className="text-xs text-muted-foreground">{product.sku} &middot; {product.stock} em estoque</p>
      </div>
      <div className="ml-4 shrink-0 text-right">
        <span className={cn('text-xs font-semibold', urgencyClass(product.daysSinceSale))}>
          {product.daysSinceSale}d sem venda
        </span>
      </div>
    </div>
  )
}

export function StaleProductsWidget() {
  const { staleProducts, isLoading } = useStaleProducts()

  return (
    <DashboardTableCard
      title="Produtos Parados"
      subtitle="Sem venda há mais de 30 dias"
      isLoading={isLoading}
    >
      {staleProducts.map((product) => (
        <ProductRow key={product.id} product={product} />
      ))}
    </DashboardTableCard>
  )
}
