'use client'

import { useState } from 'react'
import { AlertTriangle, ChevronLeft, ChevronRight, Filter, RefreshCw } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Skeleton } from '@/components/ui/skeleton'
import { useAuditLogs, useAuditFilters } from '../hooks'
import type { AuditLogParams } from '@/services/audit.service'

// ── Helpers ──────────────────────────────────────────────────────────────────

function ActionBadge({ action, label, isHighRisk }: { action: string; label: string; isHighRisk: boolean }) {
  const variant = isHighRisk ? 'destructive' : action.startsWith('auth.') ? 'secondary' : 'outline'
  return (
    <div className="flex items-center gap-1">
      {isHighRisk && <AlertTriangle className="h-3 w-3 text-destructive shrink-0" />}
      <Badge variant={variant as 'destructive' | 'secondary' | 'outline'} className="text-xs font-mono">
        {label}
      </Badge>
    </div>
  )
}

function formatDate(iso: string) {
  return new Date(iso).toLocaleString('pt-BR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit', second: '2-digit',
  })
}

function truncate(str: string | null | undefined, len = 30) {
  if (!str) return '—'
  return str.length > len ? str.slice(0, len) + '…' : str
}

// ── Filtros ──────────────────────────────────────────────────────────────────

interface FilterPanelProps {
  params: AuditLogParams
  onChange: (p: AuditLogParams) => void
}

function FilterPanel({ params, onChange }: FilterPanelProps) {
  const { data: filters } = useAuditFilters()

  return (
    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 p-4 border rounded-lg bg-muted/20">
      <div className="space-y-1">
        <Label className="text-xs">Ação</Label>
        <select
          value={params.action ?? ''}
          onChange={(e) => onChange({ ...params, action: e.target.value || undefined, page: 1 })}
          className="w-full rounded-md border bg-background px-2 py-1.5 text-xs outline-none focus:ring-2 focus:ring-ring"
        >
          <option value="">Todas as ações</option>
          {filters?.actions.map((a) => (
            <option key={a.value} value={a.value}>{a.label}</option>
          ))}
        </select>
      </div>

      <div className="space-y-1">
        <Label className="text-xs">Tipo de entidade</Label>
        <select
          value={params.entity_type ?? ''}
          onChange={(e) => onChange({ ...params, entity_type: e.target.value || undefined, page: 1 })}
          className="w-full rounded-md border bg-background px-2 py-1.5 text-xs outline-none focus:ring-2 focus:ring-ring"
        >
          <option value="">Todos os tipos</option>
          {filters?.entity_types.map((e) => (
            <option key={e.value} value={e.value}>{e.value}</option>
          ))}
        </select>
      </div>

      <div className="space-y-1">
        <Label className="text-xs">Data inicial</Label>
        <Input
          type="date"
          className="h-8 text-xs"
          value={params.date_from ?? ''}
          onChange={(e) => onChange({ ...params, date_from: e.target.value || undefined, page: 1 })}
        />
      </div>

      <div className="space-y-1">
        <Label className="text-xs">Data final</Label>
        <Input
          type="date"
          className="h-8 text-xs"
          value={params.date_to ?? ''}
          onChange={(e) => onChange({ ...params, date_to: e.target.value || undefined, page: 1 })}
        />
      </div>

      <div className="space-y-1">
        <Label className="text-xs">UUID do usuário</Label>
        <Input
          placeholder="UUID do usuário"
          className="h-8 text-xs"
          value={params.user_id ?? ''}
          onChange={(e) => onChange({ ...params, user_id: e.target.value || undefined, page: 1 })}
        />
      </div>

      <div className="flex items-end">
        <label className="flex items-center gap-2 cursor-pointer text-xs">
          <input
            type="checkbox"
            checked={params.high_risk === true}
            onChange={(e) => onChange({ ...params, high_risk: e.target.checked || undefined, page: 1 })}
            className="rounded"
          />
          Apenas alto risco
        </label>
      </div>

      <div className="flex items-end">
        <Button
          type="button"
          variant="outline"
          size="sm"
          className="text-xs"
          onClick={() => onChange({ page: 1 })}
        >
          Limpar filtros
        </Button>
      </div>
    </div>
  )
}

// ── Tabela principal ─────────────────────────────────────────────────────────

export function AuditLogsTable() {
  const [params, setParams] = useState<AuditLogParams>({ page: 1 })
  const [showFilters, setShowFilters] = useState(false)

  const { data, isLoading, isError, refetch, isFetching } = useAuditLogs(params)

  const logs   = data?.data ?? []
  const meta   = data?.meta
  const total  = meta?.total ?? 0
  const lastPage = meta?.last_page ?? 1

  return (
    <div className="space-y-4">
      {/* Toolbar */}
      <div className="flex items-center justify-between gap-2">
        <p className="text-sm text-muted-foreground">
          {!isLoading && `${total.toLocaleString('pt-BR')} registro(s)`}
        </p>
        <div className="flex gap-2">
          <Button type="button" variant="outline" size="sm" onClick={() => setShowFilters((v) => !v)}>
            <Filter className="h-3.5 w-3.5 mr-1.5" />
            Filtros
          </Button>
          <Button type="button" variant="outline" size="sm" onClick={() => refetch()} disabled={isFetching}>
            <RefreshCw className={`h-3.5 w-3.5 ${isFetching ? 'animate-spin' : ''}`} />
          </Button>
        </div>
      </div>

      {/* Painel de filtros */}
      {showFilters && <FilterPanel params={params} onChange={setParams} />}

      {/* Tabela */}
      <div className="overflow-x-auto rounded-lg border">
        <table className="w-full text-xs">
          <thead>
            <tr className="border-b bg-muted/40">
              <th className="px-3 py-2.5 text-left font-semibold whitespace-nowrap">Data/Hora</th>
              <th className="px-3 py-2.5 text-left font-semibold">Ação</th>
              <th className="px-3 py-2.5 text-left font-semibold">Entidade</th>
              <th className="px-3 py-2.5 text-left font-semibold">Usuário</th>
              <th className="px-3 py-2.5 text-left font-semibold">IP</th>
            </tr>
          </thead>
          <tbody>
            {isLoading && Array.from({ length: 8 }).map((_, i) => (
              <tr key={i} className="border-b">
                {Array.from({ length: 5 }).map((_, j) => (
                  <td key={j} className="px-3 py-2.5"><Skeleton className="h-4 w-full" /></td>
                ))}
              </tr>
            ))}

            {isError && (
              <tr>
                <td colSpan={5} className="px-3 py-8 text-center text-muted-foreground">
                  Erro ao carregar logs de auditoria.
                </td>
              </tr>
            )}

            {!isLoading && !isError && logs.length === 0 && (
              <tr>
                <td colSpan={5} className="px-3 py-8 text-center text-muted-foreground">
                  Nenhum registro encontrado para os filtros selecionados.
                </td>
              </tr>
            )}

            {logs.map((log) => (
              <tr key={log.uuid} className={`border-b transition-colors hover:bg-muted/30 ${log.is_high_risk ? 'bg-destructive/5' : ''}`}>
                <td className="px-3 py-2.5 whitespace-nowrap font-mono text-muted-foreground">
                  {formatDate(log.created_at)}
                </td>
                <td className="px-3 py-2.5">
                  <ActionBadge action={log.action} label={log.action_label} isHighRisk={log.is_high_risk} />
                </td>
                <td className="px-3 py-2.5 text-muted-foreground">
                  {log.entity_type
                    ? <span><span className="font-medium text-foreground">{log.entity_type}</span> {truncate(log.entity_uuid, 8)}</span>
                    : '—'}
                </td>
                <td className="px-3 py-2.5 font-mono text-muted-foreground">
                  {truncate(log.user_id, 8)}
                </td>
                <td className="px-3 py-2.5 font-mono text-muted-foreground">
                  {log.ip ?? '—'}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Paginação */}
      {lastPage > 1 && (
        <div className="flex items-center justify-between">
          <p className="text-xs text-muted-foreground">
            Página {params.page ?? 1} de {lastPage}
          </p>
          <div className="flex gap-1">
            <Button
              type="button"
              variant="outline"
              size="sm"
              disabled={(params.page ?? 1) <= 1}
              onClick={() => setParams((p) => ({ ...p, page: (p.page ?? 1) - 1 }))}
            >
              <ChevronLeft className="h-3.5 w-3.5" />
            </Button>
            <Button
              type="button"
              variant="outline"
              size="sm"
              disabled={(params.page ?? 1) >= lastPage}
              onClick={() => setParams((p) => ({ ...p, page: (p.page ?? 1) + 1 }))}
            >
              <ChevronRight className="h-3.5 w-3.5" />
            </Button>
          </div>
        </div>
      )}
    </div>
  )
}
