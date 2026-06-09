import React from 'react'
import ReactDOM from 'react-dom/client'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { Toaster } from 'sonner'
import { App } from './app/App'
import './app/globals.css'

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime:            1000 * 30,      // 30s — SQLite é local, dados quase sempre frescos
      gcTime:               1000 * 60 * 5,  // 5min GC
      retry:                1,
      refetchOnWindowFocus: false,           // desktop POS não precisa refetch ao focar
    },
  },
})

ReactDOM.createRoot(document.getElementById('root')!).render(
  <React.StrictMode>
    <QueryClientProvider client={queryClient}>
      <App />
      <Toaster
        position="top-right"
        richColors
        closeButton
        duration={3000}
        toastOptions={{
          classNames: {
            toast: 'font-sans text-sm',
          },
        }}
      />
    </QueryClientProvider>
  </React.StrictMode>,
)
