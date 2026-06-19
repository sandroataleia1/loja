'use client'

import { useState } from 'react'
import Link from 'next/link'
import { Settings, RefreshCw, XCircle, FileText, Loader2, AlertCircle, ExternalLink } from 'lucide-react'
import { toast } from 'sonner'
import { AppPageHeader }  from '@/components/shared/app-page-header'
import { AppCard }        from '@/components/shared/app-card'
import { Skeleton }       from '@/components/ui/skeleton'
import { Button }         from '@/components/ui/button'
import {
  useFiscalDocuments,
  useCancelFiscalDocument,
  useRetryFiscalDocument,
} from '@/features/sales/hooks'
import { ROUTES } from '@/constants'
import type { FiscalDocument } from '@/types/shared-types'

// ── Status badge ──────────────────────────────────────────────────────────────

const STATUS_STYLES: Record<string, string> = {
  draft:       'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
  pending:     'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
  processing:  'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
  authorized:  'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
  rejected:    'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
  cancelled:   'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
  contingency: 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300',
  error:       'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
}

function StatusBadge({ status, label }: { status: string; label: string }) {
  return (
    <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${STATUS_STYLES[status] ?? STATUS_STYLES.draft}`}>
      {label}
    </span>
  )
}

function TypeBadge({ type, label }: { type: string; label: string }) {
  const cls = type === 'nfce'
    ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300'
    : 'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-300'
  return (
    <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${cls}`}>
      {label}
    </span>
  )
}

// ── Helpers ───────────────────────────────────────────────────────────────────

const fmtDateTime = (iso: string | null) =>
  iso ? new Date(iso).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—'

function truncateKey(key: string | null) {
  if (!key) return '—'
  return key.length > 12 ? `${key.slice(0, 6)}…${key.slice(-6)}` : key
}

const STATUS_OPTIONS = [
  { value: '',            label: 'Todos os status' },
  { value: 'authorized',  label: 'Autorizado' },
  { value: 'rejected',    label: 'Rejeitado' },
  { value: 'pending',     label: 'Pendente' },
  { value: 'processing',  label: 'Processando' },
  { value: 'cancelled',   label: 'Cancelado' },
  { value: 'contingency', label: 'Contingência' },
  { value: 'error',       label: 'Erro' },
]

// ── Row actions ───────────────────────────────────────────────────────────────

function DocumentRow({ doc }: { doc: FiscalDocument }) {
  const cancel = useCancelFiscalDocument()
  const retry  = useRetryFiscalDocument()

  function handleCancel() {
    if (!confirm('Cancelar este documento fiscal? Esta ação não pode ser desfeita.')) return
    cancel.mutate({ uuid: doc.uuid }, {
      onSuccess: () => toast.success('Documento cancelado.'),
      onError:   (err) => toast.error(err instanceof Error ? err.message : 'Erro ao cancelar.'),
    })
  }

  function handleRetry() {
    retry.mutate(doc.uuid, {
      onSuccess: () => toast.success('Retransmissão solicitada.'),
      onError:   (err) => toast.error(err instanceof Error ? err.message : 'Erro ao retentar.'),
    })
  }

  return (
    <tr className="border-b last:border-0 hover:bg-muted/30 transition-colors">
      <td className="px-4 py-3">
        <TypeBadge type={doc.type} label={doc.type_label} />
      </td>
      <td className="px-4 py-3">
        <StatusBadge status={doc.status} label={doc.status_label} />
      </td>
      <td className="px-4 py-3 font-mono text-xs text-muted-foreground">
        {truncateKey(doc.access_key)}
      </td>
      <td className="px-4 py-3 text-sm text-muted-foreground">
        {fmtDateTime(doc.issued_at ?? doc.created_at)}
      </td>
      {doc.error_message && (
        <td className="px-4 py-3 text-xs text-destructive max-w-[200px] truncate" title={doc.error_message}>
          {doc.error_message}
        </td>
      )}
      {!doc.error_message && <td className="px-4 py-3 text-muted-foreground text-xs">—</td>}
      <td className="px-4 py-3">
        <div className="flex items-center gap-1.5 justify-end">
          {doc.pdf_path && (
            <a
              href={doc.pdf_path}
              target="_blank"
              rel="noopener noreferrer"
              title="Ver DANFe/DANFCE"
              className="p-1.5 rounded-lg text-muted-foreground hover:text-foreground hover:bg-accent transition-colors"
            >
              <ExternalLink className="w-3.5 h-3.5" />
            </a>
          )}
          {doc.can_retry && (
            <button
              onClick={handleRetry}
              disabled={retry.isPending}
              title="Retentar transmissão"
              className="p-1.5 rounded-lg text-muted-foreground hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/40 transition-colors disabled:opacity-50"
            >
              {retry.isPending
                ? <Loader2 className="w-3.5 h-3.5 animate-spin" />
                : <RefreshCw className="w-3.5 h-3.5" />}
            </button>
          )}
          {doc.can_cancel && (
            <button
              onClick={handleCancel}
              disabled={cancel.isPending}
              title="Cancelar documento"
              className="p-1.5 rounded-lg text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors disabled:opacity-50"
            >
              {cancel.isPending
                ? <Loader2 className="w-3.5 h-3.5 animate-spin" />
                : <XCircle className="w-3.5 h-3.5" />}
            </button>
          )}
        </div>
      </td>
    </tr>
  )
}

// ── Page ──────────────────────────────────────────────────────────────────────

export default function FiscalDocumentsPage() {
  const [status, setStatus] = useState('')
  const [page,   setPage]   = useState(1)

  const { data, isLoading, isError } = useFiscalDocuments({ status: status || undefined, per_page: 25 })
  const documents = data?.data ?? []
  const meta      = data?.meta

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Documentos Fiscais"
        description="NFC-e e NF-e emitidas pela loja."
        actions={
          <Button variant="outline" asChild size="sm">
            <Link href={ROUTES.FISCAL_SETTINGS}>
              <Settings className="mr-1.5 h-3.5 w-3.5" />
              Configurações
            </Link>
          </Button>
        }
      />

      <AppCard className="overflow-hidden">
        {/* Filters */}
        <div className="flex items-center gap-3 px-4 py-3 border-b">
          <select
            value={status}
            onChange={(e) => { setStatus(e.target.value); setPage(1) }}
            className="h-8 rounded-lg border bg-background px-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
          >
            {STATUS_OPTIONS.map((o) => (
              <option key={o.value} value={o.value}>{o.label}</option>
            ))}
          </select>

          <span className="ml-auto text-xs text-muted-foreground">
            {meta ? `${meta.total} documento${meta.total !== 1 ? 's' : ''}` : ''}
          </span>
        </div>

        {/* Table */}
        {isLoading ? (
          <div className="space-y-3 p-4">
            {Array.from({ length: 8 }).map((_, i) => (
              <Skeleton key={i} className="h-10 w-full rounded-lg" />
            ))}
          </div>
        ) : isError ? (
          <div className="flex items-center gap-2 p-6 text-destructive text-sm">
            <AlertCircle className="w-4 h-4 shrink-0" />
            Erro ao carregar documentos.
          </div>
        ) : documents.length === 0 ? (
          <div className="flex flex-col items-center gap-3 py-16 text-muted-foreground">
            <FileText className="w-10 h-10 opacity-30" />
            <p className="text-sm">Nenhum documento fiscal encontrado.</p>
            {status && (
              <button
                onClick={() => setStatus('')}
                className="text-xs text-primary underline-offset-2 hover:underline"
              >
                Limpar filtro
              </button>
            )}
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b bg-muted/30">
                  <th className="px-4 py-2.5 text-left text-xs font-medium text-muted-foreground">Tipo</th>
                  <th className="px-4 py-2.5 text-left text-xs font-medium text-muted-foreground">Status</th>
                  <th className="px-4 py-2.5 text-left text-xs font-medium text-muted-foreground">Chave de acesso</th>
                  <th className="px-4 py-2.5 text-left text-xs font-medium text-muted-foreground">Data</th>
                  <th className="px-4 py-2.5 text-left text-xs font-medium text-muted-foreground">Erro</th>
                  <th className="px-4 py-2.5 text-right text-xs font-medium text-muted-foreground">Ações</th>
                </tr>
              </thead>
              <tbody>
                {documents.map((doc) => (
                  <DocumentRow key={doc.uuid} doc={doc} />
                ))}
              </tbody>
            </table>
          </div>
        )}

        {/* Pagination */}
        {meta && meta.last_page > 1 && (
          <div className="flex items-center justify-between px-4 py-3 border-t text-sm">
            <button
              onClick={() => setPage((p) => Math.max(1, p - 1))}
              disabled={page <= 1}
              className="px-3 py-1 rounded-lg border disabled:opacity-40 hover:bg-accent transition-colors"
            >
              Anterior
            </button>
            <span className="text-muted-foreground">
              Página {meta.current_page} de {meta.last_page}
            </span>
            <button
              onClick={() => setPage((p) => Math.min(meta.last_page, p + 1))}
              disabled={page >= meta.last_page}
              className="px-3 py-1 rounded-lg border disabled:opacity-40 hover:bg-accent transition-colors"
            >
              Próxima
            </button>
          </div>
        )}
      </AppCard>
    </div>
  )
}
