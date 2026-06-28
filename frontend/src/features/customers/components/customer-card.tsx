'use client'

import Link from 'next/link'
import { Phone, Lock, Unlock, Pencil, Trash2, Eye } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { ROUTES } from '@/constants'
import { formatDocument } from '@/lib/formatters'
import type { Customer } from '@store/shared-types'

function getInitials(name: string) {
  return name.split(' ').map((n) => n[0]).slice(0, 2).join('').toUpperCase()
}

function getPrimaryPhone(customer: Customer): string | null {
  if (!customer.contacts?.length) return null
  const p =
    customer.contacts.find((c) => (c.type === 'PHONE' || c.type === 'WHATSAPP') && c.is_primary) ??
    customer.contacts.find((c) => c.type === 'PHONE' || c.type === 'WHATSAPP') ??
    customer.contacts[0]
  return p?.value ?? null
}

function avatarClass(status: string) {
  if (status === 'active')   return 'bg-emerald-100 text-emerald-700'
  if (status === 'blocked')  return 'bg-red-100 text-red-700'
  return 'bg-gray-100 text-gray-500'
}

function StatusBadge({ status }: { status: string }) {
  if (status === 'active')
    return <Badge variant="outline" className="bg-green-50 text-green-700 border-green-200 text-[10px] h-5">Ativo</Badge>
  if (status === 'blocked')
    return <Badge variant="outline" className="bg-red-50 text-red-700 border-red-200 text-[10px] h-5">Bloqueado</Badge>
  return <Badge variant="outline" className="bg-gray-50 text-gray-600 border-gray-200 text-[10px] h-5">Inativo</Badge>
}

interface CustomerCardProps {
  customer: Customer
  onDelete:   (uuid: string) => void
  onBlock:    (uuid: string) => void
  onUnblock:  (uuid: string) => void
}

export function CustomerCard({ customer, onDelete, onBlock, onUnblock }: CustomerCardProps) {
  const c = customer as any
  const status: string       = c.status ?? (customer.is_active ? 'active' : 'inactive')
  const creditLimitCents     = c.credit_limit_cents ?? 0
  const phone                = getPrimaryPhone(customer)

  return (
    <div className="rounded-lg border bg-card p-4 space-y-3 hover:shadow-sm transition-shadow">
      {/* Header: avatar + nome + badges */}
      <div className="flex items-start gap-3">
        <div className={`h-10 w-10 rounded-full flex items-center justify-center font-semibold text-sm shrink-0 ${avatarClass(status)}`}>
          {getInitials(customer.name)}
        </div>
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-1.5 flex-wrap">
            <Link
              href={`${ROUTES.CUSTOMERS}/${customer.uuid}`}
              className="font-semibold text-sm hover:text-primary hover:underline underline-offset-2 transition-colors truncate"
            >
              {customer.name}
            </Link>
            <span className="inline-flex items-center rounded px-1 py-0.5 text-[10px] font-medium bg-muted text-muted-foreground border shrink-0">
              {customer.person_type === 'INDIVIDUAL' ? 'PF' : 'PJ'}
            </span>
          </div>
          {customer.trade_name && (
            <p className="text-xs text-muted-foreground truncate">{customer.trade_name}</p>
          )}
          <div className="mt-1">
            <StatusBadge status={status} />
          </div>
        </div>
      </div>

      {/* Document */}
      <p className="font-mono text-xs text-muted-foreground">
        {formatDocument(customer.document)}
      </p>

      {/* Phone */}
      {phone && (
        <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
          <Phone className="h-3 w-3 shrink-0" />
          <span>{phone}</span>
        </div>
      )}

      <div className="border-t pt-3 flex items-center justify-between">
        <div className="text-xs text-muted-foreground">
          Limite:{' '}
          <span className="font-medium text-foreground">
            {new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(creditLimitCents / 100)}
          </span>
        </div>

        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="sm" className="h-7 px-2 text-xs">
              Ações ▾
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" className="w-44">
            <DropdownMenuItem asChild>
              <Link href={`${ROUTES.CUSTOMERS}/${customer.uuid}`} className="flex items-center gap-2">
                <Eye className="h-3.5 w-3.5" /> Ver detalhes
              </Link>
            </DropdownMenuItem>
            <DropdownMenuItem asChild>
              <Link href={`${ROUTES.CUSTOMERS}/${customer.uuid}/edit`} className="flex items-center gap-2">
                <Pencil className="h-3.5 w-3.5" /> Editar
              </Link>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            {status === 'active' ? (
              <DropdownMenuItem
                className="text-orange-600 flex items-center gap-2"
                onClick={() => onBlock(customer.uuid)}
              >
                <Lock className="h-3.5 w-3.5" /> Bloquear
              </DropdownMenuItem>
            ) : status === 'blocked' ? (
              <DropdownMenuItem
                className="text-green-600 flex items-center gap-2"
                onClick={() => onUnblock(customer.uuid)}
              >
                <Unlock className="h-3.5 w-3.5" /> Desbloquear
              </DropdownMenuItem>
            ) : null}
            {!customer.is_default_consumer && (
              <>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                  className="text-destructive flex items-center gap-2"
                  onClick={() => onDelete(customer.uuid)}
                >
                  <Trash2 className="h-3.5 w-3.5" /> Excluir
                </DropdownMenuItem>
              </>
            )}
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>
  )
}
