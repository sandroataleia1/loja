'use client'

import { useState, useEffect } from 'react'
import Link from 'next/link'
import { usePathname } from 'next/navigation'
import {
  LayoutDashboard,
  Users,
  Settings,
  ChevronLeft,
  ChevronRight,
  ChevronDown,
  Package,
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
  Bookmark,
  ArrowRightLeft,
  SlidersHorizontal,
  Truck,
  Boxes,
  Monitor,
  Clock,
  Receipt,
  Download,
  Store,
  PackagePlus,
  Building2,
  ClipboardList,
  ScrollText,
  Warehouse,
  Handshake,
  CreditCard,
  Database,
  CalendarClock,
  Landmark,
} from 'lucide-react'
import { useHasUpdate } from '@/hooks/use-latest-release'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Tooltip, TooltipContent, TooltipTrigger, TooltipProvider } from '@/components/ui/tooltip'
import { ROUTES } from '@/constants'
import { useAuth } from '@/hooks/use-auth'

// ── Types ─────────────────────────────────────────────────────────────────────

interface NavItem {
  label:       string
  href:        string
  icon:        React.ComponentType<{ className?: string }>
  permission?: string
}

interface NavGroup {
  label: string
  icon:  React.ComponentType<{ className?: string }>
  items: NavItem[]
}

// ── Navigation structure ──────────────────────────────────────────────────────

const NAV_GROUPS: NavGroup[] = [
  {
    label: 'Dashboard',
    icon:  LayoutDashboard,
    items: [
      { label: 'Dashboard', href: ROUTES.DASHBOARD, icon: LayoutDashboard, permission: 'dashboard.view' },
    ],
  },
  {
    label: 'Cadastros',
    icon:  Database,
    items: [
      { label: 'Clientes',    href: ROUTES.CUSTOMERS,  icon: Users,     permission: 'customers.view' },
      { label: 'Produtos',    href: ROUTES.PRODUCTS,   icon: Package,   permission: 'products.view'  },
      { label: 'Marcas',      href: ROUTES.BRANDS,     icon: Bookmark,  permission: 'products.view'  },
      { label: 'Categorias',  href: ROUTES.CATEGORIES, icon: Tag,       permission: 'products.view'  },
      { label: 'Fornecedores', href: ROUTES.SUPPLIERS, icon: Building2, permission: 'suppliers.view' },
    ],
  },
  {
    label: 'Estoque',
    icon:  Warehouse,
    items: [
      { label: 'Saldo',          href: ROUTES.INVENTORY,             icon: Boxes,             permission: 'inventory.view' },
      { label: 'Movimentações',  href: ROUTES.INVENTORY_MOVEMENTS,   icon: ArrowRightLeft,    permission: 'inventory.view' },
      { label: 'Ajustes',        href: ROUTES.INVENTORY_ADJUSTMENTS, icon: SlidersHorizontal, permission: 'inventory.view' },
      { label: 'Transferências', href: ROUTES.INVENTORY_TRANSFERS,   icon: Truck,             permission: 'inventory.view' },
    ],
  },
  {
    label: 'Comercial',
    icon:  Handshake,
    items: [
      { label: 'Orçamentos', href: ROUTES.QUOTES, icon: ScrollText,    permission: 'sales.view' },
      { label: 'Pedidos',    href: ROUTES.ORDERS, icon: ClipboardList, permission: 'sales.view' },
    ],
  },
  {
    label: 'PDV',
    icon:  Store,
    items: [
      { label: 'Abrir PDV',    href: ROUTES.PDV,            icon: Monitor,      permission: 'sales.view' },
      { label: 'Caixas',       href: ROUTES.CASH_REGISTERS, icon: CreditCard,   permission: 'sales.view' },
      { label: 'Sessões',      href: ROUTES.CASH_SESSIONS,  icon: Clock,        permission: 'sales.view' },
      { label: 'Vendas',       href: ROUTES.SALES,          icon: ShoppingCart, permission: 'sales.view' },
    ],
  },
  {
    label: 'Financeiro',
    icon:  Banknote,
    items: [
      { label: 'Visão Geral',      href: ROUTES.FINANCIAL,            icon: Banknote,       permission: 'financial.view'   },
      { label: 'A Pagar',          href: ROUTES.FINANCIAL_PAYABLE,    icon: TrendingDown,   permission: 'financial.view'   },
      { label: 'A Receber',        href: ROUTES.FINANCIAL_RECEIVABLE, icon: TrendingUp,     permission: 'financial.view'   },
      { label: 'Transferências',   href: ROUTES.FINANCIAL_TRANSFERS,  icon: ArrowLeftRight, permission: 'financial.view'   },
      { label: 'Contas Bancárias', href: ROUTES.FINANCIAL_ACCOUNTS,   icon: Wallet,         permission: 'financial.view'   },
      { label: 'Categorias',       href: ROUTES.FINANCIAL_CATEGORIES, icon: Tags,           permission: 'financial.create' },
    ],
  },
  {
    label: 'Compras',
    icon:  PackagePlus,
    items: [
      { label: 'Pedidos de Compra', href: ROUTES.PURCHASING, icon: PackagePlus, permission: 'purchase_orders.view' },
    ],
  },
  {
    label: 'Fiscal',
    icon:  FileText,
    items: [
      { label: 'Configurações', href: ROUTES.FISCAL_SETTINGS, icon: FileText, permission: 'fiscal.view' },
      { label: 'Documentos',    href: ROUTES.FISCAL_DOCS,     icon: Receipt,  permission: 'fiscal.view' },
    ],
  },
  {
    label: 'CRM',
    icon:  HeartHandshake,
    items: [
      { label: 'CRM', href: ROUTES.CRM, icon: HeartHandshake },
    ],
  },
  {
    label: 'Administração',
    icon:  Shield,
    items: [
      { label: 'Usuários',         href: ROUTES.USERS, icon: UserCog, permission: 'users.view' },
      { label: 'Perfis de Acesso', href: ROUTES.ROLES, icon: Shield,  permission: 'users.view' },
    ],
  },
  {
    label: 'Configurações',
    icon:  Settings,
    items: [
      { label: 'Configurações',       href: ROUTES.SETTINGS,            icon: Settings,     permission: 'settings.view' },
      { label: 'Cond. Pagamento',     href: ROUTES.PAYMENT_CONDITIONS,  icon: CalendarClock, permission: 'settings.view' },
      { label: 'Formas Pagamento',    href: ROUTES.PAYMENT_METHODS,     icon: Landmark,     permission: 'settings.view' },
      { label: 'Downloads',           href: ROUTES.DOWNLOADS,           icon: Download,     permission: 'settings.view' },
    ],
  },
]

// Flat list of all hrefs for sibling-aware active detection
const ALL_NAV_HREFS = NAV_GROUPS.flatMap((g) => g.items.map((i) => i.href))

function isItemActive(pathname: string, href: string): boolean {
  return (
    pathname === href ||
    (href !== '/' &&
      pathname.startsWith(href + '/') &&
      !ALL_NAV_HREFS.some(
        (h) =>
          h !== href &&
          h.startsWith(href + '/') &&
          (pathname === h || pathname.startsWith(h + '/'))
      ))
  )
}

function isGroupActive(pathname: string, items: NavItem[]): boolean {
  return items.some((i) => isItemActive(pathname, i.href))
}

// ── Sidebar props ─────────────────────────────────────────────────────────────

interface SidebarProps {
  collapsed?:  boolean
  onCollapse?: (v: boolean) => void
  className?:  string
}

// ── Collapsed group tooltip (shows sub-items on hover) ────────────────────────

function CollapsedGroupTooltip({
  group,
  visibleItems,
  pathname,
}: {
  group:        NavGroup
  visibleItems: NavItem[]
  pathname:     string
}) {
  const GroupIcon = group.icon
  const active    = isGroupActive(pathname, visibleItems)
  const hasUpdate = useHasUpdate()

  const trigger = (
    <button
      className={cn(
        'flex h-9 w-9 items-center justify-center rounded-md transition-colors',
        active
          ? 'bg-white/20 text-white dark:bg-primary dark:text-primary-foreground'
          : 'text-white/60 hover:bg-white/10 hover:text-white dark:text-muted-foreground dark:hover:bg-accent dark:hover:text-accent-foreground',
      )}
    >
      <span className="relative">
        <GroupIcon className="h-4.5 w-4.5" />
        {group.label === 'Configurações' && hasUpdate && (
          <span className="absolute -top-1 -right-1 h-2 w-2 rounded-full bg-red-500" />
        )}
      </span>
    </button>
  )

  if (visibleItems.length === 1) {
    return (
      <Tooltip>
        <TooltipTrigger asChild>
          <Link href={visibleItems[0].href}>{trigger}</Link>
        </TooltipTrigger>
        <TooltipContent side="right">{visibleItems[0].label}</TooltipContent>
      </Tooltip>
    )
  }

  return (
    <Tooltip>
      <TooltipTrigger asChild>{trigger}</TooltipTrigger>
      <TooltipContent side="right" className="p-0">
        <div className="py-1.5 min-w-40">
          <p className="px-3 pb-1 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground/70">
            {group.label}
          </p>
          {visibleItems.map((item) => {
            const ItemIcon = item.icon
            const active   = isItemActive(pathname, item.href)
            return (
              <Link
                key={item.href}
                href={item.href}
                className={cn(
                  'flex items-center gap-2.5 px-3 py-1.5 text-sm transition-colors',
                  active ? 'text-primary font-medium' : 'hover:text-foreground text-muted-foreground',
                )}
              >
                <ItemIcon className="h-3.5 w-3.5 shrink-0" />
                {item.label}
              </Link>
            )
          })}
        </div>
      </TooltipContent>
    </Tooltip>
  )
}

// ── Expanded group section ────────────────────────────────────────────────────

function ExpandedGroup({
  group,
  visibleItems,
  pathname,
  hasUpdate,
}: {
  group:        NavGroup
  visibleItems: NavItem[]
  pathname:     string
  hasUpdate:    boolean
}) {
  const isSingleItem = visibleItems.length === 1
  const groupActive  = isGroupActive(pathname, visibleItems)

  const [open, setOpen] = useState(() => groupActive)

  // Auto-open when navigating into this group
  useEffect(() => {
    if (groupActive) setOpen(true)
  }, [groupActive])

  const GroupIcon = group.icon

  // Single-item group — render as a plain link
  if (isSingleItem) {
    const item   = visibleItems[0]
    const active = isItemActive(pathname, item.href)
    const showBadge = item.href === ROUTES.DOWNLOADS && hasUpdate

    return (
      <Link
        href={item.href}
        className={cn(
          'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
          active
            ? 'bg-white/20 text-white dark:bg-primary dark:text-primary-foreground'
            : 'text-white/70 hover:bg-white/10 hover:text-white dark:text-muted-foreground dark:hover:bg-accent dark:hover:text-accent-foreground',
        )}
      >
        <span className="relative shrink-0">
          <GroupIcon className="h-4 w-4" />
          {showBadge && (
            <span className="absolute -top-1 -right-1 h-2 w-2 rounded-full bg-red-500" />
          )}
        </span>
        <span className="flex-1 flex items-center justify-between">
          {item.label}
          {showBadge && (
            <span className="rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-bold text-white leading-none">
              NEW
            </span>
          )}
        </span>
      </Link>
    )
  }

  // Multi-item group — collapsible
  return (
    <div>
      {/* Group header */}
      <button
        onClick={() => setOpen((v) => !v)}
        className={cn(
          'w-full flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
          groupActive
            ? 'text-white dark:text-foreground'
            : 'text-white/70 hover:bg-white/10 hover:text-white dark:text-muted-foreground dark:hover:bg-accent dark:hover:text-accent-foreground',
        )}
      >
        <GroupIcon className="h-4 w-4 shrink-0" />
        <span className="flex-1 text-left">{group.label}</span>
        <ChevronDown
          className={cn(
            'h-3.5 w-3.5 shrink-0 transition-transform duration-200 opacity-60',
            open && 'rotate-180',
          )}
        />
      </button>

      {/* Sub-items */}
      {open && (
        <div className="mt-0.5 ml-3 border-l border-white/15 dark:border-border pl-3 space-y-0.5">
          {visibleItems.map((item) => {
            const ItemIcon = item.icon
            const active   = isItemActive(pathname, item.href)
            return (
              <Link
                key={item.href}
                href={item.href}
                className={cn(
                  'flex items-center gap-2.5 rounded-md px-2.5 py-1.5 text-sm transition-colors',
                  active
                    ? 'bg-white/15 text-white font-medium dark:bg-primary/10 dark:text-primary'
                    : 'text-white/60 hover:bg-white/10 hover:text-white dark:text-muted-foreground dark:hover:bg-accent dark:hover:text-accent-foreground',
                )}
              >
                <ItemIcon className="h-3.5 w-3.5 shrink-0" />
                {item.label}
              </Link>
            )
          })}
        </div>
      )}
    </div>
  )
}

// ── Sidebar ───────────────────────────────────────────────────────────────────

export function Sidebar({ collapsed: externalCollapsed, onCollapse, className }: SidebarProps) {
  const pathname             = usePathname()
  const [pinned, setPinned]  = useState(true)
  const [hovered, setHovered] = useState(false)
  const hasUpdate            = useHasUpdate()
  const { hasPermission, permissions, isLoading } = useAuth()

  const pinnedCollapsed = externalCollapsed ?? pinned
  const collapsed       = pinnedCollapsed && !hovered

  function toggle() {
    const next = !pinnedCollapsed
    setPinned(next)
    onCollapse?.(next)
  }

  function getVisibleItems(items: NavItem[]): NavItem[] {
    if (isLoading || permissions.length === 0) return items
    return items.filter((item) => !item.permission || hasPermission(item.permission))
  }

  return (
    <TooltipProvider delayDuration={0}>
      <aside
        className={cn(
          'relative flex flex-col border-r bg-slate-800 dark:bg-background transition-all duration-200',
          collapsed ? 'w-16' : 'w-60',
          className,
        )}
        onMouseEnter={() => setHovered(true)}
        onMouseLeave={() => setHovered(false)}
      >
        {/* Logo */}
        <div
          className={cn(
            'flex h-14 items-center border-b border-white/15 dark:border-border px-4 shrink-0',
            collapsed ? 'justify-center px-2' : 'gap-2.5',
          )}
        >
          <img src="/icon.svg" alt="Loomi" className="h-7 w-7 shrink-0" />
          {!collapsed && (
            <span className="text-base font-bold text-white tracking-tight">Loomi</span>
          )}
        </div>

        {/* Navigation */}
        <nav className="sidebar-scroll flex-1 overflow-y-auto py-3 px-2 space-y-0.5">
          {collapsed ? (
            // ── Collapsed: only group icons ──
            <>
              {NAV_GROUPS.map((group) => {
                const visible = getVisibleItems(group.items)
                if (visible.length === 0) return null
                return (
                  <CollapsedGroupTooltip
                    key={group.label}
                    group={group}
                    visibleItems={visible}
                    pathname={pathname}
                  />
                )
              })}
            </>
          ) : (
            // ── Expanded: collapsible groups ──
            <>
              {NAV_GROUPS.map((group) => {
                const visible = getVisibleItems(group.items)
                if (visible.length === 0) return null
                return (
                  <ExpandedGroup
                    key={group.label}
                    group={group}
                    visibleItems={visible}
                    pathname={pathname}
                    hasUpdate={hasUpdate}
                  />
                )
              })}
            </>
          )}
        </nav>

        {/* Divider + toggle */}
        <div className="shrink-0 border-t border-white/15 dark:border-border p-2 flex justify-center">
          <Button
            variant="ghost"
            size="icon"
            onClick={toggle}
            className="h-8 w-8 text-white/50 hover:bg-white/10 hover:text-white dark:text-muted-foreground dark:hover:bg-accent dark:hover:text-accent-foreground"
            aria-label={collapsed ? 'Expandir menu' : 'Recolher menu'}
          >
            {collapsed ? (
              <ChevronRight className="h-4 w-4" />
            ) : (
              <ChevronLeft className="h-4 w-4" />
            )}
          </Button>
        </div>
      </aside>
    </TooltipProvider>
  )
}
