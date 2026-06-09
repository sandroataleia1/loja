'use client'

import { Store as StoreIcon, ChevronDown } from 'lucide-react'
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

export function StoreSwitcher() {
  const { store } = useAuth()

  if (!store) return null

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button
          variant="ghost"
          size="sm"
          className="flex items-center gap-2 max-w-[180px] truncate"
        >
          <StoreIcon className="h-4 w-4 shrink-0 text-muted-foreground" />
          <span className="truncate text-sm font-medium">{store.name}</span>
          <ChevronDown className="h-3 w-3 shrink-0 opacity-50" />
        </Button>
      </DropdownMenuTrigger>

      <DropdownMenuContent align="start" className="w-56">
        <DropdownMenuLabel className="text-xs">Loja ativa</DropdownMenuLabel>
        <DropdownMenuSeparator />

        <DropdownMenuItem className="flex flex-col items-start gap-0.5 py-2">
          <span className="font-medium text-sm">{store.name}</span>
          <span className="text-xs text-muted-foreground">{store.code}</span>
        </DropdownMenuItem>

        <DropdownMenuSeparator />
        <DropdownMenuLabel className="text-xs text-muted-foreground">
          Múltiplas lojas disponíveis em planos superiores.
        </DropdownMenuLabel>
      </DropdownMenuContent>
    </DropdownMenu>
  )
}
