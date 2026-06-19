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

// Polyfill para crypto.randomUUID — necessário em contextos não-HTTPS (HTTP local/VPS sem TLS).
// react-hook-form v7.54+ e outros pacotes dependem desta API, que browsers bloqueiam fora de
// secure contexts. Este script roda de forma síncrona antes de qualquer hidratação React.
const cryptoPolyfill = `
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID !== 'function') {
    crypto.randomUUID = function() {
      return '10000000-1000-4000-8000-100000000000'.replace(/[018]/g, function(c) {
        var n = parseInt(c);
        return (n ^ (Math.random() * 16 >> n / 4)).toString(16);
      });
    };
  }
`

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="pt-BR" suppressHydrationWarning>
      <head>
        {/* eslint-disable-next-line react/no-danger */}
        <script dangerouslySetInnerHTML={{ __html: cryptoPolyfill }} />
      </head>
      <body suppressHydrationWarning>
        <Providers>{children}</Providers>
      </body>
    </html>
  )
}
