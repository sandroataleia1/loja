'use client'

import { Badge } from '@/components/ui/badge'
import { AppCard } from '@/components/shared/app-card'
import type { Customer } from '@store/shared-types'

// ── Helpers ──────────────────────────────────────────────────────────────────

function formatCurrency(cents: number): string {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)
}

function formatDate(iso: string | null): string {
  if (!iso) return '—'
  return new Intl.DateTimeFormat('pt-BR').format(new Date(iso))
}

// ── Component ─────────────────────────────────────────────────────────────────

interface CustomerDetailsCardProps {
  customer: Customer
}

export function CustomerDetailsCard({ customer }: CustomerDetailsCardProps) {
  return (
    <AppCard>
      <div className="space-y-4">
        {/* Header */}
        <div className="flex flex-wrap items-start gap-3">
          <div className="flex-1 min-w-0">
            <h2 className="text-xl font-semibold truncate">{customer.name}</h2>
            {customer.trade_name && (
              <p className="text-sm text-muted-foreground">{customer.trade_name}</p>
            )}
          </div>
          <div className="flex flex-wrap items-center gap-2 shrink-0">
            <Badge variant="outline" className="font-mono text-xs">{customer.code}</Badge>
            <Badge variant="outline">
              {customer.person_type_label}
            </Badge>
            {customer.is_active ? (
              <Badge variant="outline" className="bg-green-50 text-green-700 border-green-200 dark:bg-green-950 dark:text-green-300 dark:border-green-800">
                Ativo
              </Badge>
            ) : (
              <Badge variant="outline" className="bg-red-50 text-red-700 border-red-200 dark:bg-red-950 dark:text-red-300 dark:border-red-800">
                Inativo
              </Badge>
            )}
            {customer.is_default_consumer && (
              <Badge variant="secondary">Consumidor Padrão</Badge>
            )}
          </div>
        </div>

        {/* Info grid */}
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 text-sm">
          <InfoRow label="Documento"  value={customer.document   ?? '—'} />
          <InfoRow label="E-mail"     value={customer.email      ?? '—'} />
          <InfoRow label="Nascimento" value={formatDate(customer.birth_date)} />
        </div>

        {/* Tags */}
        {customer.tags && customer.tags.length > 0 && (
          <div className="flex flex-wrap gap-2">
            {customer.tags.map((tag) => (
              <span
                key={tag.uuid}
                className="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium"
              >
                <span
                  className="h-2 w-2 rounded-full"
                  style={{ backgroundColor: tag.color ?? '#94a3b8' }}
                />
                {tag.name}
              </span>
            ))}
          </div>
        )}

        {/* Metrics */}
        <div className="grid gap-3 sm:grid-cols-3 border-t pt-4">
          <MetricCard label="Pedidos"           value={String(customer.total_orders)} />
          <MetricCard label="Total em Compras"  value={formatCurrency(customer.total_purchases)} />
          <MetricCard label="Última Compra"     value={formatDate(customer.last_purchase_at)} />
        </div>
      </div>
    </AppCard>
  )
}

function InfoRow({ label, value }: { label: string; value: string }) {
  return (
    <div>
      <p className="text-xs text-muted-foreground">{label}</p>
      <p className="font-medium">{value}</p>
    </div>
  )
}

function MetricCard({ label, value }: { label: string; value: string }) {
  return (
    <div className="rounded-md bg-muted/40 px-3 py-2">
      <p className="text-xs text-muted-foreground">{label}</p>
      <p className="text-base font-semibold">{value}</p>
    </div>
  )
}
