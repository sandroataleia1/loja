import { cn } from '@/lib/utils'
import type { ProductCategory } from '@/types/catalog'

interface Props {
  categories:   ProductCategory[]
  selected:     string | null
  onSelect:     (uuid: string | null) => void
}

export function CategoryChips({ categories, selected, onSelect }: Props) {
  if (categories.length === 0) return null

  return (
    <div className="flex gap-1.5 px-3 py-2 overflow-x-auto scrollbar-none shrink-0">
      <Chip
        label="Todos"
        active={selected === null}
        onClick={() => onSelect(null)}
      />
      {categories.map((cat) => (
        <Chip
          key={cat.uuid}
          label={cat.name}
          active={selected === cat.uuid}
          onClick={() => onSelect(selected === cat.uuid ? null : cat.uuid)}
        />
      ))}
    </div>
  )
}

function Chip({
  label,
  active,
  onClick,
}: {
  label:   string
  active:  boolean
  onClick: () => void
}) {
  return (
    <button
      onClick={onClick}
      className={cn(
        'shrink-0 px-3 py-1 rounded-full text-xs font-medium transition-colors',
        active
          ? 'bg-primary text-primary-foreground'
          : 'bg-muted text-muted-foreground hover:bg-accent hover:text-foreground',
      )}
    >
      {label}
    </button>
  )
}
