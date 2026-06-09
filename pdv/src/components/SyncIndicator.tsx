/**
 * src/components/SyncIndicator.tsx
 *
 * Compact sync-status badge for the top bar.
 *
 * Visual states:
 *  - Syncing:  spinning cloud icon + "Sincronizando..."
 *  - Pending:  yellow cloud-off + count of pending items
 *  - Failed:   red alert + failed count
 *  - Online:   green dot (hidden in compact mode when all synced)
 *  - Offline:  gray cloud-off
 *
 * Usage in Topbar:
 *   <SyncIndicator compact />
 *
 * Usage in a settings/status page:
 *   <SyncIndicator />
 */

import { Cloud, CloudOff, RefreshCw, AlertTriangle, CheckCircle2 } from 'lucide-react'
import { useSyncStore } from '@/stores/syncStore'
import { cn }          from '@/lib/utils'

interface SyncIndicatorProps {
  /** When true, hides the text label and reduces padding (for top bar use). */
  compact?: boolean
  /** Extra class names applied to the root element. */
  className?: string
}

export function SyncIndicator({ compact = false, className }: SyncIndicatorProps) {
  const { status, isOnline, pending, failedCount, isSyncing, lastSyncAt, lastError } =
    useSyncStore()

  // ── Determine display state ──────────────────────────────────────────────

  const isOffline  = !isOnline
  const hasFailed  = failedCount > 0
  const hasPending = pending > 0

  // ── Icon ─────────────────────────────────────────────────────────────────

  const iconClass = 'w-3.5 h-3.5 shrink-0'

  let icon: React.ReactNode
  let colorClass: string
  let label: string

  if (isSyncing || status === 'running') {
    icon       = <RefreshCw className={cn(iconClass, 'animate-spin')} />
    colorClass = 'text-blue-400'
    label      = 'Sincronizando...'
  } else if (isOffline) {
    icon       = <CloudOff className={iconClass} />
    colorClass = 'text-muted-foreground'
    label      = 'Offline'
  } else if (hasFailed) {
    icon       = <AlertTriangle className={iconClass} />
    colorClass = 'text-red-400'
    label      = `${failedCount} ${failedCount === 1 ? 'erro' : 'erros'}`
  } else if (hasPending) {
    icon       = <CloudOff className={iconClass} />
    colorClass = 'text-yellow-500'
    label      = `${pending} ${pending === 1 ? 'pendente' : 'pendentes'}`
  } else if (status === 'error') {
    icon       = <AlertTriangle className={iconClass} />
    colorClass = 'text-red-400'
    label      = 'Erro de sync'
  } else {
    // idle + online + nothing pending = all synced
    icon       = <CheckCircle2 className={iconClass} />
    colorClass = 'text-emerald-500'
    label      = 'Sincronizado'
  }

  // ── Tooltip text ─────────────────────────────────────────────────────────

  let title = label
  if (lastSyncAt) {
    const d = new Date(lastSyncAt)
    title += ` · Último sync: ${d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}`
  }
  if (lastError) {
    title += ` · Erro: ${lastError}`
  }

  // ── Render ────────────────────────────────────────────────────────────────

  return (
    <div
      className={cn(
        'flex items-center gap-1.5 text-xs select-none',
        colorClass,
        compact ? 'px-0' : 'px-2 py-1 rounded-md bg-card border',
        className,
      )}
      title={title}
      role="status"
      aria-label={title}
    >
      {icon}
      {!compact && (
        <span className="font-medium">{label}</span>
      )}
      {compact && hasPending && !isSyncing && (
        <span className="tabular-nums">
          {pending}
        </span>
      )}
    </div>
  )
}
