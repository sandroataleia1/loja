'use client'

import { MessageCircle } from 'lucide-react'
import { Button } from '@/components/ui/button'
import type { Quote } from '@store/shared-types'

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatBRL(cents: number): string {
  return (cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function formatDate(iso: string | null): string {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('pt-BR')
}

function buildText(quote: Quote): string {
  const lines: string[] = []

  lines.push(`*ORÇAMENTO ${quote.number}*`)
  lines.push(`Data: ${formatDate(quote.created_at)}`)

  if (quote.customer?.name) {
    lines.push(`Cliente: ${quote.customer.name}`)
  }

  if (quote.valid_until) {
    lines.push(`Válido até: ${formatDate(quote.valid_until)}`)
  }

  lines.push('')
  lines.push('*Itens:*')

  const items = quote.items ?? []
  items.forEach((item, i) => {
    lines.push(
      `${i + 1}. ${item.name_snapshot} — ${item.quantity}x ${formatBRL(item.unit_price_cents)} = ${formatBRL(item.subtotal_cents)}`
    )
  })

  lines.push('')

  if (quote.discount_cents > 0) {
    lines.push(`Subtotal: ${formatBRL(quote.subtotal_cents)}`)
    lines.push(`Desconto: ${formatBRL(quote.discount_cents)}`)
  }

  lines.push(`*Total: ${formatBRL(quote.total_cents)}*`)

  if (quote.payment_terms) {
    lines.push(`Condições: ${quote.payment_terms}`)
  }

  if (quote.notes) {
    lines.push(`Obs: ${quote.notes}`)
  }

  return lines.join('\n')
}

// ── Component ─────────────────────────────────────────────────────────────────

interface Props {
  quote: Quote
}

export function WhatsAppShare({ quote }: Props) {
  function handleShare() {
    const text = buildText(quote)
    const url  = `https://wa.me/?text=${encodeURIComponent(text)}`
    window.open(url, '_blank', 'noopener,noreferrer')
  }

  return (
    <Button variant="outline" size="sm" onClick={handleShare} className="text-green-600 border-green-200 hover:bg-green-50">
      <MessageCircle className="mr-2 h-4 w-4" />
      WhatsApp
    </Button>
  )
}
