import { useState, useMemo } from 'react'
import { useNavigate } from 'react-router-dom'
import { ArrowLeft, RefreshCw } from 'lucide-react'
import { Button }    from '@/components/ui/button'
import { Badge }     from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { useSales }       from '../hooks/useSale'
import { formatCurrency } from '@/utils/currency'
import { useKeyboardShortcut } from '@/hooks/useKeyboardShortcut'
import { cn } from '@/lib/utils'

type Filter = 'today' | 'week' | 'all'

const FILTER_LABELS: Record<Filter, string> = {
  today: 'Hoje',
  week:  'Semana',
  all:   'Todas',
}

export function SaleListPage() {
  const [filter, setFilter] = useState<Filter>('today')
  const { data: sales = [], isLoading, refetch } = useSales(300)
  const navigate = useNavigate()

  // Esc / F8 → voltar ao PDV
  useKeyboardShortcut('Escape', () => navigate('/'))
  useKeyboardShortcut('F8',     () => navigate('/'))

  const filtered = useMemo(() => {
    if (filter === 'all') return sales

    const now  = new Date()
    const from = new Date()
    if (filter === 'today') {
      from.setHours(0, 0, 0, 0)
    } else {
      from.setDate(now.getDate() - 6)
      from.setHours(0, 0, 0, 0)
    }
    return sales.filter((s) => new Date(s.created_at) >= from)
  }, [sales, filter])

  const totalAmount = filtered.reduce((sum, s) => sum + s.total, 0)
  const completed   = filtered.filter((s) => s.status === 'completed').length
  const pending     = filtered.filter((s) => s.status !== 'completed').length

  const formatTime = (iso: string) =>
    new Date(iso).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })

  const formatShortDate = (iso: string) => {
    const d = new Date(iso)
    const today = new Date().toDateString() === d.toDateString()
    return today
      ? formatTime(iso)
      : d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' }) + ' ' + formatTime(iso)
  }

  return (
    <div className="flex flex-col h-full overflow-hidden">
      {/* Header */}
      <div className="flex items-center justify-between px-5 py-3 border-b shrink-0 bg-card">
        <div className="flex items-center gap-3">
          <button
            onClick={() => navigate('/')}
            className="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground transition-colors"
          >
            <ArrowLeft className="w-4 h-4" />
            PDV
          </button>
          <Separator orientation="vertical" className="h-4" />
          <h1 className="text-sm font-semibold">Histórico de Vendas</h1>
        </div>

        <div className="flex items-center gap-2">
          {/* Filter tabs */}
          <div className="flex rounded-lg border overflow-hidden">
            {(Object.keys(FILTER_LABELS) as Filter[]).map((f) => (
              <button
                key={f}
                onClick={() => setFilter(f)}
                className={cn(
                  'px-3 py-1.5 text-xs font-medium transition-colors',
                  f === filter
                    ? 'bg-primary text-primary-foreground'
                    : 'text-muted-foreground hover:bg-accent',
                )}
              >
                {FILTER_LABELS[f]}
              </button>
            ))}
          </div>

          <Button
            variant="ghost"
            size="icon"
            className="h-8 w-8"
            onClick={() => void refetch()}
            title="Atualizar (F5)"
          >
            <RefreshCw className={cn('w-3.5 h-3.5', isLoading && 'animate-spin')} />
          </Button>
        </div>
      </div>

      {/* Summary bar */}
      {filtered.length > 0 && (
        <div className="flex items-center gap-6 px-5 py-2.5 bg-muted/30 border-b shrink-0">
          <SummaryChip label="Vendas"     value={String(filtered.length)} />
          <SummaryChip label="Concluídas" value={String(completed)} color="emerald" />
          {pending > 0 && <SummaryChip label="Pendentes" value={String(pending)} color="yellow" />}
          <div className="flex-1" />
          <div className="text-right">
            <p className="text-xs text-muted-foreground">Total do período</p>
            <p className="text-base font-bold tabular-nums text-primary">{formatCurrency(totalAmount)}</p>
          </div>
        </div>
      )}

      {/* Table */}
      <div className="flex-1 overflow-auto">
        {isLoading ? (
          <div className="flex items-center justify-center h-32">
            <div className="w-5 h-5 border-2 border-primary border-t-transparent rounded-full animate-spin" />
          </div>
        ) : filtered.length === 0 ? (
          <div className="flex flex-col items-center justify-center h-40 gap-2 text-muted-foreground">
            <p className="text-sm font-medium">Nenhuma venda no período</p>
            <p className="text-xs opacity-60">
              {filter === 'today' ? 'Nenhuma venda registrada hoje' : 'Tente ampliar o filtro'}
            </p>
          </div>
        ) : (
          <table className="w-full text-sm">
            <thead className="bg-muted/40 sticky top-0 z-10">
              <tr className="border-b">
                <th className="text-left px-5 py-2.5 font-medium text-xs text-muted-foreground uppercase tracking-wide w-32">
                  Horário
                </th>
                <th className="text-left px-4 py-2.5 font-medium text-xs text-muted-foreground uppercase tracking-wide">
                  Cliente
                </th>
                <th className="text-right px-4 py-2.5 font-medium text-xs text-muted-foreground uppercase tracking-wide w-32">
                  Total
                </th>
                <th className="text-center px-4 py-2.5 font-medium text-xs text-muted-foreground uppercase tracking-wide w-28">
                  Status
                </th>
                <th className="text-center px-4 py-2.5 font-medium text-xs text-muted-foreground uppercase tracking-wide w-28">
                  Sync
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border/40">
              {filtered.map((sale) => (
                <tr
                  key={sale.uuid}
                  className="hover:bg-muted/30 transition-colors"
                >
                  <td className="px-5 py-3 tabular-nums text-muted-foreground text-sm">
                    {formatShortDate(sale.created_at)}
                  </td>
                  <td className="px-4 py-3">
                    {sale.customer_name ?? (
                      <span className="text-muted-foreground/40 text-xs">Consumidor</span>
                    )}
                  </td>
                  <td className="px-4 py-3 text-right tabular-nums font-semibold">
                    {formatCurrency(sale.total)}
                  </td>
                  <td className="px-4 py-3 text-center">
                    <Badge
                      variant={sale.status === 'completed' ? 'success' : 'destructive'}
                      className="text-[11px]"
                    >
                      {sale.status === 'completed' ? 'Concluída' : 'Cancelada'}
                    </Badge>
                  </td>
                  <td className="px-4 py-3 text-center">
                    {sale.synced_at ? (
                      <Badge variant="success" className="text-[11px]">Sync</Badge>
                    ) : (
                      <Badge variant="warning" className="text-[11px]">Pendente</Badge>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      {/* Footer hint */}
      <div className="flex items-center gap-3 px-5 py-2 border-t bg-card/50 shrink-0">
        <p className="text-xs text-muted-foreground/50">
          <kbd className="kbd">Esc</kbd>
          {' '}ou{' '}
          <kbd className="kbd">F8</kbd>
          {' '}para voltar ao PDV
        </p>
      </div>
    </div>
  )
}

function SummaryChip({
  label, value, color,
}: {
  label:  string
  value:  string
  color?: 'emerald' | 'yellow'
}) {
  return (
    <div className="flex items-center gap-1.5">
      <span className={cn(
        'text-xs font-bold tabular-nums',
        color === 'emerald' ? 'text-emerald-400' : color === 'yellow' ? 'text-yellow-400' : 'text-foreground',
      )}>
        {value}
      </span>
      <span className="text-xs text-muted-foreground">{label}</span>
    </div>
  )
}
