'use client'

import dynamic from 'next/dynamic'
import { DashboardChartCard } from './dashboard-chart-card'
import { useCategoryChart } from '../hooks'

const CategoryBarChart = dynamic(
  () => import('./category-bar-chart').then((m) => m.CategoryBarChart),
  { ssr: false },
)

export function CategoryChartSection() {
  const { categoryChart, isLoading } = useCategoryChart()

  return (
    <DashboardChartCard
      title="Receita por Categoria"
      subtitle="Top categorias no mês"
      isLoading={isLoading}
    >
      <CategoryBarChart data={categoryChart} />
    </DashboardChartCard>
  )
}
