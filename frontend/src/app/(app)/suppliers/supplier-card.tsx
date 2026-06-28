'use client'

import { Building2, Phone, Mail, Edit2, Trash2 } from 'lucide-react'
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
import type { Supplier } from '@store/shared-types'

function getInitials(name: string) {
  return name.split(' ').map((n) => n[0]).slice(0, 2).join('').toUpperCase()
}

interface SupplierCardProps {
  supplier: Supplier
  onEdit:   (uuid: string) => void
  onDelete: (uuid: string) => void
}

export function SupplierCard({ supplier, onEdit, onDelete }: SupplierCardProps) {
  return (
    <div className="rounded-lg border bg-card p-4 space-y-3 hover:shadow-sm transition-shadow">
      {/* Header */}
      <div className="flex items-start gap-3">
        <div className="h-10 w-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-semibold text-sm shrink-0">
          {getInitials(supplier.name)}
        </div>
        <div className="flex-1 min-w-0">
          <p className="font-semibold text-sm truncate">{supplier.name}</p>
          {supplier.trade_name && (
            <p className="text-xs text-muted-foreground truncate">{supplier.trade_name}</p>
          )}
          <div className="mt-1">
            <Badge variant={supplier.is_active ? 'default' : 'secondary'} className="text-[10px] h-5">
              {supplier.is_active ? 'Ativo' : 'Inativo'}
            </Badge>
          </div>
        </div>
      </div>

      {/* Document */}
      <p className="font-mono text-xs text-muted-foreground">
        {formatDocument(supplier.document)}
      </p>

      {/* Contact */}
      <div className="space-y-1">
        {supplier.phone && (
          <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
            <Phone className="h-3 w-3 shrink-0" />
            <span>{supplier.phone}</span>
          </div>
        )}
        {supplier.email && (
          <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
            <Mail className="h-3 w-3 shrink-0" />
            <span className="truncate">{supplier.email}</span>
          </div>
        )}
      </div>

      <div className="border-t pt-3 flex items-center justify-between">
        <Building2 className="h-4 w-4 text-muted-foreground" />
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="sm" className="h-7 px-2 text-xs">Ações ▾</Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" className="w-36">
            <DropdownMenuItem className="flex items-center gap-2" onClick={() => onEdit(supplier.uuid)}>
              <Edit2 className="h-3.5 w-3.5" /> Editar
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem className="text-destructive flex items-center gap-2" onClick={() => onDelete(supplier.uuid)}>
              <Trash2 className="h-3.5 w-3.5" /> Excluir
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>
  )
}
