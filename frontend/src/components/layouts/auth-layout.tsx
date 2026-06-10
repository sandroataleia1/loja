import type { ReactNode } from 'react'

export function AuthLayout({ children }: { children: ReactNode }) {
  return (
    <div className="min-h-screen flex flex-col items-center justify-center bg-muted/40 p-4">
      <div className="mb-8 text-center">
        <div className="mx-auto mb-4 flex items-center justify-center gap-3">
          <img src="/icon.svg" alt="" className="h-10 w-10" />
          <span className="text-3xl font-bold tracking-tight text-foreground">Loomi</span>
        </div>
        <p className="text-sm text-muted-foreground">Plataforma operacional para varejo moda</p>
      </div>
      <div className="w-full max-w-105">{children}</div>
    </div>
  )
}
