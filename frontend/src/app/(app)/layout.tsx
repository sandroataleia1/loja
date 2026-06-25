'use client'

import { useEffect } from 'react'
import { useRouter, usePathname } from 'next/navigation'
import { AppLayout } from '@/components/layouts/app-layout'
import { LoadingState } from '@/components/states/loading-state'
import { useAuth } from '@/hooks/use-auth'
import { ROUTES } from '@/constants'

// Map route prefixes to the minimum permission required to access them.
// Order matters: more specific prefixes should come before their parents.
const ROUTE_PERMISSIONS: Array<[prefix: string, permission: string]> = [
  // Cadastros / Catálogo
  ['/products',       'products.view'],
  ['/catalog',        'products.view'],
  // Estoque
  ['/inventory',      'inventory.view'],
  // Financeiro
  ['/financial',      'financial.view'],
  // Fiscal
  ['/fiscal',         'fiscal.view'],
  // Comercial (orçamentos/pedidos) — sales.view
  ['/quotes',         'sales.view'],
  ['/orders',         'sales.view'],
  // PDV / Vendas
  ['/sales',          'sales.view'],
  // Compras
  ['/purchasing',     'purchase_orders.view'],
  // Cadastros Mestres
  ['/suppliers',      'suppliers.view'],
  ['/carriers',       'carriers.view'],
  ['/sellers',        'sellers.view'],
  ['/partners',       'partners.view'],
  ['/cost-centers',   'cost_centers.view'],
  // Administração
  ['/users',          'users.view'],
  ['/roles',          'users.view'],
  ['/permissions',    'users.view'],
  ['/store-access',   'users.view'],
  ['/audit-logs',     'users.view'],
  // Configurações
  ['/settings',       'settings.view'],
  ['/downloads',      'settings.view'],
  // Sistema
  ['/settings/system', 'settings.view'],
]

function requiredPermission(pathname: string): string | null {
  return ROUTE_PERMISSIONS.find(([prefix]) => pathname.startsWith(prefix))?.[1] ?? null
}

export default function AdminLayout({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, isLoading, hasPermission, permissions } = useAuth()
  const router   = useRouter()
  const pathname = usePathname()

  useEffect(() => {
    if (isLoading) return

    if (!isAuthenticated) {
      router.replace(ROUTES.LOGIN)
      return
    }

    const required = requiredPermission(pathname)
    if (required && !hasPermission(required)) {
      router.replace(ROUTES.DASHBOARD)
    }
  }, [isAuthenticated, isLoading, pathname, hasPermission, router])

  if (isLoading) {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <LoadingState message="Verificando sessão..." />
      </div>
    )
  }

  if (!isAuthenticated) return null

  // Prevent flash: block render if the user lacks permission for this route.
  // Only do this check once permissions have been loaded (permissions.length > 0
  // or we're confident they're set — isLoading is false at this point).
  const required = requiredPermission(pathname)
  if (required && !hasPermission(required)) return null

  return <AppLayout>{children}</AppLayout>
}
