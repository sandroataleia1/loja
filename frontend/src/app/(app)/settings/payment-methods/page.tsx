'use client'

import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Plus, Pencil, Trash2, Check, X, Lock } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard } from '@/components/shared/app-card'
import { paymentService, type TenantPaymentMethod } from '@/services/payment.service'

const QK = ['payment-methods'] as const

export default function PaymentMethodsPage() {
  const qc = useQueryClient()

  const { data: items = [], isLoading } = useQuery({
    queryKey: QK,
    queryFn:  () => paymentService.listMethods(),
  })

  const createMutation = useMutation({
    mutationFn: (name: string) => paymentService.createMethod(name),
    onSuccess:  () => { qc.invalidateQueries({ queryKey: QK }); toast.success('Forma adicionada.') },
    onError:    () => toast.error('Erro ao adicionar forma de pagamento.'),
  })

  const updateMutation = useMutation({
    mutationFn: ({ uuid, name }: { uuid: string; name: string }) => paymentService.updateMethod(uuid, name),
    onSuccess:  () => { qc.invalidateQueries({ queryKey: QK }); toast.success('Forma atualizada.') },
    onError:    () => toast.error('Erro ao atualizar forma de pagamento.'),
  })

  const deleteMutation = useMutation({
    mutationFn: (uuid: string) => paymentService.deleteMethod(uuid),
    onSuccess:  () => { qc.invalidateQueries({ queryKey: QK }); toast.success('Forma removida.') },
    onError:    () => toast.error('Erro ao remover forma de pagamento.'),
  })

  const [newName,     setNewName]     = useState('')
  const [editingUuid, setEditingUuid] = useState<string | null>(null)
  const [editingName, setEditingName] = useState('')

  function handleCreate(e: React.FormEvent) {
    e.preventDefault()
    const name = newName.trim()
    if (!name) return
    createMutation.mutate(name, { onSuccess: () => setNewName('') })
  }

  function startEdit(item: TenantPaymentMethod) {
    setEditingUuid(item.uuid)
    setEditingName(item.name)
  }

  function saveEdit(uuid: string) {
    const name = editingName.trim()
    if (!name) return
    updateMutation.mutate({ uuid, name }, { onSuccess: () => setEditingUuid(null) })
  }

  function cancelEdit() {
    setEditingUuid(null)
    setEditingName('')
  }

  const isBusy = createMutation.isPending || updateMutation.isPending || deleteMutation.isPending

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Formas de Pagamento"
        description="Gerencie as formas de pagamento disponíveis nos documentos."
      />

      <AppCard>
        {isLoading ? (
          <p className="text-sm text-muted-foreground">Carregando...</p>
        ) : (
          <div className="divide-y">
            {items.map((item) => (
              <div key={item.uuid} className="flex items-center gap-3 py-2.5">
                {editingUuid === item.uuid ? (
                  <>
                    <Input
                      value={editingName}
                      onChange={(e) => setEditingName(e.target.value)}
                      className="h-8 flex-1"
                      onKeyDown={(e) => { if (e.key === 'Enter') saveEdit(item.uuid); if (e.key === 'Escape') cancelEdit() }}
                      autoFocus
                    />
                    <Button size="icon" variant="ghost" className="h-8 w-8" onClick={() => saveEdit(item.uuid)} disabled={isBusy}>
                      <Check className="h-4 w-4 text-green-600" />
                    </Button>
                    <Button size="icon" variant="ghost" className="h-8 w-8" onClick={cancelEdit}>
                      <X className="h-4 w-4" />
                    </Button>
                  </>
                ) : (
                  <>
                    <span className="flex-1 text-sm">{item.name}</span>
                    {item.is_system ? (
                      <span className="flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-[11px] text-muted-foreground">
                        <Lock className="h-3 w-3" />
                        Padrão
                      </span>
                    ) : (
                      <>
                        <Button size="icon" variant="ghost" className="h-8 w-8" onClick={() => startEdit(item)} disabled={isBusy}>
                          <Pencil className="h-3.5 w-3.5" />
                        </Button>
                        <Button
                          size="icon"
                          variant="ghost"
                          className="h-8 w-8 text-destructive hover:text-destructive"
                          onClick={() => deleteMutation.mutate(item.uuid)}
                          disabled={isBusy}
                        >
                          <Trash2 className="h-3.5 w-3.5" />
                        </Button>
                      </>
                    )}
                  </>
                )}
              </div>
            ))}

            {items.length === 0 && !isLoading && (
              <p className="py-4 text-sm text-muted-foreground">Nenhuma forma de pagamento cadastrada.</p>
            )}
          </div>
        )}

        <form onSubmit={handleCreate} className="mt-4 flex gap-2 border-t pt-4">
          <Input
            placeholder="Nova forma (ex: Crédito Loja)"
            value={newName}
            onChange={(e) => setNewName(e.target.value)}
            className="flex-1"
            disabled={isBusy}
          />
          <Button type="submit" disabled={!newName.trim() || isBusy}>
            <Plus className="mr-1.5 h-4 w-4" />
            Adicionar
          </Button>
        </form>
      </AppCard>
    </div>
  )
}
