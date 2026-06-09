'use client'

import dynamic from 'next/dynamic'
import { DashboardChartCard } from './dashboard-chart-card'
import { useSalesChart } from '../hooks'

const SalesLineChart = dynamic(
  () => import('./sales-line-chart').then((m) => m.SalesLineChart),
  { ssr: false },
)

export function SalesChartSection() {
  const { salesChart, isLoading } = useSalesChart()

  return (
    <DashboardChartCard
      title="Receita & Pedidos"
      subtitle="Evolução dos últimos 30 dias"
      className="lg:col-span-2"
      isLoading={isLoading}
    >
      <SalesLineChart data={salesChart} />
    </DashboardChartCard>
  )
}
