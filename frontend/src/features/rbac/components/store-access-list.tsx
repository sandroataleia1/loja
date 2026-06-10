'use client'

import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import { AppCard } from '@/components/shared/app-card'
import { useUsers } from '../hooks'
import { MOCK_USER_PROFILES } from '../mocks/users.mock'

export function StoreAccessList() {
  const { data: users, isLoading, isError } = useUsers()

  if (isLoading) {
    return (
      <div className="space-y-3">
        {Array.from({ length: 3 }).map((_, i) => (
          <Skeleton key={i} className="h-24 w-full rounded-lg" />
        ))}
      </div>
    )
  }

  if (isError) {
    return (
      <p className="text-sm text-muted-foreground text-center py-8">
        Erro ao carregar acessos.
      </p>
    )
  }

  if (!users || users.length === 0) {
    return (
      <p className="text-sm text-muted-foreground text-center py-8">
        Nenhum acesso configurado.
      </p>
    )
  }

  return (
    <div className="space-y-3">
      {users.map((u) => {
        const profile = MOCK_USER_PROFILES[u.uuid]
        return (
          <AppCard key={u.uuid} title={profile?.name ?? u.uuid}>
            <div className="flex flex-col gap-2">
              <span className="text-xs text-muted-foreground">{profile?.email}</span>
              <div className="flex flex-wrap gap-2 items-center">
                <Badge variant="secondary">{u.role.name}</Badge>
                {u.store_ids.length === 0 ? (
                  <Badge variant="outline" className="text-xs">
                    Acesso irrestrito (todas as lojas)
                  </Badge>
                ) : (
                  u.store_ids.map((sid) => (
                    <Badge key={sid} variant="outline" className="text-xs font-mono">
                      {sid}
                    </Badge>
                  ))
                )}
              </div>
            </div>
          </AppCard>
        )
      })}
    </div>
  )
}
