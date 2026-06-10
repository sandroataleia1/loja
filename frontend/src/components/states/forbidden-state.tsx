'use client'

import { ShieldX } from 'lucide-react'
import Link from 'next/link'
import { Button } from '@/components/ui/button'
import { ROUTES } from '@/constants'

export function ForbiddenState() {
  return (
    <div className="flex flex-col items-center justify-center gap-4 py-24 text-center">
      <div className="rounded-full bg-destructive/10 p-4">
        <ShieldX className="h-10 w-10 text-destructive" />
      </div>
      <div>
        <h2 className="text-xl font-semibold">Acesso negado</h2>
        <p className="text-sm text-muted-foreground mt-1">
          Você não tem permissão para acessar este recurso.
        </p>
      </div>
      <Button asChild variant="outline" size="sm">
        <Link href={ROUTES.HOME}>Voltar ao início</Link>
      </Button>
    </div>
  )
}
