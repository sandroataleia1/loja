import { useQuery } from '@tanstack/react-query'
import { authService } from '@/services/auth.service'
import { getToken } from '@/lib/api-client'
import { QUERY_KEYS } from '@/constants'

export function useMeQuery() {
  return useQuery({
    queryKey:  QUERY_KEYS.ME,
    queryFn:   () => authService.me(),
    enabled:   !!getToken(),
    staleTime: 5 * 60 * 1000, // 5 minutes
  })
}
