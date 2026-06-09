'use client'

import { useQuery } from '@tanstack/react-query'
import { QUERY_KEYS } from '@/constants'
import { dashboardService } from '../services/dashboard.service'

function useDashboard() {
  return useQuery({
    queryKey: [QUERY_KEYS.DASHBOARD],
    queryFn:  () => dashboardService.getDashboardData(),
    staleTime: 5 * 60 * 1000,
  })
}

export function useKpis() {
  const { data, isLoading } = useDashboard()
  return { kpis: data?.kpis ?? [], isLoading }
}

export function useSalesChart() {
  const { data, isLoading } = useDashboard()
  return { salesChart: data?.salesChart ?? [], isLoading }
}

export function useCategoryChart() {
  const { data, isLoading } = useDashboard()
  return { categoryChart: data?.categoryChart ?? [], isLoading }
}

export function useChannelChart() {
  const { data, isLoading } = useDashboard()
  return { channelChart: data?.channelChart ?? [], isLoading }
}

export function useAlerts() {
  const { data, isLoading } = useDashboard()
  return { alerts: data?.alerts ?? [], isLoading }
}

export function useRecentSales() {
  const { data, isLoading } = useDashboard()
  return { recentSales: data?.recentSales ?? [], isLoading }
}

export function useTopProducts() {
  const { data, isLoading } = useDashboard()
  return { topProducts: data?.topProducts ?? [], isLoading }
}

export function useStaleProducts() {
  const { data, isLoading } = useDashboard()
  return { staleProducts: data?.staleProducts ?? [], isLoading }
}
