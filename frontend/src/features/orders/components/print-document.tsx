'use client'

import { useState } from 'react'
import { Printer } from 'lucide-react'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog'
import type { Quote, Order, DocumentItem } from '@store/shared-types'

// ── Types ─────────────────────────────────────────────────────────────────────

type PrintSize  = 'a4' | 'thermal'
type DocumentType = 'quote' | 'order'
type PrintDoc = Quote | Order

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatBRL(cents: number): string {
  return (cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function formatDate(iso: string | null): string {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('pt-BR')
}

// ── Print HTML builder ────────────────────────────────────────────────────────

function buildPrintHtml(doc: PrintDoc, type: DocumentType, size: PrintSize, copies: number): string {
  const isQuote   = type === 'quote'
  const title     = isQuote ? 'ORÇAMENTO' : 'PEDIDO'
  const number    = doc.number
  const customer  = doc.customer?.name ?? 'Consumidor Final'
  const phone     = doc.customer?.phone ?? ''
  const items     = doc.items ?? []
  const createdAt = formatDate(doc.created_at)

  const thermalCss = `
    @page { size: 80mm auto; margin: 0; }
    body { font-family: monospace; font-size: 11px; width: 72mm; margin: 0 auto; padding: 4mm; }
    h1 { font-size: 13px; text-align: center; margin: 0 0 4px; }
    .divider { border-top: 1px dashed #000; margin: 4px 0; }
    table { width: 100%; border-collapse: collapse; font-size: 10px; }
    th { text-align: left; border-bottom: 1px solid #000; padding: 1px 0; }
    td { padding: 1px 0; }
    td.right, th.right { text-align: right; }
    .total { font-weight: bold; font-size: 12px; }
    .copy-sep { page-break-after: always; }
  `
  const a4Css = `
    @page { size: A4; margin: 15mm 20mm; }
    body { font-family: Arial, sans-serif; font-size: 12px; color: #111; }
    h1 { font-size: 20px; margin: 0 0 4px; }
    .subtitle { color: #666; font-size: 12px; margin-bottom: 16px; }
    .header-grid { display: flex; justify-content: space-between; margin-bottom: 12px; }
    .divider { border-top: 1px solid #ccc; margin: 10px 0; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #f3f3f3; border: 1px solid #ddd; padding: 6px 8px; text-align: left; font-size: 11px; }
    td { border: 1px solid #ddd; padding: 6px 8px; font-size: 11px; }
    td.right { text-align: right; }
    .total-row td { font-weight: bold; font-size: 13px; }
    .copy-sep { page-break-after: always; }
  `

  const itemsHtml = items.map((item: DocumentItem) => `
    <tr>
      <td>${item.name_snapshot}${item.sku_snapshot ? ` <span style="color:#888">(${item.sku_snapshot})</span>` : ''}</td>
      <td class="right">${item.quantity}</td>
      <td class="right">${formatBRL(item.unit_price_cents)}</td>
      ${item.discount_cents > 0 ? `<td class="right">${formatBRL(item.discount_cents)}</td>` : `<td class="right">—</td>`}
      <td class="right">${formatBRL(item.subtotal_cents)}</td>
    </tr>
  `).join('')

  const validityHtml = isQuote && (doc as Quote).valid_until
    ? `<p>Válido até: <strong>${formatDate((doc as Quote).valid_until)}</strong></p>`
    : ''

  const notesHtml = doc.notes
    ? `<div class="divider"></div><p><strong>Obs:</strong> ${doc.notes}</p>`
    : ''

  const termsHtml = doc.payment_terms
    ? `<p><strong>Condições:</strong> ${doc.payment_terms}</p>`
    : ''

  const oneCopy = `
    <h1>${title} Nº ${number}</h1>
    <p class="subtitle">${createdAt}</p>
    <div class="header-grid">
      <div><strong>Cliente:</strong> ${customer}${phone ? `<br><strong>Fone:</strong> ${phone}` : ''}</div>
    </div>
    <div class="divider"></div>
    <table>
      <thead>
        <tr>
          <th>Produto</th>
          <th class="right">Qtd</th>
          <th class="right">Unit.</th>
          <th class="right">Desc.</th>
          <th class="right">Total</th>
        </tr>
      </thead>
      <tbody>${itemsHtml}</tbody>
    </table>
    <div class="divider"></div>
    ${doc.discount_cents > 0 ? `<p>Subtotal: ${formatBRL(doc.subtotal_cents)} | Desconto: ${formatBRL(doc.discount_cents)}</p>` : ''}
    <p class="total">TOTAL: ${formatBRL(doc.total_cents)}</p>
    ${termsHtml}
    ${validityHtml}
    ${notesHtml}
  `

  const copyPages = Array.from({ length: copies }).map((_, i) =>
    i < copies - 1 ? `<div class="copy-sep">${oneCopy}</div>` : oneCopy
  ).join('')

  return `<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<style>${size === 'thermal' ? thermalCss : a4Css}</style>
</head><body>${copyPages}</body></html>`
}

// ── Component ─────────────────────────────────────────────────────────────────

interface Props {
  doc:  PrintDoc
  type: DocumentType
}

export function PrintDocument({ doc, type }: Props) {
  const [open,   setOpen]   = useState(false)
  const [size,   setSize]   = useState<PrintSize>('a4')
  const [copies, setCopies] = useState(1)

  function handlePrint() {
    const html    = buildPrintHtml(doc, type, size, copies)
    const w       = window.open('', '_blank', 'width=800,height=600')
    if (!w) return
    w.document.write(html)
    w.document.close()
    w.focus()
    setTimeout(() => { w.print(); w.close() }, 300)
    setOpen(false)
  }

  return (
    <>
      <Button variant="outline" size="sm" onClick={() => setOpen(true)}>
        <Printer className="mr-2 h-4 w-4" />
        Imprimir
      </Button>

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Imprimir {type === 'quote' ? 'Orçamento' : 'Pedido'}</DialogTitle>
          </DialogHeader>

          <div className="space-y-4 py-2">
            <div className="space-y-1">
              <label className="text-sm font-medium">Tamanho do papel</label>
              <div className="flex gap-3">
                {(['a4', 'thermal'] as const).map((s) => (
                  <label key={s} className="flex items-center gap-2 cursor-pointer text-sm">
                    <input
                      type="radio"
                      value={s}
                      checked={size === s}
                      onChange={() => setSize(s)}
                      className="h-4 w-4"
                    />
                    {s === 'a4' ? 'A4' : 'Térmica (80mm)'}
                  </label>
                ))}
              </div>
            </div>

            <div className="space-y-1">
              <label className="text-sm font-medium">Número de vias</label>
              <div className="flex gap-2">
                {[1, 2, 3].map((n) => (
                  <Button
                    key={n}
                    type="button"
                    variant={copies === n ? 'default' : 'outline'}
                    size="sm"
                    onClick={() => setCopies(n)}
                  >
                    {n} {n === 1 ? 'via' : 'vias'}
                  </Button>
                ))}
              </div>
            </div>
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={() => setOpen(false)}>Cancelar</Button>
            <Button onClick={handlePrint}>
              <Printer className="mr-2 h-4 w-4" />
              Imprimir
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  )
}
