'use client'

import { use, useState } from 'react'
import Link from 'next/link'
import { ChevronLeft, Pencil, Globe, Archive, Package, Tag, Scale, Ruler, CalendarDays, Barcode, Info, FileText, BarChart3 } from 'lucide-react'
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

function formatWeight(grams: number | null | undefined): string {
  if (!grams) return '—'
  return grams >= 1000 ? `${(grams / 1000).toFixed(3)} kg` : `${grams} g`
}

function formatDate(iso: string | null | undefined): string {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('pt-BR')
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
    { key: 'general',    label: 'Geral'      },
    { key: 'variants',   label: 'Variantes'  },
    { key: 'categories', label: 'Categorias' },
    { key: 'media',      label: `Mídia${product.images?.length ? ` (${product.images.length})` : ''}` },
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

  // Primary image for header
  const primaryImage = product.images?.find((i) => i.is_primary) ?? product.images?.[0]

  return (
    <div className="space-y-6">

      {/* ── Header card ───────────────────────────────────────────────────────── */}
      <AppCard>
        <div className="flex flex-wrap items-start gap-4">
          {/* Thumbnail */}
          {primaryImage ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img
              src={primaryImage.thumbnail_url ?? primaryImage.url}
              alt={primaryImage.alt_text ?? product.name}
              className="h-20 w-20 rounded-lg object-cover border shrink-0"
              onError={(e) => { e.currentTarget.style.display = 'none' }}
            />
          ) : (
            <div className="h-20 w-20 rounded-lg border bg-muted flex items-center justify-center shrink-0">
              <Package className="h-8 w-8 text-muted-foreground/40" />
            </div>
          )}

          <div className="flex-1 min-w-0 space-y-2">
            <div className="flex items-center gap-2 flex-wrap">
              <h2 className="text-xl font-semibold leading-tight">{product.name}</h2>
              <Badge variant="outline" className="font-mono text-xs shrink-0">{product.code}</Badge>
              <Badge variant="outline" className={STATUS_STYLES[product.status]}>
                {product.status_label}
              </Badge>
              <Badge variant="outline">{product.type_label}</Badge>
            </div>

            <div className="flex flex-wrap gap-x-4 gap-y-0.5 text-sm text-muted-foreground">
              {product.brand && <span>Marca: <span className="text-foreground font-medium">{product.brand.name}</span></span>}
              {product.collection && <span>Coleção: <span className="text-foreground font-medium">{product.collection.name}</span></span>}
              {product.slug && <span className="font-mono text-xs opacity-60">/{product.slug}</span>}
            </div>

            {product.short_description && (
              <p className="text-sm text-muted-foreground line-clamp-2">{product.short_description}</p>
            )}

            {/* Tags */}
            {product.tags && product.tags.length > 0 && (
              <div className="flex items-center gap-1.5 flex-wrap">
                <Tag className="h-3 w-3 text-muted-foreground" />
                {product.tags.map((tag) => (
                  <Badge key={tag} variant="secondary" className="text-xs">{tag}</Badge>
                ))}
              </div>
            )}
          </div>

          {/* Actions */}
          <div className="flex items-center gap-2 flex-wrap shrink-0">
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

      {/* ── Price summary bar ─────────────────────────────────────────────────── */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <AppCard className="py-3 px-4">
          <p className="text-xs text-muted-foreground">Preço de Venda</p>
          <p className="text-lg font-bold text-green-700">{formatBRL(product.base_price)}</p>
        </AppCard>
        <AppCard className="py-3 px-4">
          <p className="text-xs text-muted-foreground">Preço de Custo</p>
          <p className="text-lg font-semibold">{formatBRL(product.cost_price)}</p>
        </AppCard>
        <AppCard className="py-3 px-4">
          <p className="text-xs text-muted-foreground">Visibilidade</p>
          <p className="text-sm font-medium mt-0.5">{product.visibility_label}</p>
        </AppCard>
        <AppCard className="py-3 px-4">
          <p className="text-xs text-muted-foreground">Unidade de Medida</p>
          <p className="text-sm font-medium mt-0.5">{product.unit_of_measure_label ?? product.unit_of_measure ?? '—'}</p>
        </AppCard>
      </div>

      {/* ── Tabs ──────────────────────────────────────────────────────────────── */}
      <div>
        <div className="border-b flex gap-1 mb-4 overflow-x-auto">
          {tabs.map((tab) => (
            <button
              key={tab.key}
              onClick={() => setActiveTab(tab.key)}
              className={[
                'px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px whitespace-nowrap',
                activeTab === tab.key
                  ? 'border-primary text-primary'
                  : 'border-transparent text-muted-foreground hover:text-foreground',
              ].join(' ')}
            >
              {tab.label}
            </button>
          ))}
        </div>

        {/* ── GENERAL TAB ─────────────────────────────────────────────────────── */}
        {activeTab === 'general' && (
          <div className="space-y-4">

            {/* Informações Gerais */}
            <AppCard title="Informações Gerais">
              <dl className="grid gap-3 sm:grid-cols-2 text-sm">
                <div>
                  <dt className="font-medium text-muted-foreground">Tipo de Produto</dt>
                  <dd>{product.type_label}</dd>
                </div>
                <div>
                  <dt className="font-medium text-muted-foreground">Unidade de Medida</dt>
                  <dd>{product.unit_of_measure_label ?? product.unit_of_measure ?? '—'}</dd>
                </div>
                <div>
                  <dt className="font-medium text-muted-foreground">Visibilidade</dt>
                  <dd>{product.visibility_label}</dd>
                </div>
                {product.location && (
                  <div>
                    <dt className="font-medium text-muted-foreground">Localização Física</dt>
                    <dd className="font-mono text-xs mt-0.5 bg-muted px-2 py-1 rounded w-fit">{product.location}</dd>
                  </div>
                )}
                {product.season && (
                  <div>
                    <dt className="font-medium text-muted-foreground">Temporada</dt>
                    <dd>{product.season}</dd>
                  </div>
                )}
                {product.launch_date && (
                  <div>
                    <dt className="font-medium text-muted-foreground">Data de Lançamento</dt>
                    <dd className="flex items-center gap-1">
                      <CalendarDays className="h-3.5 w-3.5 text-muted-foreground" />
                      {formatDate(product.launch_date)}
                    </dd>
                  </div>
                )}
                <div>
                  <dt className="font-medium text-muted-foreground">Destaque</dt>
                  <dd>{product.is_featured ? <span className="text-amber-600 font-medium">Sim</span> : 'Não'}</dd>
                </div>
                <div>
                  <dt className="font-medium text-muted-foreground">Produto Digital</dt>
                  <dd>{product.is_digital ? <span className="text-blue-600 font-medium">Sim</span> : 'Não'}</dd>
                </div>
                <div>
                  <dt className="font-medium text-muted-foreground">Publicável</dt>
                  <dd>{product.is_publishable ? 'Sim' : 'Não'}</dd>
                </div>
                {product.last_sale_at && (
                  <div>
                    <dt className="font-medium text-muted-foreground">Última Venda</dt>
                    <dd>{formatDate(product.last_sale_at)}</dd>
                  </div>
                )}
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

            {/* Medidas e Peso (só para produtos físicos não-digitais) */}
            {!product.is_digital && (product.weight_gross_g || product.weight_net_g || product.dimensions) && (
              <AppCard title="Medidas e Peso">
                <dl className="grid gap-3 sm:grid-cols-2 text-sm">
                  {product.weight_gross_g != null && (
                    <div>
                      <dt className="font-medium text-muted-foreground flex items-center gap-1">
                        <Scale className="h-3.5 w-3.5" /> Peso Bruto
                      </dt>
                      <dd>{formatWeight(product.weight_gross_g)}</dd>
                    </div>
                  )}
                  {product.weight_net_g != null && (
                    <div>
                      <dt className="font-medium text-muted-foreground flex items-center gap-1">
                        <Scale className="h-3.5 w-3.5" /> Peso Líquido
                      </dt>
                      <dd>{formatWeight(product.weight_net_g)}</dd>
                    </div>
                  )}
                  {product.dimensions && (
                    <div className="sm:col-span-2">
                      <dt className="font-medium text-muted-foreground flex items-center gap-1">
                        <Ruler className="h-3.5 w-3.5" /> Dimensões (C × L × A)
                      </dt>
                      <dd className="font-mono text-xs mt-0.5">
                        {[
                          product.dimensions.depth   != null ? `${product.dimensions.depth} cm` : null,
                          product.dimensions.width   != null ? `${product.dimensions.width} cm` : null,
                          product.dimensions.height  != null ? `${product.dimensions.height} cm` : null,
                        ].filter(Boolean).join(' × ') || '—'}
                      </dd>
                    </div>
                  )}
                </dl>
              </AppCard>
            )}

            {/* Códigos de Barras */}
            {product.barcodes && product.barcodes.length > 0 && (
              <AppCard title="Códigos de Barras">
                <div className="space-y-2">
                  {product.barcodes.map((b) => (
                    <div key={b.uuid} className="flex items-center gap-3 text-sm">
                      <Barcode className="h-4 w-4 text-muted-foreground shrink-0" />
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

            {/* Descrição */}
            {product.description && (
              <AppCard title="Descrição">
                <p className="text-sm whitespace-pre-wrap leading-relaxed">{product.description}</p>
              </AppCard>
            )}

            {/* Descrição Curta (se diferente da descrição) */}
            {product.short_description && product.short_description !== product.description && (
              <AppCard title="Descrição Curta">
                <p className="text-sm text-muted-foreground whitespace-pre-wrap">{product.short_description}</p>
              </AppCard>
            )}

            {/* Descrição de Marketing */}
            {product.marketing_description && (
              <AppCard title="Texto de Marketing">
                <p className="text-sm whitespace-pre-wrap leading-relaxed">{product.marketing_description}</p>
              </AppCard>
            )}

            {/* Observações Internas */}
            {product.internal_notes && (
              <AppCard title="Observações Internas">
                <div className="flex gap-2">
                  <Info className="h-4 w-4 text-amber-500 shrink-0 mt-0.5" />
                  <p className="text-sm whitespace-pre-wrap text-muted-foreground">{product.internal_notes}</p>
                </div>
              </AppCard>
            )}

            {/* Dados Fiscais */}
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
                    <div className="sm:col-span-2">
                      <dt className="font-medium text-muted-foreground">Origem</dt>
                      <dd>{product.origin_code_label ?? ORIGIN_LABELS[product.origin_code] ?? `Código ${product.origin_code}`}</dd>
                    </div>
                  )}
                </dl>
              </AppCard>
            )}

            {/* SEO */}
            {product.seo && (product.seo.title || product.seo.description) && (
              <AppCard title="SEO">
                <dl className="grid gap-3 text-sm">
                  {product.seo.title && (
                    <div>
                      <dt className="font-medium text-muted-foreground">Título SEO</dt>
                      <dd>{product.seo.title}</dd>
                    </div>
                  )}
                  {product.seo.description && (
                    <div>
                      <dt className="font-medium text-muted-foreground">Descrição SEO</dt>
                      <dd className="text-muted-foreground">{product.seo.description}</dd>
                    </div>
                  )}
                  {product.seo.keywords && product.seo.keywords.length > 0 && (
                    <div>
                      <dt className="font-medium text-muted-foreground">Palavras-chave</dt>
                      <dd className="flex flex-wrap gap-1 mt-1">
                        {product.seo.keywords.map((kw) => (
                          <Badge key={kw} variant="outline" className="text-xs">{kw}</Badge>
                        ))}
                      </dd>
                    </div>
                  )}
                </dl>
              </AppCard>
            )}

            {/* Analytics */}
            {(product.days_without_sale != null || product.stock_age != null || product.sales_velocity != null) && (
              <AppCard title="Indicadores">
                <dl className="grid gap-3 sm:grid-cols-3 text-sm">
                  {product.sales_velocity != null && (
                    <div>
                      <dt className="font-medium text-muted-foreground flex items-center gap-1">
                        <BarChart3 className="h-3.5 w-3.5" /> Velocidade de Vendas
                      </dt>
                      <dd>{Number(product.sales_velocity).toFixed(2)} un/dia</dd>
                    </div>
                  )}
                  {product.days_without_sale != null && (
                    <div>
                      <dt className="font-medium text-muted-foreground">Dias sem Venda</dt>
                      <dd className={product.days_without_sale > 60 ? 'text-red-600 font-medium' : ''}>
                        {product.days_without_sale} dias
                      </dd>
                    </div>
                  )}
                  {product.stock_age != null && (
                    <div>
                      <dt className="font-medium text-muted-foreground">Idade do Estoque</dt>
                      <dd>{product.stock_age} dias</dd>
                    </div>
                  )}
                </dl>
              </AppCard>
            )}

            {/* QR Code */}
            {product.qr_code_url && (
              <AppCard title="QR Code">
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img
                  src={product.qr_code_url}
                  alt="QR Code do produto"
                  className="h-32 w-32 object-contain"
                />
              </AppCard>
            )}
          </div>
        )}

        {/* ── VARIANTS TAB ──────────────────────────────────────────────────────── */}
        {activeTab === 'variants' && (
          <AppCard title="Variantes">
            <VariantTable
              variants={variants}
              productUuid={uuid}
              onRefresh={() => { void refetchVariants(); void refetchProduct() }}
            />
          </AppCard>
        )}

        {/* ── CATEGORIES TAB ────────────────────────────────────────────────────── */}
        {activeTab === 'categories' && (
          <AppCard title="Categorias">
            {!product.categories || product.categories.length === 0 ? (
              <div className="flex flex-col items-center gap-2 py-8 text-muted-foreground">
                <FileText className="h-8 w-8 opacity-30" />
                <p className="text-sm">Nenhuma categoria associada.</p>
              </div>
            ) : (
              <div className="flex flex-wrap gap-2">
                {product.categories.map((cat) => (
                  <Badge key={cat.uuid} variant="secondary" className="text-sm py-1 px-3">{cat.name}</Badge>
                ))}
              </div>
            )}
          </AppCard>
        )}

        {/* ── MEDIA TAB ─────────────────────────────────────────────────────────── */}
        {activeTab === 'media' && (
          <AppCard title="Imagens do Produto">
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
