import { cn } from '@/lib/utils'

const fmtBRL = (cents: number) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)

function getMonthlyRate(n: number): number {
  if (n <= 3) return 0
  if (n <= 6) return 0.0199
  return 0.0249
}

export function calcInstallmentCents(totalCents: number, n: number): number {
  const rate = getMonthlyRate(n)
  if (rate === 0) return Math.ceil(totalCents / n)
  return Math.ceil(
    (totalCents * rate * Math.pow(1 + rate, n)) / (Math.pow(1 + rate, n) - 1),
  )
}

interface Props {
  totalCents: number
  selected:   number
  onSelect:   (n: number) => void
}

export function InstallmentTable({ totalCents, selected, onSelect }: Props) {
  return (
    <div className="border rounded-xl overflow-hidden text-xs">
      <div className="grid grid-cols-3 bg-muted/50 px-3 py-2 text-muted-foreground font-medium">
        <span>Parcelas</span>
        <span className="text-center">Valor/Parcela</span>
        <span className="text-right">Total</span>
      </div>
      <div className="divide-y max-h-44 overflow-y-auto">
        {Array.from({ length: 12 }, (_, i) => i + 1).map((n) => {
          const installCents = calcInstallmentCents(totalCents, n)
          const totalWithInterest = installCents * n
          const rate = getMonthlyRate(n)
          const isSelected = selected === n

          return (
            <button
              key={n}
              onClick={() => onSelect(n)}
              className={cn(
                'w-full grid grid-cols-3 px-3 py-2 text-left hover:bg-accent transition-colors',
                isSelected && 'bg-primary/10 text-primary font-semibold',
              )}
            >
              <span>
                {n}x
                {rate > 0 && (
                  <span className="ml-1 text-[10px] text-muted-foreground font-normal">
                    {(rate * 100).toFixed(2)}%am
                  </span>
                )}
              </span>
              <span className="text-center">{fmtBRL(installCents)}</span>
              <span className={cn(
                'text-right',
                totalWithInterest > totalCents && !isSelected && 'text-amber-600 dark:text-amber-400',
              )}>
                {fmtBRL(totalWithInterest)}
              </span>
            </button>
          )
        })}
      </div>
    </div>
  )
}
