'use client'

import { useState, useEffect, useMemo } from 'react'
import { useRouter } from 'next/navigation'
import { Save, Loader2, ChevronLeft, Search, X } from 'lucide-react'
import { toast } from 'sonner'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Skeleton } from '@/components/ui/skeleton'
import { AppCard } from '@/components/shared/app-card'
import { useRole, useUpdateRole, useSyncPermissions, usePermissions } from '../hooks'
import { ROUTES } from '@/constants'
import type { Permission } from '@store/shared-types'

// ── Helpers ───────────────────────────────────────────────────────────────────

function groupByModule(perms: Permission[]): Record<string, Permission[]> {
  return perms.reduce<Record<string, Permission[]>>((acc, p) => {
    if (!acc[p.module]) acc[p.module] = []
    acc[p.module].push(p)
    return acc
  }, {})
}

const MODULE_LABELS: Record<string, string> = {
  dashboard:         'Painel',
  customers:         'Clientes',
  products:          'Produtos',
  inventory:         'Estoque',
  sales:             'Vendas',
  cashier:           'Caixa',
  accounts_payable:  'Contas a Pagar',
  accounts_receivable: 'Contas a Receber',
  fiscal:            'Fiscal',
  users:             'Usuários',
  settings:          'Configurações',
  suppliers:         'Fornecedores',
  purchasing:        'Compras',
  purchase_orders:   'Pedidos de Compra',
  purchase_receipts: 'Recebimentos',
  carriers:          'Transportadoras',
  sellers:           'Vendedores',
  partners:          'Parceiros',
  cost_centers:      'Centros de Custo',
  reports:           'Relatórios',
}

// ── Componente principal ──────────────────────────────────────────────────────

interface RoleDetailProps {
  uuid: string
}

export function RoleDetail({ uuid }: RoleDetailProps) {
  const router = useRouter()
  const { data: role,     isLoading: loadingRole  } = useRole(uuid)
  const { data: allPerms, isLoading: loadingPerms } = usePermissions()
  const { mutate: updateRole, isPending: savingInfo  } = useUpdateRole(uuid)
  const { mutate: syncPerms,  isPending: savingPerms } = useSyncPermissions(uuid)

  const [name,        setName]        = useState('')
  const [description, setDescription] = useState('')
  const [selected,    setSelected]    = useState<Set<string>>(new Set())
  const [dirty,       setDirty]       = useState(false)
  const [search,      setSearch]      = useState('')

  useEffect(() => {
    if (!role) return
    setName(role.name)
    setDescription(role.description ?? '')
    setSelected(new Set(role.permissions.map((p) => p.uuid)))
    setDirty(false)
  }, [role])

  if (loadingRole || loadingPerms) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-32 w-full rounded-lg" />
        <Skeleton className="h-64 w-full rounded-lg" />
      </div>
    )
  }

  if (!role || !allPerms) {
    return <p className="text-sm text-muted-foreground text-center py-8">Perfil nao encontrado.</p>
  }

  const filteredPerms = useMemo(() => {
    const q = search.trim().toLowerCase()
    if (!q) return allPerms
    return allPerms.filter(
      (p) => p.name.toLowerCase().includes(q) || p.slug.toLowerCase().includes(q)
    )
  }, [allPerms, search])

  const grouped  = groupByModule(filteredPerms)
  const isSaving = savingInfo || savingPerms

  function togglePerm(permUuid: string) {
    setSelected((prev) => {
      const next = new Set(prev)
      next.has(permUuid) ? next.delete(permUuid) : next.add(permUuid)
      return next
    })
    setDirty(true)
  }

  function toggleModule(perms: Permission[]) {
    const allSel = perms.every((p) => selected.has(p.uuid))
    setSelected((prev) => {
      const next = new Set(prev)
      perms.forEach((p) => allSel ? next.delete(p.uuid) : next.add(p.uuid))
      return next
    })
    setDirty(true)
  }

  function handleSave() {
    let infoOk = false
    let permsOk = false

    const done = () => {
      if (infoOk && permsOk) {
        toast.success('Perfil atualizado com sucesso.')
        setDirty(false)
      }
    }

    updateRole(
      { name: name.trim(), description: description.trim() || undefined },
      {
        onSuccess: () => { infoOk = true; done() },
        onError:   (e) => toast.error(e instanceof Error ? e.message : 'Erro ao salvar nome.'),
      }
    )

    syncPerms(
      [...selected],
      {
        onSuccess: () => { permsOk = true; done() },
        onError:   (e) => toast.error(e instanceof Error ? e.message : 'Erro ao salvar permissoes.'),
      }
    )
  }

  return (
    <div className="space-y-6">
      {/* Informacoes basicas */}
      <AppCard title="Informacoes do Perfil">
        <div className="space-y-4 max-w-lg">
          <div className="space-y-1.5">
            <Label htmlFor="role-name">Nome</Label>
            <Input
              id="role-name"
              value={name}
              onChange={(e) => { setName(e.target.value); setDirty(true) }}
              placeholder="Nome do perfil"
            />
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="role-desc">Descricao</Label>
            <Input
              id="role-desc"
              value={description}
              onChange={(e) => { setDescription(e.target.value); setDirty(true) }}
              placeholder="Descreva as responsabilidades deste perfil..."
            />
          </div>
          <div className="flex items-center gap-2">
            <Badge variant="outline" className="font-mono text-xs">{role.slug}</Badge>
            <span className="text-xs text-muted-foreground">
              {selected.size} de {allPerms.length} permissoes selecionadas
            </span>
          </div>
        </div>
      </AppCard>

      {/* Editor de permissoes */}
      <AppCard title="Permissões de Acesso">
        <div className="space-y-6">
          {/* Busca */}
          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
            <input
              type="text"
              placeholder="Buscar permissão por nome…"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full rounded-md border bg-background pl-9 pr-9 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
            />
            {search && (
              <button
                type="button"
                onClick={() => setSearch('')}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
              >
                <X className="h-4 w-4" />
              </button>
            )}
          </div>

          {Object.keys(grouped).length === 0 && (
            <p className="text-sm text-muted-foreground text-center py-4">
              Nenhuma permissão encontrada para &quot;{search}&quot;.
            </p>
          )}

          {Object.entries(grouped).map(([module, perms]) => {
            const allSel  = perms.every((p) => selected.has(p.uuid))
            const someSel = perms.some((p) => selected.has(p.uuid))
            const label   = MODULE_LABELS[module] ?? (module.charAt(0).toUpperCase() + module.slice(1))
            const count   = perms.filter((p) => selected.has(p.uuid)).length

            return (
              <div key={module} className="border rounded-md overflow-hidden">
                {/* Header do modulo */}
                <label className="flex items-center gap-3 px-4 py-3 bg-muted/40 cursor-pointer hover:bg-muted/60 transition-colors">
                  <input
                    type="checkbox"
                    checked={allSel}
                    ref={(el) => { if (el) el.indeterminate = someSel && !allSel }}
                    onChange={() => toggleModule(perms)}
                    className="h-4 w-4 accent-primary cursor-pointer"
                  />
                  <span className="font-semibold text-sm flex-1">{label}</span>
                  <span className="text-xs text-muted-foreground bg-background border rounded-full px-2 py-0.5">
                    {count}/{perms.length}
                  </span>
                </label>

                {/* Permissoes do modulo */}
                <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-0 divide-x divide-y">
                  {perms.map((perm) => (
                    <label
                      key={perm.uuid}
                      className="flex items-start gap-2.5 px-4 py-2.5 cursor-pointer hover:bg-muted/30 transition-colors"
                    >
                      <input
                        type="checkbox"
                        checked={selected.has(perm.uuid)}
                        onChange={() => togglePerm(perm.uuid)}
                        className="mt-0.5 h-4 w-4 accent-primary cursor-pointer shrink-0"
                      />
                      <span className="text-sm leading-snug">{perm.name}</span>
                    </label>
                  ))}
                </div>
              </div>
            )
          })}
        </div>
      </AppCard>

      {/* Barra de acoes sticky */}
      <div className="flex items-center gap-3 sticky bottom-4 z-10 bg-background/90 backdrop-blur-sm border rounded-lg px-4 py-3 shadow-md">
        <Button onClick={handleSave} disabled={isSaving || !dirty || !name.trim()}>
          {isSaving
            ? <><Loader2 className="h-4 w-4 animate-spin mr-2" />Salvando...</>
            : <><Save className="h-4 w-4 mr-2" />Salvar alteracoes</>
          }
        </Button>
        <Button variant="outline" onClick={() => router.push(ROUTES.ROLES)} disabled={isSaving}>
          <ChevronLeft className="h-4 w-4 mr-1.5" />
          Voltar
        </Button>
        {dirty && (
          <span className="text-xs text-amber-600 ml-auto font-medium">
            Alteracoes nao salvas
          </span>
        )}
      </div>
    </div>
  )
}
