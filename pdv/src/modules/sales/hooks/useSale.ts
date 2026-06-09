import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { saleService } from '@/services/sale.service'
import type { CreateSaleInput } from '@/types/sale'
import { toast } from 'sonner'
import { useCartStore } from '@/stores/cartStore'
import { syncEngine } from '@/sync'

export function useSales(limit = 50) {
  return useQuery({
    queryKey: ['sales', limit],
    queryFn:  () => saleService.list({ limit }),
  })
}

export function useSaleCreate() {
  const queryClient = useQueryClient()
  const clearCart   = useCartStore((s) => s.clear)

  return useMutation({
    mutationFn: (input: CreateSaleInput) => saleService.create(input),
    onSuccess: () => {
      clearCart()
      void queryClient.invalidateQueries({ queryKey: ['sales'] })
      void queryClient.invalidateQueries({ queryKey: ['products'] })
      void syncEngine.triggerSync()
      toast.success('Venda finalizada com sucesso!')
    },
    onError: (err) => {
      toast.error(`Erro ao finalizar venda: ${String(err)}`)
    },
  })
}
