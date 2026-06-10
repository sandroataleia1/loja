import { useMutation } from '@tanstack/react-query'
import { useRouter } from 'next/navigation'
import { toast } from 'sonner'
import { authService } from '@/services/auth.service'
import { useAuth } from './use-auth'
import { ROUTES } from '@/constants'
import type { RegisterRequest } from '@store/contracts'
import type { AxiosError } from 'axios'
import type { ApiResponse } from '@/types'

export function useRegisterMutation() {
  const { login } = useAuth()
  const router    = useRouter()

  return useMutation({
    mutationFn: (data: RegisterRequest) => authService.register(data),

    onSuccess: ({ token, user, tenant, store, channel }) => {
      login(token, user, tenant, store, channel)
      toast.success('Empresa criada com sucesso! Verifique seu e-mail.')
      router.push(ROUTES.HOME)
    },

    onError: (error: AxiosError<ApiResponse>) => {
      const data = error.response?.data as { message?: string; errors?: Record<string, string[]> }
      const message = data?.message ?? 'Erro ao criar conta.'
      toast.error(message)
    },
  })
}
