'use client'

import { useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { usePdvSessionStore } from '@/features/pdv/stores/pdvSessionStore'
import { ROUTES } from '@/constants'

export function usePdvSession() {
  const { session } = usePdvSessionStore()
  const router      = useRouter()

  useEffect(() => {
    if (!session) {
      router.replace(ROUTES.PDV_CAIXA)
    }
  }, [session, router])

  return { session }
}
