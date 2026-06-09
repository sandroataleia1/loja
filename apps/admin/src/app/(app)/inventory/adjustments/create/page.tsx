'use client'

import { useRouter } from 'next/navigation'
import { ArrowLeft } from 'lucide-react'
import Link from 'next/link'
import { Button } from '@/components/ui/button'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AdjustmentForm } from '@/features/inventory/components/adjustment-form'
import { ROUTES } from '@/constants'

export default function CreateAdjustmentPage() {
  const router = useRouter()

  function handleSuccess() {
    router.push(ROUTES.INVENTORY)
  }

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Novo Ajuste de Estoque"
        description="Registre um ajuste manual de estoque."
        actions={
          <Button variant="outline" asChild>
            <Link href={ROUTES.INVENTORY_ADJUSTMENTS}>
              <ArrowLeft className="mr-2 h-4 w-4" />
              Voltar
            </Link>
          </Button>
        }
      />

      <AdjustmentForm onSuccess={handleSuccess} />
    </div>
  )
}
