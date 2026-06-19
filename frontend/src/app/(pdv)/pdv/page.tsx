'use client'

import { useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { usePdvSessionStore } from '@/features/pdv/stores/pdvSessionStore'
import { ROUTES } from '@/constants'

export default function PdvRootPage() {
  const { session } = usePdvSessionStore()
  const router      = useRouter()

  useEffect(() => {
    router.replace(session ? ROUTES.PDV_VENDA : ROUTES.PDV_CAIXA)
  }, [session, router])

  return (
    <div className="flex min-h-screen items-center justify-center bg-background">
      <div className="h-6 w-6 animate-spin rounded-full border-2 border-primary border-t-transparent" />
    </div>
  )
}
