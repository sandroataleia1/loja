'use client'

import dynamic from 'next/dynamic'
import { DashboardChartCard } from './dashboard-chart-card'
import { useChannelChart } from '../hooks'

const ChannelPieChart = dynamic(
  () => import('./channel-pie-chart').then((m) => m.ChannelPieChart),
  { ssr: false },
)

export function ChannelChartSection() {
  const { channelChart, isLoading } = useChannelChart()

  return (
    <DashboardChartCard
      title="Canais de Venda"
      subtitle="Participação no mês"
      isLoading={isLoading}
    >
      <ChannelPieChart data={channelChart} />
    </DashboardChartCard>
  )
}
