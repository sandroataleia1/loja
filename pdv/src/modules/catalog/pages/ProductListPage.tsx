import { useState } from 'react'
import { Plus, Search } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input }  from '@/components/ui/input'
import { Badge }  from '@/components/ui/badge'
import { useProducts } from '../hooks/useProducts'
import { formatCurrency } from '@/utils/currency'

export function ProductListPage() {
  const [search, setSearch] = useState('')
  const { data: products = [], isLoading } = useProducts(search || undefined, 'active')

  return (
    <div className="p-6 space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold">Produtos</h1>
          <p className="text-sm text-muted-foreground">{products.length} produtos ativos</p>
        </div>
        <Button size="sm" className="gap-2">
          <Plus className="w-4 h-4" />
          Novo Produto
        </Button>
      </div>

      {/* Search */}
      <div className="relative max-w-sm">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
        <Input
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          placeholder="Buscar produtos..."
          className="pl-9"
        />
      </div>

      {/* Table */}
      {isLoading ? (
        <p className="text-sm text-muted-foreground">Carregando...</p>
      ) : products.length === 0 ? (
        <p className="text-sm text-muted-foreground">
          {search ? `Nenhum produto para "${search}"` : 'Nenhum produto cadastrado.'}
        </p>
      ) : (
        <div className="rounded-lg border overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-muted/50">
              <tr className="border-b">
                <th className="text-left px-4 py-3 font-medium">Nome</th>
                <th className="text-left px-4 py-3 font-medium">SKU</th>
                <th className="text-left px-4 py-3 font-medium">Categoria</th>
                <th className="text-right px-4 py-3 font-medium">Preço</th>
                <th className="text-right px-4 py-3 font-medium">Estoque</th>
                <th className="text-center px-4 py-3 font-medium">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {products.map((product) => (
                <tr key={product.uuid} className="hover:bg-muted/30 transition-colors cursor-pointer">
                  <td className="px-4 py-3 font-medium">{product.name}</td>
                  <td className="px-4 py-3 text-muted-foreground font-mono text-xs">
                    {product.sku}
                  </td>
                  <td className="px-4 py-3 text-muted-foreground">
                    {product.category_name ?? '—'}
                  </td>
                  <td className="px-4 py-3 text-right tabular-nums font-medium">
                    {formatCurrency(product.price)}
                  </td>
                  <td className="px-4 py-3 text-right tabular-nums">
                    <span className={product.stock <= 0 ? 'text-red-500' : ''}>
                      {product.stock}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-center">
                    <Badge variant={product.status === 'active' ? 'success' : 'secondary'}>
                      {product.status === 'active' ? 'Ativo' : 'Inativo'}
                    </Badge>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
