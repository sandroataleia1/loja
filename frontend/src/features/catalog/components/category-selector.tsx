'use client'

import { X, Plus } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { useCategories } from '@/features/catalog/hooks'
import type { Category } from '@store/shared-types'

// ── Helpers ───────────────────────────────────────────────────────────────────

interface FlatCategory {
  uuid:   string
  label:  string
  depth:  number
}

function flattenCategories(categories: Category[], depth = 0): FlatCategory[] {
  const result: FlatCategory[] = []
  for (const cat of categories) {
    result.push({ uuid: cat.uuid, label: cat.name, depth })
    if (cat.children && cat.children.length > 0) {
      result.push(...flattenCategories(cat.children, depth + 1))
    }
  }
  return result
}

// ── Component ─────────────────────────────────────────────────────────────────

interface CategorySelectorProps {
  value:          string[]
  onChange:       (uuids: string[]) => void
  disabled?:      boolean
  onQuickCreate?: () => void
}

export function CategorySelector({ value, onChange, disabled, onQuickCreate }: CategorySelectorProps) {
  const { data: categories = [], isLoading } = useCategories()
  const flat = flattenCategories(categories)

  function handleAdd(uuid: string) {
    if (!uuid || value.includes(uuid)) return
    onChange([...value, uuid])
  }

  function handleRemove(uuid: string) {
    onChange(value.filter((v) => v !== uuid))
  }

  const selectedLabels = value.map((uuid) => {
    const found = flat.find((f) => f.uuid === uuid)
    return { uuid, label: found?.label ?? uuid }
  })

  return (
    <div className="space-y-2">
      {/* Selected chips */}
      {selectedLabels.length > 0 && (
        <div className="flex flex-wrap gap-1.5">
          {selectedLabels.map(({ uuid, label }) => (
            <Badge key={uuid} variant="secondary" className="gap-1 pr-1">
              {label}
              {!disabled && (
                <button
                  type="button"
                  onClick={() => handleRemove(uuid)}
                  className="ml-0.5 rounded-full hover:bg-muted-foreground/20 p-0.5"
                  aria-label={`Remover ${label}`}
                >
                  <X className="h-3 w-3" />
                </button>
              )}
            </Badge>
          ))}
        </div>
      )}

      {/* Select + botao nova categoria */}
      <div className="flex gap-2">
        <select
          disabled={disabled || isLoading}
          value=""
          onChange={(e) => handleAdd(e.target.value)}
          className="flex-1 rounded-md border bg-background px-3 py-2 text-sm outline-none ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:opacity-50"
        >
          <option value="">Adicionar categoria...</option>
          {flat.map((cat) => (
            <option
              key={cat.uuid}
              value={cat.uuid}
              disabled={value.includes(cat.uuid)}
            >
              {'  '.repeat(cat.depth)}{cat.depth > 0 ? '> ' : ''}{cat.label}
            </option>
          ))}
        </select>

        {onQuickCreate && (
          <button
            type="button"
            onClick={onQuickCreate}
            disabled={disabled}
            title="Nova categoria"
            className="flex items-center justify-center h-9 w-9 rounded-md border bg-background hover:bg-muted transition-colors disabled:opacity-50 shrink-0"
          >
            <Plus className="h-4 w-4" />
          </button>
        )}
      </div>
    </div>
  )
}
