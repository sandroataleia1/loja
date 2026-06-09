'use client'

import { useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { AppLayout } from '@/components/layouts/app-layout'
import { LoadingState } from '@/components/states/loading-state'
import { useAuth } from '@/hooks/use-auth'
import { ROUTES } from '@/constants'

export default function AdminLayout({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, isLoading } = useAuth()
  const router = useRouter()

  useEffect(() => {
    if (!isLoading && !isAuthenticated) {
      router.replace(ROUTES.LOGIN)
    }
  }, [isAuthenticated, isLoading, router])

  if (isLoading) {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <LoadingState message="Verificando sessão..." />
      </div>
    )
  }

  if (!isAuthenticated) return null

  return <AppLayout>{children}</AppLayout>
}
