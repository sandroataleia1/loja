'use client'

import { useRouter } from 'next/navigation'
import Link from 'next/link'
import { ChevronLeft } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { ConditionalForm } from '@/features/conditional/components/conditional-form'
import { useCreateConditional } from '@/features/conditional/hooks'
import { ROUTES } from '@/constants'
import type { CreateConditionalRequest } from '@store/contracts'

export default function CreateConditionalPage() {
  const router = useRouter()
  const { mutate: createConditional, isPending } = useCreateConditional()

  function handleSubmit(data: CreateConditionalRequest) {
    createConditional(data, {
      onSuccess: (conditional) => {
        toast.success('Condicional criado com sucesso.')
        router.push(`${ROUTES.CONDITIONALS}/${conditional.uuid}`)
      },
      onError: (err) => {
        toast.error(err instanceof Error ? err.message : 'Erro ao criar condicional.')
      },
    })
  }

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Novo Condicional"
        description="Registre uma saída condicional de produtos."
        actions={
          <Button variant="outline" asChild>
            <Link href={ROUTES.CONDITIONALS}>
              <ChevronLeft className="mr-1.5 h-4 w-4" />
              Voltar
            </Link>
          </Button>
        }
      />

      <ConditionalForm onSubmit={handleSubmit} isSubmitting={isPending} />
    </div>
  )
}
