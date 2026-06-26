'use client'

import { use, useState } from 'react'
import Link from 'next/link'
import { ChevronLeft, Pencil, Globe, Archive } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard } from '@/components/shared/app-card'
import { VariantTable } from '@/features/catalog/components/variant-table'
import { ProductImageManager } from '@/features/catalog/components/product-image-manager'
import {
  useProduct,
  useVariants,
  usePublishProduct,
  useArchiveProduct,
} from '@/features/catalog/hooks'
import { ROUTES } from '@/constants'
import type { ProductStatus } from '@store/shared-types'

// ── Helpers ───────────────────────────────────────────────────────────────────

const ORIGIN_LABELS: Record<number, string> = {
  0: '0 – Nacional',
  1: '1 – Estrangeira (importação direta)',
  2: '2 – Estrangeira (adquirida internamente)',
  3: '3 – Nacional, conteúdo importado > 40%',
  4: '4 – Nacional (processos produtivos básicos)',
  5: '5 – Estrangeira (importação direta, sem similar)',
  6: '6 – Estrangeira (interna, sem similar)',
  7: '7 – Nacional, conteúdo importado ≤ 40%',
  8: '8 – Nacional, conteúdo importado > 40% e ≤ 70%',
}

function formatBRL(value: number | null | undefined): string {
  if (value == null) return '—'
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value)
}

const STATUS_STYLES: Record<ProductStatus, string> = {
  draft:    'bg-gray-100 text-gray-700 border-gray-200',
  active:   'bg-green-50 text-green-700 border-green-200',
  inactive: 'bg-yellow-50 text-yellow-700 border-yellow-200',
  archived: 'bg-red-50 text-red-700 border-red-200',
  seasonal: 'bg-blue-50 text-blue-700 border-blue-200',
}

type Tab = 'general' | 'variants' | 'categories' | 'media'

// ── Content ───────────────────────────────────────────────────────────────────

function ProductDetailContent({ uuid }: { uuid: string }) {
  const { data: product, isLoading, isError, refetch: refetchProduct } = useProduct(uuid)
  const { data: variants = [], refetch: refetchVariants }              = useVariants(uuid)
  const { mutate: publishProduct, isPending: isPublishing }            = usePublishProduct(uuid)
  const { mutate: archiveProduct, isPending: isArchiving }             = useArchiveProduct(uuid)

  const [activeTab, setActiveTab] = useState<Tab>('general')

  if (isLoading) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-32 w-full rounded-lg" />
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

  const tabs: { key: Tab; label: string }[] = [
    { key: 'general',    label: 'Geral'       },
    { key: 'variants',   label: 'Variantes'   },
    { key: 'categories', label: 'Categorias'  },
    { key: 'media',      label: 'Mídia'       },
  ]

  function handlePublish() {
    publishProduct(undefined, {
      onSuccess: () => toast.success('Produto publicado.'),
      onError:   (err) => toast.error(err instanceof Error ? err.message : 'Erro ao publicar.'),
    })
  }

  function handleArchive() {
    archiveProduct(undefined, {
      onSuccess: () => toast.success('Produto arquivado.'),
      onError:   (err) => toast.error(err instanceof Error ? err.message : 'Erro ao arquivar.'),
    })
  }

  return (
    <div className="space-y-6">
      {/* Header info */}
      <AppCard>
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div className="space-y-2">
            <div className="flex items-center gap-2 flex-wrap">
              <h2 className="text-xl font-semibold">{product.name}</h2>
              <Badge variant="outline" className="font-mono text-xs">{product.code}</Badge>
              <Badge variant="outline" className={STATUS_STYLES[product.status]}>
                {product.status_label}
              </Badge>
              <Badge variant="outline">{product.type_label}</Badge>
            </div>
            {product.brand && (
              <p className="text-sm text-muted-foreground">Marca: {product.brand.name}</p>
            )}
          </div>
          <div className="flex items-center gap-2 flex-wrap">
            {product.status === 'draft' && (
              <Button variant="outline" size="sm" onClick={handlePublish} disabled={isPublishing}>
                <Globe className="mr-1.5 h-3.5 w-3.5" />
                {isPublishing ? 'Publicando…' : 'Publicar'}
              </Button>
            )}
            {product.status === 'active' && (
              <Button variant="outline" size="sm" onClick={handleArchive} disabled={isArchiving}>
                <Archive className="mr-1.5 h-3.5 w-3.5" />
                {isArchiving ? 'Arquivando…' : 'Arquivar'}
              </Button>
            )}
          </div>
        </div>
      </AppCard>

      {/* Tabs */}
      <div>
        <div className="border-b flex gap-1 mb-4">
          {tabs.map((tab) => (
            <button
              key={tab.key}
              onClick={() => setActiveTab(tab.key)}
              className={[
                'px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px',
                activeTab === tab.key
                  ? 'border-primary text-primary'
                  : 'border-transparent text-muted-foreground hover:text-foreground',
              ].join(' ')}
            >
              {tab.label}
            </button>
          ))}
        </div>

        {/* General tab */}
        {activeTab === 'general' && (
          <div className="space-y-4">
            <AppCard title="Informações Gerais">
              <dl className="grid gap-3 sm:grid-cols-2 text-sm">
                <div>
                  <dt className="font-medium text-muted-foreground">Unidade de Medida</dt>
                  <dd>{product.unit_of_measure_label ?? product.unit_of_measure ?? '—'}</dd>
                </div>
                <div>
                  <dt className="font-medium text-muted-foreground">Visibilidade</dt>
                  <dd>{product.visibility_label}</dd>
                </div>
                <div>
                  <dt className="font-medium text-muted-foreground">Preço de Venda</dt>
                  <dd className="font-semibold">{formatBRL(product.base_price)}</dd>
                </div>
                <div>
                  <dt className="font-medium text-muted-foreground">Preço de Custo</dt>
                  <dd>{formatBRL(product.cost_price)}</dd>
                </div>
                {product.location && (
                  <div className="sm:col-span-2">
                    <dt className="font-medium text-muted-foreground">Localização Física</dt>
                    <dd className="font-mono text-xs mt-0.5 bg-muted px-2 py-1 rounded w-fit">{product.location}</dd>
                  </div>
                )}
                <div>
                  <dt className="font-medium text-muted-foreground">Destaque</dt>
                  <dd>{product.is_featured ? 'Sim' : 'Não'}</dd>
                </div>
                <div>
                  <dt className="font-medium text-muted-foreground">Digital</dt>
                  <dd>{product.is_digital ? 'Sim' : 'Não'}</dd>
                </div>
                <div>
                  <dt className="font-medium text-muted-foreground">Cadastrado em</dt>
                  <dd>{new Date(product.created_at).toLocaleString('pt-BR')}</dd>
                </div>
                <div>
                  <dt className="font-medium text-muted-foreground">Última alteração</dt>
                  <dd>{new Date(product.updated_at).toLocaleString('pt-BR')}</dd>
                </div>
              </dl>
            </AppCard>

            {product.barcodes && product.barcodes.length > 0 && (
              <AppCard title="Códigos de Barras">
                <div className="space-y-2">
                  {product.barcodes.map((b) => (
                    <div key={b.uuid} className="flex items-center gap-3 text-sm">
                      <span className="text-xs bg-muted text-muted-foreground px-2 py-0.5 rounded">{b.type_label}</span>
                      <span className="font-mono">{b.value}</span>
                      {b.is_primary && (
                        <span className="text-xs text-primary font-medium">Principal</span>
                      )}
                    </div>
                  ))}
                </div>
              </AppCard>
            )}

            {product.description && (
              <AppCard title="Descrição">
                <p className="text-sm whitespace-pre-wrap">{product.description}</p>
              </AppCard>
            )}
            {product.internal_notes && (
              <AppCard title="Observações Internas">
                <p className="text-sm whitespace-pre-wrap text-muted-foreground">{product.internal_notes}</p>
              </AppCard>
            )}
            {(product.ncm || product.cest || product.cfop_default || product.origin_code != null) && (
              <AppCard title="Dados Fiscais">
                <dl className="grid gap-3 sm:grid-cols-2 text-sm">
                  {product.ncm && (
                    <div>
                      <dt className="font-medium text-muted-foreground">NCM</dt>
                      <dd className="font-mono">{product.ncm}</dd>
                    </div>
                  )}
                  {product.cest && (
                    <div>
                      <dt className="font-medium text-muted-foreground">CEST</dt>
                      <dd className="font-mono">{product.cest}</dd>
                    </div>
                  )}
                  {product.cfop_default && (
                    <div>
                      <dt className="font-medium text-muted-foreground">CFOP Padrão</dt>
                      <dd className="font-mono">{product.cfop_default}</dd>
                    </div>
                  )}
                  {product.origin_code != null && (
                    <div>
                      <dt className="font-medium text-muted-foreground">Origem</dt>
                      <dd>{ORIGIN_LABELS[product.origin_code] ?? `Código ${product.origin_code}`}</dd>
                    </div>
                  )}
                </dl>
              </AppCard>
            )}
          </div>
        )}

        {/* Variants tab */}
        {activeTab === 'variants' && (
          <AppCard title="Variantes">
            <VariantTable
              variants={variants}
              productUuid={uuid}
              onRefresh={() => { void refetchVariants(); void refetchProduct() }}
            />
          </AppCard>
        )}

        {/* Categories tab */}
        {activeTab === 'categories' && (
          <AppCard title="Categorias">
            {!product.categories || product.categories.length === 0 ? (
              <p className="text-sm text-muted-foreground">Nenhuma categoria associada.</p>
            ) : (
              <div className="flex flex-wrap gap-2">
                {product.categories.map((cat) => (
                  <Badge key={cat.uuid} variant="secondary">{cat.name}</Badge>
                ))}
              </div>
            )}
          </AppCard>
        )}

        {/* Media tab */}
        {activeTab === 'media' && (
          <AppCard title="Imagens">
            <ProductImageManager
              productUuid={uuid}
              images={product.images ?? []}
              onRefresh={() => void refetchProduct()}
            />
          </AppCard>
        )}
      </div>
    </div>
  )
}

// ── Page ──────────────────────────────────────────────────────────────────────

interface ProductDetailPageProps {
  params: Promise<{ uuid: string }>
}

export default function ProductDetailPage({ params }: ProductDetailPageProps) {
  const { uuid } = use(params)

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Detalhes do Produto"
        actions={
          <div className="flex items-center gap-2">
            <Button variant="outline" asChild>
              <Link href={ROUTES.PRODUCTS}>
                <ChevronLeft className="mr-1.5 h-4 w-4" />
                Voltar
              </Link>
            </Button>
            <Button asChild>
              <Link href={`${ROUTES.PRODUCTS}/${uuid}/edit`}>
                <Pencil className="mr-1.5 h-4 w-4" />
                Editar
              </Link>
            </Button>
          </div>
        }
      />
      <ProductDetailContent uuid={uuid} />
    </div>
  )
}
