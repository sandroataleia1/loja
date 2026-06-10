'use client'

import { useState } from 'react'
import Link from 'next/link'
import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Skeleton } from '@/components/ui/skeleton'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { ConditionalStatusBadge } from '@/features/conditional/components/conditional-status-badge'
import { useConditionals } from '@/features/conditional/hooks'
import { ROUTES } from '@/constants'
import type { ConditionalFilters } from '@/services/conditional.service'
import type { Conditional } from '@store/shared-types'

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatBRL(amount: number): string {
  return amount.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function formatDate(iso: string | null): string {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('pt-BR')
}

// ── Status options ────────────────────────────────────────────────────────────

const STATUS_OPTIONS = [
  { value: '',                    label: 'Todos os status' },
  { value: 'open',                label: 'Aberto' },
  { value: 'partially_returned',  label: 'Dev. Parcial' },
  { value: 'returned',            label: 'Devolvido' },
  { value: 'partially_converted', label: 'Conv. Parcial' },
  { value: 'converted',           label: 'Convertido' },
  { value: 'overdue',             label: 'Vencido' },
  { value: 'cancelled',           label: 'Cancelado' },
]

// ── Table head ────────────────────────────────────────────────────────────────

function TableHead() {
  return (
    <tr className="border-b bg-muted/50 text-left">
      <th className="px-4 py-3 font-medium text-muted-foreground">Código</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Cliente</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Loja</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Abertura</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Vencimento</th>
      <th className="px-4 py-3 font-medium text-muted-foreground text-right">Total</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Status</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Ações</th>
    </tr>
  )
}

// ── Skeleton rows ─────────────────────────────────────────────────────────────

function SkeletonRows() {
  return (
    <>
      {Array.from({ length: 5 }).map((_, i) => (
        <tr key={i} className="border-b last:border-0">
          {Array.from({ length: 8 }).map((__, j) => (
            <td key={j} className="px-4 py-3">
              <Skeleton className="h-4 w-full" />
            </td>
          ))}
        </tr>
      ))}
    </>
  )
}

// ── Row ───────────────────────────────────────────────────────────────────────

function ConditionalRow({ conditional }: { conditional: Conditional }) {
  return (
    <tr className="border-b last:border-0 hover:bg-muted/50 transition-colors">
      <td className="px-4 py-3 font-mono text-xs text-muted-foreground">{conditional.code}</td>
      <td className="px-4 py-3 text-sm">
        {conditional.customer ? (
          <div>
            <div className="font-medium">{conditional.customer.name}</div>
            <div className="text-xs text-muted-foreground font-mono">{conditional.customer.code}</div>
          </div>
        ) : '—'}
      </td>
      <td className="px-4 py-3 text-sm text-muted-foreground">
        {conditional.store?.name ?? '—'}
      </td>
      <td className="px-4 py-3 text-sm text-muted-foreground">
        {formatDate(conditional.created_at)}
      </td>
      <td className="px-4 py-3 text-sm">
        <span className={conditional.is_overdue ? 'text-red-600 font-medium' : 'text-muted-foreground'}>
          {formatDate(conditional.due_date ?? conditional.expires_at)}
          {conditional.is_overdue && ' (vencido)'}
        </span>
      </td>
      <td className="px-4 py-3 text-sm text-right tabular-nums font-medium">
        {formatBRL(conditional.total_amount)}
      </td>
      <td className="px-4 py-3">
        <ConditionalStatusBadge
          status={conditional.status}
          label={conditional.status_label}
        />
      </td>
      <td className="px-4 py-3">
        <Button variant="ghost" size="sm" asChild>
          <Link href={`${ROUTES.CONDITIONALS}/${conditional.uuid}`}>
            Ver
          </Link>
        </Button>
      </td>
    </tr>
  )
}

// ── Page ──────────────────────────────────────────────────────────────────────

export default function ConditionalsPage() {
  const [search,  setSearch]  = useState('')
  const [status,  setStatus]  = useState('')
  const [overdue, setOverdue] = useState(false)
  const [page,    setPage]    = useState(1)

  const filters: ConditionalFilters = {
    q:        search  || undefined,
    status:   status  || undefined,
    overdue:  overdue || undefined,
    page,
    per_page: 20,
  }

  const { data, isLoading } = useConditionals(filters)
  const conditionals = data?.data ?? []
  const meta         = data?.meta

  function handleSearchChange(e: React.ChangeEvent<HTMLInputElement>) {
    setSearch(e.target.value)
    setPage(1)
  }

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Condicionais"
        description="Gerencie saídas condicionais de produtos."
        actions={
          <Button asChild>
            <Link href={`${ROUTES.CONDITIONALS}/create`}>
              <Plus className="mr-2 h-4 w-4" />
              Novo Condicional
            </Link>
          </Button>
        }
      />

      {/* Filters */}
      <div className="flex flex-wrap items-center gap-3">
        <div className="relative">
          <Input
            className="w-64"
            placeholder="Buscar por código, cliente…"
            value={search}
            onChange={handleSearchChange}
          />
        </div>

        <select
          value={status}
          onChange={(e) => { setStatus(e.target.value); setPage(1) }}
          className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
        >
          {STATUS_OPTIONS.map((opt) => (
            <option key={opt.value} value={opt.value}>{opt.label}</option>
          ))}
        </select>

        <label className="flex items-center gap-2 text-sm cursor-pointer select-none">
          <input
            type="checkbox"
            checked={overdue}
            onChange={(e) => { setOverdue(e.target.checked); setPage(1) }}
            className="h-4 w-4 rounded border"
          />
          Apenas vencidos
        </label>
      </div>

      {/* Table */}
      <div className="rounded-md border overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <TableHead />
          </thead>
          <tbody>
            {isLoading ? (
              <SkeletonRows />
            ) : conditionals.length === 0 ? (
              <tr>
                <td colSpan={8} className="px-4 py-10 text-center text-muted-foreground">
                  Nenhum condicional encontrado.
                </td>
              </tr>
            ) : (
              conditionals.map((c) => (
                <ConditionalRow key={c.uuid} conditional={c} />
              ))
            )}
          </tbody>
        </table>
      </div>

      {/* Pagination */}
      {meta && meta.last_page > 1 && (
        <div className="flex items-center justify-between text-sm text-muted-foreground">
          <span>
            Mostrando {conditionals.length} de {meta.total} condicionais
          </span>
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              disabled={page <= 1}
              onClick={() => setPage((p) => p - 1)}
            >
              Anterior
            </Button>
            <span className="px-2">{page} / {meta.last_page}</span>
            <Button
              variant="outline"
              size="sm"
              disabled={page >= meta.last_page}
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
