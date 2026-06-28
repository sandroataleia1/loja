'use client'

import { UserCog, Edit2 } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import type { Seller } from '@/services/sellers.service'

function getInitials(name: string) {
  return name.split(' ').map((n) => n[0]).slice(0, 2).join('').toUpperCase()
}

interface SellerCardProps {
  seller: Seller
  onEdit: (uuid: string) => void
}

export function SellerCard({ seller, onEdit }: SellerCardProps) {
  const name = seller.user?.name ?? '—'

  return (
    <div className="rounded-lg border bg-card p-4 space-y-3 hover:shadow-sm transition-shadow">
      {/* Header */}
      <div className="flex items-start gap-3">
        <div className={`h-10 w-10 rounded-full flex items-center justify-center font-semibold text-sm shrink-0 ${seller.is_active ? 'bg-violet-100 text-violet-700' : 'bg-gray-100 text-gray-500'}`}>
          {getInitials(name)}
        </div>
        <div className="flex-1 min-w-0">
          <p className="font-semibold text-sm truncate">{name}</p>
          {seller.user?.email && (
            <p className="text-xs text-muted-foreground truncate">{seller.user.email}</p>
          )}
          <div className="mt-1">
            <Badge variant={seller.is_active ? 'default' : 'secondary'} className="text-[10px] h-5">
              {seller.is_active ? 'Ativo' : 'Inativo'}
            </Badge>
          </div>
        </div>
      </div>

      {/* Code */}
      {seller.code && (
        <p className="font-mono text-xs text-muted-foreground">
          Cód: {seller.code}
        </p>
      )}

      <div className="border-t pt-3 flex items-center justify-between">
        <div className="text-xs text-muted-foreground">
          {seller.commission_rate != null ? (
            <span>Comissão: <span className="font-medium text-foreground">{seller.commission_rate}%</span></span>
          ) : (
            <UserCog className="h-4 w-4" />
          )}
        </div>

        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="sm" className="h-7 px-2 text-xs">Ações ▾</Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" className="w-36">
            <DropdownMenuItem className="flex items-center gap-2" onClick={() => onEdit(seller.uuid)}>
              <Edit2 className="h-3.5 w-3.5" /> Editar
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>
  )
}
