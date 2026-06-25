import type { Metadata } from 'next'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard } from '@/components/shared/app-card'
import { AuditLogsTable } from '@/features/audit/components/audit-logs-table'

export const metadata: Metadata = { title: 'Auditoria' }

export default function AuditLogsPage() {
  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Auditoria Operacional"
        description="Histórico de todas as ações realizadas no sistema, com rastreabilidade completa."
      />
      <AppCard>
        <AuditLogsTable />
      </AppCard>
    </div>
  )
}
