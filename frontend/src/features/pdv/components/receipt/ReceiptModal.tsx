'use client'

import { CheckCircle2, Printer, Plus } from 'lucide-react'
import { usePrintReceipt } from '../../hooks/usePrintReceipt'
import { ReceiptDocument } from './ReceiptDocument'
import type { ReceiptData } from '../../types'
import './receipt.print.css'

const fmtBRL = (cents: number) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)

interface Props {
  data:    ReceiptData
  onClose: () => void
}

export function ReceiptModal({ data, onClose }: Props) {
  const { ref, print } = usePrintReceipt()
  const { sale } = data

  const isFiscal = sale.fiscal_status === 'issued'

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
      <div className="bg-card border rounded-2xl shadow-2xl w-full max-w-sm flex flex-col max-h-[92vh] overflow-hidden">

        {/* Cabeçalho de sucesso */}
        <div className="bg-green-600 rounded-t-2xl px-5 py-5 text-white text-center shrink-0">
          <CheckCircle2 className="w-10 h-10 mx-auto mb-2 opacity-90" />
          <p className="text-lg font-bold">Venda Concluída!</p>
          {sale.code && (
            <p className="text-sm opacity-80 mt-0.5">Venda #{sale.code}</p>
          )}
          <p className="text-3xl font-bold tabular-nums mt-1.5">
            {fmtBRL(sale.total_cents)}
          </p>
          <p className="text-xs opacity-70 mt-1">
            {isFiscal ? '✓ NFC-e Autorizada' : 'Comprovante Interno'}
          </p>
        </div>

        {/* Preview do cupom */}
        <div className="flex-1 overflow-y-auto p-4 bg-gray-50 dark:bg-zinc-900">
          {/* Este div recebe a classe receipt-printable ao imprimir */}
          <div
            ref={ref}
            className="mx-auto bg-white rounded-lg p-1 shadow-sm border border-gray-200"
            style={{ width: 'fit-content' }}
          >
            <ReceiptDocument data={data} />
          </div>
        </div>

        {/* Ações */}
        <div className="px-4 pb-4 pt-3 border-t shrink-0 flex gap-3">
          <button
            onClick={print}
            className="flex-1 h-12 rounded-xl border flex items-center justify-center gap-2 text-sm font-medium hover:bg-accent transition-colors"
          >
            <Printer className="w-4 h-4" />
            Imprimir
          </button>
          <button
            onClick={onClose}
            className="flex-1 h-12 rounded-xl bg-primary text-primary-foreground flex items-center justify-center gap-2 text-sm font-bold hover:bg-primary/90 transition-colors active:scale-[0.98]"
          >
            <Plus className="w-4 h-4" />
            Nova Venda
          </button>
        </div>
      </div>
    </div>
  )
}
