'use client'

import { useAuthContext } from '@/providers/auth-provider'

function getGreeting(): string {
  const h = new Date().getHours()
  if (h < 12) return 'Bom dia'
  if (h < 18) return 'Boa tarde'
  return 'Boa noite'
}

export function DashboardHeader() {
  const { user, tenant } = useAuthContext()
  const greeting = getGreeting()
  const firstName = user?.name?.split(' ')[0] ?? ''
  const today = new Date().toLocaleDateString('pt-BR', {
    weekday: 'long',
    day:     'numeric',
    month:   'long',
    year:    'numeric',
  })

  return (
    <div className="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 className="text-xl font-bold tracking-tight">
          {greeting}{firstName ? `, ${firstName}` : ''}!
        </h1>
        <p className="text-sm text-muted-foreground">
          {tenant?.trade_name ?? tenant?.legal_name ?? 'Sua empresa'} &middot; {today}
        </p>
      </div>
    </div>
  )
}
