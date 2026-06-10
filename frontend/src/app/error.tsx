'use client'

import { ErrorState } from '@/components/states/error-state'

export default function GlobalError({ reset }: { error: Error; reset: () => void }) {
  return (
    <div className="flex min-h-screen items-center justify-center">
      <ErrorState
        message="Ocorreu um erro inesperado na aplicação."
        onRetry={reset}
      />
    </div>
  )
}
