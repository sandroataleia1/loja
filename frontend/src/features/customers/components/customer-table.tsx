'use client'

import Link from 'next/link'
import { Eye, Pencil, Trash2, Lock, Unlock } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import { ROUTES } from '@/constants'
import { formatDocument } from '@/lib/formatters'
import type { Customer } from '@store/shared-types'

// ── Helpers ──────────────────────────────────────────────────────────────────

function formatCurrency(value: number): string {
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function getPrimaryPhone(customer: Customer): string {
  if (!customer.contacts || customer.contacts.length === 0) return '—'
  const phone =
    customer.contacts.find((c) => (c.type === 'PHONE' || c.type === 'WHATSAPP') && c.is_primary) ??
    customer.contacts.find((c) => c.type === 'PHONE' || c.type === 'WHATSAPP') ??
    customer.contacts[0]
  return phone?.value ?? '—'
}

function StatusBadge({ status }: { status: string }) {
  if (status === 'active') {
    return (
      <Badge variant="outline" className="bg-green-50 text-green-700 border-green-200 dark:bg-green-950 dark:text-green-300 dark:border-green-800">
        Ativo
      </Badge>
    )
  }
  if (status === 'blocked') {
    return (
      <Badge variant="outline" className="bg-red-50 text-red-700 border-red-200 dark:bg-red-950 dark:text-red-300 dark:border-red-800">
        Bloqueado
      </Badge>
    )
  }
  return (
    <Badge variant="outline" className="bg-gray-50 text-gray-600 border-gray-200 dark:bg-gray-900 dark:text-gray-400 dark:border-gray-700">
      Inativo
    </Badge>
  )
}

// ── Table ────────────────────────────────────────────────────────────────────

interface CustomerTableProps {
  customers:  Customer[]
  isLoading:  boolean
  onDelete:   (uuid: string) => void
  onBlock:    (uuid: string) => void
  onUnblock:  (uuid: string) => void
}

export function CustomerTable({ customers, isLoading, onDelete, onBlock, onUnblock }: CustomerTableProps) {
  if (isLoading) {
    return (
      <>
        {/* Mobile loading */}
        <div className="md:hidden space-y-3">
          {Array.from({ length: 3 }).map((_, i) => (
            <div key={i} className="rounded-lg border p-4 space-y-3">
              <Skeleton className="h-4 w-3/4" />
              <Skeleton className="h-4 w-1/2" />
              <Skeleton className="h-4 w-1/3" />
            </div>
          ))}
        </div>
        {/* Desktop loading */}
        <div className="hidden md:block rounded-md border overflow-hidden">
          <table className="w-full text-sm">
            <thead><TableHead /></thead>
            <tbody>
              {Array.from({ length: 4 }).map((_, i) => (
                <tr key={i} className="border-b last:border-0">
                  {Array.from({ length: 7 }).map((__, j) => (
                    <td key={j} className="px-4 py-3"><Skeleton className="h-4 w-full" /></td>
                  ))}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </>
    )
  }

  if (customers.length === 0) {
    return (
      <div className="rounded-md border">
        <table className="w-full text-sm">
          <thead><TableHead /></thead>
          <tbody>
            <tr>
              <td colSpan={7} className="px-4 py-10 text-center text-muted-foreground">
                Nenhum cliente encontrado.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    )
  }

  return (
    <>
      {/* ── Mobile cards ── */}
      <div className="md:hidden space-y-3">
        {customers.map((customer) => {
          const c = customer as any
          const customerStatus: string = c.status ?? (customer.is_active ? 'active' : 'inactive')
          const creditLimitCents: number = c.credit_limit_cents ?? 0

          return (
            <div key={customer.uuid} className="rounded-lg border p-4 space-y-3">
              <div className="flex items-start gap-2 flex-wrap">
                <Link
                  href={`${ROUTES.CUSTOMERS}/${customer.uuid}`}
                  className="font-medium hover:text-primary hover:underline underline-offset-2 transition-colors"
                >
                  {customer.name}
                </Link>
                <span className="inline-flex items-center rounded px-1 py-0.5 text-[10px] font-medium bg-muted text-muted-foreground border">
                  {customer.person_type === 'INDIVIDUAL' ? 'PF' : 'PJ'}
                </span>
                <StatusBadge status={customerStatus} />
              </div>

              <div className="flex items-center gap-3 text-sm text-muted-foreground">
                <span className="font-mono text-xs">{formatDocument(customer.document)}</span>
                <span>·</span>
                <span>{getPrimaryPhone(customer)}</span>
              </div>

              <div className="text-sm text-muted-foreground">
                Limite: <span className="font-medium text-foreground">{formatCurrency(creditLimitCents / 100)}</span>
              </div>

              <div className="flex items-center gap-1 pt-1 border-t">
                <Link
                  href={`${ROUTES.CUSTOMERS}/${customer.uuid}`}
                  className="flex items-center gap-1 px-2 py-1 rounded text-xs hover:bg-muted transition-colors text-muted-foreground hover:text-foreground"
                >
                  <Eye className="h-3.5 w-3.5" /> Ver
                </Link>
                <Link
                  href={`${ROUTES.CUSTOMERS}/${customer.uuid}/edit`}
                  className="flex items-center gap-1 px-2 py-1 rounded text-xs hover:bg-muted transition-colors text-muted-foreground hover:text-foreground"
                >
                  <Pencil className="h-3.5 w-3.5" /> Editar
                </Link>
                {customerStatus === 'active' ? (
                  <button type="button" onClick={() => onBlock(customer.uuid)}
                    className="flex items-center gap-1 px-2 py-1 rounded text-xs hover:bg-muted transition-colors text-muted-foreground hover:text-orange-600">
                    <Lock className="h-3.5 w-3.5" /> Bloquear
                  </button>
                ) : customerStatus === 'blocked' ? (
                  <button type="button" onClick={() => onUnblock(customer.uuid)}
                    className="flex items-center gap-1 px-2 py-1 rounded text-xs hover:bg-muted transition-colors text-muted-foreground hover:text-green-600">
                    <Unlock className="h-3.5 w-3.5" /> Desbloquear
                  </button>
                ) : null}
                {!customer.is_default_consumer && (
                  <button type="button" onClick={() => onDelete(customer.uuid)}
                    className="flex items-center gap-1 px-2 py-1 rounded text-xs hover:bg-muted transition-colors text-muted-foreground hover:text-destructive">
                    <Trash2 className="h-3.5 w-3.5" /> Excluir
                  </button>
                )}
              </div>
            </div>
          )
        })}
      </div>

      {/* ── Desktop table ── */}
      <div className="hidden md:block rounded-md border overflow-x-auto">
        <table className="w-full text-sm">
          <thead><TableHead /></thead>
          <tbody>
            {customers.map((customer) => {
              const c = customer as any
              const customerStatus: string = c.status ?? (customer.is_active ? 'active' : 'inactive')
              const creditLimitCents: number = c.credit_limit_cents ?? 0

              return (
                <tr key={customer.uuid} className="border-b last:border-0 hover:bg-muted/50 transition-colors">
                  <td className="px-4 py-3 font-mono text-xs text-muted-foreground whitespace-nowrap">
                    {c.code ?? customer.code ?? '—'}
                  </td>

                  <td className="px-4 py-3">
                    <div className="flex items-center gap-2">
                      <Link
                        href={`${ROUTES.CUSTOMERS}/${customer.uuid}`}
                        className="font-medium hover:text-primary hover:underline underline-offset-2 transition-colors"
                      >
                        {customer.name}
                      </Link>
                      <span className="inline-flex items-center rounded px-1 py-0.5 text-[10px] font-medium bg-muted text-muted-foreground border">
                        {customer.person_type === 'INDIVIDUAL' ? 'PF' : 'PJ'}
                      </span>
                    </div>
                    {customer.trade_name && (
                      <div className="text-xs text-muted-foreground mt-0.5">{customer.trade_name}</div>
                    )}
                  </td>

                  {/* CPF/CNPJ — direto, sem mascaramento */}
                  <td className="px-4 py-3 text-muted-foreground whitespace-nowrap">
                    <span className="font-mono text-xs">{formatDocument(customer.document)}</span>
                  </td>

                  <td className="px-4 py-3 text-muted-foreground whitespace-nowrap">
                    {getPrimaryPhone(customer)}
                  </td>

                  <td className="px-4 py-3 text-muted-foreground whitespace-nowrap">
                    {formatCurrency(creditLimitCents / 100)}
                  </td>

                  <td className="px-4 py-3">
                    <StatusBadge status={customerStatus} />
                  </td>

                  <td className="px-4 py-3">
                    <div className="flex items-center gap-1">
                      <Link href={`${ROUTES.CUSTOMERS}/${customer.uuid}`} title="Ver"
                        className="p-1.5 rounded hover:bg-muted transition-colors text-muted-foreground hover:text-foreground">
                        <Eye className="h-3.5 w-3.5" />
                      </Link>
                      <Link href={`${ROUTES.CUSTOMERS}/${customer.uuid}/edit`} title="Editar"
                        className="p-1.5 rounded hover:bg-muted transition-colors text-muted-foreground hover:text-foreground">
                        <Pencil className="h-3.5 w-3.5" />
                      </Link>
                      {customerStatus === 'active' ? (
                        <button type="button" title="Bloquear" onClick={() => onBlock(customer.uuid)}
                          className="p-1.5 rounded hover:bg-muted transition-colors text-muted-foreground hover:text-orange-600">
                          <Lock className="h-3.5 w-3.5" />
                        </button>
                      ) : customerStatus === 'blocked' ? (
                        <button type="button" title="Desbloquear" onClick={() => onUnblock(customer.uuid)}
                          className="p-1.5 rounded hover:bg-muted transition-colors text-muted-foreground hover:text-green-600">
                          <Unlock className="h-3.5 w-3.5" />
                        </button>
                      ) : null}
                      {!customer.is_default_consumer && (
                        <button type="button" title="Excluir" onClick={() => onDelete(customer.uuid)}
                          className="p-1.5 rounded hover:bg-muted transition-colors text-muted-foreground hover:text-destructive">
                          <Trash2 className="h-3.5 w-3.5" />
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              )
            })}
          </tbody>
        </table>
      </div>
    </>
  )
}

function TableHead() {
  return (
    <tr className="border-b bg-muted/50 text-left">
      <th className="px-4 py-3 font-medium text-muted-foreground">Código</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Nome / Tipo</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">CPF/CNPJ</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Telefone</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Limite Crédito</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Situação</th>
      <th className="px-4 py-3 font-medium text-muted-foreground w-36">Ações</th>
    </tr>
  )
}
