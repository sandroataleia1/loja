import type { Metadata } from 'next'
import { DashboardHeader } from '@/features/dashboard/components/dashboard-header'
import { KpiSection } from '@/features/dashboard/components/kpi-section'
import { SalesChartSection } from '@/features/dashboard/components/sales-chart-section'
import { CategoryChartSection } from '@/features/dashboard/components/category-chart-section'
import { ChannelChartSection } from '@/features/dashboard/components/channel-chart-section'
import { RecentSalesWidget } from '@/features/dashboard/components/recent-sales-widget'
import { TopProductsWidget } from '@/features/dashboard/components/top-products-widget'
import { StaleProductsWidget } from '@/features/dashboard/components/stale-products-widget'
import { AlertsWidget } from '@/features/dashboard/components/alerts-widget'

export const metadata: Metadata = { title: 'Dashboard' }

export default function DashboardPage() {
  return (
    <div className="space-y-6">
      <DashboardHeader />

      <KpiSection />

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <SalesChartSection />
        <ChannelChartSection />
      </div>

      <CategoryChartSection />

      <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <RecentSalesWidget />
        <TopProductsWidget />
        <StaleProductsWidget />
        <AlertsWidget />
      </div>
    </div>
  )
}
