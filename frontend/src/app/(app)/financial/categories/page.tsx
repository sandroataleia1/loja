'use client'

import { useState } from 'react'
import { Plus, Pencil, Trash2 } from 'lucide-react'
import { toast }          from 'sonner'
import { AppPageHeader }  from '@/components/shared/app-page-header'
import { AppCard }        from '@/components/shared/app-card'
import { Button }         from '@/components/ui/button'
import { Input }          from '@/components/ui/input'
import { Label }          from '@/components/ui/label'
import { Skeleton }       from '@/components/ui/skeleton'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { useFinancialCategories, useCreateCategory, useUpdateCategory, useDeleteCategory } from '@/features/financial/hooks'
import type { FinancialCategory, FinancialEntryType } from '@/types/financial'

interface FormState {
  name:     string
  type:     FinancialEntryType
  is_active: boolean
}

const EMPTY_FORM: FormState = { name: '', type: 'expense', is_active: true }

export default function CategoriesPage() {
  const { data: categories = [], isLoading }    = useFinancialCategories()
  const { mutate: createCategory, isPending: creating } = useCreateCategory()
  const { mutate: updateCategory, isPending: updating } = useUpdateCategory()
  const { mutate: deleteCategory, isPending: deleting } = useDeleteCategory()

  const [showDialog,   setShowDialog]   = useState(false)
  const [editTarget,   setEditTarget]   = useState<FinancialCategory | null>(null)
  const [form,         setForm]         = useState<FormState>(EMPTY_FORM)
  const [deleteTarget, setDeleteTarget] = useState<FinancialCategory | null>(null)
  const [filterType,   setFilterType]   = useState<FinancialEntryType | ''>('')

  function openCreate() {
    setEditTarget(null)
    setForm(EMPTY_FORM)
    setShowDialog(true)
  }

  function openEdit(cat: FinancialCategory) {
    setEditTarget(cat)
    setForm({ name: cat.name, type: cat.type, is_active: cat.is_active })
    setShowDialog(true)
  }

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    const payload = { name: form.name, type: form.type, is_active: form.is_active }

    if (editTarget) {
      updateCategory({ uuid: editTarget.uuid, data: payload }, {
        onSuccess: () => { toast.success('Categoria atualizada.'); setShowDialog(false) },
        onError:   (err) => toast.error(err instanceof Error ? err.message : 'Erro ao atualizar.'),
      })
    } else {
      createCategory(payload, {
        onSuccess: () => { toast.success('Categoria criada.'); setShowDialog(false) },
        onError:   (err) => toast.error(err instanceof Error ? err.message : 'Erro ao criar categoria.'),
      })
    }
  }

  function handleDelete() {
    if (!deleteTarget) return
    deleteCategory(deleteTarget.uuid, {
      onSuccess: () => { toast.success('Categoria excluída.'); setDeleteTarget(null) },
      onError:   (err) => toast.error(err instanceof Error ? err.message : 'Erro ao excluir.'),
    })
  }

  const filtered = filterType ? categories.filter((c) => c.type === filterType) : categories

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Categorias"
        description="Organize lançamentos financeiros por categoria."
        actions={
          <Button onClick={openCreate}>
            <Plus className="h-4 w-4 mr-1.5" />
            Nova categoria
          </Button>
        }
      />

      {/* Filtro de tipo */}
      <div className="flex gap-3">
        <select
          value={filterType}
          onChange={(e) => setFilterType(e.target.value as FinancialEntryType | '')}
          className="flex h-9 rounded-md border border-input bg-background px-3 py-1 text-sm"
        >
          <option value="">Todos os tipos</option>
          <option value="income">Receita</option>
          <option value="expense">Despesa</option>
        </select>
      </div>

      <AppCard>
        <div className="rounded-md border">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Nome</TableHead>
                <TableHead>Tipo</TableHead>
                <TableHead>Status</TableHead>
                <TableHead className="w-24">Ações</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {isLoading ? (
                Array.from({ length: 4 }).map((_, i) => (
                  <TableRow key={i}>
                    {Array.from({ length: 4 }).map((_, j) => (
                      <TableCell key={j}><Skeleton className="h-4 w-full" /></TableCell>
                    ))}
                  </TableRow>
                ))
              ) : filtered.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={4} className="text-center text-muted-foreground py-8">
                    Nenhuma categoria encontrada.
                  </TableCell>
                </TableRow>
              ) : filtered.map((cat) => (
                <TableRow key={cat.uuid}>
                  <TableCell className="font-medium">{cat.name}</TableCell>
                  <TableCell>
                    <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium border ${
                      cat.type === 'income' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-red-100 text-red-700 border-red-200'
                    }`}>
                      {cat.type === 'income' ? 'Receita' : 'Despesa'}
                    </span>
                  </TableCell>
                  <TableCell>
                    <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium border ${
                      cat.is_active ? 'bg-green-100 text-green-700 border-green-200' : 'bg-gray-100 text-gray-600 border-gray-200'
                    }`}>
                      {cat.is_active ? 'Ativa' : 'Inativa'}
                    </span>
                  </TableCell>
                  <TableCell>
                    <div className="flex gap-1">
                      <Button variant="ghost" size="icon" onClick={() => openEdit(cat)}>
                        <Pencil className="h-4 w-4" />
                      </Button>
                      <Button variant="ghost" size="icon" onClick={() => setDeleteTarget(cat)}>
                        <Trash2 className="h-4 w-4 text-destructive" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>
      </AppCard>

      {/* Dialog: criar / editar */}
      {showDialog && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
          <div className="w-full max-w-sm rounded-lg border bg-background p-6 shadow-lg">
            <h2 className="text-lg font-semibold mb-4">{editTarget ? 'Editar Categoria' : 'Nova Categoria'}</h2>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid gap-1.5">
                <Label htmlFor="cat_name">Nome <span className="text-red-500">*</span></Label>
                <Input id="cat_name" value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} placeholder="Ex: Aluguel, Fornecedores, Vendas..." required />
              </div>
              <div className="grid gap-1.5">
                <Label htmlFor="cat_type">Tipo</Label>
                <select
                  id="cat_type"
                  value={form.type}
                  onChange={(e) => setForm((f) => ({ ...f, type: e.target.value as FinancialEntryType }))}
                  className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                  disabled={!!editTarget}
                >
                  <option value="expense">Despesa</option>
                  <option value="income">Receita</option>
                </select>
                {editTarget && <p className="text-xs text-muted-foreground">O tipo não pode ser alterado após a criação.</p>}
              </div>
              <label className="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" checked={form.is_active} onChange={(e) => setForm((f) => ({ ...f, is_active: e.target.checked }))} className="h-4 w-4 rounded border-input" />
                <span className="text-sm">Categoria ativa</span>
              </label>
              <div className="flex gap-3 pt-2">
                <Button type="submit" disabled={creating || updating}>{(creating || updating) ? 'Salvando...' : 'Salvar'}</Button>
                <Button type="button" variant="outline" onClick={() => setShowDialog(false)}>Cancelar</Button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Dialog: confirmar exclusão */}
      {deleteTarget && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
          <div className="w-full max-w-sm rounded-lg border bg-background p-6 shadow-lg">
            <h2 className="text-lg font-semibold mb-2">Excluir categoria</h2>
            <p className="text-sm text-muted-foreground mb-4">
              Tem certeza que deseja excluir <strong>{deleteTarget.name}</strong>?
            </p>
            <div className="flex gap-3">
              <Button variant="destructive" disabled={deleting} onClick={handleDelete}>{deleting ? 'Excluindo...' : 'Excluir'}</Button>
              <Button variant="outline" onClick={() => setDeleteTarget(null)}>Cancelar</Button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
