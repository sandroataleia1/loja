import { Navigate, Outlet } from 'react-router-dom'
import { useSessionStore } from '@/stores/sessionStore'

export function AuthGuard() {
  const isLoggedIn = useSessionStore((s) => s.isLoggedIn)
  return isLoggedIn ? <Outlet /> : <Navigate to="/login" replace />
}

export function RegisterGuard() {
  const isOpen = useSessionStore((s) => s.isOpen)
  return isOpen ? <Outlet /> : <Navigate to="/caixa/abrir" replace />
}
