'use client'

import Link from 'next/link'
import { Eye, Pencil, Trash2 } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { ROUTES } from '@/constants'
import type { Customer } from '@store/shared-types'

// ── Helpers ──────────────────────────────────────────────────────────────────

function formatDocument(document: string | null, personType: Customer['person_type']): string {
  if (!document) return '—'
  const digits = document.replace(/\D/g, '')
  if (personType === 'INDIVIDUAL' && digits.length === 11) {
    return digits.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')
  }
  if (personType === 'COMPANY' && digits.length === 14) {
    return digits.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5')
  }
  return document
}

function getPrimaryContact(customer: Customer): string {
  if (!customer.contacts || customer.contacts.length === 0) return '—'
  const primary = customer.contacts.find((c) => c.is_primary) ?? customer.contacts[0]
  return primary ? `${primary.type_label}: ${primary.value}` : '—'
}

// ── Table ────────────────────────────────────────────────────────────────────

interface CustomerTableProps {
  customers:  Customer[]
  isLoading:  boolean
  onDelete:   (uuid: string) => void
}

export function CustomerTable({ customers, isLoading, onDelete }: CustomerTableProps) {
  if (isLoading) {
    return (
      <div className="rounded-md border overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <TableHead />
          </thead>
          <tbody>
            {Array.from({ length: 4 }).map((_, i) => (
              <tr key={i} className="border-b last:border-0">
                {Array.from({ length: 7 }).map((__, j) => (
                  <td key={j} className="px-4 py-3">
                    <Skeleton className="h-4 w-full" />
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    )
  }

  if (customers.length === 0) {
    return (
      <div className="rounded-md border">
        <table className="w-full text-sm">
          <thead>
            <TableHead />
          </thead>
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
    <div className="rounded-md border overflow-x-auto">
      <table className="w-full text-sm">
        <thead>
          <TableHead />
        </thead>
        <tbody>
          {customers.map((customer) => (
            <tr key={customer.uuid} className="border-b last:border-0 hover:bg-muted/50 transition-colors">
              <td className="px-4 py-3 font-mono text-xs text-muted-foreground">{customer.code}</td>
              <td className="px-4 py-3 font-medium">
                <div>{customer.name}</div>
                {customer.trade_name && (
                  <div className="text-xs text-muted-foreground">{customer.trade_name}</div>
                )}
              </td>
              <td className="px-4 py-3 text-muted-foreground">
                {formatDocument(customer.document, customer.person_type)}
              </td>
              <td className="px-4 py-3 text-muted-foreground">{customer.email ?? '—'}</td>
              <td className="px-4 py-3 text-muted-foreground">{getPrimaryContact(customer)}</td>
              <td className="px-4 py-3">
                {customer.is_active ? (
                  <Badge variant="outline" className="bg-green-50 text-green-700 border-green-200 dark:bg-green-950 dark:text-green-300 dark:border-green-800">
                    Ativo
                  </Badge>
                ) : (
                  <Badge variant="outline" className="bg-red-50 text-red-700 border-red-200 dark:bg-red-950 dark:text-red-300 dark:border-red-800">
                    Inativo
                  </Badge>
                )}
              </td>
              <td className="px-4 py-3">
                <div className="flex items-center gap-1">
                  <Button variant="ghost" size="icon" asChild>
                    <Link href={`${ROUTES.CUSTOMERS}/${customer.uuid}`} aria-label="Ver detalhes">
                      <Eye className="h-4 w-4" />
                    </Link>
                  </Button>
                  {!customer.is_default_consumer && (
                    <Button
                      variant="ghost"
                      size="icon"
                      className="text-destructive hover:text-destructive"
                      aria-label="Excluir cliente"
                      onClick={() => onDelete(customer.uuid)}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  )}
                </div>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}

function TableHead() {
  return (
    <tr className="border-b bg-muted/50 text-left">
      <th className="px-4 py-3 font-medium text-muted-foreground">Código</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Nome</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Documento</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">E-mail</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Contato Principal</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Status</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Ações</th>
    </tr>
  )
}
