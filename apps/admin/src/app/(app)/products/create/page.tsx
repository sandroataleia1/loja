'use client'

import Link from 'next/link'
import { ChevronLeft } from 'lucide-react'
import { useRouter } from 'next/navigation'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { ProductForm } from '@/features/catalog/components/product-form'
import { useCreateProduct } from '@/features/catalog/hooks'
import { ROUTES } from '@/constants'
import type { CreateProductRequest } from '@store/contracts'

export default function CreateProductPage() {
  const router  = useRouter()
  const { mutate: createProduct, isPending } = useCreateProduct()

  function handleSubmit(data: CreateProductRequest) {
    createProduct(data, {
      onSuccess: (created) => {
        toast.success('Produto criado com sucesso.')
        router.push(`${ROUTES.PRODUCTS}/${created.uuid}`)
      },
      onError: (err) => {
        toast.error(err instanceof Error ? err.message : 'Erro ao criar produto.')
      },
    })
  }

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Novo Produto"
        description="Preencha os dados do novo produto."
        actions={
          <Button variant="outline" asChild>
            <Link href={ROUTES.PRODUCTS}>
              <ChevronLeft className="mr-1.5 h-4 w-4" />
              Voltar
            </Link>
          </Button>
        }
      />
      <ProductForm
        mode="create"
        onSubmit={handleSubmit}
        isSubmitting={isPending}
      />
    </div>
  )
}
