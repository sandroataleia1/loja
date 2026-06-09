import type { ReactNode } from 'react'
import { cn } from '@/lib/utils'
import { Separator } from '@/components/ui/separator'

interface AppSectionProps {
  title?:       string
  description?: string
  children:     ReactNode
  className?:   string
  separator?:   boolean
}

export function AppSection({ title, description, children, className, separator = false }: AppSectionProps) {
  return (
    <section className={cn('space-y-4', className)}>
      {(title || description) && (
        <div>
          {title && <h2 className="text-lg font-semibold">{title}</h2>}
          {description && <p className="text-sm text-muted-foreground">{description}</p>}
        </div>
      )}
      {separator && <Separator />}
      {children}
    </section>
  )
}
