'use client'

import { useRef, useState, useCallback } from 'react'
import { Upload, Trash2, Star, ImageIcon } from 'lucide-react'
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

export function ProductImageManager({ productUuid, images, onRefresh }: ProductImageManagerProps) {
  const inputRef                  = useRef<HTMLInputElement>(null)
  const [uploading, setUploading] = useState(false)
  const [dragging,  setDragging]  = useState(false)
  const [removing,  setRemoving]  = useState<string | null>(null)

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
    // Re-upload trick not needed — we just upload as primary via a delete+re-upload isn't right.
    // The backend sets is_primary on existing image via the AttachImage endpoint (it only creates).
    // Simplest UX: remove current primary flag visually and tell user to delete and re-upload.
    // Better: we need a PATCH endpoint. For now, show a toast guiding the user.
    toast.info('Para definir como principal: exclua e reenvie a imagem marcando-a como a primeira.')
    void imageUuid
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
              <Upload className="h-8 w-8 animate-bounce" />
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
        </div>
      ) : (
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
              />

              {/* Primary badge */}
              {img.is_primary && (
                <span className="absolute top-1.5 left-1.5 flex items-center gap-1 text-[10px] font-medium bg-primary text-primary-foreground px-1.5 py-0.5 rounded-full">
                  <Star className="h-2.5 w-2.5 fill-current" />
                  Principal
                </span>
              )}

              {/* Hover overlay */}
              <div className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                {!img.is_primary && (
                  <Button
                    type="button"
                    variant="secondary"
                    size="icon"
                    className="h-8 w-8"
                    title="Definir como principal"
                    onClick={() => void handleSetPrimary(img.uuid)}
                  >
                    <Star className="h-3.5 w-3.5" />
                  </Button>
                )}
                <Button
                  type="button"
                  variant="destructive"
                  size="icon"
                  className="h-8 w-8"
                  title="Remover imagem"
                  disabled={removing === img.uuid}
                  onClick={() => void handleRemove(img.uuid)}
                >
                  <Trash2 className="h-3.5 w-3.5" />
                </Button>
              </div>

              {/* Remove spinner */}
              {removing === img.uuid && (
                <div className="absolute inset-0 bg-black/60 flex items-center justify-center">
                  <div className="h-5 w-5 rounded-full border-2 border-white border-t-transparent animate-spin" />
                </div>
              )}
            </div>
          ))}
        </div>
      )}

      {images.length > 0 && (
        <p className="text-xs text-muted-foreground">
          {images.length} imagem{images.length !== 1 ? 's' : ''} — passe o mouse sobre uma imagem para ver as opções
        </p>
      )}
    </div>
  )
}
