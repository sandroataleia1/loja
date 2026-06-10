'use client'

import { useState } from 'react'
import Link from 'next/link'
import { Plus, Pencil, Trash2 } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { ConfirmDialog } from '@/components/shared/confirm-dialog'
import { useCashRegisters, useDeleteCashRegister } from '@/features/sales/hooks'
import { ROUTES } from '@/constants'

export default function CashRegistersPage() {
  const { data: registers = [], isLoading } = useCashRegisters()
  const { mutate: deleteRegister, isPending: isDeleting } = useDeleteCashRegister()
  const [deleteTarget, setDeleteTarget] = useState<{ uuid: string; name: string } | null>(null)

  function handleConfirmDelete() {
    if (!deleteTarget) return
    deleteRegister(deleteTarget.uuid, {
      onSuccess: () => { toast.success('Caixa excluído.'); setDeleteTarget(null) },
      onError:   () => { toast.error('Erro ao excluir caixa.'); setDeleteTarget(null) },
    })
  }

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Caixas PDV"
        description="Terminais de ponto de venda cadastrados."
        actions={
          <Button asChild>
            <Link href={`${ROUTES.CASH_REGISTERS}/create`}>
              <Plus className="mr-2 h-4 w-4" />
              Novo Caixa
            </Link>
          </Button>
        }
      />

      <div className="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Código</TableHead>
              <TableHead>Nome</TableHead>
              <TableHead>Loja</TableHead>
              <TableHead>Status</TableHead>
              <TableHead className="w-24">Ações</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {isLoading ? (
              Array.from({ length: 3 }).map((_, i) => (
                <TableRow key={i}>
                  {Array.from({ length: 5 }).map((_, j) => (
                    <TableCell key={j}><Skeleton className="h-4 w-full" /></TableCell>
                  ))}
                </TableRow>
              ))
            ) : registers.length === 0 ? (
              <TableRow>
                <TableCell colSpan={5} className="text-center text-muted-foreground py-8">
                  Nenhum caixa cadastrado.
                </TableCell>
              </TableRow>
            ) : registers.map((r) => (
              <TableRow key={r.uuid}>
                <TableCell className="font-mono text-sm">{r.code}</TableCell>
                <TableCell className="font-medium">{r.name}</TableCell>
                <TableCell className="text-muted-foreground">{r.store?.name ?? r.store_id}</TableCell>
                <TableCell>
                  <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium border ${r.is_active ? 'bg-green-100 text-green-700 border-green-200' : 'bg-gray-100 text-gray-600 border-gray-200'}`}>
                    {r.is_active ? 'Ativo' : 'Inativo'}
                  </span>
                </TableCell>
                <TableCell>
                  <div className="flex items-center gap-1">
                    <Button variant="ghost" size="icon" asChild>
                      <Link href={`${ROUTES.CASH_REGISTERS}/${r.uuid}/edit`}>
                        <Pencil className="h-4 w-4" />
                      </Link>
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      className="text-destructive hover:text-destructive"
                      onClick={() => setDeleteTarget({ uuid: r.uuid, name: r.name })}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>

      <ConfirmDialog
        open={!!deleteTarget}
        onOpenChange={(open) => !open && setDeleteTarget(null)}
        title="Excluir caixa"
        description={`Deseja excluir o caixa "${deleteTarget?.name}"? Esta ação não pode ser desfeita.`}
        confirmLabel="Excluir"
        onConfirm={handleConfirmDelete}
        loading={isDeleting}
        variant="destructive"
      />
    </div>
  )
}
