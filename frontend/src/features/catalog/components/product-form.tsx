'use client'

import { useForm, Controller, useFieldArray } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useEffect, useState } from 'react'
import { Plus, Trash2 } from 'lucide-react'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { AppCard } from '@/components/shared/app-card'
import { ProductImageManager } from './product-image-manager'
import { BrandSelector } from './brand-selector'
import { CategorySelector } from './category-selector'
import { QuickCreateBrandModal } from './quick-create-brand-modal'
import { QuickCreateCategoryModal } from './quick-create-category-modal'
import type { CreateProductRequest } from '@store/contracts'
import type { Product, ProductMedia } from '@store/shared-types'

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

// ── Options ───────────────────────────────────────────────────────────────────

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

const BARCODE_TYPE_OPTIONS = [
  { value: 'ean13',   label: 'EAN-13' },
  { value: 'ean8',    label: 'EAN-8' },
  { value: 'dun14',   label: 'DUN-14' },
  { value: 'code128', label: 'Code 128' },
  { value: 'qrcode',  label: 'QR Code' },
  { value: 'custom',  label: 'Personalizado' },
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

// ── Tabs ──────────────────────────────────────────────────────────────────────

const TABS = [
  {
    id:     'principal',
    label:  'Principal',
    fields: [
      'name', 'slug', 'type', 'unit_of_measure', 'location',
      'base_price', 'cost_price',
      'brand_id', 'category_uuids',
      'short_description', 'description', 'internal_notes',
    ] as const,
  },
  {
    id:     'codigos_barras',
    label:  'Cód. Barras',
    fields: ['barcodes'] as const,
  },
  {
    id:     'status',
    label:  'Status',
    fields: ['status', 'visibility'] as const,
  },
  {
    id:     'configuracoes',
    label:  'Configurações',
    fields: ['is_featured', 'is_digital', 'is_publishable'] as const,
  },
  {
    id:     'fiscal',
    label:  'Fiscal',
    fields: ['ncm', 'cest', 'cfop_default', 'origin_code'] as const,
  },
] as const

type TabId = (typeof TABS)[number]['id']

// ── Zod Schema ────────────────────────────────────────────────────────────────

const barcodeSchema = z.object({
  value:      z.string().min(1, 'Código obrigatório').max(100),
  type:       z.string().default('ean13'),
  is_primary: z.boolean().default(false),
})

const productSchema = z.object({
  name:              z.string().min(2, 'Nome deve ter ao menos 2 caracteres'),
  slug:              z.string().min(2, 'Slug obrigatório'),
  type:              z.enum(['simple', 'variable', 'kit'] as const, { required_error: 'Tipo obrigatório' }),
  unit_of_measure:   z.enum(['UN', 'M', 'M2', 'M3', 'KG', 'LT', 'CX', 'SC'] as const).nullable().optional(),
  status:            z.enum(['draft', 'active', 'inactive', 'archived', 'seasonal'] as const, { required_error: 'Status obrigatório' }),
  visibility:        z.enum(['PRIVATE', 'PUBLIC', 'UNLISTED'] as const).optional(),
  base_price:        z.coerce.number().positive('Deve ser positivo').optional().or(z.literal('')),
  cost_price:        z.coerce.number().positive('Deve ser positivo').optional().or(z.literal('')),
  brand_id:          z.string().nullable().optional(),
  category_uuids:    z.array(z.string()).optional(),
  description:       z.string().optional().or(z.literal('')),
  short_description: z.string().max(500, 'Máximo 500 caracteres').optional().or(z.literal('')),
  internal_notes:    z.string().optional().or(z.literal('')),
  is_featured:       z.boolean().optional(),
  is_digital:        z.boolean().optional(),
  is_publishable:    z.boolean().optional(),
  location:          z.string().max(100).optional().or(z.literal('')),
  barcodes:          z.array(barcodeSchema).max(2, 'Máximo 2 códigos de barras').optional(),
  ncm:               z.string().max(10).optional().or(z.literal('')),
  cest:              z.string().max(9).optional().or(z.literal('')),
  cfop_default:      z.string().max(5).optional().or(z.literal('')),
  origin_code:       z.coerce.number().int().min(0).max(8).optional(),
})

type ProductFormValues = z.infer<typeof productSchema>

// ── Props ─────────────────────────────────────────────────────────────────────

interface ProductFormProps {
  defaultValues?:   Partial<Product>
  onSubmit:         (data: CreateProductRequest) => void
  isSubmitting:     boolean
  mode:             'create' | 'edit'
  images?:          ProductMedia[]
  onRefreshImages?: () => void
}

// ── Tab bar ───────────────────────────────────────────────────────────────────

type AllTabId = TabId | 'imagens'

function TabBar({
  active,
  errors,
  onChange,
  showImages,
}: {
  active:      AllTabId
  errors:      Record<string, unknown>
  onChange:    (id: AllTabId) => void
  showImages:  boolean
}) {
  return (
    <div className="flex flex-wrap gap-2">
      {TABS.map((tab) => {
        const hasError = tab.fields.some((f) => f in errors)
        const isActive = active === tab.id
        return (
          <button
            key={tab.id}
            type="button"
            onClick={() => onChange(tab.id)}
            className={cn(
              'relative inline-flex items-center gap-1.5 rounded-md px-4 py-2 text-sm font-medium transition-colors',
              isActive
                ? 'bg-primary text-primary-foreground shadow-sm'
                : 'bg-muted text-muted-foreground hover:bg-accent hover:text-accent-foreground',
            )}
          >
            {tab.label}
            {hasError && <span className="h-1.5 w-1.5 rounded-full bg-destructive" />}
          </button>
        )
      })}
      {showImages && (
        <button
          type="button"
          onClick={() => onChange('imagens')}
          className={cn(
            'relative inline-flex items-center gap-1.5 rounded-md px-4 py-2 text-sm font-medium transition-colors',
            active === 'imagens'
              ? 'bg-primary text-primary-foreground shadow-sm'
              : 'bg-muted text-muted-foreground hover:bg-accent hover:text-accent-foreground',
          )}
        >
          Imagens
        </button>
      )}
    </div>
  )
}

// ── Component ─────────────────────────────────────────────────────────────────

export function ProductForm({ defaultValues, onSubmit, isSubmitting, mode, images, onRefreshImages }: ProductFormProps) {
  const [activeTab,         setActiveTab]         = useState<AllTabId>('principal')
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
      status:            defaultValues?.status            ?? 'active',
      visibility:        defaultValues?.visibility        ?? 'PRIVATE',
      base_price:        defaultValues?.base_price        ?? '',
      cost_price:        defaultValues?.cost_price        ?? '',
      brand_id:          defaultValues?.brand_id          ?? null,
      category_uuids:    defaultValues?.categories?.map((c) => c.uuid) ?? [],
      description:       defaultValues?.description       ?? '',
      short_description: defaultValues?.short_description ?? '',
      internal_notes:    defaultValues?.internal_notes    ?? '',
      is_featured:       defaultValues?.is_featured       ?? false,
      is_digital:        defaultValues?.is_digital        ?? false,
      is_publishable:    defaultValues?.is_publishable    ?? false,
      location:          defaultValues?.location          ?? '',
      barcodes:          defaultValues?.barcodes?.map((b) => ({
        value:      b.value,
        type:       b.type,
        is_primary: b.is_primary,
      })) ?? [],
      ncm:               defaultValues?.ncm          ?? '',
      cest:              defaultValues?.cest         ?? '',
      cfop_default:      defaultValues?.cfop_default ?? '',
      origin_code:       defaultValues?.origin_code  ?? 0,
    },
  })

  const { fields: barcodeFields, append: appendBarcode, remove: removeBarcode } = useFieldArray({
    control,
    name: 'barcodes',
  })

  const nameValue = watch('name')

  useEffect(() => {
    if (mode === 'create') {
      setValue('slug', slugify(nameValue ?? ''))
    }
  }, [nameValue, mode, setValue])

  function handleFormSubmit(values: ProductFormValues) {
    const barcodes = (values.barcodes ?? [])
      .filter((b) => b.value.trim() !== '')
      .map((b, i) => ({ ...b, is_primary: i === 0 }))

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
      internal_notes:    values.internal_notes    || undefined,
      is_featured:       values.is_featured,
      is_digital:        values.is_digital,
      is_publishable:    values.is_publishable,
      location:          values.location || undefined,
      barcodes:          barcodes.length ? barcodes : undefined,
      ncm:               values.ncm          || undefined,
      cest:              values.cest         || undefined,
      cfop_default:      values.cfop_default || undefined,
      origin_code:       values.origin_code  ?? 0,
    }
    onSubmit(payload)
  }

  function handleInvalid() {
    for (const tab of TABS) {
      if (tab.fields.some((f) => f in errors)) {
        setActiveTab(tab.id)
        break
      }
    }
  }

  const showImages = mode === 'edit' && !!defaultValues?.uuid

  return (
    <form onSubmit={handleSubmit(handleFormSubmit, handleInvalid)} className="space-y-6">

      {/* ── Tab buttons ── */}
      <TabBar active={activeTab} errors={errors} onChange={setActiveTab} showImages={showImages} />

      {/* ── Tab: Principal ── */}
      {activeTab === 'principal' && (
        <div className="space-y-6">

          {/* Identificação */}
          <AppCard title="Identificação">
            <div className="grid gap-4 sm:grid-cols-2 max-w-2xl">

              <div className="sm:col-span-2 space-y-1.5">
                <Label htmlFor="name">Nome *</Label>
                <Input id="name" placeholder="Nome do produto" {...register('name')} />
                {errors.name && <p className="text-xs text-destructive">{errors.name.message}</p>}
              </div>

              <div className="sm:col-span-2 space-y-1.5">
                <Label htmlFor="slug">Slug</Label>
                <Input id="slug" placeholder="slug-do-produto" {...register('slug')} />
                {errors.slug && <p className="text-xs text-destructive">{errors.slug.message}</p>}
              </div>

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
              </div>

              <div className="sm:col-span-2 space-y-1.5">
                <Label htmlFor="location">Localização Física</Label>
                <Input
                  id="location"
                  placeholder="Ex: Galpão A / Prateleira 3 / Corredor 2"
                  {...register('location')}
                />
              </div>
            </div>
          </AppCard>

          {/* Preços */}
          <AppCard title="Preços">
            <div className="grid gap-4 sm:grid-cols-2 max-w-2xl">
              <div className="space-y-1.5">
                <Label htmlFor="base_price">Preço de Venda (R$)</Label>
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
                <Label htmlFor="cost_price">Preço de Custo (R$)</Label>
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

          {/* Marca e Categorias */}
          <AppCard title="Classificação">
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

          {/* Descrição */}
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
              <div className="space-y-1.5">
                <Label htmlFor="internal_notes">Observações Internas</Label>
                <textarea
                  id="internal_notes"
                  className="w-full min-h-24 rounded-md border bg-background px-3 py-2 text-sm outline-none ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 resize-y"
                  placeholder="Notas internas — não visíveis ao cliente…"
                  {...register('internal_notes')}
                />
              </div>
            </div>
          </AppCard>

        </div>
      )}

      {/* ── Tab: Códigos de Barras ── */}
      {activeTab === 'codigos_barras' && (
        <AppCard title="Códigos de Barras">
          <div className="space-y-4 max-w-2xl">
            <div className="flex items-center justify-between">
              <p className="text-sm text-muted-foreground">Máximo de 2 códigos por produto.</p>
              {barcodeFields.length < 2 && (
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={() => appendBarcode({ value: '', type: 'ean13', is_primary: barcodeFields.length === 0 })}
                >
                  <Plus className="mr-1 h-3.5 w-3.5" />
                  Adicionar código
                </Button>
              )}
            </div>
            {barcodeFields.length === 0 && (
              <p className="text-xs text-muted-foreground">Nenhum código cadastrado. Clique em &quot;Adicionar código&quot; para incluir.</p>
            )}
            {barcodeFields.map((field, index) => (
              <div key={field.id} className="flex gap-2 items-start">
                <div className="flex-1 space-y-1">
                  <div className="flex gap-2">
                    <select
                      className="w-36 rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
                      {...register(`barcodes.${index}.type`)}
                    >
                      {BARCODE_TYPE_OPTIONS.map((opt) => (
                        <option key={opt.value} value={opt.value}>{opt.label}</option>
                      ))}
                    </select>
                    <Input
                      placeholder={index === 0 ? 'Código primário' : 'Código secundário'}
                      {...register(`barcodes.${index}.value`)}
                    />
                  </div>
                  {errors.barcodes?.[index]?.value && (
                    <p className="text-xs text-destructive">{errors.barcodes[index].value?.message}</p>
                  )}
                </div>
                <Button
                  type="button"
                  variant="ghost"
                  size="icon"
                  className="mt-0.5 text-muted-foreground hover:text-destructive"
                  onClick={() => removeBarcode(index)}
                >
                  <Trash2 className="h-4 w-4" />
                </Button>
              </div>
            ))}
            {errors.barcodes && !Array.isArray(errors.barcodes) && (
              <p className="text-xs text-destructive">{errors.barcodes.message}</p>
            )}
          </div>
        </AppCard>
      )}

      {/* ── Tab: Status ── */}
      {activeTab === 'status' && (
        <AppCard title="Status e Visibilidade">
          <div className="grid gap-4 sm:grid-cols-2 max-w-2xl">

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
      )}

      {/* ── Tab: Configurações ── */}
      {activeTab === 'configuracoes' && (
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
      )}

      {/* ── Tab: Fiscal ── */}
      {activeTab === 'fiscal' && (
        <AppCard title="Dados Fiscais">
          <div className="grid gap-4 sm:grid-cols-2 max-w-2xl">

            <div className="space-y-1.5">
              <Label htmlFor="ncm">NCM</Label>
              <Input id="ncm" placeholder="00000000" maxLength={10} {...register('ncm')} />
              <p className="text-xs text-muted-foreground">Nomenclatura Comum do Mercosul (8 dígitos)</p>
              {errors.ncm && <p className="text-xs text-destructive">{errors.ncm.message}</p>}
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="cest">CEST</Label>
              <Input id="cest" placeholder="0000000" maxLength={9} {...register('cest')} />
              <p className="text-xs text-muted-foreground">Código Especificador da Substituição Tributária (7 dígitos)</p>
              {errors.cest && <p className="text-xs text-destructive">{errors.cest.message}</p>}
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="cfop_default">CFOP Padrão</Label>
              <Input id="cfop_default" placeholder="5102" maxLength={5} {...register('cfop_default')} />
              <p className="text-xs text-muted-foreground">Código Fiscal de Operações — saída padrão (4 dígitos)</p>
              {errors.cfop_default && <p className="text-xs text-destructive">{errors.cfop_default.message}</p>}
            </div>

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
      )}

      {/* ── Tab: Imagens (edit only) ── */}
      {activeTab === 'imagens' && showImages && (
        <AppCard title="Imagens do Produto">
          <ProductImageManager
            productUuid={defaultValues!.uuid!}
            images={images ?? []}
            onRefresh={onRefreshImages ?? (() => {})}
          />
        </AppCard>
      )}

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

      {/* ── Actions ── */}
      <div className="flex gap-2">
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting ? 'Salvando…' : mode === 'create' ? 'Criar Produto' : 'Salvar Alterações'}
        </Button>
      </div>
    </form>
  )
}
