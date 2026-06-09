import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useRouter } from 'next/navigation'
import { useAuth } from './use-auth'
import { ROUTES } from '@/constants'

export function useLogoutMutation() {
  const { logout }    = useAuth()
  const router        = useRouter()
  const queryClient   = useQueryClient()

  return useMutation({
    mutationFn: logout,

    onSuccess: () => {
      queryClient.clear()
      router.push(ROUTES.LOGIN)
    },
  })
}
