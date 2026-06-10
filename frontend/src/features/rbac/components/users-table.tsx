'use client'

import { useState } from 'react'
import Link from 'next/link'
import { UserPlus, Users, Pencil, Trash2, X, Loader2, Eye, EyeOff, Info } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Skeleton } from '@/components/ui/skeleton'
import {
  useUsers,
  useRoles,
  useCreateUser,
  useAssignRole,
  useChangeRole,
  useRevokeUser,
} from '../hooks'
import { rbacService } from '@/services/rbac.service'
import { ROUTES } from '@/constants'
import type { TenantUserData, Role } from '@store/shared-types'

// ── Helpers ───────────────────────────────────────────────────────────────────

const ROLE_VARIANT_MAP: Record<string, 'default' | 'secondary' | 'outline' | 'success' | 'warning' | 'destructive'> = {
  owner:          'default',
  manager:        'secondary',
  salesperson:    'success',
  financial:      'warning',
  stock_operator: 'outline',
  cashier:        'outline',
}
const ROLE_SLUGS = ['owner', 'manager', 'salesperson', 'cashier', 'stock_operator', 'financial']

function RoleBadge({ slug, name }: { slug: string; name: string }) {
  return <Badge variant={ROLE_VARIANT_MAP[slug] ?? 'secondary'}>{name}</Badge>
}
function StatusBadge({ isActive }: { isActive: boolean }) {
  return <Badge variant={isActive ? 'success' : 'destructive'}>{isActive ? 'Ativo' : 'Inativo'}</Badge>
}

// ── Modal base ────────────────────────────────────────────────────────────────

function Modal({ title, subtitle, onClose, children }: {
  title:     string
  subtitle?: string
  onClose:   () => void
  children:  React.ReactNode
}) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center">
      <div className="absolute inset-0 bg-black/50" onClick={onClose} />
      <div className="relative z-10 w-full max-w-md rounded-lg border bg-background p-6 shadow-xl">
        <div className="mb-5">
          <div className="flex items-start justify-between">
            <h2 className="text-lg font-semibold">{title}</h2>
            <button type="button" onClick={onClose} className="rounded-md p-1 hover:bg-muted" aria-label="Fechar">
              <X className="h-4 w-4" />
            </button>
          </div>
          {subtitle && <p className="text-sm text-muted-foreground mt-1">{subtitle}</p>}
        </div>
        {children}
      </div>
    </div>
  )
}

// ── Modal: Criar novo usuario ─────────────────────────────────────────────────

function CreateUserModal({ onClose, roles }: { onClose: () => void; roles: Role[] }) {
  const [name,        setName]        = useState('')
  const [email,       setEmail]       = useState('')
  const [password,    setPassword]    = useState('')
  const [showPass,    setShowPass]    = useState(false)
  const [roleId,      setRoleId]      = useState('')
  const [errors,      setErrors]      = useState<Record<string, string>>({})

  const { mutate: create, isPending } = useCreateUser()
  const systemRoles = roles.filter((r) => ROLE_SLUGS.includes(r.slug))

  function validate() {
    const e: Record<string, string> = {}
    if (!name.trim())            e.name     = 'Nome obrigatorio'
    if (!email.trim())           e.email    = 'E-mail obrigatorio'
    if (password.length < 8)     e.password = 'Minimo 8 caracteres'
    if (!roleId)                 e.role_id  = 'Selecione um perfil'
    setErrors(e)
    return Object.keys(e).length === 0
  }

  function handleCreate() {
    if (!validate()) return
    create(
      { name: name.trim(), email: email.trim(), password, role_id: roleId },
      {
        onSuccess: onClose,
        onError: (err) => {
          const msg = err instanceof Error ? err.message : 'Erro ao criar usuario'
          setErrors({ general: msg })
        },
      }
    )
  }

  return (
    <Modal
      title="Criar Novo Usuario"
      subtitle="O usuario pode acessar o sistema com este e-mail e senha."
      onClose={onClose}
    >
      <div className="space-y-4">
        {errors.general && (
          <div className="rounded-md bg-destructive/10 border border-destructive/20 px-3 py-2 text-sm text-destructive">
            {errors.general}
          </div>
        )}

        <div className="space-y-1.5">
          <Label>Nome completo *</Label>
          <Input
            placeholder="Ex.: Ana Paula Silva"
            value={name}
            onChange={(e) => { setName(e.target.value); setErrors((p) => ({ ...p, name: '' })) }}
            autoFocus
          />
          {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
        </div>

        <div className="space-y-1.5">
          <Label>E-mail *</Label>
          <Input
            type="email"
            placeholder="usuario@email.com"
            value={email}
            onChange={(e) => { setEmail(e.target.value); setErrors((p) => ({ ...p, email: '' })) }}
          />
          {errors.email && <p className="text-xs text-destructive">{errors.email}</p>}
        </div>

        <div className="space-y-1.5">
          <Label>Senha *</Label>
          <div className="relative">
            <Input
              type={showPass ? 'text' : 'password'}
              placeholder="Minimo 8 caracteres"
              value={password}
              onChange={(e) => { setPassword(e.target.value); setErrors((p) => ({ ...p, password: '' })) }}
              className="pr-10"
            />
            <button
              type="button"
              onClick={() => setShowPass((v) => !v)}
              className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
            >
              {showPass ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
            </button>
          </div>
          {errors.password && <p className="text-xs text-destructive">{errors.password}</p>}
        </div>

        <div className="space-y-1.5">
          <Label>Perfil de acesso *</Label>
          <select
            value={roleId}
            onChange={(e) => { setRoleId(e.target.value); setErrors((p) => ({ ...p, role_id: '' })) }}
            className="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
          >
            <option value="">Selecione um perfil...</option>
            {systemRoles.map((r) => (
              <option key={r.uuid} value={r.uuid}>{r.name}</option>
            ))}
          </select>
          {errors.role_id && <p className="text-xs text-destructive">{errors.role_id}</p>}

          {roleId && (
            <p className="text-xs text-muted-foreground flex items-center gap-1">
              <Info className="h-3 w-3" />
              As permissoes deste perfil podem ser editadas em{' '}
              <Link href={ROUTES.ROLES} className="underline hover:text-foreground" onClick={onClose}>
                Perfis de Acesso
              </Link>.
            </p>
          )}
        </div>

        <div className="flex justify-end gap-2 pt-2">
          <Button type="button" variant="outline" onClick={onClose} disabled={isPending}>Cancelar</Button>
          <Button type="button" onClick={handleCreate} disabled={isPending}>
            {isPending ? <><Loader2 className="h-4 w-4 animate-spin mr-2" />Criando...</> : 'Criar usuario'}
          </Button>
        </div>
      </div>
    </Modal>
  )
}

// ── Modal: Convidar usuario existente ─────────────────────────────────────────

function InviteUserModal({ onClose, roles }: { onClose: () => void; roles: Role[] }) {
  const [email,    setEmail]    = useState('')
  const [roleId,   setRoleId]   = useState('')
  const [found,    setFound]    = useState<{ uuid: string; name: string; email: string } | null>(null)
  const [lookupError, setLookupError] = useState('')
  const [searching, setSearching]    = useState(false)

  const { mutate: assign, isPending } = useAssignRole()
  const systemRoles = roles.filter((r) => ROLE_SLUGS.includes(r.slug))

  async function handleLookup() {
    if (!email.trim()) return
    setSearching(true); setFound(null); setLookupError('')
    try {
      setFound(await rbacService.lookupByEmail(email.trim()))
    } catch (err: unknown) {
      setLookupError((err as { message?: string })?.message ?? 'Usuario nao encontrado.')
    } finally {
      setSearching(false)
    }
  }

  function handleInvite() {
    if (!found || !roleId) return
    assign({ user_id: found.uuid, role_id: roleId }, { onSuccess: onClose })
  }

  return (
    <Modal
      title="Convidar Usuario Existente"
      subtitle="Para usuarios que ja possuem conta no sistema."
      onClose={onClose}
    >
      <div className="space-y-4">
        <div className="space-y-1.5">
          <Label>E-mail do usuario</Label>
          <div className="flex gap-2">
            <Input
              type="email"
              placeholder="usuario@email.com"
              value={email}
              onChange={(e) => { setEmail(e.target.value); setFound(null); setLookupError('') }}
              onKeyDown={(e) => e.key === 'Enter' && handleLookup()}
              autoFocus
            />
            <Button type="button" variant="outline" onClick={handleLookup} disabled={searching || !email.trim()}>
              {searching ? <Loader2 className="h-4 w-4 animate-spin" /> : 'Buscar'}
            </Button>
          </div>
          {lookupError && <p className="text-xs text-destructive">{lookupError}</p>}
        </div>

        {found && (
          <div className="rounded-md border bg-muted/30 px-4 py-3 text-sm">
            <p className="font-medium">{found.name}</p>
            <p className="text-muted-foreground text-xs">{found.email}</p>
          </div>
        )}

        <div className="space-y-1.5">
          <Label>Perfil de acesso</Label>
          <select
            value={roleId}
            onChange={(e) => setRoleId(e.target.value)}
            className="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
          >
            <option value="">Selecione um perfil...</option>
            {systemRoles.map((r) => (
              <option key={r.uuid} value={r.uuid}>{r.name}</option>
            ))}
          </select>
        </div>

        <div className="flex justify-end gap-2 pt-2">
          <Button type="button" variant="outline" onClick={onClose} disabled={isPending}>Cancelar</Button>
          <Button type="button" onClick={handleInvite} disabled={!found || !roleId || isPending}>
            {isPending ? <><Loader2 className="h-4 w-4 animate-spin mr-2" />Convidando...</> : 'Convidar'}
          </Button>
        </div>
      </div>
    </Modal>
  )
}

// ── Modal: Trocar role ────────────────────────────────────────────────────────

function ChangeRoleModal({ tenantUser, roles, onClose }: { tenantUser: TenantUserData; roles: Role[]; onClose: () => void }) {
  const [roleId, setRoleId] = useState(tenantUser.role.uuid)
  const { mutate: changeRole, isPending } = useChangeRole()
  const systemRoles = roles.filter((r) => ROLE_SLUGS.includes(r.slug))

  return (
    <Modal title={`Trocar perfil — ${tenantUser.name ?? tenantUser.email}`} onClose={onClose}>
      <div className="space-y-4">
        <div className="space-y-1.5">
          <Label>Perfil atual</Label>
          <RoleBadge slug={tenantUser.role.slug} name={tenantUser.role.name} />
        </div>
        <div className="space-y-1.5">
          <Label>Novo perfil</Label>
          <select
            value={roleId}
            onChange={(e) => setRoleId(e.target.value)}
            className="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
          >
            {systemRoles.map((r) => (
              <option key={r.uuid} value={r.uuid}>{r.name}</option>
            ))}
          </select>
          <p className="text-xs text-muted-foreground flex items-center gap-1">
            <Info className="h-3 w-3" />
            As permissoes de cada perfil sao editadas em{' '}
            <Link href={ROUTES.ROLES} className="underline hover:text-foreground" onClick={onClose}>
              Perfis de Acesso
            </Link>.
          </p>
        </div>
        <div className="flex justify-end gap-2 pt-2">
          <Button type="button" variant="outline" onClick={onClose} disabled={isPending}>Cancelar</Button>
          <Button type="button" onClick={() => changeRole({ tenantUserUuid: tenantUser.uuid, roleId }, { onSuccess: onClose })}
            disabled={isPending || roleId === tenantUser.role.uuid}>
            {isPending ? <><Loader2 className="h-4 w-4 animate-spin mr-2" />Salvando...</> : 'Salvar'}
          </Button>
        </div>
      </div>
    </Modal>
  )
}

// ── Modal: Confirmar revogacao ────────────────────────────────────────────────

function RevokeModal({ tenantUser, onClose }: { tenantUser: TenantUserData; onClose: () => void }) {
  const { mutate: revoke, isPending } = useRevokeUser()
  return (
    <Modal title="Revogar acesso" onClose={onClose}>
      <div className="space-y-4">
        <p className="text-sm text-muted-foreground">
          Tem certeza que deseja revogar o acesso de{' '}
          <span className="font-medium text-foreground">{tenantUser.name ?? tenantUser.email}</span>?
          O usuario perdera acesso imediatamente.
        </p>
        <div className="flex justify-end gap-2 pt-2">
          <Button type="button" variant="outline" onClick={onClose} disabled={isPending}>Cancelar</Button>
          <Button type="button" variant="destructive" onClick={() => revoke(tenantUser.uuid, { onSuccess: onClose })} disabled={isPending}>
            {isPending ? <><Loader2 className="h-4 w-4 animate-spin mr-2" />Revogando...</> : 'Revogar acesso'}
          </Button>
        </div>
      </div>
    </Modal>
  )
}

// ── Linha da tabela ───────────────────────────────────────────────────────────

function UserRow({ tenantUser, currentUserId, roles }: {
  tenantUser:    TenantUserData
  currentUserId: string | undefined
  roles:         Role[]
}) {
  const [changeRoleOpen, setChangeRoleOpen] = useState(false)
  const [revokeOpen,     setRevokeOpen]     = useState(false)
  const isSelf     = tenantUser.user_id === currentUserId
  const joinedDate = new Date(tenantUser.joined_at).toLocaleDateString('pt-BR')

  return (
    <>
      <tr className="border-b transition-colors hover:bg-muted/50">
        <td className="px-4 py-3">
          <div className="flex flex-col">
            <span className="font-medium text-sm">{tenantUser.name ?? '—'}</span>
            <span className="text-xs text-muted-foreground">{tenantUser.email ?? '—'}</span>
          </div>
        </td>
        <td className="px-4 py-3">
          <RoleBadge slug={tenantUser.role.slug} name={tenantUser.role.name} />
        </td>
        <td className="px-4 py-3"><StatusBadge isActive={tenantUser.is_active} /></td>
        <td className="px-4 py-3 text-sm text-muted-foreground">
          {(tenantUser.store_ids?.length ?? 0) === 0
            ? <span className="text-xs italic">Todas as lojas</span>
            : <span>{tenantUser.store_ids.length} loja(s)</span>}
        </td>
        <td className="px-4 py-3 text-sm text-muted-foreground">{joinedDate}</td>
        <td className="px-4 py-3">
          <div className="flex items-center gap-1">
            <Button size="sm" variant="ghost" onClick={() => setChangeRoleOpen(true)} title="Trocar perfil">
              <Pencil className="h-3.5 w-3.5" />
            </Button>
            <Button size="sm" variant="ghost" onClick={() => setRevokeOpen(true)} disabled={isSelf}
              title={isSelf ? 'Nao e possivel revogar a si mesmo' : 'Revogar acesso'}
              className="text-destructive hover:text-destructive hover:bg-destructive/10">
              <Trash2 className="h-3.5 w-3.5" />
            </Button>
          </div>
        </td>
      </tr>
      {changeRoleOpen && <ChangeRoleModal tenantUser={tenantUser} roles={roles} onClose={() => setChangeRoleOpen(false)} />}
      {revokeOpen     && <RevokeModal tenantUser={tenantUser} onClose={() => setRevokeOpen(false)} />}
    </>
  )
}

// ── Skeleton ──────────────────────────────────────────────────────────────────

function LoadingRows() {
  return (
    <>
      {Array.from({ length: 4 }).map((_, i) => (
        <tr key={i} className="border-b">
          {Array.from({ length: 6 }).map((_, j) => (
            <td key={j} className="px-4 py-3"><Skeleton className="h-5 w-full" /></td>
          ))}
        </tr>
      ))}
    </>
  )
}

// ── Tabela principal ──────────────────────────────────────────────────────────

export function UsersTable({ currentUserId }: { currentUserId?: string }) {
  const [createOpen, setCreateOpen] = useState(false)
  const [inviteOpen, setInviteOpen] = useState(false)
  const { data: users, isLoading, isError } = useUsers()
  const { data: roles = [] } = useRoles()

  return (
    <div className="space-y-4">
      {/* Banner explicativo */}
      <div className="flex items-start gap-3 rounded-md border border-blue-200 bg-blue-50 dark:bg-blue-950/30 dark:border-blue-900 px-4 py-3">
        <Info className="h-4 w-4 text-blue-600 dark:text-blue-400 mt-0.5 shrink-0" />
        <div className="text-sm text-blue-800 dark:text-blue-300 space-y-1">
          <p className="font-medium">Como funcionam as permissoes?</p>
          <p className="text-xs opacity-90">
            Cada usuario recebe um <strong>Perfil de acesso</strong> (ex.: Vendedor, Gerente, Caixa).
            As permissoes do sistema sao definidas no perfil — ao alterar o perfil, todos os usuarios com aquele perfil sao afetados.
            Para ver ou editar as permissoes de cada perfil, acesse{' '}
            <Link href={ROUTES.ROLES} className="underline font-medium hover:opacity-80">Perfis de Acesso</Link>.
          </p>
        </div>
      </div>

      {/* Header com botoes */}
      <div className="flex items-center justify-between">
        <p className="text-sm text-muted-foreground">
          {!isLoading && !isError && `${users?.length ?? 0} usuario(s)`}
        </p>
        <div className="flex gap-2">
          <Button size="sm" variant="outline" onClick={() => setInviteOpen(true)} title="Convidar usuario que ja possui conta">
            <Users className="h-4 w-4 mr-2" />
            Convidar
          </Button>
          <Button size="sm" onClick={() => setCreateOpen(true)}>
            <UserPlus className="h-4 w-4 mr-2" />
            Novo usuario
          </Button>
        </div>
      </div>

      {/* Tabela */}
      <div className="overflow-x-auto rounded-lg border">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b bg-muted/40">
              <th className="px-4 py-3 text-left font-semibold">Usuario</th>
              <th className="px-4 py-3 text-left font-semibold">Perfil</th>
              <th className="px-4 py-3 text-left font-semibold">Status</th>
              <th className="px-4 py-3 text-left font-semibold">Acesso</th>
              <th className="px-4 py-3 text-left font-semibold">Entrou em</th>
              <th className="px-4 py-3 text-left font-semibold">Acoes</th>
            </tr>
          </thead>
          <tbody>
            {isLoading && <LoadingRows />}
            {isError && (
              <tr><td colSpan={6} className="px-4 py-8 text-center text-sm text-muted-foreground">Erro ao carregar usuarios.</td></tr>
            )}
            {!isLoading && !isError && users?.map((u) => (
              <UserRow key={u.uuid} tenantUser={u} currentUserId={currentUserId} roles={roles} />
            ))}
            {!isLoading && !isError && (users?.length ?? 0) === 0 && (
              <tr><td colSpan={6} className="px-4 py-8 text-center text-sm text-muted-foreground">Nenhum usuario encontrado.</td></tr>
            )}
          </tbody>
        </table>
      </div>

      {createOpen && <CreateUserModal roles={roles} onClose={() => setCreateOpen(false)} />}
      {inviteOpen && <InviteUserModal roles={roles} onClose={() => setInviteOpen(false)} />}
    </div>
  )
}
