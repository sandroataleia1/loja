'use client'

import { Truck, Phone, Mail, Edit2, Trash2 } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { formatDocument } from '@/lib/formatters'
import type { Carrier } from '@/services/carriers.service'

function getInitials(name: string) {
  return name.split(' ').map((n) => n[0]).slice(0, 2).join('').toUpperCase()
}

interface CarrierCardProps {
  carrier:  Carrier
  onEdit:   (uuid: string) => void
  onDelete: (uuid: string) => void
}

export function CarrierCard({ carrier, onEdit, onDelete }: CarrierCardProps) {
  return (
    <div className="rounded-lg border bg-card p-4 space-y-3 hover:shadow-sm transition-shadow">
      {/* Header */}
      <div className="flex items-start gap-3">
        <div className="h-10 w-10 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-semibold text-sm shrink-0">
          {getInitials(carrier.name)}
        </div>
        <div className="flex-1 min-w-0">
          <p className="font-semibold text-sm truncate">{carrier.name}</p>
          {carrier.trade_name && (
            <p className="text-xs text-muted-foreground truncate">{carrier.trade_name}</p>
          )}
          <div className="mt-1">
            <Badge variant={carrier.is_active ? 'default' : 'secondary'} className="text-[10px] h-5">
              {carrier.is_active ? 'Ativa' : 'Inativa'}
            </Badge>
          </div>
        </div>
      </div>

      {/* CNPJ */}
      <p className="font-mono text-xs text-muted-foreground">
        {formatDocument(carrier.cnpj)}
      </p>

      {/* Contact */}
      <div className="space-y-1">
        {carrier.phone && (
          <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
            <Phone className="h-3 w-3 shrink-0" />
            <span>{carrier.phone}</span>
          </div>
        )}
        {carrier.email && (
          <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
            <Mail className="h-3 w-3 shrink-0" />
            <span className="truncate">{carrier.email}</span>
          </div>
        )}
      </div>

      <div className="border-t pt-3 flex items-center justify-between">
        <Truck className="h-4 w-4 text-muted-foreground" />
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="sm" className="h-7 px-2 text-xs">Ações ▾</Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" className="w-36">
            <DropdownMenuItem className="flex items-center gap-2" onClick={() => onEdit(carrier.uuid)}>
              <Edit2 className="h-3.5 w-3.5" /> Editar
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem className="text-destructive flex items-center gap-2" onClick={() => onDelete(carrier.uuid)}>
              <Trash2 className="h-3.5 w-3.5" /> Excluir
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>
  )
}
