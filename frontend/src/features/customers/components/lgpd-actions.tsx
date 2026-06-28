'use client'

import { useState } from 'react'
import { Download, Trash2, Loader2, X } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { useAuth } from '@/hooks/use-auth'
import { getToken, apiPost } from '@/lib/api-client'

interface LGPDActionsProps {
  customerUuid: string
}

export function LGPDActions({ customerUuid }: LGPDActionsProps) {
  const { hasPermission } = useAuth()
  const [exporting,        setExporting]        = useState(false)
  const [showDeleteModal,  setShowDeleteModal]  = useState(false)
  const [deleteReason,     setDeleteReason]     = useState('')
  const [submittingDelete, setSubmittingDelete] = useState(false)

  async function handleExport() {
    setExporting(true)
    try {
      const token = getToken()
      const res = await fetch(`/api/v1/customers/${customerUuid}/export-data`, {
        headers: token ? { Authorization: `Bearer ${token}` } : {},
      })
      if (!res.ok) throw new Error(`Erro ${res.status}`)
      const blob = await res.blob()
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `cliente-${customerUuid}-lgpd.json`
      a.click()
      URL.revokeObjectURL(url)
      toast.success('Dados exportados com sucesso.')
    } catch {
      toast.error('Erro ao exportar dados.')
    } finally {
      setExporting(false)
    }
  }

  async function handleRequestDeletion() {
    if (!deleteReason.trim()) return
    setSubmittingDelete(true)
    try {
      await apiPost(`/customers/${customerUuid}/request-deletion`, { reason: deleteReason.trim() })
      toast.success('Solicitação de exclusão enviada para revisão.')
      setShowDeleteModal(false)
      setDeleteReason('')
    } catch {
      toast.error('Erro ao solicitar exclusão.')
    } finally {
      setSubmittingDelete(false)
    }
  }

  return (
    <>
      {hasPermission('customers.export') && (
        <Button variant="outline" size="sm" onClick={handleExport} disabled={exporting}>
          {exporting ? (
            <Loader2 className="h-3.5 w-3.5 mr-1.5 animate-spin" />
          ) : (
            <Download className="h-3.5 w-3.5 mr-1.5" />
          )}
          Exportar (LGPD)
        </Button>
      )}

      {hasPermission('customers.request_deletion') && (
        <Button
          variant="outline"
          size="sm"
          className="text-destructive hover:text-destructive"
          onClick={() => setShowDeleteModal(true)}
        >
          <Trash2 className="h-3.5 w-3.5 mr-1.5" />
          Solicitar exclusão
        </Button>
      )}

      {/* Deletion modal */}
      {showDeleteModal && (
        <div className="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-sm p-4">
          <div className="bg-card border rounded-2xl shadow-2xl w-full max-w-md space-y-4 p-6">
            <div className="flex items-center justify-between">
              <h3 className="font-bold text-destructive">Solicitar Exclusão de Dados</h3>
              <button
                type="button"
                onClick={() => setShowDeleteModal(false)}
                className="text-muted-foreground hover:text-foreground"
              >
                <X className="h-4 w-4" />
              </button>
            </div>
            <p className="text-sm text-muted-foreground">
              A solicitação será enviada para revisão do administrador. Os dados{' '}
              <strong>não serão excluídos imediatamente</strong>.
            </p>
            <div className="space-y-1.5">
              <label className="text-sm font-medium">Motivo *</label>
              <textarea
                className="w-full min-h-[100px] rounded-md border bg-background px-3 py-2 text-sm resize-y"
                placeholder="Descreva o motivo da solicitação…"
                value={deleteReason}
                onChange={(e) => setDeleteReason(e.target.value)}
              />
            </div>
            <div className="flex gap-2 justify-end">
              <Button variant="outline" size="sm" onClick={() => setShowDeleteModal(false)}>
                Cancelar
              </Button>
              <Button
                variant="destructive"
                size="sm"
                disabled={!deleteReason.trim() || submittingDelete}
                onClick={handleRequestDeletion}
              >
                {submittingDelete ? (
                  <Loader2 className="h-3.5 w-3.5 animate-spin mr-1.5" />
                ) : null}
                Solicitar exclusão
              </Button>
            </div>
          </div>
        </div>
      )}
    </>
  )
}
