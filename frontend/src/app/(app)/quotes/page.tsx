'use client'

import { useState } from 'react'
import Link from 'next/link'
import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Skeleton } from '@/components/ui/skeleton'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { DocumentStatusBadge } from '@/features/orders/components/document-status-badge'
import { useQuotes } from '@/features/orders/hooks'
import { ROUTES } from '@/constants'
import type { Quote } from '@store/shared-types'

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatBRL(cents: number): string {
  return (cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('pt-BR')
}

// ── Status options ────────────────────────────────────────────────────────────

const STATUS_OPTIONS = [
  { value: '',          label: 'Todos os status' },
  { value: 'draft',     label: 'Rascunho' },
  { value: 'sent',      label: 'Enviado' },
  { value: 'viewed',    label: 'Visualizado' },
  { value: 'accepted',  label: 'Aceito' },
  { value: 'rejected',  label: 'Recusado' },
  { value: 'expired',   label: 'Expirado' },
  { value: 'converted', label: 'Convertido' },
  { value: 'cancelled', label: 'Cancelado' },
]

// ── Row ───────────────────────────────────────────────────────────────────────

function QuoteRow({ quote }: { quote: Quote }) {
  return (
    <tr className="border-b last:border-0 hover:bg-muted/50 transition-colors">
      <td className="px-4 py-3 font-mono text-xs font-medium">{quote.number}</td>
      <td className="px-4 py-3 text-sm">
        {quote.customer ? (
          <span className="font-medium">{quote.customer.name}</span>
        ) : (
          <span className="text-muted-foreground">—</span>
        )}
      </td>
      <td className="px-4 py-3 text-sm text-muted-foreground">{formatDate(quote.created_at)}</td>
      <td className="px-4 py-3 text-sm">
        {quote.valid_until ? (
          <span className={quote.is_expired ? 'text-red-600 font-medium' : 'text-muted-foreground'}>
            {formatDate(quote.valid_until)}
            {quote.is_expired && ' (vencido)'}
          </span>
        ) : '—'}
      </td>
      <td className="px-4 py-3 text-sm text-right tabular-nums font-medium">
        {formatBRL(quote.total_cents)}
      </td>
      <td className="px-4 py-3">
        <DocumentStatusBadge status={quote.status} label={quote.status_label} type="quote" />
      </td>
      <td className="px-4 py-3">
        <Button variant="ghost" size="sm" asChild>
          <Link href={`${ROUTES.QUOTES}/${quote.uuid}`}>Ver</Link>
        </Button>
      </td>
    </tr>
  )
}

// ── Skeleton rows ─────────────────────────────────────────────────────────────

function SkeletonRows() {
  return (
    <>
      {Array.from({ length: 5 }).map((_, i) => (
        <tr key={i} className="border-b">
          {Array.from({ length: 7 }).map((__, j) => (
            <td key={j} className="px-4 py-3"><Skeleton className="h-4 w-full" /></td>
          ))}
        </tr>
      ))}
    </>
  )
}

// ── Page ──────────────────────────────────────────────────────────────────────

export default function QuotesPage() {
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('')
  const [page,   setPage]   = useState(1)

  const params: Record<string, string | number> = { page, per_page: 20 }
  if (search) params.q      = search
  if (status) params.status = status

  const { data, isLoading } = useQuotes(params)
  const quotes = data?.data ?? []
  const meta   = data?.meta

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Orçamentos"
        description="Crie e gerencie orçamentos para clientes."
        actions={
          <Button asChild>
            <Link href={ROUTES.QUOTES_NEW}>
              <Plus className="mr-2 h-4 w-4" />
              Novo Orçamento
            </Link>
          </Button>
        }
      />

      <div className="flex flex-wrap items-center gap-3">
        <Input
          className="w-56"
          placeholder="Buscar por número, cliente…"
          value={search}
          onChange={(e) => { setSearch(e.target.value); setPage(1) }}
        />
        <select
          value={status}
          onChange={(e) => { setStatus(e.target.value); setPage(1) }}
          className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
        >
          {STATUS_OPTIONS.map((opt) => (
            <option key={opt.value} value={opt.value}>{opt.label}</option>
          ))}
        </select>
      </div>

      <div className="rounded-md border overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b bg-muted/50 text-left">
              <th className="px-4 py-3 font-medium text-muted-foreground">Número</th>
              <th className="px-4 py-3 font-medium text-muted-foreground">Cliente</th>
              <th className="px-4 py-3 font-medium text-muted-foreground">Data</th>
              <th className="px-4 py-3 font-medium text-muted-foreground">Validade</th>
              <th className="px-4 py-3 font-medium text-muted-foreground text-right">Total</th>
              <th className="px-4 py-3 font-medium text-muted-foreground">Status</th>
              <th className="px-4 py-3 font-medium text-muted-foreground">Ações</th>
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              <SkeletonRows />
            ) : quotes.length === 0 ? (
              <tr>
                <td colSpan={7} className="px-4 py-10 text-center text-muted-foreground">
                  Nenhum orçamento encontrado.
                </td>
              </tr>
            ) : (
              quotes.map((q: Quote) => <QuoteRow key={q.uuid} quote={q} />)
            )}
          </tbody>
        </table>
      </div>

      {meta && meta.total > meta.per_page && (
        <div className="flex items-center justify-between text-sm text-muted-foreground">
          <span>Mostrando {quotes.length} de {meta.total}</span>
          <div className="flex items-center gap-2">
            <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
              Anterior
            </Button>
            <span className="px-2">{page} / {Math.ceil(meta.total / meta.per_page)}</span>
            <Button
              variant="outline"
              size="sm"
              disabled={quotes.length < meta.per_page}
              onClick={() => setPage((p) => p + 1)}
            >
              Próximo
            </Button>
          </div>
        </div>
      )}
    </div>
  )
}
