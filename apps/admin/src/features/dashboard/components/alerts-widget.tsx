'use client'

import { DashboardAlertCard } from './dashboard-alert-card'
import { useAlerts } from '../hooks'

export function AlertsWidget() {
  const { alerts, isLoading } = useAlerts()
  return <DashboardAlertCard alerts={alerts} isLoading={isLoading} />
}
