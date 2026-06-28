'use client'

import { LayoutList, LayoutGrid } from 'lucide-react'
import { Button } from '@/components/ui/button'

interface ViewToggleProps {
  view:     'table' | 'grid'
  onChange: (view: 'table' | 'grid') => void
}

export function ViewToggle({ view, onChange }: ViewToggleProps) {
  return (
    <div className="flex items-center border rounded-md overflow-hidden">
      <Button
        variant={view === 'table' ? 'secondary' : 'ghost'}
        size="sm"
        className="rounded-none rounded-l-md border-r h-8 px-2"
        onClick={() => onChange('table')}
        aria-label="Visualização em tabela"
      >
        <LayoutList className="h-4 w-4" />
      </Button>
      <Button
        variant={view === 'grid' ? 'secondary' : 'ghost'}
        size="sm"
        className="rounded-none rounded-r-md h-8 px-2"
        onClick={() => onChange('grid')}
        aria-label="Visualização em grade"
      >
        <LayoutGrid className="h-4 w-4" />
      </Button>
    </div>
  )
}
