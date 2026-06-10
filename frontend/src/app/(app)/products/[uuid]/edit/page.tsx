'use client'

import { use } from 'react'
import Link from 'next/link'
import { ChevronLeft } from 'lucide-react'
import { useRouter } from 'next/navigation'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { ProductForm } from '@/features/catalog/components/product-form'
import { useProduct, useUpdateProduct } from '@/features/catalog/hooks'
import { ROUTES } from '@/constants'
import type { CreateProductRequest } from '@store/contracts'

interface EditProductPageProps {
  params: Promise<{ uuid: string }>
}

function EditProductContent({ uuid }: { uuid: string }) {
  const router = useRouter()
  const { data: product, isLoading, isError } = useProduct(uuid)
  const { mutate: updateProduct, isPending }  = useUpdateProduct(uuid)

  function handleSubmit(data: CreateProductRequest) {
    updateProduct(data, {
      onSuccess: () => {
        toast.success('Produto atualizado com sucesso.')
        router.push(`${ROUTES.PRODUCTS}/${uuid}`)
      },
      onError: (err) => {
        toast.error(err instanceof Error ? err.message : 'Erro ao atualizar produto.')
      },
    })
  }

  if (isLoading) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-48 w-full rounded-lg" />
        <Skeleton className="h-64 w-full rounded-lg" />
      </div>
    )
  }

  if (isError || !product) {
    return (
      <p className="text-center text-sm text-muted-foreground py-12">
        Produto não encontrado.
      </p>
    )
  }

  return (
    <ProductForm
      mode="edit"
      defaultValues={product}
      onSubmit={handleSubmit}
      isSubmitting={isPending}
    />
  )
}

export default function EditProductPage({ params }: EditProductPageProps) {
  const { uuid } = use(params)

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Editar Produto"
        description="Atualize os dados do produto."
        actions={
          <Button variant="outline" asChild>
            <Link href={`${ROUTES.PRODUCTS}/${uuid}`}>
              <ChevronLeft className="mr-1.5 h-4 w-4" />
              Voltar
            </Link>
          </Button>
        }
      />
      <EditProductContent uuid={uuid} />
    </div>
  )
}
