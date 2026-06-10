import type { ReactNode } from 'react'
import Image from 'next/image'

export function AuthLayout({ children }: { children: ReactNode }) {
  return (
    <div className="min-h-screen flex flex-col items-center justify-center bg-muted/40 p-4">
      <div className="mb-8 text-center">
        <div className="mx-auto mb-4 flex items-center justify-center gap-3">
          <Image
            src="/icon-light.png"
            alt="Logo"
            width={40}
            height={40}
            className="block dark:hidden"
            priority
          />
          <Image
            src="/icon-dark.png"
            alt="Logo"
            width={40}
            height={40}
            className="hidden dark:block"
            priority
          />
          <span className="text-3xl font-bold tracking-tight text-foreground">Loomi</span>
        </div>
        <p className="text-sm text-muted-foreground">Plataforma operacional para varejo moda</p>
      </div>
      <div className="w-full max-w-md">{children}</div>
    </div>
  )
}
