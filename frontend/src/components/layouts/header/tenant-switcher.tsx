'use client'

import { Building2, ChevronDown } from 'lucide-react'
import { useAuth } from '@/hooks/use-auth'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
  DropdownMenuItem,
} from '@/components/ui/dropdown-menu'

export function TenantSwitcher() {
  const { tenant } = useAuth()

  if (!tenant) return null

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button
          variant="ghost"
          size="sm"
          className="flex items-center gap-2 max-w-[200px] truncate"
        >
          <Building2 className="h-4 w-4 shrink-0 text-muted-foreground" />
          <span className="truncate text-sm font-medium">{tenant.trade_name}</span>
          <ChevronDown className="h-3 w-3 shrink-0 opacity-50" />
        </Button>
      </DropdownMenuTrigger>

      <DropdownMenuContent align="start" className="w-64">
        <DropdownMenuLabel className="text-xs">Empresa ativa</DropdownMenuLabel>
        <DropdownMenuSeparator />

        {/* Current tenant — active */}
        <DropdownMenuItem className="flex flex-col items-start gap-0.5 py-2">
          <span className="font-medium text-sm">{tenant.trade_name}</span>
          {tenant.code && (
            <span className="text-xs text-muted-foreground">{tenant.code}</span>
          )}
        </DropdownMenuItem>

        <DropdownMenuSeparator />
        <DropdownMenuLabel className="text-xs text-muted-foreground">
          Múltiplas empresas disponíveis em planos superiores.
        </DropdownMenuLabel>
      </DropdownMenuContent>
    </DropdownMenu>
  )
}
