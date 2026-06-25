'use client'

import Link from 'next/link'
import { PanelLeft, Download, Settings } from 'lucide-react'
import { Button }        from '@/components/ui/button'
import { Separator }     from '@/components/ui/separator'
import { TooltipProvider, Tooltip, TooltipTrigger, TooltipContent } from '@/components/ui/tooltip'
import { ThemeToggle }    from './header/theme-toggle'
import { TenantSwitcher } from './header/tenant-switcher'
import { StoreSwitcher }  from './header/store-switcher'
import { UserMenu }       from './header/user-menu'
import { useHasUpdate }   from '@/hooks/use-latest-release'
import { useAuth }        from '@/hooks/use-auth'
import { ROUTES }         from '@/constants'

interface HeaderProps {
  onToggleSidebar?: () => void
}

export function Header({ onToggleSidebar }: HeaderProps) {
  const hasUpdate = useHasUpdate()
  const { hasPermission } = useAuth()

  return (
    <TooltipProvider>
      <header className="sticky top-0 z-30 flex h-14 items-center gap-3 border-b bg-background/95 backdrop-blur supports-backdrop-filter:bg-background/60 px-4">
        {/* Sidebar toggle */}
        <Button
          variant="ghost"
          size="icon"
          onClick={onToggleSidebar}
          className="shrink-0 md:hidden"
          aria-label="Abrir menu"
        >
          <PanelLeft className="h-5 w-5" />
        </Button>

        {/* Company / store switchers on the left */}
        <TenantSwitcher />
        <StoreSwitcher />

        <div className="flex-1" />

        {/* Right actions */}
        <div className="flex items-center gap-1">
          {hasUpdate && (
            <Tooltip>
              <TooltipTrigger asChild>
                <Button variant="ghost" size="icon" className="relative" asChild>
                  <Link href={ROUTES.DOWNLOADS} aria-label="Nova versão disponível">
                    <Download className="h-4 w-4" />
                    <span className="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-red-500" />
                  </Link>
                </Button>
              </TooltipTrigger>
              <TooltipContent>Nova versão disponível</TooltipContent>
            </Tooltip>
          )}

          {/* Settings gear — visible only for users with settings.view */}
          {hasPermission('settings.view') && (
            <Tooltip>
              <TooltipTrigger asChild>
                <Button variant="ghost" size="icon" asChild>
                  <Link href={ROUTES.SYSTEM_SETTINGS} aria-label="Configurações do Sistema">
                    <Settings className="h-4 w-4" />
                  </Link>
                </Button>
              </TooltipTrigger>
              <TooltipContent>Configurações do Sistema</TooltipContent>
            </Tooltip>
          )}

          <Separator orientation="vertical" className="h-5 mx-1" />
          <ThemeToggle />
          <UserMenu />
        </div>
      </header>
    </TooltipProvider>
  )
}
