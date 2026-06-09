import axios from 'axios'
import type { ApiResponse } from '@store/contracts'

export const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000/api/v1',
  headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
})

api.interceptors.request.use((config) => {
  if (typeof window !== 'undefined') {
    const token = localStorage.getItem('token')
    if (token) config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

api.interceptors.response.use(
  (res) => res,
  (err) => {
    if (err.response?.status === 401 && typeof window !== 'undefined') {
      localStorage.removeItem('token')
      window.location.href = '/login'
    }
    return Promise.reject(err)
  },
)

export async function apiFetch<T>(path: string): Promise<T> {
  const res = await api.get<ApiResponse<T>>(path)
  const body = res.data
  if (!body.success) throw new Error((body as { message: string }).message)
  return (body as { data: T }).data
}
