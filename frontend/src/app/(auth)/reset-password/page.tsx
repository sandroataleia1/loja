import type { Metadata } from 'next'
import { Suspense } from 'react'
import { ResetPasswordPageClient } from './page-client'

export const metadata: Metadata = { title: 'Redefinir senha' }

export default function ResetPasswordPage() {
  return (
    <Suspense fallback={<div className="h-64 animate-pulse rounded-lg bg-muted" />}>
      <ResetPasswordPageClient />
    </Suspense>
  )
}
