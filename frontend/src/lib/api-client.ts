'use client'

import axios, { type AxiosError, type AxiosResponse, type InternalAxiosRequestConfig } from 'axios'
import Cookies from 'js-cookie'
import { env } from '@/config/env'
import { TOKEN_KEY, COOKIE_KEY, ROUTES } from '@/constants'
import type { ApiResponse } from '@/types'

// ── Token helpers ──────────────────────────────────────────────────────────

export function getToken(): string | null {
  if (typeof window === 'undefined') return null
  return localStorage.getItem(TOKEN_KEY)
}

export function setToken(token: string): void {
  localStorage.setItem(TOKEN_KEY, token)
  Cookies.set(COOKIE_KEY, '1', { expires: 7, sameSite: 'lax' })
}

export function clearToken(): void {
  localStorage.removeItem(TOKEN_KEY)
  Cookies.remove(COOKIE_KEY)
}

// ── Axios instance ─────────────────────────────────────────────────────────

export const apiClient = axios.create({
  baseURL:         env.apiUrl,
  timeout:         30_000,
  headers: {
    Accept:         'application/json',
    'Content-Type': 'application/json',
  },
})

// ── Request interceptor: attach Bearer token ───────────────────────────────

apiClient.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  const token = getToken()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// ── Response interceptor: normalize errors ─────────────────────────────────

apiClient.interceptors.response.use(
  (response: AxiosResponse) => response,
  (error: AxiosError<ApiResponse>) => {
    if (error.response?.status === 401 && typeof window !== 'undefined') {
      clearToken()
      // Avoid redirect loops on the login page itself
      if (!window.location.pathname.startsWith('/auth')) {
        window.location.href = ROUTES.LOGIN
      }
    }
    return Promise.reject(error)
  }
)

// ── Typed fetch helpers ────────────────────────────────────────────────────

export async function apiGet<T>(path: string): Promise<T> {
  const res = await apiClient.get<ApiResponse<T>>(path)
  const body = res.data
  if (!body.success) throw new Error((body as { message: string }).message)
  return (body as { data: T }).data
}

export async function apiPost<T, D = unknown>(path: string, data?: D): Promise<T> {
  const res = await apiClient.post<ApiResponse<T>>(path, data)
  const body = res.data
  if (!body.success) throw new Error((body as { message: string }).message)
  return (body as { data: T }).data
}

export async function apiPut<T, D = unknown>(path: string, data?: D): Promise<T> {
  const res = await apiClient.put<ApiResponse<T>>(path, data)
  const body = res.data
  if (!body.success) throw new Error((body as { message: string }).message)
  return (body as { data: T }).data
}

export async function apiDelete<T = void>(path: string): Promise<T> {
  const res = await apiClient.delete<ApiResponse<T>>(path)
  const body = res.data
  if (!body.success) throw new Error((body as { message: string }).message)
  return (body as { data: T }).data
}

// Keep the legacy export for backward compatibility
export { apiClient as api }
