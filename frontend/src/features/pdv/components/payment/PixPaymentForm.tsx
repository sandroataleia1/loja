'use client'

import { useState, useEffect, useRef, useCallback } from 'react'
import { Copy, Check, QrCode, Key, Loader2, CheckCircle2, AlertCircle, RefreshCw } from 'lucide-react'
import { cn } from '@/lib/utils'
import {
  usePixPublicInfo,
  useCreatePixCharge,
  usePixChargeStatus,
  useSimulatePixPayment,
} from '../../hooks/usePixCharge'

const fmtBRL = (cents: number) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)

function useCountdown(expiresAt: string | null): number {
  const [seconds, setSeconds] = useState(0)

  useEffect(() => {
    if (!expiresAt) return
    const update = () => {
      const diff = Math.max(0, Math.floor((new Date(expiresAt).getTime() - Date.now()) / 1000))
      setSeconds(diff)
    }
    update()
    const id = setInterval(update, 1000)
    return () => clearInterval(id)
  }, [expiresAt])

  return seconds
}

function formatCountdown(s: number): string {
  const m = Math.floor(s / 60)
  const sec = s % 60
  return `${m}:${String(sec).padStart(2, '0')}`
}

type PixMode = 'key' | 'qrcode'

interface Props {
  remainingCents: number
  saleUuid?:      string
  onAdd:          (amountCents: number) => void
}

export function PixPaymentForm({ remainingCents, saleUuid, onAdd }: Props) {
  const [mode,       setMode]       = useState<PixMode>('key')
  const [pixKey,     setPixKey]     = useState('')
  const [copied,     setCopied]     = useState(false)
  const [chargeUuid, setChargeUuid] = useState<string | null>(null)
  const [confirmed,  setConfirmed]  = useState(false)
  const paidCalledRef               = useRef(false)

  const { data: publicInfo } = usePixPublicInfo()
  const createCharge         = useCreatePixCharge()
  const simulate             = useSimulatePixPayment()

  const handlePaid = useCallback((charge: import('@/services/pix.service').PixCharge) => {
    if (paidCalledRef.current) return
    paidCalledRef.current = true
    setConfirmed(true)
    setTimeout(() => onAdd(charge.amount_cents), 800)
  }, [onAdd])

  const { data: charge } = usePixChargeStatus(chargeUuid, { onPaid: handlePaid })

  const countdown = useCountdown(charge?.expires_at ?? null)

  // Auto-fill pix_key from store settings
  useEffect(() => {
    if (publicInfo?.pix_key && !pixKey) {
      setPixKey(publicInfo.pix_key)
    }
  }, [publicInfo?.pix_key]) // eslint-disable-line react-hooks/exhaustive-deps

  function handleCopy(text: string) {
    navigator.clipboard.writeText(text).then(() => {
      setCopied(true)
      setTimeout(() => setCopied(false), 2000)
    })
  }

  async function handleCreateCharge() {
    paidCalledRef.current = false
    setConfirmed(false)
    const result = await createCharge.mutateAsync({
      amount_cents:        remainingCents,
      description:         `Venda PDV${saleUuid ? ` #${saleUuid.slice(0, 8)}` : ''}`,
      sale_uuid:           saleUuid,
      expires_in_minutes:  30,
    })
    setChargeUuid(result.uuid)
  }

  function handleModeSwitch(newMode: PixMode) {
    setMode(newMode)
    setChargeUuid(null)
    setConfirmed(false)
    paidCalledRef.current = false
  }

  const hasQrCode       = publicInfo?.has_qrcode_enabled ?? false
  const isSandbox       = publicInfo?.environment !== 'production'
  const chargeIsPending = charge?.status === 'pending'

  return (
    <div className="space-y-4">
      {/* Mode toggle */}
      <div className="flex rounded-xl border overflow-hidden">
        <button
          onClick={() => handleModeSwitch('key')}
          className={cn(
            'flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-medium transition-colors',
            mode === 'key' ? 'bg-primary text-primary-foreground' : 'hover:bg-accent text-muted-foreground',
          )}
        >
          <Key className="w-3.5 h-3.5" />
          Chave PIX
        </button>
        <button
          onClick={() => handleModeSwitch('qrcode')}
          className={cn(
            'flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-medium transition-colors',
            mode === 'qrcode' ? 'bg-primary text-primary-foreground' : 'hover:bg-accent text-muted-foreground',
          )}
        >
          <QrCode className="w-3.5 h-3.5" />
          QR Code
          {!hasQrCode && <span className="text-[9px] opacity-60 ml-0.5">(config)</span>}
        </button>
      </div>

      {mode === 'key' ? (
        /* ── Modo Chave PIX ─────────────────────────────────────────────── */
        <div className="space-y-3">
          <div className="bg-muted/40 rounded-2xl p-3 text-center">
            <p className="text-xs text-muted-foreground mb-0.5">Valor a receber</p>
            <p className="text-3xl font-bold tabular-nums">{fmtBRL(remainingCents)}</p>
          </div>

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
                  onClick={() => handleCopy(pixKey)}
                  className="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                  title="Copiar chave"
                >
                  {copied
                    ? <Check className="w-4 h-4 text-green-600" />
                    : <Copy className="w-4 h-4" />}
                </button>
              )}
            </div>
          </div>

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
        <div className="space-y-3">
          {!hasQrCode && !chargeUuid ? (
            /* Gateway não configurado */
            <div className="rounded-2xl border border-dashed p-6 text-center space-y-2 text-muted-foreground">
              <AlertCircle className="w-8 h-8 mx-auto opacity-40" />
              <p className="text-sm font-medium">Gateway PIX não configurado</p>
              <p className="text-xs">Configure a conta Asaas em <strong>Configurações → Gateway PIX</strong>.</p>
            </div>
          ) : !chargeUuid ? (
            /* Botão para gerar QR */
            <div className="space-y-3">
              <div className="bg-muted/40 rounded-2xl p-3 text-center">
                <p className="text-xs text-muted-foreground mb-0.5">Valor a cobrar</p>
                <p className="text-3xl font-bold tabular-nums">{fmtBRL(remainingCents)}</p>
              </div>
              <button
                onClick={handleCreateCharge}
                disabled={createCharge.isPending}
                className="w-full h-12 rounded-xl bg-primary text-primary-foreground font-bold hover:bg-primary/90 disabled:opacity-60 flex items-center justify-center gap-2 active:scale-[0.98] transition-all"
              >
                {createCharge.isPending
                  ? <><Loader2 className="w-4 h-4 animate-spin" /> Gerando QR Code…</>
                  : <><QrCode className="w-4 h-4" /> Gerar QR Code PIX</>}
              </button>
              {createCharge.isError && (
                <p className="text-xs text-destructive text-center">
                  {createCharge.error.message}
                </p>
              )}
            </div>
          ) : confirmed ? (
            /* Pagamento confirmado */
            <div className="flex flex-col items-center gap-3 py-8 text-center">
              <CheckCircle2 className="w-14 h-14 text-green-500" />
              <p className="font-bold text-green-600 dark:text-green-400">PIX Recebido!</p>
              <p className="text-sm text-muted-foreground">{fmtBRL(remainingCents)}</p>
            </div>
          ) : charge && chargeIsPending ? (
            /* QR Code exibido — aguardando pagamento */
            <div className="space-y-3">
              {/* QR Code image */}
              <div className="flex justify-center">
                {charge.qr_code_image ? (
                  <img
                    src={`data:image/png;base64,${charge.qr_code_image}`}
                    alt="QR Code PIX"
                    className="w-44 h-44 rounded-xl border p-1 bg-white"
                  />
                ) : (
                  <div className="w-44 h-44 rounded-xl border flex items-center justify-center bg-muted/30">
                    <QrCode className="w-12 h-12 text-muted-foreground/40" />
                  </div>
                )}
              </div>

              {/* Valor + timer */}
              <div className="text-center">
                <p className="text-2xl font-bold tabular-nums">{fmtBRL(charge.amount_cents)}</p>
                {countdown > 0 ? (
                  <p className="text-xs text-muted-foreground mt-0.5">
                    Expira em <span className="font-mono font-semibold">{formatCountdown(countdown)}</span>
                  </p>
                ) : (
                  <p className="text-xs text-destructive mt-0.5 font-medium">QR Code expirado</p>
                )}
              </div>

              {/* Copia-e-cola */}
              {charge.pix_copy_paste && (
                <button
                  onClick={() => handleCopy(charge.pix_copy_paste!)}
                  className="w-full flex items-center justify-center gap-2 h-10 rounded-xl border hover:bg-accent transition-colors text-sm font-medium"
                >
                  {copied
                    ? <><Check className="w-3.5 h-3.5 text-green-600" /> Copiado!</>
                    : <><Copy className="w-3.5 h-3.5" /> Copiar código PIX</>}
                </button>
              )}

              {/* Polling status indicator */}
              <div className="flex items-center justify-center gap-1.5 text-xs text-muted-foreground">
                <Loader2 className="w-3 h-3 animate-spin" />
                Aguardando pagamento…
              </div>

              {/* Sandbox simulate button */}
              {isSandbox && (
                <button
                  onClick={() => simulate.mutate(charge.uuid)}
                  disabled={simulate.isPending}
                  className="w-full h-9 rounded-xl border border-dashed text-xs text-muted-foreground hover:text-foreground hover:border-primary transition-colors flex items-center justify-center gap-1.5"
                >
                  <RefreshCw className={cn('w-3 h-3', simulate.isPending && 'animate-spin')} />
                  Simular pagamento (sandbox)
                </button>
              )}
            </div>
          ) : charge?.status === 'expired' || countdown === 0 ? (
            /* Expirado — opção de regerar */
            <div className="space-y-3 text-center">
              <div className="rounded-2xl border border-dashed p-5 space-y-1.5 text-muted-foreground">
                <AlertCircle className="w-8 h-8 mx-auto opacity-40" />
                <p className="text-sm font-medium">QR Code expirado</p>
              </div>
              <button
                onClick={() => { setChargeUuid(null); paidCalledRef.current = false }}
                className="w-full h-11 rounded-xl border text-sm font-medium hover:bg-accent transition-colors flex items-center justify-center gap-2"
              >
                <RefreshCw className="w-4 h-4" />
                Gerar novo QR Code
              </button>
            </div>
          ) : null}
        </div>
      )}
    </div>
  )
}
