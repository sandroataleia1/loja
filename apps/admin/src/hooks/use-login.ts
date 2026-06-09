import { useMutation } from '@tanstack/react-query'
import { useRouter } from 'next/navigation'
import { toast } from 'sonner'
import { authService } from '@/services/auth.service'
import { useAuth } from './use-auth'
import { ROUTES } from '@/constants'
import type { LoginRequest } from '@store/contracts'
import type { AxiosError } from 'axios'
import type { ApiResponse } from '@/types'

export function useLoginMutation() {
  const { login } = useAuth()
  const router    = useRouter()

  return useMutation({
    mutationFn: (data: LoginRequest) => authService.login(data),

    onSuccess: ({ token, user, tenant, store, channel }) => {
      login(token, user, tenant, store, channel)
      router.push(ROUTES.HOME)
    },

    onError: (error: AxiosError<ApiResponse>) => {
      const message =
        (error.response?.data as { message?: string })?.message ?? 'Erro ao fazer login.'
      toast.error(message)
    },
  })
}
