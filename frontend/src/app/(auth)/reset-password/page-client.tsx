'use client'

import { useSearchParams } from 'next/navigation'
import { ResetPasswordForm } from '@/components/forms/reset-password-form'

export function ResetPasswordPageClient() {
  const params = useSearchParams()
  const token  = params.get('token') ?? ''
  const email  = params.get('email') ?? ''

  return <ResetPasswordForm token={token} email={email} />
}
