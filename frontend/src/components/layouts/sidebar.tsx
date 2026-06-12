'use client'

import { useState } from 'react'
import Link from 'next/link'
import { usePathname } from 'next/navigation'
import {
  LayoutDashboard,
  Users,
  Settings,
  ChevronLeft,
  ChevronRight,
  Package,
  Warehouse,
  ShoppingCart,
  Banknote,
  TrendingDown,
  TrendingUp,
  Wallet,
  Tags,
  ArrowLeftRight,
  FileText,
  HeartHandshake,
  Shield,
  UserCog,
  Tag,
  Grid3x3,
  Layers,
  Bookmark,
  ArrowRightLeft,
  SlidersHorizontal,
  Truck,
  Boxes,
  ShoppingBag,
  Monitor,
  Clock,
  Receipt,
  Download,
} from 'lucide-react'
import { useHasUpdate } from '@/hooks/use-latest-release'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Tooltip, TooltipContent, TooltipTrigger, TooltipProvider } from '@/components/ui/tooltip'
import { Separator } from '@/components/ui/separator'
import { ROUTES } from '@/constants'
import { useAuth } from '@/hooks/use-auth'

interface NavItem {
  label:       string
  href:        string
  icon:        React.ComponentType<{ className?: string }>
  permission?: string
}

interface NavGroup {
  label: string
  items: NavItem[]
}

const NAV_GROUPS: NavGroup[] = [
  {
    label: 'Dashboard',
    items: [
      { label: 'Dashboard', href: ROUTES.DASHBOARD, icon: LayoutDashboard, permission: 'dashboard.view' },
    ],
  },
  {
    label: 'Cadastros',
    items: [
      { label: 'Clientes', href: ROUTES.CUSTOMERS, icon: Users, permission: 'customers.view' },
    ],
  },
  {
    label: 'Estoque',
    items: [
      { label: 'Estoque',        href: ROUTES.INVENTORY,             icon: Boxes,            permission: 'inventory.view' },
      { label: 'Movimentações',  href: ROUTES.INVENTORY_MOVEMENTS,   icon: ArrowRightLeft,   permission: 'inventory.view' },
      { label: 'Ajustes',        href: ROUTES.INVENTORY_ADJUSTMENTS, icon: SlidersHorizontal,permission: 'inventory.view' },
      { label: 'Transferências', href: ROUTES.INVENTORY_TRANSFERS,   icon: Truck,            permission: 'inventory.view' },
    ],
  },
  {
    label: 'Catálogo',
    items: [
      { label: 'Produtos',   href: ROUTES.PRODUCTS,    icon: Package,    permission: 'products.view'  },
      { label: 'Marcas',     href: ROUTES.BRANDS,      icon: Bookmark,   permission: 'products.view'  },
      { label: 'Categorias', href: ROUTES.CATEGORIES,  icon: Tag,        permission: 'products.view'  },
      { label: 'Atributos',  href: ROUTES.ATTRIBUTES,  icon: Layers,     permission: 'products.view'  },
      { label: 'Grades',     href: ROUTES.GRIDS,       icon: Grid3x3,    permission: 'products.view'  },
    ],
  },
  {
    label: 'PDV',
    items: [
      { label: 'Caixas',       href: ROUTES.CASH_REGISTERS, icon: Monitor,      permission: 'sales.view' },
      { label: 'Sessões',      href: ROUTES.CASH_SESSIONS,  icon: Clock,        permission: 'sales.view' },
      { label: 'Vendas',       href: ROUTES.SALES,          icon: ShoppingCart, permission: 'sales.view' },
      { label: 'Condicionais', href: ROUTES.CONDITIONALS,   icon: ShoppingBag,  permission: 'sales.view' },
    ],
  },
  {
    label: 'Financeiro',
    items: [
      { label: 'Visão Geral',       href: ROUTES.FINANCIAL,            icon: Banknote,      permission: 'financial.view'   },
      { label: 'Contas a Pagar',    href: ROUTES.FINANCIAL_PAYABLE,    icon: TrendingDown,  permission: 'financial.view'   },
      { label: 'Contas a Receber',  href: ROUTES.FINANCIAL_RECEIVABLE, icon: TrendingUp,    permission: 'financial.view'   },
      { label: 'Transferências',    href: ROUTES.FINANCIAL_TRANSFERS,  icon: ArrowLeftRight,permission: 'financial.view'   },
      { label: 'Contas Bancárias',  href: ROUTES.FINANCIAL_ACCOUNTS,   icon: Wallet,        permission: 'financial.view'   },
      { label: 'Categorias',        href: ROUTES.FINANCIAL_CATEGORIES, icon: Tags,          permission: 'financial.create' },
    ],
  },
  {
    label: 'Fiscal',
    items: [
      { label: 'Config. Fiscal', href: ROUTES.FISCAL_SETTINGS, icon: FileText,  permission: 'fiscal.view' },
      { label: 'Documentos',     href: ROUTES.FISCAL_DOCS,     icon: Receipt,   permission: 'fiscal.view' },
    ],
  },
  {
    label: 'CRM',
    items: [
      { label: 'CRM', href: ROUTES.CRM, icon: HeartHandshake },
    ],
  },
  {
    label: 'Administração',
    items: [
      { label: 'Usuários', href: ROUTES.USERS,       icon: UserCog, permission: 'users.view' },
      { label: 'Perfis de Acesso', href: ROUTES.ROLES, icon: Shield,  permission: 'users.view' },
    ],
  },
  {
    label: 'Configurações',
    items: [
      { label: 'Configurações', href: ROUTES.SETTINGS,   icon: Settings,  permission: 'settings.view' },
      { label: 'Downloads',     href: ROUTES.DOWNLOADS,  icon: Download,  permission: 'settings.view' },
    ],
  },
]

interface SidebarProps {
  collapsed?: boolean
  onCollapse?: (v: boolean) => void
  className?: string
}

function NavLink({ item, collapsed }: { item: NavItem; collapsed: boolean }) {
  const pathname  = usePathname()
  const hasUpdate = useHasUpdate()
  const isActive  = pathname === item.href || (item.href !== '/' && pathname.startsWith(item.href))
  const Icon      = item.icon
  const showBadge = item.href === ROUTES.DOWNLOADS && hasUpdate

  const link = (
    <Link
      href={item.href}
      className={cn(
        'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
        isActive
          ? 'bg-white/20 text-white dark:bg-primary dark:text-primary-foreground'
          : 'text-white/70 hover:bg-white/10 hover:text-white dark:text-muted-foreground dark:hover:bg-accent dark:hover:text-accent-foreground',
        collapsed && 'justify-center px-2',
      )}
    >
      <span className="relative shrink-0">
        <Icon className="h-4 w-4" />
        {showBadge && (
          <span className="absolute -top-1 -right-1 h-2 w-2 rounded-full bg-red-500" />
        )}
      </span>
      {!collapsed && (
        <span className="flex flex-1 items-center justify-between">
          {item.label}
          {showBadge && (
            <span className="ml-2 rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-bold text-white leading-none">
              NEW
            </span>
          )}
        </span>
      )}
    </Link>
  )

  if (collapsed) {
    return (
      <Tooltip>
        <TooltipTrigger asChild>{link}</TooltipTrigger>
        <TooltipContent side="right">{item.label}</TooltipContent>
      </Tooltip>
    )
  }

  return link
}

function NavGroupSection({
  group,
  collapsed,
  visibleItems,
}: {
  group:        NavGroup
  collapsed:    boolean
  visibleItems: NavItem[]
}) {
  if (visibleItems.length === 0) return null

  return (
    <div>
      {!collapsed && (
        <p className="mb-1 px-3 text-[10px] font-semibold uppercase tracking-widest text-white/40 dark:text-muted-foreground/60">
          {group.label}
        </p>
      )}
      <div className="space-y-0.5">
        {visibleItems.map((item) => (
          <NavLink key={item.href} item={item} collapsed={collapsed} />
        ))}
      </div>
    </div>
  )
}

export function Sidebar({ collapsed: externalCollapsed, onCollapse, className }: SidebarProps) {
  const [internalCollapsed, setInternalCollapsed] = useState(false)
  const collapsed = externalCollapsed ?? internalCollapsed
  const { hasPermission, permissions, isLoading } = useAuth()

  function toggle() {
    const next = !collapsed
    setInternalCollapsed(next)
    onCollapse?.(next)
  }

  /**
   * Filter nav items by permission.
   * During loading (permissions.length === 0 or isLoading) we show all items
   * to avoid the sidebar flashing. Once permissions are known we filter.
   */
  function getVisibleItems(items: NavItem[]): NavItem[] {
    if (isLoading || permissions.length === 0) return items
    return items.filter((item) => {
      if (!item.permission) return true
      return hasPermission(item.permission)
    })
  }

  return (
    <TooltipProvider delayDuration={0}>
      <aside
        className={cn(
          'relative flex flex-col border-r bg-slate-800 dark:bg-background transition-all duration-300',
          collapsed ? 'w-16' : 'w-65',
          className,
        )}
      >
        {/* Logo */}
        <div className={cn(
          'flex h-14 items-center border-b border-white/15 px-4 dark:border-border',
          collapsed ? 'justify-center px-2' : 'gap-2.5',
        )}>
          <img src="/icon.svg" alt="Loomi" className="h-7 w-7 shrink-0" />
          {!collapsed && (
            <span className="text-base font-bold text-white tracking-tight">Loomi</span>
          )}
        </div>

        {/* Navigation */}
        <nav className="sidebar-scroll flex-1 overflow-y-auto py-4 px-2 space-y-4">
          {NAV_GROUPS.map((group, i) => {
            const visibleItems = getVisibleItems(group.items)
            if (visibleItems.length === 0) return null
            return (
              <div key={group.label}>
                {i > 0 && !collapsed && (
                  <Separator className="mb-3 bg-white/15 dark:bg-border" />
                )}
                <NavGroupSection
                  group={group}
                  collapsed={collapsed}
                  visibleItems={visibleItems}
                />
              </div>
            )
          })}
        </nav>

        <Separator className="bg-white/15 dark:bg-border" />

        {/* Collapse toggle */}
        <div className={cn('p-2', collapsed && 'flex justify-center')}>
          <Button
            variant="ghost"
            size="icon"
            onClick={toggle}
            className="h-8 w-8 text-white/60 hover:bg-white/10 hover:text-white dark:text-muted-foreground dark:hover:bg-accent dark:hover:text-accent-foreground"
            aria-label={collapsed ? 'Expandir menu' : 'Recolher menu'}
          >
            {collapsed ? (
              <ChevronRight className="h-4 w-4" />
            ) : (
              <ChevronLeft className="h-4 w-4" />
            ) }
          </Button>
        </div>
      </aside>
    </TooltipProvider>
  )
}
