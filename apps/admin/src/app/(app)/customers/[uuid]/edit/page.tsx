'use client'

import { use } from 'react'
import Link from 'next/link'
import { ChevronLeft } from 'lucide-react'
import { useRouter } from 'next/navigation'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { CustomerForm } from '@/features/customers/components/customer-form'
import { useCustomer, useUpdateCustomer } from '@/features/customers/hooks'
import { ROUTES } from '@/constants'
import type { CreateCustomerRequest } from '@store/contracts'

interface EditCustomerPageProps {
  params: Promise<{ uuid: string }>
}

function EditCustomerContent({ uuid }: { uuid: string }) {
  const router = useRouter()
  const { data: customer, isLoading } = useCustomer(uuid)
  const { mutate: updateCustomer, isPending } = useUpdateCustomer(uuid)

  if (isLoading) {
    return <Skeleton className="h-96 w-full rounded-lg" />
  }

  if (!customer) {
    return (
      <p className="text-center text-sm text-muted-foreground py-12">
        Cliente não encontrado.
      </p>
    )
  }

  function handleSubmit(data: CreateCustomerRequest) {
    updateCustomer(data, {
      onSuccess: () => {
        toast.success('Cliente atualizado com sucesso.')
        router.push(`${ROUTES.CUSTOMERS}/${uuid}`)
      },
      onError: (err) => {
        toast.error(err instanceof Error ? err.message : 'Erro ao atualizar cliente.')
      },
    })
  }

  // Build defaultValues from the loaded customer
  const defaultValues: Partial<CreateCustomerRequest> = {
    person_type: customer.person_type,
    name:        customer.name,
    trade_name:  customer.trade_name        ?? undefined,
    document:    customer.document          ?? undefined,
    email:       customer.email             ?? undefined,
    birth_date:  customer.birth_date        ?? undefined,
    notes:       customer.notes             ?? undefined,
    addresses:   customer.addresses?.map((addr) => ({
      zipcode:    addr.zipcode,
      street:     addr.street,
      number:     addr.number,
      complement: addr.complement ?? undefined,
      district:   addr.district,
      city:       addr.city,
      state:      addr.state,
      country:    addr.country,
      is_default: addr.is_default,
    })),
    contacts: customer.contacts?.map((contact) => ({
      type:       contact.type,
      value:      contact.value,
      label:      contact.label ?? undefined,
      is_primary: contact.is_primary,
    })),
    tags: customer.tags?.map((t) => t.uuid),
  }

  return (
    <CustomerForm
      mode="edit"
      defaultValues={defaultValues}
      onSubmit={handleSubmit}
      isSubmitting={isPending}
    />
  )
}

export default function EditCustomerPage({ params }: EditCustomerPageProps) {
  const { uuid } = use(params)

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Editar Cliente"
        description="Atualize os dados do cliente."
        actions={
          <Button variant="outline" asChild>
            <Link href={`${ROUTES.CUSTOMERS}/${uuid}`}>
              <ChevronLeft className="mr-1.5 h-4 w-4" />
              Voltar
            </Link>
          </Button>
        }
      />
      <EditCustomerContent uuid={uuid} />
    </div>
  )
}
