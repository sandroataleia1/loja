'use client'

import { useState, FormEvent, Suspense } from 'react'
import { useRouter, useSearchParams } from 'next/navigation'
import { Monitor, ArrowLeft, Loader2 } from 'lucide-react'
import { toast } from 'sonner'
import { usePdvSessionStore } from '@/features/pdv/stores/pdvSessionStore'
import { useOpenSession } from '@/features/pdv/hooks'
import { Button } from '@/components/ui/button'
import { ROUTES } from '@/constants'

function fmt(cents: number) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)
}

function AbrirCaixaForm() {
  const router   = useRouter()
  const params   = useSearchParams()
  const regUuid  = params.get('register') ?? ''
  const regName  = decodeURIComponent(params.get('name') ?? 'Caixa')

  const { setSession } = usePdvSessionStore()
  const openSession    = useOpenSession()

  const [valueStr, setValueStr] = useState('0')
  const valueCents = Math.round(parseFloat(valueStr.replace(',', '.')) * 100) || 0

  if (!regUuid) {
    router.replace(ROUTES.PDV_CAIXA)
    return null
  }

  async function handleSubmit(e: FormEvent) {
    e.preventDefault()
    try {
      const session = await openSession.mutateAsync({
        cash_register_id:     regUuid,
        opening_amount_cents: valueCents,
      })
      setSession({
        sessionUuid:         session.uuid,
        registerUuid:        regUuid,
        registerName:        regName,
        storeUuid:           session.store_id,
        openedAt:            session.opened_at,
        openingBalanceCents: valueCents,
      })
      toast.success('Caixa aberto com sucesso!')
      router.replace(ROUTES.PDV_VENDA)
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Erro ao abrir o caixa.')
    }
  }

  return (
    <div className="flex min-h-screen flex-col items-center justify-center gap-8 p-6 bg-background">
      {/* Header */}
      <div className="flex flex-col items-center gap-3 text-center">
        <div className="flex items-center justify-center w-14 h-14 rounded-2xl bg-primary/10 text-primary">
          <Monitor className="w-7 h-7" />
        </div>
        <h1 className="text-2xl font-bold">Abrir Caixa</h1>
        <p className="text-sm text-muted-foreground font-medium">{regName}</p>
      </div>

      {/* Form */}
      <form
        onSubmit={handleSubmit}
        className="w-full max-w-sm space-y-5 bg-card border rounded-2xl p-6 shadow-sm"
      >
        <div className="space-y-2">
          <label className="text-sm font-medium">
            Fundo de troco (valor em caixa)
          </label>
          <div className="relative">
            <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground text-sm font-medium select-none">
              R$
            </span>
            <input
              type="number"
              min="0"
              step="0.01"
              value={valueStr}
              onChange={(e) => setValueStr(e.target.value)}
              className="w-full h-13 rounded-xl border bg-background pl-10 pr-4 text-xl font-bold tabular-nums focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all"
              placeholder="0,00"
              autoFocus
            />
          </div>
          {valueCents > 0 && (
            <p className="text-xs text-muted-foreground">
              Valor de abertura: <span className="font-semibold text-foreground">{fmt(valueCents)}</span>
            </p>
          )}
        </div>

        <div className="flex gap-3">
          <Button
            type="button"
            variant="outline"
            className="flex-1"
            onClick={() => router.back()}
            disabled={openSession.isPending}
          >
            <ArrowLeft className="w-4 h-4 mr-1.5" />
            Voltar
          </Button>
          <Button type="submit" className="flex-1" disabled={openSession.isPending}>
            {openSession.isPending ? (
              <><Loader2 className="w-4 h-4 mr-1.5 animate-spin" />Abrindo...</>
            ) : (
              'Abrir Caixa'
            )}
          </Button>
        </div>
      </form>
    </div>
  )
}

export default function AbrirCaixaPage() {
  return (
    <Suspense>
      <AbrirCaixaForm />
    </Suspense>
  )
}
