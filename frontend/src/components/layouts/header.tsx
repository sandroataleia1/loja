'use client'

import { PanelLeft } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import { TooltipProvider } from '@/components/ui/tooltip'
import { ThemeToggle }    from './header/theme-toggle'
import { TenantSwitcher } from './header/tenant-switcher'
import { StoreSwitcher }  from './header/store-switcher'
import { UserMenu }       from './header/user-menu'

interface HeaderProps {
  onToggleSidebar?: () => void
}

export function Header({ onToggleSidebar }: HeaderProps) {
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
          <Separator orientation="vertical" className="h-5 mx-1" />
          <ThemeToggle />
          <UserMenu />
        </div>
      </header>
    </TooltipProvider>
  )
}
