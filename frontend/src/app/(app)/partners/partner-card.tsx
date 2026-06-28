'use client'

import { Phone, Mail, Edit2, Trash2 } from 'lucide-react'
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
import type { Partner } from '@/services/partners.service'

function getInitials(name: string) {
  return name.split(' ').map((n) => n[0]).slice(0, 2).join('').toUpperCase()
}

interface PartnerCardProps {
  partner:  Partner
  onEdit:   (uuid: string) => void
  onDelete: (uuid: string) => void
}

export function PartnerCard({ partner, onEdit, onDelete }: PartnerCardProps) {
  return (
    <div className="rounded-lg border bg-card p-4 space-y-3 hover:shadow-sm transition-shadow">
      {/* Header */}
      <div className="flex items-start gap-3">
        <div className={`h-10 w-10 rounded-full flex items-center justify-center font-semibold text-sm shrink-0 ${partner.is_active ? 'bg-teal-100 text-teal-700' : 'bg-gray-100 text-gray-500'}`}>
          {getInitials(partner.name)}
        </div>
        <div className="flex-1 min-w-0">
          <p className="font-semibold text-sm truncate">{partner.name}</p>
          <Badge variant="outline" className="text-[10px] h-5 mt-1">{partner.type_label}</Badge>
          <div className="mt-1">
            <Badge variant={partner.is_active ? 'default' : 'secondary'} className="text-[10px] h-5">
              {partner.is_active ? 'Ativo' : 'Inativo'}
            </Badge>
          </div>
        </div>
      </div>

      {/* Document */}
      {partner.document && (
        <p className="font-mono text-xs text-muted-foreground">
          {formatDocument(partner.document)}
        </p>
      )}

      {/* Contact */}
      <div className="space-y-1">
        {partner.phone && (
          <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
            <Phone className="h-3 w-3 shrink-0" />
            <span>{partner.phone}</span>
          </div>
        )}
        {partner.email && (
          <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
            <Mail className="h-3 w-3 shrink-0" />
            <span className="truncate">{partner.email}</span>
          </div>
        )}
      </div>

      <div className="border-t pt-3 flex items-center justify-between">
        <span className="text-xs text-muted-foreground">{partner.type_label}</span>
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="sm" className="h-7 px-2 text-xs">Ações ▾</Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" className="w-36">
            <DropdownMenuItem className="flex items-center gap-2" onClick={() => onEdit(partner.uuid)}>
              <Edit2 className="h-3.5 w-3.5" /> Editar
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem className="text-destructive flex items-center gap-2" onClick={() => onDelete(partner.uuid)}>
              <Trash2 className="h-3.5 w-3.5" /> Excluir
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>
  )
}
