'use client'

import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import { AppSection } from '@/components/shared/app-section'
import { usePermissions } from '../hooks'
import type { Permission } from '@store/shared-types'

interface PermissionsMatrixProps {
  /** When provided, highlights the selected permission slugs */
  selectedSlugs?: string[]
}

function groupByModule(permissions: Permission[]): Record<string, Permission[]> {
  return permissions.reduce<Record<string, Permission[]>>((acc, p) => {
    if (!acc[p.module]) acc[p.module] = []
    acc[p.module].push(p)
    return acc
  }, {})
}

export function PermissionsMatrix({ selectedSlugs }: PermissionsMatrixProps) {
  const { data: permissions, isLoading, isError } = usePermissions()

  if (isLoading) {
    return (
      <div className="space-y-6">
        {Array.from({ length: 3 }).map((_, i) => (
          <div key={i} className="space-y-2">
            <Skeleton className="h-5 w-32" />
            <div className="flex flex-wrap gap-2">
              {Array.from({ length: 4 }).map((_, j) => (
                <Skeleton key={j} className="h-7 w-28 rounded-full" />
              ))}
            </div>
          </div>
        ))}
      </div>
    )
  }

  if (isError || !permissions) {
    return (
      <p className="text-sm text-muted-foreground">
        Erro ao carregar permissões.
      </p>
    )
  }

  const grouped = groupByModule(permissions)

  return (
    <div className="space-y-6">
      {Object.entries(grouped).map(([module, perms]) => (
        <AppSection key={module} title={module.charAt(0).toUpperCase() + module.slice(1)}>
          <div className="flex flex-wrap gap-2">
            {perms.map((p) => {
              const isSelected = selectedSlugs?.includes(p.slug)
              return (
                <Badge
                  key={p.uuid}
                  variant={isSelected ? 'default' : 'outline'}
                  className="text-xs"
                  title={p.description ?? undefined}
                >
                  {p.name}
                  <span className="ml-1 opacity-60 font-mono text-[10px]">
                    {p.slug}
                  </span>
                </Badge>
              )
            })}
          </div>
        </AppSection>
      ))}
    </div>
  )
}
