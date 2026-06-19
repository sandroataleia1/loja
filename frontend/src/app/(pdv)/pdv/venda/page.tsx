'use client'

import { usePdvSession } from '@/features/pdv/hooks/usePdvSession'
import { usePdvCartStore } from '@/features/pdv/stores/pdvCartStore'

const fmtBRL = (cents: number) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)

const SHORTCUT_KEYS = [
  ['F1', 'Produto'],
  ['F2', 'Cliente'],
  ['F4', 'Cobrar'],
  ['F8', 'Histórico'],
  ['F9', 'Cancelar'],
  ['F10', 'Desconto'],
]

export default function VendaPage() {
  const { session } = usePdvSession()
  const total       = usePdvCartStore((s) => s.totalCents())
  const itemCount   = usePdvCartStore((s) => s.itemCount())

  if (!session) return null

  return (
    <div className="flex flex-col h-screen bg-background overflow-hidden">

      {/* ── Topbar ─────────────────────────────────────────────────────────── */}
      <header className="flex items-center justify-between h-11 px-4 border-b bg-card text-sm shrink-0">
        <div className="flex items-center gap-3 font-medium">
          <span className="text-primary font-bold">PDV Fashion</span>
          <span className="text-border">|</span>
          <span className="text-muted-foreground">{session.registerName}</span>
        </div>
        <div className="flex items-center gap-4 text-xs text-muted-foreground">
          <span>
            Aberto:{' '}
            <span className="text-foreground font-medium">
              {new Date(session.openedAt).toLocaleTimeString('pt-BR', {
                hour: '2-digit', minute: '2-digit',
              })}
            </span>
          </span>
          <a
            href="/sales"
            className="hover:text-primary transition-colors"
            title="Voltar ao admin"
          >
            Sair do PDV
          </a>
        </div>
      </header>

      {/* ── Conteúdo principal: 2 painéis ──────────────────────────────────── */}
      <div className="flex flex-1 overflow-hidden">

        {/* ── Painel esquerdo: busca de produtos ────────────────────────── */}
        <div className="flex flex-col flex-1 border-r overflow-hidden">
          {/* Search bar placeholder */}
          <div className="p-3 border-b">
            <div className="flex items-center gap-2 h-10 rounded-lg border bg-muted/50 px-3 text-sm text-muted-foreground">
              <span className="opacity-50">🔍</span>
              <span>Buscar produto por nome, SKU ou código de barras… (Etapa 2)</span>
            </div>
          </div>

          {/* Product grid placeholder */}
          <div className="flex flex-1 items-center justify-center text-muted-foreground text-sm">
            <div className="text-center space-y-2">
              <p className="text-4xl opacity-20">📦</p>
              <p>Grade de produtos — Etapa 2</p>
            </div>
          </div>
        </div>

        {/* ── Painel direito: carrinho ───────────────────────────────────── */}
        <div className="flex flex-col w-96 shrink-0 bg-card overflow-hidden">

          {/* Cliente */}
          <div className="flex items-center gap-2 px-4 py-2.5 border-b text-sm">
            <span className="text-muted-foreground">Cliente:</span>
            <span className="text-muted-foreground italic">Consumidor Final</span>
            <button className="ml-auto text-xs text-primary hover:underline">F2 Buscar</button>
          </div>

          {/* Items */}
          <div className="flex-1 overflow-y-auto px-4 py-3">
            {itemCount === 0 ? (
              <div className="flex flex-col items-center justify-center h-full text-muted-foreground text-sm gap-2 opacity-60">
                <p className="text-3xl">🛒</p>
                <p>Carrinho vazio</p>
                <p className="text-xs">Busque um produto para iniciar</p>
              </div>
            ) : (
              <p className="text-sm text-muted-foreground">{itemCount} item(s) no carrinho</p>
            )}
          </div>

          {/* Totais */}
          <div className="border-t px-4 py-3 space-y-1 text-sm">
            <div className="flex justify-between text-muted-foreground">
              <span>Subtotal</span>
              <span>{fmtBRL(total)}</span>
            </div>
            <div className="flex justify-between font-bold text-base">
              <span>Total</span>
              <span className="text-primary">{fmtBRL(total)}</span>
            </div>
          </div>

          {/* Botão Cobrar */}
          <div className="px-4 pb-3">
            <button
              disabled={itemCount === 0}
              className="w-full h-12 rounded-xl bg-primary text-primary-foreground font-bold text-sm disabled:opacity-40 disabled:cursor-not-allowed transition-opacity hover:bg-primary/90 active:scale-[0.98]"
            >
              F4 — Cobrar {fmtBRL(total)}
            </button>
          </div>
        </div>
      </div>

      {/* ── Keybar ─────────────────────────────────────────────────────────── */}
      <footer className="flex items-center gap-1 px-3 py-1.5 border-t bg-muted/30 text-xs text-muted-foreground shrink-0">
        {SHORTCUT_KEYS.map(([key, label]) => (
          <span key={key} className="flex items-center gap-1 mr-3">
            <kbd className="px-1.5 py-0.5 rounded border border-border bg-background font-mono text-[10px] leading-none">
              {key}
            </kbd>
            {label}
          </span>
        ))}
        <span className="ml-auto opacity-50">PDV Fashion v1.0</span>
      </footer>
    </div>
  )
}
