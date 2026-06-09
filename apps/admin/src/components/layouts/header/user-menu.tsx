'use client'

import { LogOut, Settings, User as UserIcon } from 'lucide-react'
import Link from 'next/link'
import { useAuth } from '@/hooks/use-auth'
import { useLogoutMutation } from '@/hooks/use-logout'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Button } from '@/components/ui/button'
import { getInitials } from '@/lib/utils'
import { ROUTES } from '@/constants'

export function UserMenu() {
  const { user }     = useAuth()
  const { mutate: logout, isPending } = useLogoutMutation()

  if (!user) return null

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" className="relative h-9 w-9 rounded-full" aria-label="Menu do usuário">
          <Avatar className="h-9 w-9">
            <AvatarFallback className="text-xs bg-primary text-primary-foreground">
              {getInitials(user.name)}
            </AvatarFallback>
          </Avatar>
        </Button>
      </DropdownMenuTrigger>

      <DropdownMenuContent className="w-56" align="end" forceMount>
        <DropdownMenuLabel className="font-normal">
          <div className="flex flex-col space-y-1">
            <p className="text-sm font-medium leading-none">{user.name}</p>
            <p className="text-xs leading-none text-muted-foreground">{user.email}</p>
          </div>
        </DropdownMenuLabel>

        <DropdownMenuSeparator />

        <DropdownMenuGroup>
          <DropdownMenuItem asChild>
            <Link href={ROUTES.SETTINGS} className="flex items-center gap-2 cursor-pointer">
              <Settings className="h-4 w-4" />
              Configurações
            </Link>
          </DropdownMenuItem>
        </DropdownMenuGroup>

        <DropdownMenuSeparator />

        <DropdownMenuItem
          className="text-destructive focus:text-destructive cursor-pointer"
          onClick={() => logout()}
          disabled={isPending}
        >
          <LogOut className="h-4 w-4" />
          {isPending ? 'Saindo...' : 'Sair'}
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  )
}
