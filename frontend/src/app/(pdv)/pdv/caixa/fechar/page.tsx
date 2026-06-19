'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { ArrowLeft, DoorOpen, Loader2, AlertCircle, CheckCircle2, Printer, ArrowRight } from 'lucide-react'
import { usePdvSessionStore } from '@/features/pdv/stores/pdvSessionStore'
import { usePdvCartStore }    from '@/features/pdv/stores/pdvCartStore'
import { useSessionMovements, useSessionSummary, useCloseSession } from '@/features/pdv/hooks'
import { useAuth } from '@/hooks/use-auth'
import { ClosingReportDocument } from '@/features/pdv/components/session/ClosingReportDocument'
import { ROUTES } from '@/constants'
import type { CashMovement } from '@/types/shared-types'
import '@/features/pdv/components/session/closing-report.print.css'

const fmtBRL = (cents: number) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)

const fmtDateTime = (iso: string) =>
  new Date(iso).toLocaleString('pt-BR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })

const MOVEMENT_LABEL: Record<string, string> = {
  opening:    'Abertura',
  sale:       'Venda',
  supply:     'Suprimento',
  withdrawal: 'Sangria',
  refund:     'Reembolso',
  adjustment: 'Ajuste',
  closing:    'Fechamento',
}

const MOVEMENT_COLOR: Record<string, string> = {
  supply:     'text-green-600 dark:text-green-400',
  sale:       'text-green-600 dark:text-green-400',
  refund:     'text-destructive',
  withdrawal: 'text-destructive',
}

function inputToCents(s: string): number {
  return Math.round(parseFloat(s.replace(',', '.')) * 100) || 0
}

function computeExpected(movements: CashMovement[], openingBalance: number) {
  const supplies    = movements.filter((m) => m.type === 'supply').reduce((s, m) => s + m.amount_cents, 0)
  const withdrawals = movements.filter((m) => m.type === 'withdrawal').reduce((s, m) => s + m.amount_cents, 0)
  const salesCash   = movements.filter((m) => m.type === 'sale').reduce((s, m) => s + m.amount_cents, 0)
  const expected    = openingBalance + supplies - withdrawals + salesCash
  return { supplies, withdrawals, salesCash, expected }
}

interface ClosedReportSnapshot {
  registerName:        string
  operatorName:        string
  openedAt:            string
  closedAt:            string
  openingBalanceCents: number
  closingBalanceCents: number
  difference:          number
  notes:               string
  summary:             ReturnType<typeof useSessionSummary>['data']
  movements:           CashMovement[]
}

export default function FecharCaixaPage() {
  const router = useRouter()
  const { user }  = useAuth()
  const { session, clearSession } = usePdvSessionStore()
  const clearCart  = usePdvCartStore((s) => s.clear)
  const closeSession = useCloseSession()

  const [closingStr, setClosingStr] = useState('')
  const [notes,      setNotes]      = useState('')
  const [error,      setError]      = useState<string | null>(null)
  const [snapshot,   setSnapshot]   = useState<ClosedReportSnapshot | null>(null)

  const { data: movements = [], isLoading } = useSessionMovements(session?.sessionUuid ?? null)
  const { data: summary }                   = useSessionSummary(session?.sessionUuid ?? null)

  if (!session && !snapshot) {
    router.replace(ROUTES.PDV)
    return null
  }

  const closingCents = inputToCents(closingStr)
  const { supplies, withdrawals, salesCash, expected } = computeExpected(
    movements,
    session?.openingBalanceCents ?? 0,
  )
  const difference = closingCents > 0 ? closingCents - expected : null

  async function handleClose() {
    if (closingCents <= 0 || !session) return
    setError(null)
    try {
      await closeSession.mutateAsync({
        uuid: session.sessionUuid,
        data: { closing_amount_cents: closingCents, notes: notes || undefined },
      })
      // Capture snapshot before clearing session state
      setSnapshot({
        registerName:        session.registerName,
        operatorName:        user?.name ?? 'Operador',
        openedAt:            session.openedAt,
        closedAt:            new Date().toISOString(),
        openingBalanceCents: session.openingBalanceCents,
        closingBalanceCents: closingCents,
        difference:          closingCents - expected,
        notes,
        summary,
        movements,
      })
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erro ao fechar o caixa')
    }
  }

  function handleDone() {
    clearCart()
    clearSession()
    router.push(ROUTES.PDV_CAIXA)
  }

  // ── Post-close: report screen ─────────────────────────────────────────────
  if (snapshot) {
    const diff = snapshot.difference
    const diffLabel = diff === 0 ? 'Sem diferença ✓'
      : diff > 0 ? `Sobra de ${fmtBRL(diff)}`
      : `Falta de ${fmtBRL(Math.abs(diff))}`
    const diffClass = diff === 0
      ? 'bg-green-50 dark:bg-green-950/30 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800'
      : Math.abs(diff) <= 100
        ? 'bg-yellow-50 dark:bg-yellow-950/30 text-yellow-700 dark:text-yellow-400 border-yellow-200 dark:border-yellow-800'
        : 'bg-destructive/10 text-destructive border-destructive/30'

    return (
      <div className="min-h-screen bg-background flex flex-col">
        <header className="flex items-center gap-3 h-14 px-4 border-b bg-card shrink-0">
          <CheckCircle2 className="w-5 h-5 text-green-500" />
          <div>
            <p className="font-bold text-sm">Caixa Fechado</p>
            <p className="text-xs text-muted-foreground">{snapshot.registerName}</p>
          </div>
        </header>

        <div className="flex-1 overflow-y-auto p-4 space-y-4 max-w-lg mx-auto w-full">
          <div className="bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 rounded-2xl p-4 text-center">
            <CheckCircle2 className="w-10 h-10 text-green-500 mx-auto mb-2" />
            <p className="font-bold text-green-700 dark:text-green-400">Caixa fechado com sucesso!</p>
            <p className="text-xs text-green-600 dark:text-green-500 mt-1">
              {fmtDateTime(snapshot.closedAt)}
            </p>
          </div>

          <div className={`flex justify-between rounded-xl p-3 text-sm font-bold border ${diffClass}`}>
            <span>Resultado</span>
            <span>{diffLabel}</span>
          </div>

          {snapshot.summary && snapshot.summary.sale_count > 0 && (
            <div className="bg-card border rounded-2xl overflow-hidden">
              <p className="px-4 py-3 text-xs text-muted-foreground font-medium uppercase tracking-wide border-b">
                Vendas — {snapshot.summary.sale_count} transações
              </p>
              <div className="divide-y">
                {[
                  ['Dinheiro',        snapshot.summary.by_method.cash],
                  ['PIX',             snapshot.summary.by_method.pix],
                  ['Cartão Crédito',  snapshot.summary.by_method.credit_card],
                  ['Cartão Débito',   snapshot.summary.by_method.debit_card],
                  ['Crédito da Loja', snapshot.summary.by_method.store_credit],
                ].filter(([, v]) => (v as number) > 0).map(([label]) => (
                  <div key={label as string} className="flex justify-between px-4 py-2 text-sm">
                    <span className="text-muted-foreground">{label as string}</span>
                    <span className="font-bold tabular-nums">
                      {fmtBRL(snapshot.summary!.by_method[
                        label === 'Dinheiro' ? 'cash'
                        : label === 'PIX' ? 'pix'
                        : label === 'Cartão Crédito' ? 'credit_card'
                        : label === 'Cartão Débito' ? 'debit_card'
                        : 'store_credit'
                      ])}
                    </span>
                  </div>
                ))}
              </div>
              <div className="border-t px-4 py-2.5 flex justify-between text-sm font-bold">
                <span>Total</span>
                <span>{fmtBRL(snapshot.summary.total_sales)}</span>
              </div>
            </div>
          )}

          <button
            onClick={() => window.print()}
            className="w-full h-12 rounded-2xl border border-border bg-card hover:bg-muted text-sm font-medium flex items-center justify-center gap-2 transition-colors"
          >
            <Printer className="w-4 h-4" />
            Imprimir Relatório de Fechamento
          </button>

          <button
            onClick={handleDone}
            className="w-full h-14 rounded-2xl bg-primary hover:bg-primary/90 text-primary-foreground font-bold text-base flex items-center justify-center gap-2 transition-all active:scale-[0.98]"
          >
            Ir para seleção de caixa
            <ArrowRight className="w-5 h-5" />
          </button>
        </div>

        {/* Hidden print document — only visible on print via CSS */}
        <ClosingReportDocument
          registerName={snapshot.registerName}
          operatorName={snapshot.operatorName}
          openedAt={snapshot.openedAt}
          closedAt={snapshot.closedAt}
          openingBalanceCents={snapshot.openingBalanceCents}
          closingBalanceCents={snapshot.closingBalanceCents}
          difference={snapshot.difference}
          notes={snapshot.notes}
          summary={snapshot.summary}
          movements={snapshot.movements}
        />
      </div>
    )
  }

  // ── Normal closing form ───────────────────────────────────────────────────
  return (
    <div className="min-h-screen bg-background flex flex-col">
      {/* Header */}
      <header className="flex items-center gap-3 h-14 px-4 border-b bg-card shrink-0">
        <button
          onClick={() => router.back()}
          className="text-muted-foreground hover:text-foreground transition-colors"
        >
          <ArrowLeft className="w-5 h-5" />
        </button>
        <div>
          <p className="font-bold text-sm">Fechar Caixa</p>
          <p className="text-xs text-muted-foreground">{session!.registerName}</p>
        </div>
      </header>

      <div className="flex-1 overflow-y-auto p-4 space-y-4 max-w-lg mx-auto w-full">

        {/* Informações da sessão */}
        <div className="bg-card border rounded-2xl p-4 space-y-2">
          <p className="text-xs text-muted-foreground font-medium uppercase tracking-wide">Sessão</p>
          <div className="grid grid-cols-2 gap-2 text-sm">
            <div>
              <p className="text-xs text-muted-foreground">Caixa</p>
              <p className="font-medium">{session!.registerName}</p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">Abertura</p>
              <p className="font-medium">{fmtDateTime(session!.openedAt)}</p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">Saldo inicial</p>
              <p className="font-medium">{fmtBRL(session!.openingBalanceCents)}</p>
            </div>
            {salesCash > 0 && (
              <div>
                <p className="text-xs text-muted-foreground">Vendas em dinheiro</p>
                <p className="font-medium text-green-600 dark:text-green-400">{fmtBRL(salesCash)}</p>
              </div>
            )}
            {supplies > 0 && (
              <div>
                <p className="text-xs text-muted-foreground">Suprimentos</p>
                <p className="font-medium text-green-600 dark:text-green-400">+ {fmtBRL(supplies)}</p>
              </div>
            )}
            {withdrawals > 0 && (
              <div>
                <p className="text-xs text-muted-foreground">Sangrias</p>
                <p className="font-medium text-destructive">- {fmtBRL(withdrawals)}</p>
              </div>
            )}
          </div>
          {expected > 0 && (
            <div className="border-t pt-2 flex justify-between text-sm font-bold">
              <span>Saldo esperado em caixa</span>
              <span>{fmtBRL(expected)}</span>
            </div>
          )}
        </div>

        {/* Resumo de vendas por método */}
        {summary && summary.sale_count > 0 && (
          <div className="bg-card border rounded-2xl overflow-hidden">
            <p className="px-4 py-3 text-xs text-muted-foreground font-medium uppercase tracking-wide border-b">
              Vendas — {summary.sale_count} {summary.sale_count === 1 ? 'transação' : 'transações'}
            </p>
            <div className="divide-y">
              {[
                ['Dinheiro',        summary.by_method.cash,         'text-green-600 dark:text-green-400'],
                ['PIX',             summary.by_method.pix,          'text-blue-600 dark:text-blue-400'],
                ['Cartão Crédito',  summary.by_method.credit_card,  ''],
                ['Cartão Débito',   summary.by_method.debit_card,   ''],
                ['Crédito da Loja', summary.by_method.store_credit, ''],
              ].filter(([, v]) => (v as number) > 0).map(([label, value, cls]) => (
                <div key={label as string} className="flex justify-between items-center px-4 py-2.5 text-sm">
                  <span className="text-muted-foreground">{label as string}</span>
                  <span className={`font-bold tabular-nums ${cls as string}`}>{fmtBRL(value as number)}</span>
                </div>
              ))}
            </div>
            <div className="border-t px-4 py-2.5 flex justify-between text-sm font-bold">
              <span>Total vendas</span>
              <span>{fmtBRL(summary.total_sales)}</span>
            </div>
          </div>
        )}

        {/* Movimentações */}
        {isLoading ? (
          <div className="flex items-center justify-center py-8 text-muted-foreground text-sm gap-2">
            <Loader2 className="w-4 h-4 animate-spin" />
            Carregando movimentações…
          </div>
        ) : movements.length > 0 && (
          <div className="bg-card border rounded-2xl overflow-hidden">
            <p className="px-4 py-3 text-xs text-muted-foreground font-medium uppercase tracking-wide border-b">
              Movimentações
            </p>
            <div className="divide-y max-h-52 overflow-y-auto">
              {movements.map((m) => (
                <div key={m.uuid} className="flex items-center gap-3 px-4 py-2.5 text-sm">
                  <div className="flex-1 min-w-0">
                    <p className="font-medium">{MOVEMENT_LABEL[m.type] ?? m.type}</p>
                    {m.description && (
                      <p className="text-xs text-muted-foreground truncate">{m.description}</p>
                    )}
                  </div>
                  <span className={`tabular-nums font-bold shrink-0 ${MOVEMENT_COLOR[m.type] ?? ''}`}>
                    {['withdrawal'].includes(m.type) ? '-' : '+'}
                    {fmtBRL(m.amount_cents)}
                  </span>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Contagem de caixa */}
        <div className="bg-card border rounded-2xl p-4 space-y-3">
          <p className="text-xs text-muted-foreground font-medium uppercase tracking-wide">
            Fechamento
          </p>

          <div>
            <label className="text-sm font-medium">Valor em caixa (sua contagem)</label>
            <div className="relative mt-1">
              <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground text-sm pointer-events-none">
                R$
              </span>
              <input
                type="text"
                inputMode="decimal"
                value={closingStr}
                onChange={(e) => setClosingStr(e.target.value)}
                placeholder="0,00"
                className="w-full h-12 rounded-xl border bg-background pl-9 pr-3 text-xl font-bold tabular-nums focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                autoFocus
              />
            </div>
          </div>

          {/* Diferença */}
          {difference !== null && expected > 0 && (
            <div className={`flex justify-between rounded-xl p-3 text-sm font-bold ${
              difference === 0
                ? 'bg-green-50 dark:bg-green-950/30 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800'
                : Math.abs(difference) <= 100
                  ? 'bg-yellow-50 dark:bg-yellow-950/30 text-yellow-700 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-800'
                  : 'bg-destructive/10 text-destructive border border-destructive/30'
            }`}>
              <span>Diferença</span>
              <span>
                {difference > 0 ? '+' : ''}{fmtBRL(difference)}
                {difference === 0 && ' ✓'}
              </span>
            </div>
          )}

          <div>
            <label className="text-sm text-muted-foreground">Observações (opcional)</label>
            <textarea
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              placeholder="Ocorrências, discrepâncias…"
              rows={2}
              className="mt-1 w-full rounded-xl border bg-background px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
            />
          </div>
        </div>

        {error && (
          <div className="flex items-center gap-2 rounded-xl bg-destructive/10 border border-destructive/30 px-3 py-2.5 text-destructive text-sm">
            <AlertCircle className="w-4 h-4 shrink-0" />
            {error}
          </div>
        )}

        {/* Botão fechar */}
        <button
          onClick={handleClose}
          disabled={closingCents <= 0 || closeSession.isPending}
          className="w-full h-14 rounded-2xl bg-destructive hover:bg-destructive/90 text-destructive-foreground font-bold text-base disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2 transition-all active:scale-[0.98]"
        >
          {closeSession.isPending
            ? <Loader2 className="w-5 h-5 animate-spin" />
            : <DoorOpen className="w-5 h-5" />}
          Fechar Caixa
        </button>

        <p className="text-center text-xs text-muted-foreground pb-4">
          Esta ação encerrará a sessão. Movimentações não poderão ser adicionadas após o fechamento.
        </p>
      </div>
    </div>
  )
}
