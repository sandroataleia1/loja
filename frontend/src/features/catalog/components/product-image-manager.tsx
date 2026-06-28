'use client'

import { useRef, useState, useCallback } from 'react'
import { Upload, Trash2, Star, ImageIcon, Loader2 } from 'lucide-react'
import { toast } from 'sonner'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { imageService } from '@/services/image.service'
import type { ProductMedia } from '@store/shared-types'

interface ProductImageManagerProps {
  productUuid: string
  images:      ProductMedia[]
  onRefresh:   () => void
}

function formatBytes(bytes: number | null): string {
  if (!bytes) return ''
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / 1048576).toFixed(1)} MB`
}

export function ProductImageManager({ productUuid, images, onRefresh }: ProductImageManagerProps) {
  const inputRef                    = useRef<HTMLInputElement>(null)
  const [uploading, setUploading]   = useState(false)
  const [dragging,  setDragging]    = useState(false)
  const [removing,  setRemoving]    = useState<string | null>(null)
  const [promoting, setPromoting]   = useState<string | null>(null)

  const uploadFiles = useCallback(async (files: FileList | File[]) => {
    const list = Array.from(files).filter((f) => f.type.startsWith('image/'))
    if (!list.length) return

    setUploading(true)
    try {
      for (let i = 0; i < list.length; i++) {
        const isPrimary = images.length === 0 && i === 0
        await imageService.upload(productUuid, list[i], isPrimary)
      }
      toast.success(list.length === 1 ? 'Imagem adicionada.' : `${list.length} imagens adicionadas.`)
      onRefresh()
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Erro ao enviar imagem.')
    } finally {
      setUploading(false)
      if (inputRef.current) inputRef.current.value = ''
    }
  }, [productUuid, images.length, onRefresh])

  async function handleRemove(imageUuid: string) {
    setRemoving(imageUuid)
    try {
      await imageService.remove(imageUuid)
      toast.success('Imagem removida.')
      onRefresh()
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Erro ao remover imagem.')
    } finally {
      setRemoving(null)
    }
  }

  async function handleSetPrimary(imageUuid: string) {
    setPromoting(imageUuid)
    try {
      await imageService.setPrimary(imageUuid)
      toast.success('Imagem definida como principal.')
      onRefresh()
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Erro ao definir imagem principal.')
    } finally {
      setPromoting(null)
    }
  }

  return (
    <div className="space-y-4">
      {/* Drop zone */}
      <div
        onDragOver={(e) => { e.preventDefault(); setDragging(true) }}
        onDragLeave={() => setDragging(false)}
        onDrop={(e) => {
          e.preventDefault()
          setDragging(false)
          void uploadFiles(e.dataTransfer.files)
        }}
        className={cn(
          'border-2 border-dashed rounded-lg p-8 text-center transition-colors cursor-pointer',
          dragging
            ? 'border-primary bg-primary/5'
            : 'border-muted-foreground/25 hover:border-primary/50 hover:bg-muted/30',
          uploading && 'pointer-events-none opacity-60',
        )}
        onClick={() => inputRef.current?.click()}
      >
        <input
          ref={inputRef}
          type="file"
          accept="image/jpeg,image/png,image/webp,image/gif"
          multiple
          className="hidden"
          onChange={(e) => e.target.files && void uploadFiles(e.target.files)}
        />
        <div className="flex flex-col items-center gap-2 text-muted-foreground">
          {uploading ? (
            <>
              <Loader2 className="h-8 w-8 animate-spin" />
              <p className="text-sm font-medium">Enviando…</p>
            </>
          ) : (
            <>
              <Upload className="h-8 w-8" />
              <p className="text-sm font-medium">Arraste imagens aqui ou clique para selecionar</p>
              <p className="text-xs">JPG, PNG, WEBP, GIF — máx. 10 MB cada</p>
            </>
          )}
        </div>
      </div>

      {/* Image grid */}
      {images.length === 0 ? (
        <div className="flex flex-col items-center gap-2 py-8 text-muted-foreground">
          <ImageIcon className="h-10 w-10 opacity-30" />
          <p className="text-sm">Nenhuma imagem cadastrada</p>
          <p className="text-xs opacity-60">Adicione imagens usando a área acima</p>
        </div>
      ) : (
        <>
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            {images.map((img) => (
              <div
                key={img.uuid}
                className="group relative rounded-lg border overflow-hidden bg-muted aspect-square"
              >
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img
                  src={img.thumbnail_url ?? img.url}
                  alt={img.alt_text ?? 'Imagem do produto'}
                  className="object-cover w-full h-full"
                  onError={(e) => {
                    // fallback to full url if thumbnail fails
                    const target = e.currentTarget
                    if (target.src !== img.url) {
                      target.src = img.url
                    }
                  }}
                />

                {/* Primary badge */}
                {img.is_primary && (
                  <span className="absolute top-1.5 left-1.5 flex items-center gap-1 text-[10px] font-medium bg-primary text-primary-foreground px-1.5 py-0.5 rounded-full">
                    <Star className="h-2.5 w-2.5 fill-current" />
                    Principal
                  </span>
                )}

                {/* Hover overlay */}
                <div className="absolute inset-0 bg-black/55 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-2 p-2">
                  {!img.is_primary && (
                    <Button
                      type="button"
                      variant="secondary"
                      size="sm"
                      className="h-7 text-xs w-full"
                      title="Definir como principal"
                      disabled={promoting === img.uuid}
                      onClick={() => void handleSetPrimary(img.uuid)}
                    >
                      {promoting === img.uuid
                        ? <Loader2 className="h-3 w-3 animate-spin mr-1" />
                        : <Star className="h-3 w-3 mr-1" />
                      }
                      Principal
                    </Button>
                  )}
                  <Button
                    type="button"
                    variant="destructive"
                    size="sm"
                    className="h-7 text-xs w-full"
                    title="Remover imagem"
                    disabled={removing === img.uuid}
                    onClick={() => void handleRemove(img.uuid)}
                  >
                    {removing === img.uuid
                      ? <Loader2 className="h-3 w-3 animate-spin mr-1" />
                      : <Trash2 className="h-3 w-3 mr-1" />
                    }
                    Remover
                  </Button>
                </div>

                {/* Full-screen spinner overlay during remove */}
                {(removing === img.uuid || promoting === img.uuid) && (
                  <div className="absolute inset-0 bg-black/60 flex items-center justify-center">
                    <Loader2 className="h-6 w-6 text-white animate-spin" />
                  </div>
                )}

                {/* Image info tooltip on bottom */}
                {(img.width || img.size_bytes) && (
                  <div className="absolute bottom-0 inset-x-0 bg-black/60 text-white text-[9px] px-1.5 py-0.5 opacity-0 group-hover:opacity-0 leading-tight">
                    {img.width && img.height && `${img.width}×${img.height}`}
                    {img.width && img.size_bytes && ' · '}
                    {img.size_bytes && formatBytes(img.size_bytes)}
                  </div>
                )}
              </div>
            ))}
          </div>

          <p className="text-xs text-muted-foreground">
            {images.length} imagem{images.length !== 1 ? 's' : ''} — passe o mouse para ver as opções
          </p>
        </>
      )}
    </div>
  )
}
