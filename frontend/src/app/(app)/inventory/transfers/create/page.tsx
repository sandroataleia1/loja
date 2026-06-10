'use client'

import { useRouter } from 'next/navigation'
import Link from 'next/link'
import { ArrowLeft } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { TransferForm } from '@/features/inventory/components/transfer-form'
import { ROUTES } from '@/constants'

export default function CreateTransferPage() {
  const router = useRouter()

  function handleSuccess(uuid: string) {
    router.push(`${ROUTES.INVENTORY_TRANSFERS}/${uuid}`)
  }

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Nova Transferência"
        description="Crie uma transferência de estoque entre lojas."
        actions={
          <Button variant="outline" asChild>
            <Link href={ROUTES.INVENTORY_TRANSFERS}>
              <ArrowLeft className="mr-2 h-4 w-4" />
              Voltar
            </Link>
          </Button>
        }
      />

      <TransferForm onSuccess={handleSuccess} />
    </div>
  )
}
