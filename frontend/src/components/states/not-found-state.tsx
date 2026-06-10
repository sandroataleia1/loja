'use client'

import { Search } from 'lucide-react'
import Link from 'next/link'
import { Button } from '@/components/ui/button'
import { ROUTES } from '@/constants'

export function NotFoundState() {
  return (
    <div className="flex flex-col items-center justify-center gap-4 py-24 text-center">
      <div className="rounded-full bg-muted p-4">
        <Search className="h-10 w-10 text-muted-foreground" />
      </div>
      <div>
        <h2 className="text-xl font-semibold">Página não encontrada</h2>
        <p className="text-sm text-muted-foreground mt-1">
          O recurso que você procura não existe ou foi removido.
        </p>
      </div>
      <Button asChild variant="outline" size="sm">
        <Link href={ROUTES.HOME}>Voltar ao início</Link>
      </Button>
    </div>
  )
}
