'use client'

import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard } from '@/components/shared/app-card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { apiGet } from '@/lib/api-client'

// ── Types ─────────────────────────────────────────────────────────────────────

interface SalesByMethodRow {
  payment_method_id: string | null
  method_name:       string
  count:             number
  total_cents:       number
  discount_cents:    number
  interest_cents:    number
}

interface SalesByConditionRow {
  payment_condition_id: string | null
  condition_name:       string
  count:                number
  total_cents:          number
  discount_cents:       number
  interest_cents:       number
}

interface PaymentTotals {
  sales_count:     number
  received_cents:  number
  discount_cents:  number
  interest_cents:  number
  fine_cents:      number
}

interface PaymentReportResponse {
  from:             string
  to:               string
  totals:           PaymentTotals
  by_method:        SalesByMethodRow[]
  by_condition:     SalesByConditionRow[]
}

function formatBRL(cents: number) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)
}

function toYMD(d: Date) {
  return d.toISOString().slice(0, 10)
}

// ── Fetch ─────────────────────────────────────────────────────────────────────

async function fetchPaymentReport(from: string, to: string): Promise<PaymentReportResponse> {
  return apiGet<PaymentReportResponse>(`/reports/payments?from=${from}&to=${to}`)
}

// ── Component ─────────────────────────────────────────────────────────────────

export default function PaymentReportsPage() {
  const now   = new Date()
  const first = new Date(now.getFullYear(), now.getMonth(), 1)

  const [from, setFrom] = useState(toYMD(first))
  const [to,   setTo]   = useState(toYMD(now))
  const [key,  setKey]  = useState(0)

  const { data, isLoading, isError } = useQuery({
    queryKey: ['payment-report', from, to, key],
    queryFn:  () => fetchPaymentReport(from, to),
  })

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Relatório de Pagamentos"
        description="Vendas por forma de pagamento, condição, descontos e juros."
      />

      {/* Filtros de data */}
      <AppCard title="Período">
        <div className="flex flex-wrap items-end gap-4">
          <div className="space-y-1.5">
            <Label htmlFor="rpt-from">De</Label>
            <Input
              id="rpt-from"
              type="date"
              value={from}
              onChange={(e) => setFrom(e.target.value)}
              className="w-40"
            />
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="rpt-to">Até</Label>
            <Input
              id="rpt-to"
              type="date"
              value={to}
              onChange={(e) => setTo(e.target.value)}
              className="w-40"
            />
          </div>
          <Button onClick={() => setKey((k) => k + 1)} disabled={isLoading}>
            Atualizar
          </Button>
        </div>
      </AppCard>

      {isLoading && <p className="text-sm text-muted-foreground">Carregando...</p>}
      {isError   && <p className="text-sm text-destructive">Erro ao carregar relatório.</p>}

      {data && (
        <>
          {/* Totais */}
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            {[
              { label: 'Vendas',       value: String(data.totals.sales_count) },
              { label: 'Recebido',     value: formatBRL(data.totals.received_cents) },
              { label: 'Descontos',    value: formatBRL(data.totals.discount_cents) },
              { label: 'Juros',        value: formatBRL(data.totals.interest_cents) },
              { label: 'Multas',       value: formatBRL(data.totals.fine_cents) },
            ].map(({ label, value }) => (
              <div key={label} className="rounded-lg border bg-card p-4">
                <p className="text-xs text-muted-foreground">{label}</p>
                <p className="mt-1 text-xl font-semibold">{value}</p>
              </div>
            ))}
          </div>

          {/* Por forma de pagamento */}
          <AppCard title="Vendas por Forma de Pagamento">
            {data.by_method.length === 0 ? (
              <p className="text-sm text-muted-foreground">Sem dados no período.</p>
            ) : (
              <div className="overflow-auto">
                <table className="w-full text-sm">
                  <thead className="border-b">
                    <tr className="text-left text-xs text-muted-foreground">
                      <th className="pb-2 font-medium">Forma</th>
                      <th className="pb-2 font-medium text-right">Qtde</th>
                      <th className="pb-2 font-medium text-right">Total</th>
                      <th className="pb-2 font-medium text-right">Descontos</th>
                      <th className="pb-2 font-medium text-right">Juros</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y">
                    {data.by_method.map((row, i) => (
                      <tr key={i}>
                        <td className="py-2">{row.method_name || 'Não informado'}</td>
                        <td className="py-2 text-right">{row.count}</td>
                        <td className="py-2 text-right">{formatBRL(row.total_cents)}</td>
                        <td className="py-2 text-right text-green-600">{formatBRL(row.discount_cents)}</td>
                        <td className="py-2 text-right text-amber-600">{formatBRL(row.interest_cents)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </AppCard>

          {/* Por condição de pagamento */}
          <AppCard title="Vendas por Condição de Pagamento">
            {data.by_condition.length === 0 ? (
              <p className="text-sm text-muted-foreground">Sem dados no período.</p>
            ) : (
              <div className="overflow-auto">
                <table className="w-full text-sm">
                  <thead className="border-b">
                    <tr className="text-left text-xs text-muted-foreground">
                      <th className="pb-2 font-medium">Condição</th>
                      <th className="pb-2 font-medium text-right">Qtde</th>
                      <th className="pb-2 font-medium text-right">Total</th>
                      <th className="pb-2 font-medium text-right">Descontos</th>
                      <th className="pb-2 font-medium text-right">Juros</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y">
                    {data.by_condition.map((row, i) => (
                      <tr key={i}>
                        <td className="py-2">{row.condition_name || 'Não informado'}</td>
                        <td className="py-2 text-right">{row.count}</td>
                        <td className="py-2 text-right">{formatBRL(row.total_cents)}</td>
                        <td className="py-2 text-right text-green-600">{formatBRL(row.discount_cents)}</td>
                        <td className="py-2 text-right text-amber-600">{formatBRL(row.interest_cents)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </AppCard>
        </>
      )}
    </div>
  )
}
