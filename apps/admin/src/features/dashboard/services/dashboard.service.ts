import { apiGet } from '@/lib/api-client'
import type { DashboardData } from '../types'

export const dashboardService = {
  getDashboardData(): Promise<DashboardData> {
    return apiGet<DashboardData>('/reports/dashboard')
  },
}
