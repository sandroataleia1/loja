import { useQuery } from '@tanstack/react-query'
import { productService } from '@/services/product.service'

export function useProducts(search?: string, status = 'active') {
  return useQuery({
    queryKey: ['products', search, status],
    queryFn:  () => productService.list({ search, status }),
    staleTime: 1000 * 60, // 1 min — produtos raramente mudam durante venda
  })
}

export function useProduct(uuid: string | null) {
  return useQuery({
    queryKey: ['product', uuid],
    queryFn:  () => productService.get(uuid!),
    enabled:  !!uuid,
  })
}
