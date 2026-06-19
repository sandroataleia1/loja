'use client'

import { useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { ShieldOff } from 'lucide-react'
import { useAuth } from '@/hooks/use-auth'
import { ROUTES } from '@/constants'

export default function PdvLayout({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, isLoading, hasPermission, permissions } = useAuth()
  const router = useRouter()

  useEffect(() => {
    if (!isLoading && !isAuthenticated) {
      router.replace(ROUTES.LOGIN)
    }
  }, [isAuthenticated, isLoading, router])

  if (isLoading) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-background">
        <div className="flex flex-col items-center gap-3 text-muted-foreground">
          <div className="h-6 w-6 animate-spin rounded-full border-2 border-primary border-t-transparent" />
          <span className="text-sm">Verificando sessão...</span>
        </div>
      </div>
    )
  }

  if (!isAuthenticated) return null

  // Permissions are populated after /me — wait until they're known before blocking
  const permissionsLoaded = permissions.length > 0
  if (permissionsLoaded && !hasPermission('cashier.open')) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-background">
        <div className="flex flex-col items-center gap-6 text-center px-6 max-w-sm">
          <div className="rounded-full bg-destructive/10 p-4">
            <ShieldOff className="h-8 w-8 text-destructive" />
          </div>
          <div className="space-y-1">
            <h1 className="text-lg font-bold">Acesso não autorizado</h1>
            <p className="text-sm text-muted-foreground">
              Seu perfil não tem permissão para acessar o PDV. Solicite ao administrador a função de Caixa ou Vendedor.
            </p>
          </div>
          <button
            onClick={() => router.push(ROUTES.DASHBOARD)}
            className="rounded-xl bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
          >
            Voltar ao painel
          </button>
        </div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-background text-foreground flex flex-col select-none">
      {children}
    </div>
  )
}
