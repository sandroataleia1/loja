'use client'

import { useState } from 'react'
import Link from 'next/link'
import { ChevronLeft } from 'lucide-react'
import { useRouter } from 'next/navigation'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard } from '@/components/shared/app-card'
import { useCreateBrand } from '@/features/catalog/hooks'
import { ROUTES } from '@/constants'

export default function CreateBrandPage() {
  const router = useRouter()
  const { mutate: createBrand, isPending } = useCreateBrand()

  const [name,        setName]        = useState('')
  const [description, setDescription] = useState('')
  const [logoUrl,     setLogoUrl]     = useState('')
  const [websiteUrl,  setWebsiteUrl]  = useState('')
  const [isActive,    setIsActive]    = useState(true)

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    if (!name.trim()) return

    createBrand(
      {
        name:        name.trim(),
        description: description.trim() || undefined,
        logo_url:    logoUrl.trim()    || undefined,
        website_url: websiteUrl.trim() || undefined,
        is_active:   isActive,
      },
      {
        onSuccess: () => {
          toast.success('Marca criada com sucesso.')
          router.push(ROUTES.BRANDS)
        },
        onError: (err) => {
          toast.error(err instanceof Error ? err.message : 'Erro ao criar marca.')
        },
      },
    )
  }

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Nova Marca"
        description="Preencha os dados da nova marca."
        actions={
          <Button variant="outline" asChild>
            <Link href={ROUTES.BRANDS}>
              <ChevronLeft className="mr-1.5 h-4 w-4" />
              Voltar
            </Link>
          </Button>
        }
      />

      <form onSubmit={handleSubmit} className="space-y-6">
        <AppCard title="Dados da Marca">
          <div className="grid gap-4 sm:grid-cols-2 max-w-2xl">
            <div className="sm:col-span-2 space-y-1.5">
              <Label htmlFor="name">Nome *</Label>
              <Input
                id="name"
                placeholder="Nome da marca"
                value={name}
                onChange={(e) => setName(e.target.value)}
                required
              />
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="logo_url">URL do Logo</Label>
              <Input
                id="logo_url"
                placeholder="https://…"
                value={logoUrl}
                onChange={(e) => setLogoUrl(e.target.value)}
              />
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="website_url">Site</Label>
              <Input
                id="website_url"
                placeholder="https://…"
                value={websiteUrl}
                onChange={(e) => setWebsiteUrl(e.target.value)}
              />
            </div>

            <div className="sm:col-span-2 space-y-1.5">
              <Label htmlFor="description">Descrição</Label>
              <textarea
                id="description"
                className="w-full min-h-20 rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring resize-y"
                placeholder="Descrição da marca…"
                value={description}
                onChange={(e) => setDescription(e.target.value)}
              />
            </div>

            <div className="flex items-center gap-2">
              <input
                type="checkbox"
                id="is_active"
                checked={isActive}
                onChange={(e) => setIsActive(e.target.checked)}
                className="accent-primary"
              />
              <Label htmlFor="is_active" className="cursor-pointer">Marca ativa</Label>
            </div>
          </div>
        </AppCard>

        <div className="flex gap-2">
          <Button type="submit" disabled={isPending || !name.trim()}>
            {isPending ? 'Salvando…' : 'Criar Marca'}
          </Button>
        </div>
      </form>
    </div>
  )
}
