import { Smartphone } from 'lucide-react'

const fmtBRL = (cents: number) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)

interface Props {
  remainingCents: number
  onAdd:          (amountCents: number) => void
}

export function PixPaymentForm({ remainingCents, onAdd }: Props) {
  return (
    <div className="space-y-4 text-center">
      <div className="flex flex-col items-center gap-3 py-8 border rounded-2xl bg-muted/30">
        {/* QR code placeholder */}
        <div className="w-28 h-28 border-2 border-dashed border-muted-foreground/30 rounded-xl flex items-center justify-center">
          <Smartphone className="w-10 h-10 text-muted-foreground/40" />
        </div>
        <div>
          <p className="font-semibold text-sm">Aguardando pagamento PIX</p>
          <p className="text-2xl font-bold tabular-nums mt-1">{fmtBRL(remainingCents)}</p>
          <p className="text-[11px] text-muted-foreground/60 mt-2">
            QR code dinâmico disponível na Etapa 5
          </p>
        </div>
      </div>

      <button
        onClick={() => onAdd(remainingCents)}
        className="w-full h-12 rounded-xl bg-primary text-primary-foreground font-bold hover:bg-primary/90 active:scale-[0.98] transition-all"
      >
        Confirmar Pagamento PIX
      </button>
    </div>
  )
}
