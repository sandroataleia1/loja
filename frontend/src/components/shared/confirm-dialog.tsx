'use client'

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'

interface ConfirmDialogProps {
  open:          boolean
  onOpenChange:  (open: boolean) => void
  onConfirm:     () => void
  title?:        string
  description?:  string
  confirmLabel?: string
  cancelLabel?:  string
  variant?:      'default' | 'destructive'
  loading?:      boolean
}

export function ConfirmDialog({
  open,
  onOpenChange,
  onConfirm,
  title        = 'Tem certeza?',
  description  = 'Esta ação não pode ser desfeita.',
  confirmLabel = 'Confirmar',
  cancelLabel  = 'Cancelar',
  variant      = 'destructive',
  loading      = false,
}: ConfirmDialogProps) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[400px]">
        <DialogHeader>
          <DialogTitle>{title}</DialogTitle>
          <DialogDescription>{description}</DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)} disabled={loading}>
            {cancelLabel}
          </Button>
          <Button variant={variant} onClick={onConfirm} disabled={loading}>
            {loading ? 'Aguarde...' : confirmLabel}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
