const fmtBRL = (cents: number) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)

interface Props {
  changeCents: number
}

export function ChangeDisplay({ changeCents }: Props) {
  if (changeCents <= 0) return null
  return (
    <div className="rounded-2xl bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 p-4 text-center">
      <p className="text-xs text-green-700 dark:text-green-400 mb-1 font-medium uppercase tracking-wide">
        Troco
      </p>
      <p className="text-4xl font-bold text-green-700 dark:text-green-400 tabular-nums">
        {fmtBRL(changeCents)}
      </p>
    </div>
  )
}
