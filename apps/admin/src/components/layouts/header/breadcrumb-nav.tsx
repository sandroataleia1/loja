'use client'

import { usePathname } from 'next/navigation'
import Link from 'next/link'
import { ChevronRight, Home } from 'lucide-react'
import { cn } from '@/lib/utils'

const LABELS: Record<string, string> = {
  settings:  'Configurações',
  customers: 'Clientes',
  products:  'Produtos',
  inventory: 'Estoque',
  reports:   'Relatórios',
  sales:     'Vendas',
}

function getLabel(segment: string): string {
  return LABELS[segment] ?? segment.charAt(0).toUpperCase() + segment.slice(1)
}

export function BreadcrumbNav() {
  const pathname = usePathname()
  const segments = pathname.split('/').filter(Boolean)

  return (
    <nav aria-label="Breadcrumb" className="flex items-center gap-1 text-sm text-muted-foreground">
      <Link href="/" className="hover:text-foreground transition-colors">
        <Home className="h-4 w-4" />
        <span className="sr-only">Início</span>
      </Link>

      {segments.map((segment, index) => {
        const href = '/' + segments.slice(0, index + 1).join('/')
        const isLast = index === segments.length - 1
        const label  = getLabel(segment)

        return (
          <span key={href} className="flex items-center gap-1">
            <ChevronRight className="h-3.5 w-3.5 opacity-50" />
            {isLast ? (
              <span className={cn('font-medium', isLast && 'text-foreground')}>{label}</span>
            ) : (
              <Link href={href} className="hover:text-foreground transition-colors">
                {label}
              </Link>
            )}
          </span>
        )
      })}
    </nav>
  )
}
