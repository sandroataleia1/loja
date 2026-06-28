'use client'

import { useState } from 'react'
import Link from 'next/link'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { toast } from 'sonner'
import {
  Plus,
  Pencil,
  Trash2,
  Upload,
  ListChecks,
  Download,
} from 'lucide-react'

import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Badge } from '@/components/ui/badge'
import { Switch } from '@/components/ui/switch'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog'
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { Checkbox } from '@/components/ui/checkbox'
import { Label } from '@/components/ui/label'

import { AppPageHeader } from '@/components/shared/app-page-header'
import { ConfirmDialog } from '@/components/shared/confirm-dialog'

import {
  usePriceLists,
  useCreatePriceList,
  useUpdatePriceList,
  useDeletePriceList,
} from '@/features/pricing/hooks'
import { useQueryClient } from '@tanstack/react-query'

import { pricingService } from '@/services/pricing.service'
import { ROUTES } from '@/constants'
import type { PriceList, PriceListType } from '@store/shared-types'
import type { CreatePriceListRequest } from '@store/contracts'

// ── Helpers ───────────────────────────────────────────────────────────────────

function fmtDate(iso: string | null) {
  if (!iso) return null
  return new Date(iso).toLocaleDateString('pt-BR')
}

// ── Badge colors by type ──────────────────────────────────────────────────────

const TYPE_BADGE: Record<PriceListType, string> = {
  retail:         'bg-green-100 text-green-800',
  wholesale:      'bg-blue-100 text-blue-800',
  representative: 'bg-purple-100 text-purple-800',
  special:        'bg-amber-100 text-amber-800',
  cost:           'bg-gray-100 text-gray-700',
}

const TYPE_LABEL: Record<PriceListType, string> = {
  retail:         'Varejo',
  wholesale:      'Atacado',
  representative: 'Representante',
  special:        'Especial',
  cost:           'Custo',
}

// ── Zod schema ────────────────────────────────────────────────────────────────

const priceListSchema = z.object({
  name:                 z.string().min(1, 'Nome obrigatório'),
  code:                 z.string().min(1, 'Código obrigatório'),
  type:                 z.enum(['retail', 'wholesale', 'representative', 'special', 'cost']),
  currency:             z.string().optional(),
  max_discount_percent: z.coerce.number().min(0).max(100).optional(),
  is_default:           z.boolean().optional(),
  is_active:            z.boolean().optional(),
  valid_from:           z.string().optional().nullable(),
  valid_to:             z.string().optional().nullable(),
})

type PriceListFormValues = z.infer<typeof priceListSchema>

// ── PriceListFormModal ────────────────────────────────────────────────────────

interface PriceListFormModalProps {
  open:          boolean
  onOpenChange:  (open: boolean) => void
  editing?:      PriceList | null
}

function PriceListFormModal({ open, onOpenChange, editing }: PriceListFormModalProps) {
  const createMutation = useCreatePriceList()
  const updateMutation = useUpdatePriceList(editing?.uuid ?? '')

  const isPending = createMutation.isPending || updateMutation.isPending

  const form = useForm<PriceListFormValues>({
    resolver: zodResolver(priceListSchema),
    values: editing
      ? {
          name:                 editing.name,
          code:                 editing.code,
          type:                 editing.type,
          currency:             editing.currency ?? 'BRL',
          max_discount_percent: editing.max_discount_percent ?? 0,
          is_default:           editing.is_default,
          is_active:            editing.is_active,
          valid_from:           editing.valid_from ?? null,
          valid_to:             editing.valid_to ?? null,
        }
      : {
          name:                 '',
          code:                 '',
          type:                 'retail',
          currency:             'BRL',
          max_discount_percent: 0,
          is_default:           false,
          is_active:            true,
          valid_from:           null,
          valid_to:             null,
        },
  })

  function onSubmit(values: PriceListFormValues) {
    const payload: CreatePriceListRequest = {
      ...values,
      valid_from: values.valid_from || null,
      valid_to:   values.valid_to   || null,
    }

    if (editing) {
      updateMutation.mutate(payload, {
        onSuccess: () => {
          toast.success('Tabela atualizada com sucesso.')
          onOpenChange(false)
          form.reset()
        },
        onError: (err) => {
          toast.error(err instanceof Error ? err.message : 'Erro ao atualizar tabela.')
        },
      })
    } else {
      createMutation.mutate(payload, {
        onSuccess: () => {
          toast.success('Tabela criada com sucesso.')
          onOpenChange(false)
          form.reset()
        },
        onError: (err) => {
          toast.error(err instanceof Error ? err.message : 'Erro ao criar tabela.')
        },
      })
    }
  }

  return (
    <Dialog open={open} onOpenChange={(v) => { if (!v) form.reset(); onOpenChange(v) }}>
      <DialogContent className="sm:max-w-135">
        <DialogHeader>
          <DialogTitle>{editing ? 'Editar Tabela de Preço' : 'Nova Tabela de Preço'}</DialogTitle>
        </DialogHeader>

        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <FormField
                control={form.control}
                name="name"
                render={({ field }) => (
                  <FormItem className="col-span-2">
                    <FormLabel>Nome</FormLabel>
                    <FormControl>
                      <Input placeholder="Ex: Tabela Varejo" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name="code"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Código</FormLabel>
                    <FormControl>
                      <Input placeholder="Ex: VAREJO" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name="type"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Tipo</FormLabel>
                    <Select onValueChange={field.onChange} value={field.value}>
                      <FormControl>
                        <SelectTrigger>
                          <SelectValue placeholder="Selecione" />
                        </SelectTrigger>
                      </FormControl>
                      <SelectContent>
                        <SelectItem value="retail">Varejo</SelectItem>
                        <SelectItem value="wholesale">Atacado</SelectItem>
                        <SelectItem value="representative">Representante</SelectItem>
                        <SelectItem value="special">Especial</SelectItem>
                        <SelectItem value="cost">Custo</SelectItem>
                      </SelectContent>
                    </Select>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name="currency"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Moeda</FormLabel>
                    <FormControl>
                      <Input placeholder="BRL" {...field} value={field.value ?? 'BRL'} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name="max_discount_percent"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Desconto máximo (%)</FormLabel>
                    <FormControl>
                      <Input type="number" min={0} max={100} step={0.01} {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name="valid_from"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Válido de</FormLabel>
                    <FormControl>
                      <Input type="date" {...field} value={field.value ?? ''} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name="valid_to"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Válido até</FormLabel>
                    <FormControl>
                      <Input type="date" {...field} value={field.value ?? ''} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>

            <div className="flex items-center gap-6 pt-1">
              <FormField
                control={form.control}
                name="is_default"
                render={({ field }) => (
                  <FormItem className="flex items-center gap-2 space-y-0">
                    <FormControl>
                      <Checkbox
                        checked={field.value ?? false}
                        onCheckedChange={field.onChange}
                      />
                    </FormControl>
                    <FormLabel className="cursor-pointer">Tabela padrão</FormLabel>
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name="is_active"
                render={({ field }) => (
                  <FormItem className="flex items-center gap-2 space-y-0">
                    <FormControl>
                      <Checkbox
                        checked={field.value ?? true}
                        onCheckedChange={field.onChange}
                      />
                    </FormControl>
                    <FormLabel className="cursor-pointer">Ativa</FormLabel>
                  </FormItem>
                )}
              />
            </div>

            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={isPending}>
                Cancelar
              </Button>
              <Button type="submit" disabled={isPending}>
                {isPending ? 'Salvando...' : editing ? 'Salvar' : 'Criar'}
              </Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  )
}

// ── PriceImportModal ──────────────────────────────────────────────────────────

interface PriceImportModalProps {
  open:         boolean
  onOpenChange: (open: boolean) => void
  priceList:    PriceList | null
}

function PriceImportModal({ open, onOpenChange, priceList }: PriceImportModalProps) {
  const [file, setFile]       = useState<File | null>(null)
  const [loading, setLoading] = useState(false)

  async function handleImport() {
    if (!file || !priceList) return
    setLoading(true)
    try {
      const result = await pricingService.importCSV(priceList.uuid, file)
      toast.success(result.message ?? 'Importação concluída.')
      onOpenChange(false)
      setFile(null)
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Erro ao importar arquivo.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <Dialog open={open} onOpenChange={(v) => { if (!v) setFile(null); onOpenChange(v) }}>
      <DialogContent className="sm:max-w-110">
        <DialogHeader>
          <DialogTitle>Importar Preços — {priceList?.name}</DialogTitle>
        </DialogHeader>

        <div className="space-y-4 py-2">
          <p className="text-sm text-muted-foreground">
            Envie um arquivo CSV com as colunas: <span className="font-mono">sku, price_cents</span> (e opcionalmente{' '}
            <span className="font-mono">min_price_cents, cost_price_cents, valid_from, valid_to</span>).
          </p>

          <a
            href={pricingService.getImportTemplate()}
            className="inline-flex items-center gap-1.5 text-sm text-primary hover:underline"
            target="_blank"
            rel="noopener noreferrer"
          >
            <Download className="h-3.5 w-3.5" />
            Baixar modelo CSV
          </a>

          <div>
            <Label htmlFor="import-file">Arquivo CSV</Label>
            <Input
              id="import-file"
              type="file"
              accept=".csv"
              className="mt-1"
              onChange={(e) => setFile(e.target.files?.[0] ?? null)}
            />
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)} disabled={loading}>
            Cancelar
          </Button>
          <Button onClick={handleImport} disabled={!file || loading}>
            {loading ? 'Importando...' : 'Importar'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}

// ── Skeleton cards ────────────────────────────────────────────────────────────

function SkeletonCards() {
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      {Array.from({ length: 6 }).map((_, i) => (
        <div key={i} className="border rounded-lg p-4 shadow-sm space-y-3">
          <div className="flex items-center gap-2">
            <Skeleton className="h-5 w-20" />
            <Skeleton className="h-5 w-14" />
          </div>
          <Skeleton className="h-6 w-48" />
          <Skeleton className="h-4 w-24" />
          <div className="space-y-1.5 pt-1">
            <Skeleton className="h-4 w-full" />
            <Skeleton className="h-4 w-3/4" />
            <Skeleton className="h-4 w-1/2" />
          </div>
          <div className="flex items-center gap-2 pt-2">
            <Skeleton className="h-8 w-8 rounded" />
            <Skeleton className="h-8 w-8 rounded" />
            <Skeleton className="h-8 w-8 rounded" />
            <Skeleton className="h-8 w-8 rounded" />
          </div>
        </div>
      ))}
    </div>
  )
}

// ── Main Page ─────────────────────────────────────────────────────────────────

export default function PriceListsPage() {
  const [typeFilter,     setTypeFilter]     = useState<string>('all')
  const [showInactive,   setShowInactive]   = useState(false)
  const [openModal,      setOpenModal]      = useState(false)
  const [selectedList,   setSelectedList]   = useState<PriceList | null>(null)
  const [openImport,     setOpenImport]     = useState(false)
  const [importTarget,   setImportTarget]   = useState<PriceList | null>(null)
  const [deleteUuid,     setDeleteUuid]     = useState<string | null>(null)
  const [togglingUuid,   setTogglingUuid]   = useState<string | null>(null)

  const filters = {
    type:             typeFilter !== 'all' ? typeFilter : undefined,
    include_inactive: showInactive || undefined,
  }

  const { data: priceLists = [], isLoading } = usePriceLists(filters)
  const deleteMutation                        = useDeletePriceList()
  const updateMutation                        = useUpdatePriceList(togglingUuid ?? '')

  function handleOpenCreate() {
    setSelectedList(null)
    setOpenModal(true)
  }

  function handleOpenEdit(pl: PriceList) {
    setSelectedList(pl)
    setOpenModal(true)
  }

  function handleOpenImport(pl: PriceList) {
    setImportTarget(pl)
    setOpenImport(true)
  }

  function handleToggleActive(pl: PriceList) {
    setTogglingUuid(pl.uuid)
    updateMutation.mutate(
      { is_active: !pl.is_active },
      {
        onSuccess: () => {
          toast.success(`Tabela ${!pl.is_active ? 'ativada' : 'desativada'}.`)
          setTogglingUuid(null)
        },
        onError: (err) => {
          toast.error(err instanceof Error ? err.message : 'Erro ao alterar status.')
          setTogglingUuid(null)
        },
      },
    )
  }

  function handleDeleteConfirm() {
    if (!deleteUuid) return
    deleteMutation.mutate(deleteUuid, {
      onSuccess: () => {
        toast.success('Tabela excluída com sucesso.')
        setDeleteUuid(null)
      },
      onError: (err) => {
        toast.error(err instanceof Error ? err.message : 'Erro ao excluir tabela.')
        setDeleteUuid(null)
      },
    })
  }

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Tabelas de Preço"
        description="Gerencie as tabelas de preço do catálogo."
        actions={
          <Button onClick={handleOpenCreate}>
            <Plus className="mr-2 h-4 w-4" />
            Nova tabela
          </Button>
        }
      />

      {/* Filtros */}
      <div className="flex flex-wrap items-center gap-4">
        <div className="w-48">
          <Select value={typeFilter} onValueChange={setTypeFilter}>
            <SelectTrigger>
              <SelectValue placeholder="Tipo" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Todos os tipos</SelectItem>
              <SelectItem value="retail">Varejo</SelectItem>
              <SelectItem value="wholesale">Atacado</SelectItem>
              <SelectItem value="representative">Representante</SelectItem>
              <SelectItem value="special">Especial</SelectItem>
              <SelectItem value="cost">Custo</SelectItem>
            </SelectContent>
          </Select>
        </div>

        <label className="flex items-center gap-2 cursor-pointer select-none">
          <Checkbox
            checked={showInactive}
            onCheckedChange={(v: boolean) => setShowInactive(v)}
          />
          <span className="text-sm text-muted-foreground">Incluir inativas</span>
        </label>
      </div>

      {/* Grid de cards */}
      {isLoading ? (
        <SkeletonCards />
      ) : priceLists.length === 0 ? (
        <div className="flex flex-col items-center justify-center py-20 text-center text-muted-foreground border rounded-lg">
          <ListChecks className="h-10 w-10 mb-3 opacity-30" />
          <p className="text-sm">Nenhuma tabela de preço encontrada.</p>
          <Button variant="outline" className="mt-4" onClick={handleOpenCreate}>
            <Plus className="mr-2 h-4 w-4" />
            Criar primeira tabela
          </Button>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {priceLists.map((pl) => {
            const isToggling = togglingUuid === pl.uuid

            return (
              <div
                key={pl.uuid}
                className="border rounded-lg p-4 shadow-sm flex flex-col gap-3 bg-card"
              >
                {/* Topo — badges */}
                <div className="flex items-center gap-2 flex-wrap">
                  <span
                    className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${TYPE_BADGE[pl.type]}`}
                  >
                    {pl.type_label || TYPE_LABEL[pl.type]}
                  </span>
                  {pl.is_default && (
                    <Badge className="bg-blue-100 text-blue-800 border-0 text-xs">
                      Padrão
                    </Badge>
                  )}
                  {!pl.is_active && (
                    <Badge variant="outline" className="text-xs text-muted-foreground">
                      Inativa
                    </Badge>
                  )}
                </div>

                {/* Nome e código */}
                <div>
                  <p className="text-lg font-semibold leading-tight">{pl.name}</p>
                  <p className="font-mono text-xs text-muted-foreground mt-0.5">{pl.code}</p>
                </div>

                {/* Corpo */}
                <div className="space-y-1 text-sm text-muted-foreground">
                  <p>
                    Desconto máximo:{' '}
                    <span className="text-foreground font-medium">{pl.max_discount_percent}%</span>
                    {' '}|{' '}Produtos:{' '}
                    <span className="text-foreground font-medium">{pl.products_count}</span>
                  </p>
                  <p>
                    Vigência:{' '}
                    {pl.valid_from || pl.valid_to
                      ? `${fmtDate(pl.valid_from) ?? '?'} → ${fmtDate(pl.valid_to) ?? '?'}`
                      : 'Sem validade definida'}
                  </p>
                  <p>Moeda: <span className="text-foreground font-medium">{pl.currency}</span></p>
                </div>

                {/* Rodapé */}
                <div className="flex items-center gap-2 flex-wrap pt-1 border-t mt-auto">
                  {/* Toggle status */}
                  <div className="flex items-center gap-1.5 mr-auto">
                    <Switch
                      checked={pl.is_active}
                      onCheckedChange={() => handleToggleActive(pl)}
                      disabled={isToggling}
                      aria-label={pl.is_active ? 'Desativar tabela' : 'Ativar tabela'}
                    />
                    <span className="text-xs text-muted-foreground">
                      {pl.is_active ? 'Ativa' : 'Inativa'}
                    </span>
                  </div>

                  {/* Editar preços */}
                  <Button variant="ghost" size="icon" asChild title="Editar preços">
                    <Link href={`${ROUTES.PRICE_LISTS}/${pl.uuid}`}>
                      <ListChecks className="h-4 w-4" />
                    </Link>
                  </Button>

                  {/* Editar dados */}
                  <Button
                    variant="ghost"
                    size="icon"
                    title="Editar dados da tabela"
                    onClick={() => handleOpenEdit(pl)}
                  >
                    <Pencil className="h-4 w-4" />
                  </Button>

                  {/* Importar */}
                  <Button
                    variant="ghost"
                    size="icon"
                    title="Importar preços via CSV"
                    onClick={() => handleOpenImport(pl)}
                  >
                    <Upload className="h-4 w-4" />
                  </Button>

                  {/* Excluir */}
                  <Button
                    variant="ghost"
                    size="icon"
                    className="text-destructive hover:text-destructive"
                    title={pl.is_default ? 'Tabela padrão não pode ser excluída' : 'Excluir tabela'}
                    disabled={pl.is_default}
                    onClick={() => setDeleteUuid(pl.uuid)}
                  >
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </div>
              </div>
            )
          })}
        </div>
      )}

      {/* Modal: criar/editar tabela */}
      <PriceListFormModal
        open={openModal}
        onOpenChange={(v) => {
          setOpenModal(v)
          if (!v) setSelectedList(null)
        }}
        editing={selectedList}
      />

      {/* Modal: importar CSV */}
      <PriceImportModal
        open={openImport}
        onOpenChange={(v) => {
          setOpenImport(v)
          if (!v) setImportTarget(null)
        }}
        priceList={importTarget}
      />

      {/* Confirmar exclusão */}
      <ConfirmDialog
        open={Boolean(deleteUuid)}
        onOpenChange={(open) => { if (!open) setDeleteUuid(null) }}
        onConfirm={handleDeleteConfirm}
        loading={deleteMutation.isPending}
        title="Excluir tabela de preço?"
        description="Esta ação não pode ser desfeita. Todos os preços vinculados serão removidos."
        confirmLabel="Excluir"
      />
    </div>
  )
}
