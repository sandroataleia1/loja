'use client'

import { Plus } from 'lucide-react'
import { useBrands } from '@/features/catalog/hooks'

interface BrandSelectorProps {
  value:          string | null
  onChange:       (uuid: string | null) => void
  disabled?:      boolean
  onQuickCreate?: () => void
}

export function BrandSelector({ value, onChange, disabled, onQuickCreate }: BrandSelectorProps) {
  const { data: brands = [], isLoading } = useBrands()

  return (
    <div className="flex gap-2">
      <select
        disabled={disabled || isLoading}
        value={value ?? ''}
        onChange={(e) => onChange(e.target.value || null)}
        className="flex-1 rounded-md border bg-background px-3 py-2 text-sm outline-none ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:opacity-50"
      >
        <option value="">Sem marca</option>
        {brands.map((brand) => (
          <option key={brand.uuid} value={brand.uuid}>
            {brand.name}
          </option>
        ))}
      </select>

      {onQuickCreate && (
        <button
          type="button"
          onClick={onQuickCreate}
          disabled={disabled}
          title="Nova marca"
          className="flex items-center justify-center h-9 w-9 rounded-md border bg-background hover:bg-muted transition-colors disabled:opacity-50 shrink-0"
        >
          <Plus className="h-4 w-4" />
        </button>
      )}
    </div>
  )
}
