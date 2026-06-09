import type { Metadata } from 'next'
import { Providers } from '@/providers'
import './globals.css'

export const metadata: Metadata = {
  title: {
    template: '%s | Store Admin',
    default:  'Store Admin',
  },
  description: 'Plataforma operacional para varejo moda',
  icons: {
    apple: '/icon-light.png',
  },
}

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="pt-BR" suppressHydrationWarning>
      <body suppressHydrationWarning>
        <Providers>{children}</Providers>
      </body>
    </html>
  )
}
