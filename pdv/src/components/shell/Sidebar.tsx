import { NavLink } from 'react-router-dom'
import type { LucideIcon } from 'lucide-react'
import {
  ShoppingCart, Package, Receipt, Users,
  Settings, ChevronLeft, ChevronRight,
} from 'lucide-react'
import {
  Tooltip, TooltipContent, TooltipProvider, TooltipTrigger,
} from '@/components/ui/tooltip'
import { Separator } from '@/components/ui/separator'
import { useUiStore } from '@/stores/uiStore'
import { cn } from '@/lib/utils'

interface NavItem {
  to:    string
  icon:  LucideIcon
  label: string
  end?:  boolean
}

const TOP_NAV: NavItem[] = [
  { to: '/',          icon: ShoppingCart, label: 'PDV',       end: true },
  { to: '/products',  icon: Package,      label: 'Produtos' },
  { to: '/sales',     icon: Receipt,      label: 'Vendas' },
  { to: '/customers', icon: Users,        label: 'Clientes' },
]

const BOTTOM_NAV: NavItem[] = [
  { to: '/settings', icon: Settings, label: 'Configurações' },
]

export function Sidebar() {
  const { sidebarExpanded, toggleSidebar } = useUiStore()

  return (
    <TooltipProvider delayDuration={0}>
      <aside
        className={cn(
          'relative flex flex-col border-r bg-card transition-[width] duration-200 ease-in-out shrink-0 overflow-hidden',
          sidebarExpanded ? 'w-sidebar' : 'w-sidebar-collapsed',
        )}
      >
        {/* Brand */}
        <div className="flex items-center h-topbar border-b px-3 shrink-0">
          <ShoppingCart className="w-5 h-5 text-primary shrink-0" />
          {sidebarExpanded && (
            <span className="ml-3 font-bold text-sm tracking-tight whitespace-nowrap animate-fade-in">
              PDV Fashion
            </span>
          )}
        </div>

        {/* Main navigation */}
        <nav className="flex-1 p-2 space-y-0.5 overflow-hidden">
          {TOP_NAV.map((item) => (
            <SidebarNavItem key={item.to} item={item} expanded={sidebarExpanded} />
          ))}
        </nav>

        <Separator />

        {/* Bottom navigation */}
        <div className="p-2 space-y-0.5">
          {BOTTOM_NAV.map((item) => (
            <SidebarNavItem key={item.to} item={item} expanded={sidebarExpanded} />
          ))}
        </div>

        {/* Collapse toggle */}
        <button
          onClick={toggleSidebar}
          title={sidebarExpanded ? 'Recolher menu' : 'Expandir menu'}
          className={cn(
            'absolute -right-3 top-[4.5rem] z-20',
            'flex items-center justify-center w-6 h-6 rounded-full',
            'border bg-card text-muted-foreground shadow-sm',
            'hover:bg-accent hover:text-accent-foreground transition-colors',
            'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
          )}
        >
          {sidebarExpanded
            ? <ChevronLeft className="w-3 h-3" />
            : <ChevronRight className="w-3 h-3" />}
        </button>
      </aside>
    </TooltipProvider>
  )
}

function SidebarNavItem({
  item,
  expanded,
}: {
  item: NavItem
  expanded: boolean
}) {
  const linkClass = ({ isActive }: { isActive: boolean }) =>
    cn(
      'flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-colors',
      'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
      isActive
        ? 'bg-primary/15 text-primary font-medium'
        : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
    )

  const icon = <item.icon className="w-4 h-4 shrink-0" />

  if (!expanded) {
    return (
      <Tooltip>
        <TooltipTrigger asChild>
          <NavLink to={item.to} end={item.end} className={linkClass}>
            {icon}
          </NavLink>
        </TooltipTrigger>
        <TooltipContent side="right" className="font-medium">
          {item.label}
        </TooltipContent>
      </Tooltip>
    )
  }

  return (
    <NavLink to={item.to} end={item.end} className={linkClass}>
      {icon}
      <span className="truncate animate-slide-in-left">{item.label}</span>
    </NavLink>
  )
}
