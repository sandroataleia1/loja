'use client'

import { useForm, Controller } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useEffect, useState } from 'react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { AppCard } from '@/components/shared/app-card'
import { BrandSelector } from './brand-selector'
import { CategorySelector } from './category-selector'
import { QuickCreateBrandModal } from './quick-create-brand-modal'
import { QuickCreateCategoryModal } from './quick-create-category-modal'
import type { CreateProductRequest } from '@store/contracts'
import type { Product } from '@store/shared-types'

// ── Helpers ───────────────────────────────────────────────────────────────────

function slugify(str: string): string {
  return str
    .toLowerCase()
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .replace(/[^a-z0-9\s-]/g, '')
    .trim()
    .replace(/\s+/g, '-')
}

// ── Zod Schema ────────────────────────────────────────────────────────────────

const UNIT_OPTIONS = [
  { value: 'UN', label: 'UN — Unidade' },
  { value: 'M',  label: 'M — Metro linear' },
  { value: 'M2', label: 'M² — Metro quadrado' },
  { value: 'M3', label: 'M³ — Metro cúbico' },
  { value: 'KG', label: 'KG — Quilograma' },
  { value: 'LT', label: 'LT — Litro' },
  { value: 'CX', label: 'CX — Caixa' },
  { value: 'SC', label: 'SC — Saco' },
] as const

const ORIGIN_OPTIONS = [
  { value: 0, label: '0 – Nacional' },
  { value: 1, label: '1 – Estrangeira (importação direta)' },
  { value: 2, label: '2 – Estrangeira (adquirida internamente)' },
  { value: 3, label: '3 – Nacional, conteúdo importado > 40%' },
  { value: 4, label: '4 – Nacional (processos produtivos básicos)' },
  { value: 5, label: '5 – Estrangeira (importação direta, sem similar)' },
  { value: 6, label: '6 – Estrangeira (interna, sem similar)' },
  { value: 7, label: '7 – Nacional, conteúdo importado ≤ 40%' },
  { value: 8, label: '8 – Nacional, conteúdo importado > 40% e ≤ 70%' },
] as const

const productSchema = z.object({
  name: z.string().min(2, 'Nome deve ter ao menos 2 caracteres'),
  slug: z.string().min(2, 'Slug obrigatório'),
  type: z.enum(['simple', 'variable', 'kit'] as const, {
    required_error: 'Tipo obrigatório',
  }),
  unit_of_measure: z.enum(['UN', 'M', 'M2', 'M3', 'KG', 'LT', 'CX', 'SC'] as const).nullable().optional(),
  status: z.enum(['draft', 'active', 'inactive', 'archived', 'seasonal'] as const, {
    required_error: 'Status obrigatório',
  }),
  visibility: z.enum(['PRIVATE', 'PUBLIC', 'UNLISTED'] as const).optional(),
  base_price: z.coerce.number().positive('Deve ser positivo').optional().or(z.literal('')),
  cost_price: z.coerce.number().positive('Deve ser positivo').optional().or(z.literal('')),
  brand_id:       z.string().nullable().optional(),
  category_uuids: z.array(z.string()).optional(),
  description:       z.string().optional().or(z.literal('')),
  short_description: z.string().max(500, 'Máximo 500 caracteres').optional().or(z.literal('')),
  is_featured:    z.boolean().optional(),
  is_digital:     z.boolean().optional(),
  is_publishable: z.boolean().optional(),
  // Fiscal
  ncm:          z.string().max(10).optional().or(z.literal('')),
  cest:         z.string().max(9).optional().or(z.literal('')),
  cfop_default: z.string().max(5).optional().or(z.literal('')),
  origin_code:  z.coerce.number().int().min(0).max(8).optional(),
})

type ProductFormValues = z.infer<typeof productSchema>

// ── Props ─────────────────────────────────────────────────────────────────────

interface ProductFormProps {
  defaultValues?: Partial<Product>
  onSubmit:       (data: CreateProductRequest) => void
  isSubmitting:   boolean
  mode:           'create' | 'edit'
}

// ── Component ─────────────────────────────────────────────────────────────────

export function ProductForm({ defaultValues, onSubmit, isSubmitting, mode }: ProductFormProps) {
  const [brandModalOpen,    setBrandModalOpen]    = useState(false)
  const [categoryModalOpen, setCategoryModalOpen] = useState(false)

  const {
    register,
    handleSubmit,
    control,
    watch,
    setValue,
    formState: { errors },
  } = useForm<ProductFormValues>({
    resolver: zodResolver(productSchema),
    defaultValues: {
      name:              defaultValues?.name              ?? '',
      slug:              defaultValues?.slug              ?? '',
      type:              defaultValues?.type              ?? 'simple',
      unit_of_measure:   defaultValues?.unit_of_measure   ?? 'UN',
      status:            defaultValues?.status            ?? 'draft',
      visibility:        defaultValues?.visibility        ?? 'PRIVATE',
      base_price:        defaultValues?.base_price        ?? '',
      cost_price:        defaultValues?.cost_price        ?? '',
      brand_id:          defaultValues?.brand_id          ?? null,
      category_uuids:    defaultValues?.categories?.map((c) => c.uuid) ?? [],
      description:       defaultValues?.description       ?? '',
      short_description: defaultValues?.short_description ?? '',
      is_featured:       defaultValues?.is_featured       ?? false,
      is_digital:        defaultValues?.is_digital        ?? false,
      is_publishable:    defaultValues?.is_publishable    ?? false,
      ncm:               defaultValues?.ncm          ?? '',
      cest:              defaultValues?.cest         ?? '',
      cfop_default:      defaultValues?.cfop_default ?? '',
      origin_code:       defaultValues?.origin_code  ?? 0,
    },
  })

  const nameValue = watch('name')

  // Auto-generate slug from name in create mode
  useEffect(() => {
    if (mode === 'create') {
      setValue('slug', slugify(nameValue ?? ''))
    }
  }, [nameValue, mode, setValue])

  function handleFormSubmit(values: ProductFormValues) {
    const payload: CreateProductRequest = {
      name:              values.name,
      type:              values.type,
      unit_of_measure:   values.unit_of_measure ?? undefined,
      status:            values.status,
      visibility:        values.visibility,
      base_price:        values.base_price ? Number(values.base_price) : undefined,
      cost_price:        values.cost_price ? Number(values.cost_price) : undefined,
      brand_id:          values.brand_id   || undefined,
      category_uuids:    values.category_uuids?.length ? values.category_uuids : undefined,
      description:       values.description       || undefined,
      short_description: values.short_description || undefined,
      is_featured:       values.is_featured,
      is_digital:        values.is_digital,
      is_publishable:    values.is_publishable,
      ncm:               values.ncm          || undefined,
      cest:              values.cest         || undefined,
      cfop_default:      values.cfop_default || undefined,
      origin_code:       values.origin_code  ?? 0,
    }
    onSubmit(payload)
  }

  return (
    <form onSubmit={handleSubmit(handleFormSubmit)} className="space-y-6">

      {/* ── Section 1: Identificação ── */}
      <AppCard title="Identificação">
        <div className="grid gap-4 sm:grid-cols-2 max-w-2xl">

          {/* name */}
          <div className="sm:col-span-2 space-y-1.5">
            <Label htmlFor="name">Nome *</Label>
            <Input id="name" placeholder="Nome do produto" {...register('name')} />
            {errors.name && <p className="text-xs text-destructive">{errors.name.message}</p>}
          </div>

          {/* slug */}
          <div className="sm:col-span-2 space-y-1.5">
            <Label htmlFor="slug">Slug</Label>
            <Input id="slug" placeholder="slug-do-produto" {...register('slug')} />
            {errors.slug && <p className="text-xs text-destructive">{errors.slug.message}</p>}
          </div>

          {/* type */}
          <div className="space-y-1.5">
            <Label htmlFor="type">Tipo *</Label>
            <select
              id="type"
              className="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
              {...register('type')}
            >
              <option value="simple">Simples</option>
              <option value="variable">Variável</option>
              <option value="kit">Kit</option>
            </select>
            {errors.type && <p className="text-xs text-destructive">{errors.type.message}</p>}
          </div>

          {/* unit_of_measure */}
          <div className="space-y-1.5">
            <Label htmlFor="unit_of_measure">Unidade de Medida</Label>
            <select
              id="unit_of_measure"
              className="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
              {...register('unit_of_measure')}
            >
              {UNIT_OPTIONS.map((opt) => (
                <option key={opt.value} value={opt.value}>{opt.label}</option>
              ))}
            </select>
            {errors.unit_of_measure && <p className="text-xs text-destructive">{errors.unit_of_measure.message}</p>}
          </div>
        </div>
      </AppCard>

      {/* ── Section 2: Status e Visibilidade ── */}
      <AppCard title="Status e Visibilidade">
        <div className="grid gap-4 sm:grid-cols-2 max-w-2xl">

          {/* status */}
          <div className="space-y-1.5">
            <Label htmlFor="status">Status *</Label>
            <select
              id="status"
              className="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
              {...register('status')}
            >
              <option value="draft">Rascunho</option>
              <option value="active">Ativo</option>
              <option value="inactive">Inativo</option>
              <option value="archived">Arquivado</option>
              <option value="seasonal">Sazonal</option>
            </select>
            {errors.status && <p className="text-xs text-destructive">{errors.status.message}</p>}
          </div>

          {/* visibility */}
          <div className="space-y-1.5">
            <Label htmlFor="visibility">Visibilidade</Label>
            <select
              id="visibility"
              className="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
              {...register('visibility')}
            >
              <option value="PRIVATE">Privado</option>
              <option value="PUBLIC">Público</option>
              <option value="UNLISTED">Não listado</option>
            </select>
          </div>
        </div>
      </AppCard>

      {/* ── Section 3: Preços ── */}
      <AppCard title="Preços">
        <div className="grid gap-4 sm:grid-cols-2 max-w-2xl">
          <div className="space-y-1.5">
            <Label htmlFor="base_price">Preço Base (R$)</Label>
            <Input
              id="base_price"
              type="number"
              min="0"
              step="0.01"
              placeholder="0,00"
              {...register('base_price')}
            />
            {errors.base_price && <p className="text-xs text-destructive">{errors.base_price.message}</p>}
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="cost_price">Custo (R$)</Label>
            <Input
              id="cost_price"
              type="number"
              min="0"
              step="0.01"
              placeholder="0,00"
              {...register('cost_price')}
            />
            {errors.cost_price && <p className="text-xs text-destructive">{errors.cost_price.message}</p>}
          </div>
        </div>
      </AppCard>

      {/* ── Section 4: Marca e Categorias ── */}
      <AppCard title="Marca e Categorias">
        <div className="grid gap-4 sm:grid-cols-2 max-w-2xl">
          <div className="space-y-1.5">
            <Label>Marca</Label>
            <Controller
              control={control}
              name="brand_id"
              render={({ field }) => (
                <BrandSelector
                  value={field.value ?? null}
                  onChange={field.onChange}
                  disabled={isSubmitting}
                  onQuickCreate={() => setBrandModalOpen(true)}
                />
              )}
            />
          </div>
          <div className="sm:col-span-2 space-y-1.5">
            <Label>Categorias</Label>
            <Controller
              control={control}
              name="category_uuids"
              render={({ field }) => (
                <CategorySelector
                  value={field.value ?? []}
                  onChange={field.onChange}
                  disabled={isSubmitting}
                  onQuickCreate={() => setCategoryModalOpen(true)}
                />
              )}
            />
          </div>
        </div>
      </AppCard>

      {/* ── Modais de criação rápida ── */}
      <QuickCreateBrandModal
        open={brandModalOpen}
        onClose={() => setBrandModalOpen(false)}
        onCreated={(uuid) => {
          setValue('brand_id', uuid)
          setBrandModalOpen(false)
        }}
      />
      <QuickCreateCategoryModal
        open={categoryModalOpen}
        onClose={() => setCategoryModalOpen(false)}
        onCreated={(uuid) => {
          const current = watch('category_uuids') ?? []
          if (!current.includes(uuid)) setValue('category_uuids', [...current, uuid])
          setCategoryModalOpen(false)
        }}
      />

      {/* ── Section 5: Descrição ── */}
      <AppCard title="Descrição">
        <div className="space-y-4 max-w-2xl">
          <div className="space-y-1.5">
            <Label htmlFor="short_description">Descrição Curta</Label>
            <textarea
              id="short_description"
              className="w-full min-h-20 rounded-md border bg-background px-3 py-2 text-sm outline-none ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 resize-y"
              placeholder="Descrição resumida (até 500 caracteres)…"
              {...register('short_description')}
            />
            {errors.short_description && (
              <p className="text-xs text-destructive">{errors.short_description.message}</p>
            )}
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="description">Descrição Completa</Label>
            <textarea
              id="description"
              className="w-full min-h-32 rounded-md border bg-background px-3 py-2 text-sm outline-none ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 resize-y"
              placeholder="Descrição detalhada do produto…"
              {...register('description')}
            />
          </div>
        </div>
      </AppCard>

      {/* ── Section 6: Configurações ── */}
      <AppCard title="Configurações">
        <div className="space-y-3 max-w-2xl">
          <label className="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" {...register('is_featured')} className="accent-primary" />
            <span className="text-sm">Produto em destaque</span>
          </label>
          <label className="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" {...register('is_digital')} className="accent-primary" />
            <span className="text-sm">Produto digital</span>
          </label>
          <label className="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" {...register('is_publishable')} className="accent-primary" />
            <span className="text-sm">Pode ser publicado</span>
          </label>
        </div>
      </AppCard>

      {/* ── Section 7: Dados Fiscais ── */}
      <AppCard title="Dados Fiscais">
        <div className="grid gap-4 sm:grid-cols-2 max-w-2xl">

          {/* NCM */}
          <div className="space-y-1.5">
            <Label htmlFor="ncm">NCM</Label>
            <Input
              id="ncm"
              placeholder="00000000"
              maxLength={10}
              {...register('ncm')}
            />
            <p className="text-xs text-muted-foreground">Nomenclatura Comum do Mercosul (8 dígitos)</p>
            {errors.ncm && <p className="text-xs text-destructive">{errors.ncm.message}</p>}
          </div>

          {/* CEST */}
          <div className="space-y-1.5">
            <Label htmlFor="cest">CEST</Label>
            <Input
              id="cest"
              placeholder="0000000"
              maxLength={9}
              {...register('cest')}
            />
            <p className="text-xs text-muted-foreground">Código Especificador da Substituição Tributária (7 dígitos)</p>
            {errors.cest && <p className="text-xs text-destructive">{errors.cest.message}</p>}
          </div>

          {/* CFOP */}
          <div className="space-y-1.5">
            <Label htmlFor="cfop_default">CFOP Padrão</Label>
            <Input
              id="cfop_default"
              placeholder="5102"
              maxLength={5}
              {...register('cfop_default')}
            />
            <p className="text-xs text-muted-foreground">Código Fiscal de Operações — saída padrão (4 dígitos)</p>
            {errors.cfop_default && <p className="text-xs text-destructive">{errors.cfop_default.message}</p>}
          </div>

          {/* Origem */}
          <div className="space-y-1.5">
            <Label htmlFor="origin_code">Origem da Mercadoria</Label>
            <select
              id="origin_code"
              className="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
              {...register('origin_code')}
            >
              {ORIGIN_OPTIONS.map((opt) => (
                <option key={opt.value} value={opt.value}>{opt.label}</option>
              ))}
            </select>
            {errors.origin_code && <p className="text-xs text-destructive">{errors.origin_code.message}</p>}
          </div>

        </div>
      </AppCard>

      {/* ── Actions ── */}
      <div className="flex gap-2">
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting ? 'Salvando…' : mode === 'create' ? 'Criar Produto' : 'Salvar Alterações'}
        </Button>
      </div>
    </form>
  )
}
