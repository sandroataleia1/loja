'use client'

import { useState } from 'react'
import { Search, ClipboardList } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { usePdvCartStore } from '../../stores/pdvCartStore'
import { ordersService } from '@/services/orders.service'
import type { Order } from '@store/shared-types'

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatBRL(cents: number): string {
  return (cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

// ── Component ─────────────────────────────────────────────────────────────────

interface Props {
  open:    boolean
  onClose: () => void
}

export function OrderSearchModal({ open, onClose }: Props) {
  const [number,     setNumber]     = useState('')
  const [loading,    setLoading]    = useState(false)
  const [foundOrder, setFoundOrder] = useState<Order | null>(null)
  const [error,      setError]      = useState('')

  const addItem    = usePdvCartStore((s) => s.addItem)
  const setCustomer = usePdvCartStore((s) => s.setCustomer)

  async function handleSearch(e: React.FormEvent) {
    e.preventDefault()
    if (!number.trim()) return

    setLoading(true)
    setError('')
    setFoundOrder(null)

    try {
      const order = await ordersService.getByNumber(number.trim())
      setFoundOrder(order)
    } catch {
      setError('Pedido não encontrado ou não está disponível para o PDV.')
    } finally {
      setLoading(false)
    }
  }

  function handleLoadIntoCart() {
    if (!foundOrder) return

    if (foundOrder.customer) {
      setCustomer(foundOrder.customer.uuid, foundOrder.customer.name)
    }

    const items = foundOrder.items ?? []
    items.forEach((item) => {
      addItem({
        productUuid:    item.product_variant_id ?? item.uuid,
        variantUuid:    item.product_variant_id ?? null,
        name:           item.name_snapshot,
        sku:            item.sku_snapshot ?? '',
        barcode:        null,
        unitPriceCents: item.unit_price_cents,
        quantity:       item.quantity,
        discountCents:  item.discount_cents,
        unitOfMeasure:  item.unit_of_measure ?? null,
      })
    })

    toast.success(`Pedido ${foundOrder.number} carregado no carrinho.`)
    handleClose()
  }

  function handleClose() {
    setNumber('')
    setFoundOrder(null)
    setError('')
    onClose()
  }

  return (
    <Dialog open={open} onOpenChange={(o) => { if (!o) handleClose() }}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <ClipboardList className="h-5 w-5" />
            Buscar Pedido
          </DialogTitle>
        </DialogHeader>

        <form onSubmit={handleSearch} className="flex gap-2">
          <Input
            autoFocus
            placeholder="Número do pedido (ex: PED000001)"
            value={number}
            onChange={(e) => setNumber(e.target.value.toUpperCase())}
            className="font-mono"
          />
          <Button type="submit" disabled={loading || !number.trim()}>
            <Search className="h-4 w-4" />
          </Button>
        </form>

        {error && (
          <p className="text-sm text-destructive">{error}</p>
        )}

        {foundOrder && (
          <div className="space-y-4 rounded-md border p-4">
            <div className="flex items-start justify-between">
              <div>
                <p className="font-mono font-semibold">{foundOrder.number}</p>
                <p className="text-sm text-muted-foreground">
                  {foundOrder.customer?.name ?? 'Consumidor Final'}
                </p>
              </div>
              <span className="text-sm font-medium">{formatBRL(foundOrder.total_cents)}</span>
            </div>

            <div className="space-y-1 text-sm">
              {(foundOrder.items ?? []).map((item) => (
                <div key={item.uuid} className="flex justify-between">
                  <span className="text-muted-foreground">
                    {item.quantity}x {item.name_snapshot}
                  </span>
                  <span>{formatBRL(item.subtotal_cents)}</span>
                </div>
              ))}
            </div>

            <div className="flex gap-2 pt-2 border-t">
              <Button variant="outline" onClick={handleClose} className="flex-1">
                Cancelar
              </Button>
              <Button onClick={handleLoadIntoCart} className="flex-1">
                Carregar no PDV
              </Button>
            </div>
          </div>
        )}
      </DialogContent>
    </Dialog>
  )
}
