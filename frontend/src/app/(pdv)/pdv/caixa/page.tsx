'use client'

import { useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { Monitor, ChevronRight, Plus } from 'lucide-react'
import { useCashRegisters } from '@/features/pdv/hooks'
import { usePdvSessionStore } from '@/features/pdv/stores/pdvSessionStore'
import { Skeleton } from '@/components/ui/skeleton'
import { ROUTES } from '@/constants'

export default function PdvCaixaPage() {
  const router      = useRouter()
  const { session } = usePdvSessionStore()
  const { data: registers, isLoading } = useCashRegisters()

  useEffect(() => {
    if (session) router.replace(ROUTES.PDV_VENDA)
  }, [session, router])

  if (session) return null

  const active = registers?.filter((r) => r.is_active) ?? []

  function handleSelect(uuid: string, name: string) {
    router.push(`${ROUTES.PDV_CAIXA_ABRIR}?register=${uuid}&name=${encodeURIComponent(name)}`)
  }

  return (
    <div className="flex min-h-screen flex-col items-center justify-center gap-8 p-6 bg-background">
      {/* Header */}
      <div className="flex flex-col items-center gap-3 text-center">
        <div className="flex items-center justify-center w-14 h-14 rounded-2xl bg-primary/10 text-primary">
          <Monitor className="w-7 h-7" />
        </div>
        <h1 className="text-2xl font-bold">Selecionar Caixa</h1>
        <p className="text-sm text-muted-foreground">
          Escolha o caixa para iniciar o atendimento
        </p>
      </div>

      {/* Lista de caixas */}
      <div className="w-full max-w-md space-y-2">
        {isLoading ? (
          Array.from({ length: 3 }).map((_, i) => (
            <Skeleton key={i} className="h-16 w-full rounded-xl" />
          ))
        ) : active.length === 0 ? (
          <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed p-10 text-center text-muted-foreground">
            <Monitor className="w-8 h-8 opacity-40" />
            <p className="text-sm">Nenhum caixa ativo encontrado.</p>
            <p className="text-xs">Cadastre um caixa em Configurações → PDV → Caixas.</p>
          </div>
        ) : (
          active.map((reg) => (
            <button
              key={reg.uuid}
              onClick={() => handleSelect(reg.uuid, reg.name)}
              className="w-full flex items-center justify-between gap-4 rounded-xl border bg-card px-4 py-3.5 text-left transition-colors hover:bg-accent hover:border-primary/30 group"
            >
              <div className="flex items-center gap-3">
                <div className="flex items-center justify-center w-10 h-10 rounded-lg bg-primary/10 text-primary shrink-0">
                  <Monitor className="w-5 h-5" />
                </div>
                <div>
                  <p className="font-semibold text-sm">{reg.name}</p>
                  <p className="text-xs text-muted-foreground">Código: {reg.code}</p>
                </div>
              </div>
              <ChevronRight className="w-4 h-4 text-muted-foreground group-hover:text-primary transition-colors shrink-0" />
            </button>
          ))
        )}
      </div>

      <a
        href={ROUTES.CASH_REGISTERS}
        className="flex items-center gap-1.5 text-xs text-muted-foreground hover:text-primary transition-colors"
      >
        <Plus className="w-3 h-3" />
        Gerenciar caixas no admin
      </a>
    </div>
  )
}
