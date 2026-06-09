import type { ReactNode } from 'react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { cn } from '@/lib/utils'

interface AppCardProps {
  title?:       string
  description?: string
  actions?:     ReactNode
  children:     ReactNode
  className?:   string
  contentClassName?: string
}

export function AppCard({
  title,
  description,
  actions,
  children,
  className,
  contentClassName,
}: AppCardProps) {
  return (
    <Card className={className}>
      {(title || actions) && (
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
          <div>
            {title && <CardTitle className="text-base font-semibold">{title}</CardTitle>}
            {description && <CardDescription className="mt-1">{description}</CardDescription>}
          </div>
          {actions && <div className="flex items-center gap-2">{actions}</div>}
        </CardHeader>
      )}
      <CardContent className={cn(!title && 'pt-6', contentClassName)}>{children}</CardContent>
    </Card>
  )
}
