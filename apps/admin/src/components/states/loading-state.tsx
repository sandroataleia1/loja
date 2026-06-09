import { Loader2 } from 'lucide-react'
import { cn } from '@/lib/utils'

interface LoadingStateProps {
  message?:   string
  className?: string
  size?:      'sm' | 'md' | 'lg'
}

const sizes = { sm: 'h-4 w-4', md: 'h-8 w-8', lg: 'h-12 w-12' }

export function LoadingState({ message = 'Carregando...', className, size = 'md' }: LoadingStateProps) {
  return (
    <div className={cn('flex flex-col items-center justify-center gap-3 py-16 text-muted-foreground', className)}>
      <Loader2 className={cn('animate-spin', sizes[size])} />
      {message && <p className="text-sm">{message}</p>}
    </div>
  )
}
