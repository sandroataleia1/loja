import type { ReactNode } from 'react'
import { Inbox } from 'lucide-react'
import { cn } from '@/lib/utils'

interface EmptyStateProps {
  title?:       string
  description?: string
  action?:      ReactNode
  icon?:        ReactNode
  className?:   string
}

export function EmptyState({
  title       = 'Nenhum item encontrado',
  description = 'Não há dados para exibir.',
  action,
  icon,
  className,
}: EmptyStateProps) {
  return (
    <div className={cn('flex flex-col items-center justify-center gap-4 py-16 text-center', className)}>
      <div className="rounded-full bg-muted p-4">
        {icon ?? <Inbox className="h-8 w-8 text-muted-foreground" />}
      </div>
      <div>
        <p className="font-medium">{title}</p>
        <p className="text-sm text-muted-foreground mt-1">{description}</p>
      </div>
      {action && <div>{action}</div>}
    </div>
  )
}
