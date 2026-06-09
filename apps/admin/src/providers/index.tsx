'use client'

import type { ReactNode } from 'react'
import { ThemeProvider }  from './theme-provider'
import { QueryProvider }  from './query-provider'
import { AuthProvider }   from './auth-provider'
import { Toaster }        from '@/components/ui/sonner'

export function Providers({ children }: { children: ReactNode }) {
  return (
    <ThemeProvider attribute="class" defaultTheme="system" enableSystem disableTransitionOnChange>
      <QueryProvider>
        <AuthProvider>
          {children}
          <Toaster richColors position="top-right" />
        </AuthProvider>
      </QueryProvider>
    </ThemeProvider>
  )
}
