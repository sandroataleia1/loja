'use client'

import Link from 'next/link'
import { ChevronLeft } from 'lucide-react'
import { useRouter } from 'next/navigation'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { CustomerForm } from '@/features/customers/components/customer-form'
import { useCreateCustomer } from '@/features/customers/hooks'
import { ROUTES } from '@/constants'
import type { CreateCustomerRequest } from '@store/contracts'

export default function CreateCustomerPage() {
  const router = useRouter()
  const { mutate: createCustomer, isPending } = useCreateCustomer()

  function handleSubmit(data: CreateCustomerRequest) {
    createCustomer(data, {
      onSuccess: (created) => {
        toast.success('Cliente criado com sucesso.')
        router.push(`${ROUTES.CUSTOMERS}/${created.uuid}`)
      },
      onError: (err) => {
        toast.error(err instanceof Error ? err.message : 'Erro ao criar cliente.')
      },
    })
  }

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Novo Cliente"
        description="Preencha os dados do novo cliente."
        actions={
          <Button variant="outline" asChild>
            <Link href={ROUTES.CUSTOMERS}>
              <ChevronLeft className="mr-1.5 h-4 w-4" />
              Voltar
            </Link>
          </Button>
        }
      />
      <CustomerForm
        mode="create"
        onSubmit={handleSubmit}
        isSubmitting={isPending}
      />
    </div>
  )
}
