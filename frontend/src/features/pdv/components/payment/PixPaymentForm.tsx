'use client'

import { useState } from 'react'
import { Copy, Check, QrCode, Key } from 'lucide-react'
import { cn } from '@/lib/utils'

const fmtBRL = (cents: number) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)

type PixMode = 'key' | 'qrcode'

interface Props {
  remainingCents: number
  onAdd:          (amountCents: number) => void
}

export function PixPaymentForm({ remainingCents, onAdd }: Props) {
  const [mode,    setMode]    = useState<PixMode>('key')
  const [pixKey,  setPixKey]  = useState('')
  const [copied,  setCopied]  = useState(false)

  function handleCopy() {
    if (!pixKey) return
    navigator.clipboard.writeText(pixKey).then(() => {
      setCopied(true)
      setTimeout(() => setCopied(false), 2000)
    })
  }

  return (
    <div className="space-y-4">
      {/* Mode toggle */}
      <div className="flex rounded-xl border overflow-hidden">
        <button
          onClick={() => setMode('key')}
          className={cn(
            'flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-medium transition-colors',
            mode === 'key' ? 'bg-primary text-primary-foreground' : 'hover:bg-accent text-muted-foreground',
          )}
        >
          <Key className="w-3.5 h-3.5" />
          Chave PIX
        </button>
        <button
          onClick={() => setMode('qrcode')}
          className={cn(
            'flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-medium transition-colors',
            mode === 'qrcode' ? 'bg-primary text-primary-foreground' : 'hover:bg-accent text-muted-foreground',
          )}
        >
          <QrCode className="w-3.5 h-3.5" />
          QR Code
        </button>
      </div>

      {mode === 'key' ? (
        /* ── Modo Chave PIX ─────────────────────────────────────────────── */
        <div className="space-y-3">
          {/* Valor */}
          <div className="bg-muted/40 rounded-2xl p-3 text-center">
            <p className="text-xs text-muted-foreground mb-0.5">Valor a receber</p>
            <p className="text-3xl font-bold tabular-nums">{fmtBRL(remainingCents)}</p>
          </div>

          {/* Chave PIX */}
          <div>
            <label className="text-xs text-muted-foreground">Chave PIX da loja</label>
            <div className="relative mt-1">
              <input
                type="text"
                value={pixKey}
                onChange={(e) => setPixKey(e.target.value)}
                placeholder="CNPJ, CPF, e-mail, telefone ou chave aleatória"
                className="w-full h-10 rounded-xl border bg-background px-3 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
              />
              {pixKey && (
                <button
                  onClick={handleCopy}
                  className="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                  title="Copiar chave"
                >
                  {copied
                    ? <Check className="w-4 h-4 text-green-600" />
                    : <Copy className="w-4 h-4" />
                  }
                </button>
              )}
            </div>
          </div>

          {/* Instrução ao cliente */}
          {pixKey && (
            <div className="rounded-xl border bg-muted/30 p-3 text-sm space-y-1.5">
              <p className="font-medium text-xs text-muted-foreground uppercase tracking-wide">
                Instrução para o cliente
              </p>
              <p className="text-sm">
                Abra o app do banco, acesse <span className="font-semibold">PIX → Pagar</span> e transfira:
              </p>
              <div className="bg-background rounded-lg px-3 py-2 font-bold text-center text-lg tabular-nums">
                {fmtBRL(remainingCents)}
              </div>
              <p className="text-sm">
                Para a chave: <span className="font-mono font-semibold break-all">{pixKey}</span>
              </p>
            </div>
          )}

          <button
            onClick={() => onAdd(remainingCents)}
            disabled={!pixKey.trim()}
            className="w-full h-12 rounded-xl bg-primary text-primary-foreground font-bold hover:bg-primary/90 disabled:opacity-40 disabled:cursor-not-allowed active:scale-[0.98] transition-all"
          >
            Confirmar Recebimento PIX
          </button>
        </div>
      ) : (
        /* ── Modo QR Code ───────────────────────────────────────────────── */
        <div className="space-y-4 text-center">
          <div className="flex flex-col items-center gap-3 py-8 border rounded-2xl bg-muted/30">
            <div className="w-28 h-28 border-2 border-dashed border-muted-foreground/30 rounded-xl flex items-center justify-center">
              <QrCode className="w-10 h-10 text-muted-foreground/40" />
            </div>
            <div>
              <p className="font-semibold text-sm">QR Code dinâmico</p>
              <p className="text-2xl font-bold tabular-nums mt-1">{fmtBRL(remainingCents)}</p>
              <p className="text-[11px] text-muted-foreground/60 mt-2">
                Integração com gateway (Asaas/Efí) disponível na Etapa 5
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
      )}
    </div>
  )
}
