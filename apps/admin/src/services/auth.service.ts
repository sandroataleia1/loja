import { apiClient, apiGet, apiPost } from '@/lib/api-client'
import type { AuthResponse, MeResponse } from '@store/shared-types'
import type { LoginRequest, RegisterRequest, ForgotPasswordRequest, ResetPasswordRequest } from '@store/contracts'

export const authService = {
  login(data: LoginRequest): Promise<AuthResponse> {
    return apiPost<AuthResponse, LoginRequest>('/auth/login', data)
  },

  register(data: RegisterRequest): Promise<AuthResponse> {
    return apiPost<AuthResponse, RegisterRequest>('/auth/register', data)
  },

  logout(): Promise<void> {
    return apiPost<void>('/auth/logout')
  },

  me(): Promise<MeResponse> {
    return apiGet<MeResponse>('/auth/me')
  },

  forgotPassword(data: ForgotPasswordRequest): Promise<{ message: string }> {
    return apiPost<{ message: string }>('/auth/forgot-password', data)
  },

  resetPassword(data: ResetPasswordRequest): Promise<{ message: string }> {
    return apiPost<{ message: string }>('/auth/reset-password', data)
  },

  resendVerification(): Promise<{ message: string }> {
    return apiPost<{ message: string }>('/auth/email/verification-notification')
  },
}
