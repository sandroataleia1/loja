'use client'

import { useState } from 'react'
import { Plus, Check, Loader2 } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { useCustomerTags, useCreateTag } from '../hooks'
import type { CustomerTag } from '@store/shared-types'

interface CustomerTagSelectorProps {
  value:     string[]   // selected tag uuids
  onChange:  (uuids: string[]) => void
  disabled?: boolean
}

function TagDot({ color }: { color: string | null }) {
  return (
    <span
      className="inline-block h-2.5 w-2.5 rounded-full shrink-0"
      style={{ backgroundColor: color ?? '#94a3b8' }}
    />
  )
}

export function CustomerTagSelector({ value, onChange, disabled }: CustomerTagSelectorProps) {
  const { data: tags = [], isLoading } = useCustomerTags()
  const { mutate: createTag, isPending: isCreating } = useCreateTag()

  const [newTagName, setNewTagName]   = useState('')
  const [showInput,  setShowInput]    = useState(false)

  function toggleTag(tag: CustomerTag) {
    if (disabled) return
    if (value.includes(tag.uuid)) {
      onChange(value.filter((id) => id !== tag.uuid))
    } else {
      onChange([...value, tag.uuid])
    }
  }

  function handleCreateTag() {
    const name = newTagName.trim()
    if (!name) return
    createTag(
      { name },
      {
        onSuccess: (created) => {
          onChange([...value, created.uuid])
          setNewTagName('')
          setShowInput(false)
          toast.success(`Tag "${created.name}" criada.`)
        },
        onError: (err) => {
          toast.error(err instanceof Error ? err.message : 'Erro ao criar tag.')
        },
      }
    )
  }

  if (isLoading) {
    return (
      <div className="flex items-center gap-2 text-sm text-muted-foreground">
        <Loader2 className="h-4 w-4 animate-spin" />
        Carregando tags…
      </div>
    )
  }

  return (
    <div className="space-y-2">
      <div className="flex flex-wrap gap-2">
        {tags.map((tag) => {
          const selected = value.includes(tag.uuid)
          return (
            <button
              key={tag.uuid}
              type="button"
              disabled={disabled}
              onClick={() => toggleTag(tag)}
              className={[
                'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition-colors',
                selected
                  ? 'border-primary bg-primary/10 text-primary'
                  : 'border-border bg-background text-muted-foreground hover:bg-muted',
                disabled && 'cursor-not-allowed opacity-50',
              ].join(' ')}
            >
              <TagDot color={tag.color} />
              {tag.name}
              {selected && <Check className="h-3 w-3" />}
            </button>
          )
        })}

        {!disabled && (
          <button
            type="button"
            onClick={() => setShowInput((v) => !v)}
            className="inline-flex items-center gap-1 rounded-full border border-dashed border-muted-foreground/40 px-3 py-1 text-xs text-muted-foreground hover:border-primary hover:text-primary transition-colors"
          >
            <Plus className="h-3 w-3" />
            Nova tag
          </button>
        )}
      </div>

      {showInput && !disabled && (
        <div className="flex items-center gap-2 mt-1">
          <Input
            className="h-7 text-xs max-w-48"
            placeholder="Nome da tag"
            value={newTagName}
            onChange={(e) => setNewTagName(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === 'Enter') {
                e.preventDefault()
                handleCreateTag()
              }
              if (e.key === 'Escape') {
                setShowInput(false)
                setNewTagName('')
              }
            }}
            disabled={isCreating}
            autoFocus
          />
          <Button
            type="button"
            size="sm"
            variant="outline"
            className="h-7 text-xs"
            onClick={handleCreateTag}
            disabled={isCreating || !newTagName.trim()}
          >
            {isCreating ? <Loader2 className="h-3 w-3 animate-spin" /> : 'Criar'}
          </Button>
          <Button
            type="button"
            size="sm"
            variant="ghost"
            className="h-7 text-xs"
            onClick={() => { setShowInput(false); setNewTagName('') }}
          >
            Cancelar
          </Button>
        </div>
      )}
    </div>
  )
}
