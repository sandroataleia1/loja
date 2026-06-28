'use client'

import { useRef, useState, useCallback, type DragEvent, type ChangeEvent } from 'react'
import { Upload, Download, FileText, X, Check, Loader2 } from 'lucide-react'
import { toast } from 'sonner'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'
import { pricingService } from '@/services/pricing.service'

// ── Types ────────────────────────────────────────────────────────────────────

interface PriceImportModalProps {
  priceListUuid:  string
  priceListName:  string
  open:           boolean
  onClose:        () => void
  onSuccess?:     () => void
}

interface ImportError {
  line:    number
  field:   string
  message: string
}

interface ImportResult {
  updated: number
  errors:  ImportError[]
}

type Step = 'idle' | 'selected' | 'importing' | 'done' | 'partial-error'

// ── Helpers ──────────────────────────────────────────────────────────────────

const MAX_SIZE_BYTES = 5 * 1024 * 1024 // 5 MB

function parseCSVPreview(text: string, maxRows = 5): { headers: string[]; rows: string[][] } {
  const lines = text.split(/\r?\n/).filter(Boolean)
  if (lines.length === 0) return { headers: [], rows: [] }

  const separator = lines[0].includes(';') ? ';' : ','

  const headers = lines[0].split(separator).map(h => h.trim().replace(/^"|"$/g, ''))
  const rows    = lines.slice(1, maxRows + 1).map(line =>
    line.split(separator).map(cell => cell.trim().replace(/^"|"$/g, ''))
  )
  return { headers, rows }
}

function formatBytes(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(2)} MB`
}

// ── Component ────────────────────────────────────────────────────────────────

export function PriceImportModal({
  priceListUuid,
  priceListName,
  open,
  onClose,
  onSuccess,
}: PriceImportModalProps) {
  const inputRef                            = useRef<HTMLInputElement>(null)
  const [file, setFile]                     = useState<File | null>(null)
  const [dragging, setDragging]             = useState(false)
  const [preview, setPreview]               = useState<{ headers: string[]; rows: string[][] } | null>(null)
  const [step, setStep]                     = useState<Step>('idle')
  const [progress, setProgress]             = useState(0)
  const [result, setResult]                 = useState<ImportResult | null>(null)

  // ── File handling ─────────────────────────────────────────────────────────

  function acceptFile(selected: File) {
    if (!selected.name.endsWith('.csv')) {
      toast.error('Selecione um arquivo .csv')
      return
    }
    if (selected.size > MAX_SIZE_BYTES) {
      toast.error('Arquivo muito grande. Máximo: 5 MB')
      return
    }

    setFile(selected)
    setStep('selected')
    setResult(null)

    const reader = new FileReader()
    reader.onload = (e) => {
      const text = e.target?.result as string
      setPreview(parseCSVPreview(text))
    }
    reader.readAsText(selected, 'utf-8')
  }

  function handleInputChange(e: ChangeEvent<HTMLInputElement>) {
    const selected = e.target.files?.[0]
    if (selected) acceptFile(selected)
    // Reset input so same file can be re-selected
    e.target.value = ''
  }

  const handleDrop = useCallback((e: DragEvent<HTMLDivElement>) => {
    e.preventDefault()
    setDragging(false)
    const dropped = e.dataTransfer.files?.[0]
    if (dropped) acceptFile(dropped)
  }, [])

  const handleDragOver = useCallback((e: DragEvent<HTMLDivElement>) => {
    e.preventDefault()
    setDragging(true)
  }, [])

  const handleDragLeave = useCallback((e: DragEvent<HTMLDivElement>) => {
    e.preventDefault()
    setDragging(false)
  }, [])

  // ── Import ────────────────────────────────────────────────────────────────

  async function handleImport() {
    if (!file) return

    setStep('importing')
    setProgress(0)

    // Fake progress: ramp to 90% over 2 seconds
    const timer = setInterval(() => {
      setProgress(prev => {
        if (prev >= 90) {
          clearInterval(timer)
          return 90
        }
        return prev + 10
      })
    }, 200)

    try {
      const data = await pricingService.importCSV(priceListUuid, file) as unknown as ImportResult
      clearInterval(timer)
      setProgress(100)

      setResult(data)

      if (!data.errors || data.errors.length === 0) {
        setStep('done')
        toast.success(`${data.updated} preços atualizados com sucesso`)
        onSuccess?.()
      } else {
        setStep('partial-error')
        toast.warning(`${data.updated} atualizados, ${data.errors.length} erro(s) encontrado(s)`)
      }
    } catch (err) {
      clearInterval(timer)
      setProgress(0)
      setStep('selected')
      toast.error(err instanceof Error ? err.message : 'Erro ao importar arquivo')
    }
  }

  // ── Reset ─────────────────────────────────────────────────────────────────

  function handleClose() {
    setFile(null)
    setPreview(null)
    setStep('idle')
    setProgress(0)
    setResult(null)
    onClose()
  }

  function handleReset() {
    setFile(null)
    setPreview(null)
    setStep('idle')
    setProgress(0)
    setResult(null)
  }

  const templateUrl = `${process.env.NEXT_PUBLIC_API_URL}/catalog/price-lists/import/template`

  // ── Render ────────────────────────────────────────────────────────────────

  return (
    <Dialog open={open} onOpenChange={(o) => { if (!o) handleClose() }}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Importar preços — {priceListName}</DialogTitle>
        </DialogHeader>

        <div className="space-y-6 pt-2">
          {/* Step 1: Template */}
          <div className="space-y-2">
            <p className="text-sm font-medium">1. Baixe o modelo CSV</p>
            <Button variant="outline" size="sm" asChild>
              <a href={templateUrl} download>
                <Download className="h-4 w-4 mr-2" />
                Baixar template CSV
              </a>
            </Button>
            <p className="text-xs text-muted-foreground">
              Preencha o arquivo com os preços e faça o upload abaixo.
            </p>
          </div>

          {/* Divider */}
          <hr />

          {/* Step 2: Upload */}
          <div className="space-y-2">
            <p className="text-sm font-medium">2. Selecione o arquivo preenchido</p>

            {/* Drop zone */}
            <div
              role="button"
              tabIndex={0}
              onClick={() => inputRef.current?.click()}
              onKeyDown={(e) => e.key === 'Enter' && inputRef.current?.click()}
              onDrop={handleDrop}
              onDragOver={handleDragOver}
              onDragLeave={handleDragLeave}
              className={cn(
                'border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-colors',
                dragging
                  ? 'border-primary bg-primary/5'
                  : 'border-muted-foreground/30 hover:border-primary/50 hover:bg-muted/30'
              )}
            >
              <input
                ref={inputRef}
                type="file"
                accept=".csv"
                className="hidden"
                onChange={handleInputChange}
              />
              <Upload className="h-8 w-8 mx-auto mb-3 text-muted-foreground" />
              <p className="text-sm font-medium">
                Arraste um arquivo CSV aqui ou clique para selecionar
              </p>
              <p className="text-xs text-muted-foreground mt-1">
                Máximo: 5 MB | Formato: .csv
              </p>
            </div>

            {/* File info */}
            {file && (
              <div className="flex items-center justify-between rounded-md border bg-muted/30 px-3 py-2 text-sm">
                <div className="flex items-center gap-2">
                  <FileText className="h-4 w-4 text-muted-foreground" />
                  <span className="font-medium">{file.name}</span>
                  <span className="text-muted-foreground">({formatBytes(file.size)})</span>
                </div>
                <Button variant="ghost" size="icon" className="h-6 w-6" onClick={handleReset}>
                  <X className="h-3 w-3" />
                </Button>
              </div>
            )}
          </div>

          {/* Step 3: Preview */}
          {preview && preview.headers.length > 0 && step !== 'done' && (
            <div className="space-y-2">
              <p className="text-sm font-medium">3. Pré-visualização (primeiras 5 linhas)</p>
              <div className="overflow-x-auto rounded-md border">
                <table className="w-full text-xs">
                  <thead className="bg-muted">
                    <tr>
                      {preview.headers.map((h, i) => (
                        <th key={i} className="px-3 py-2 text-left font-medium whitespace-nowrap">
                          {h}
                        </th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {preview.rows.map((row, ri) => (
                      <tr key={ri} className="border-t">
                        {row.map((cell, ci) => (
                          <td key={ci} className="px-3 py-1.5 text-muted-foreground whitespace-nowrap">
                            {cell}
                          </td>
                        ))}
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* Progress bar */}
          {step === 'importing' && (
            <div className="space-y-2">
              <div className="flex items-center gap-2 text-sm text-muted-foreground">
                <Loader2 className="h-4 w-4 animate-spin" />
                <span>Importando... {progress}%</span>
              </div>
              <div className="h-2 w-full rounded-full bg-muted overflow-hidden">
                <div
                  className="h-full bg-primary transition-all duration-200 rounded-full"
                  style={{ width: `${progress}%` }}
                />
              </div>
            </div>
          )}

          {/* Result: success */}
          {step === 'done' && result && (
            <div className="flex items-start gap-3 rounded-md border border-green-500/30 bg-green-50 dark:bg-green-950/20 p-4">
              <Check className="h-5 w-5 text-green-600 mt-0.5 shrink-0" />
              <div>
                <p className="text-sm font-semibold text-green-700 dark:text-green-400">
                  Importação concluída!
                </p>
                <p className="text-sm text-green-600 dark:text-green-500">
                  {result.updated} preço(s) atualizado(s) com sucesso.
                </p>
              </div>
            </div>
          )}

          {/* Result: partial errors */}
          {step === 'partial-error' && result && (
            <div className="space-y-3">
              <div className="flex items-start gap-3 rounded-md border border-yellow-500/30 bg-yellow-50 dark:bg-yellow-950/20 p-4">
                <FileText className="h-5 w-5 text-yellow-600 mt-0.5 shrink-0" />
                <div>
                  <p className="text-sm font-semibold text-yellow-700 dark:text-yellow-400">
                    Importação parcial
                  </p>
                  <p className="text-sm text-yellow-600 dark:text-yellow-500">
                    {result.updated} atualizados, {result.errors.length} erro(s) encontrado(s).
                  </p>
                </div>
              </div>

              {result.errors.length > 0 && (
                <div className="overflow-x-auto rounded-md border">
                  <table className="w-full text-xs">
                    <thead className="bg-muted">
                      <tr>
                        <th className="px-3 py-2 text-left font-medium">Linha</th>
                        <th className="px-3 py-2 text-left font-medium">Campo</th>
                        <th className="px-3 py-2 text-left font-medium">Erro</th>
                      </tr>
                    </thead>
                    <tbody>
                      {result.errors.map((err, i) => (
                        <tr key={i} className="border-t">
                          <td className="px-3 py-1.5">{err.line}</td>
                          <td className="px-3 py-1.5 text-muted-foreground">{err.field}</td>
                          <td className="px-3 py-1.5 text-destructive">{err.message}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          )}

          {/* Actions */}
          <div className="flex justify-end gap-2 pt-2 border-t">
            <Button variant="outline" onClick={handleClose}>
              {step === 'done' ? 'Fechar' : 'Cancelar'}
            </Button>
            {(step === 'idle' || step === 'selected' || step === 'partial-error') && (
              <Button
                onClick={handleImport}
                disabled={!file}
              >
                <Upload className="h-4 w-4 mr-2" />
                Importar
              </Button>
            )}
          </div>
        </div>
      </DialogContent>
    </Dialog>
  )
}
