import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { ArrowLeft } from 'lucide-react'
import { Button }    from '@/components/ui/button'
import { Input }     from '@/components/ui/input'
import { Label }     from '@/components/ui/label'
import { Separator } from '@/components/ui/separator'
import { useSessionStore } from '@/stores/sessionStore'
import { useSales }        from '@/modules/sales/hooks/useSale'
import { formatCurrency, parseCurrency } from '@/utils/currency'
import { formatDate } from '@/utils/date'
import { cn } from '@/lib/utils'

export function CloseRegisterPage() {
  const [cashCounted, setCashCounted] = useState('')
  const [confirmed, setConfirmed]     = useState(false)

  const { userName, openedAt, openingBalance, closeRegister, logout } = useSessionStore()
  const { data: sales = [], isLoading } = useSales(200)
  const navigate = useNavigate()

  // Filter today's sales only
  const todayStr  = new Date().toDateString()
  const todaySales = sales.filter((s) => new Date(s.created_at).toDateString() === todayStr)
  const totalSales = todaySales.reduce((sum, s) => sum + s.total, 0)
  const countSales = todaySales.length

  const counted   = parseCurrency(cashCounted)
  const expected  = openingBalance + totalSales
  const diff      = counted - expected
  const hasCounted = cashCounted !== ''

  const handleClose = () => {
    setConfirmed(true)
    closeRegister()
    logout()
    navigate('/login', { replace: true })
  }

  return (
    <div className="min-h-screen bg-background flex flex-col auth-enter">
      {/* Topbar */}
      <header className="flex items-center gap-3 px-6 py-4 border-b bg-card shrink-0">
        <button
          onClick={() => navigate(-1)}
          className="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground transition-colors"
        >
          <ArrowLeft className="w-4 h-4" />
          Voltar ao PDV
        </button>
        <Separator orientation="vertical" className="h-4 mx-1" />
        <h1 className="text-sm font-semibold">Fechamento de Caixa</h1>
      </header>

      <div className="flex-1 overflow-auto p-6">
        <div className="max-w-2xl mx-auto space-y-6">
          {/* Session info */}
          <div className="bg-card border rounded-xl p-5">
            <h2 className="text-base font-semibold mb-4">Informações do Turno</h2>
            <div className="grid grid-cols-3 gap-4">
              <InfoBlock label="Operador"     value={userName} />
              <InfoBlock label="Abertura"     value={openedAt ? formatDate(openedAt) : '—'} />
              <InfoBlock label="Fechamento"   value={new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })} />
            </div>
          </div>

          {/* Sales summary */}
          <div className="bg-card border rounded-xl p-5">
            <h2 className="text-base font-semibold mb-4">Resumo de Vendas (hoje)</h2>
            {isLoading ? (
              <p className="text-sm text-muted-foreground">Carregando...</p>
            ) : (
              <div className="space-y-3">
                <SummaryRow
                  label="Total de vendas"
                  value={`${countSales} venda${countSales !== 1 ? 's' : ''}`}
                  amount={totalSales}
                  highlight
                />

                {countSales === 0 && (
                  <p className="text-sm text-muted-foreground text-center py-4">
                    Nenhuma venda registrada hoje
                  </p>
                )}
              </div>
            )}
          </div>

          {/* Cash count */}
          <div className="bg-card border rounded-xl p-5 space-y-4">
            <h2 className="text-base font-semibold">Contagem de Caixa</h2>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <Label className="text-xs text-muted-foreground mb-1.5 block">
                  Fundo de troca inicial
                </Label>
                <p className="text-base font-semibold tabular-nums">
                  {formatCurrency(openingBalance)}
                </p>
              </div>
              <div>
                <Label className="text-xs text-muted-foreground mb-1.5 block">
                  Total de vendas
                </Label>
                <p className="text-base font-semibold tabular-nums text-primary">
                  {formatCurrency(totalSales)}
                </p>
              </div>
            </div>

            <Separator />

            <div className="space-y-2">
              <Label htmlFor="cash-counted">Dinheiro contado no caixa</Label>
              <div className="relative max-w-xs">
                <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground text-sm pointer-events-none">R$</span>
                <Input
                  id="cash-counted"
                  value={cashCounted}
                  onChange={(e) => setCashCounted(e.target.value)}
                  placeholder="0,00"
                  className="pl-9 h-11 text-right tabular-nums"
                  autoComplete="off"
                  onKeyDown={(e) => e.key === 'Enter' && handleClose()}
                />
              </div>
            </div>

            {hasCounted && (
              <div className={cn(
                'flex items-center justify-between rounded-lg px-4 py-3 text-sm font-medium',
                Math.abs(diff) < 100
                  ? 'bg-emerald-500/10 text-emerald-500'
                  : diff > 0
                    ? 'bg-blue-500/10 text-blue-400'
                    : 'bg-destructive/10 text-destructive',
              )}>
                <span>
                  {Math.abs(diff) < 100
                    ? 'Caixa correto'
                    : diff > 0
                      ? 'Sobra de caixa'
                      : 'Diferença de caixa'}
                </span>
                <span className="tabular-nums font-bold">
                  {diff >= 0 ? '+' : ''}{formatCurrency(diff)}
                </span>
              </div>
            )}
          </div>

          {/* CTA */}
          <div className="flex gap-3">
            <Button
              variant="outline"
              className="flex-1 h-12"
              onClick={() => navigate(-1)}
            >
              Cancelar
            </Button>
            <Button
              variant="destructive"
              className="flex-1 h-12 font-semibold"
              onClick={handleClose}
              disabled={confirmed}
            >
              {confirmed ? 'Fechando...' : 'Fechar Caixa e Sair'}
            </Button>
          </div>
        </div>
      </div>
    </div>
  )
}

function InfoBlock({ label, value }: { label: string; value: string }) {
  return (
    <div className="bg-muted/50 rounded-lg px-3 py-2.5">
      <p className="text-xs text-muted-foreground mb-0.5">{label}</p>
      <p className="text-sm font-medium">{value}</p>
    </div>
  )
}

function SummaryRow({
  label, value, amount, highlight,
}: {
  label: string; value: string; amount: number; highlight?: boolean
}) {
  return (
    <div className={cn(
      'flex items-center justify-between py-2.5 px-3 rounded-lg',
      highlight ? 'bg-muted/50' : '',
    )}>
      <div>
        <p className={cn('text-sm', highlight ? 'font-semibold' : '')}>{label}</p>
        <p className="text-xs text-muted-foreground">{value}</p>
      </div>
      <p className={cn(
        'tabular-nums',
        highlight ? 'text-lg font-bold text-primary' : 'text-sm font-medium',
      )}>
        {formatCurrency(amount)}
      </p>
    </div>
  )
}
