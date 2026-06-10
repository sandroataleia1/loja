import type { ReactNode } from 'react'
import { cn } from '@/lib/utils'

interface AppPageHeaderProps {
  title:       string
  description?: string
  actions?:    ReactNode
  className?:  string
}

export function AppPageHeader({ title, description, actions, className }: AppPageHeaderProps) {
  return (
    <div className={cn('flex flex-col gap-1 pb-6 md:flex-row md:items-center md:justify-between', className)}>
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
        {description && (
          <p className="text-sm text-muted-foreground mt-0.5">{description}</p>
        )}
      </div>
      {actions && <div className="flex items-center gap-2 mt-2 md:mt-0">{actions}</div>}
    </div>
  )
}
