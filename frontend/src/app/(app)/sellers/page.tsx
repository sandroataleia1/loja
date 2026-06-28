'use client'

import { useState } from 'react'
import { Plus, Search, UserCog, Edit2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { ViewToggle } from '@/components/ui/view-toggle'
import { SellerCard } from './seller-card'
import { useSellers } from '@/features/sellers/hooks'
import { useLocalStorage } from '@/hooks/use-local-storage'
import type { SellerFilters } from '@/services/sellers.service'
import { SellerFormDialog } from './seller-form-dialog'

export default function SellersPage() {
  const [search,     setSearch]     = useState('')
  const [page,       setPage]       = useState(1)
  const [editUuid,   setEditUuid]   = useState<string | null>(null)
  const [showCreate, setShowCreate] = useState(false)
  const [view,       setView]       = useLocalStorage<'table' | 'grid'>('sellers-view', 'grid')

  const filters: SellerFilters = { q: search || undefined, page, per_page: 20 }
  const { data, isLoading } = useSellers(filters)

  const sellers = data?.data ?? []
  const meta    = data?.meta

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Vendedores"
        description="Gerencie os vendedores vinculados a usuários do sistema."
        actions={
          <Button onClick={() => setShowCreate(true)}>
            <Plus className="mr-2 h-4 w-4" />
            Habilitar Vendedor
          </Button>
        }
      />

      {/* Filter bar */}
      <div className="flex items-center gap-2 flex-wrap">
        <div className="relative flex-1 min-w-48 max-w-sm">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Buscar por nome ou código..."
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1) }}
            className="pl-9"
          />
        </div>
        <div className="ml-auto flex items-center gap-2">
          {meta && <span className="text-sm text-muted-foreground whitespace-nowrap">{meta.total} registros</span>}
          <Separator orientation="vertical" className="h-6" />
          <ViewToggle view={view} onChange={setView} />
        </div>
      </div>

      {/* Content */}
      {view === 'grid' ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          {isLoading
            ? Array.from({ length: 6 }).map((_, i) => (
                <div key={i} className="rounded-lg border p-4 space-y-3">
                  <div className="h-10 w-10 rounded-full bg-muted animate-pulse" />
                  <div className="h-4 bg-muted rounded animate-pulse w-3/4" />
                </div>
              ))
            : sellers.length === 0
            ? <div className="col-span-full py-12 text-center text-muted-foreground border rounded-lg">
                {search ? 'Nenhum vendedor encontrado.' : 'Nenhum vendedor habilitado.'}
              </div>
            : sellers.map((s) => (
                <SellerCard
                  key={s.uuid}
                  seller={s}
                  onEdit={(uuid) => setEditUuid(uuid)}
                />
              ))
          }
        </div>
      ) : (
        <div className="rounded-lg border bg-card">
          {isLoading ? (
            <div className="p-8 text-center text-sm text-muted-foreground">Carregando...</div>
          ) : sellers.length === 0 ? (
            <div className="p-8 text-center text-sm text-muted-foreground">
              {search ? 'Nenhum vendedor encontrado.' : 'Nenhum vendedor habilitado.'}
            </div>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b text-left text-xs text-muted-foreground">
                  <th className="px-4 py-3 font-medium">Vendedor</th>
                  <th className="px-4 py-3 font-medium">Código</th>
                  <th className="px-4 py-3 font-medium">Comissão</th>
                  <th className="px-4 py-3 font-medium">Status</th>
                  <th className="px-4 py-3 font-medium w-16"></th>
                </tr>
              </thead>
              <tbody className="divide-y">
                {sellers.map((s) => (
                  <tr key={s.uuid} className="hover:bg-muted/50 transition-colors">
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-2">
                        <UserCog className="h-4 w-4 text-muted-foreground shrink-0" />
                        <div>
                          <p className="font-medium">{s.user?.name ?? '—'}</p>
                          <p className="text-xs text-muted-foreground">{s.user?.email ?? ''}</p>
                        </div>
                      </div>
                    </td>
                    <td className="px-4 py-3 text-muted-foreground">{s.code ?? '—'}</td>
                    <td className="px-4 py-3 text-muted-foreground">
                      {s.commission_rate != null ? `${s.commission_rate}%` : '—'}
                    </td>
                    <td className="px-4 py-3">
                      <Badge variant={s.is_active ? 'default' : 'secondary'}>{s.is_active ? 'Ativo' : 'Inativo'}</Badge>
                    </td>
                    <td className="px-4 py-3">
                      <Button size="icon" variant="ghost" className="h-7 w-7" onClick={() => setEditUuid(s.uuid)}>
                        <Edit2 className="h-3.5 w-3.5" />
                      </Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      )}

      {meta && meta.last_page > 1 && (
        <div className="flex items-center justify-between text-sm">
          <span className="text-muted-foreground">{meta.total} vendedor{meta.total !== 1 ? 'es' : ''}</span>
          <div className="flex gap-2">
            <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage(p => p - 1)}>Anterior</Button>
            <Button variant="outline" size="sm" disabled={page >= meta.last_page} onClick={() => setPage(p => p + 1)}>Próxima</Button>
          </div>
        </div>
      )}

      {(showCreate || editUuid !== null) && (
        <SellerFormDialog open sellerUuid={editUuid} onClose={() => { setShowCreate(false); setEditUuid(null) }} />
      )}
    </div>
  )
}
