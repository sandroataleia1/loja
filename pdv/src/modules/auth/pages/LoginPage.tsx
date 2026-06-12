'use client'

import { useState, useEffect, FormEvent } from 'react'
import { useNavigate } from 'react-router-dom'
import { ShoppingCart, Eye, EyeOff, Loader2, AlertCircle } from 'lucide-react'
import { useSessionStore } from '@/stores/sessionStore'
import { apiLogin }        from '@/services/auth.service'
import { cn }              from '@/lib/utils'

export function LoginPage() {
  const [email,    setEmail]    = useState('')
  const [password, setPassword] = useState('')
  const [showPwd,  setShowPwd]  = useState(false)
  const [loading,  setLoading]  = useState(false)
  const [error,    setError]    = useState<string | null>(null)
  const [now,      setNow]      = useState(new Date())

  const { login, isOpen } = useSessionStore()
  const navigate           = useNavigate()

  // Live clock
  useEffect(() => {
    const t = setInterval(() => setNow(new Date()), 1000)
    return () => clearInterval(t)
  }, [])

  async function handleSubmit(e: FormEvent) {
    e.preventDefault()
    if (!email || !password) { setError('Informe e-mail e senha.'); return }

    setLoading(true)
    setError(null)

    try {
      const result = await apiLogin(email.trim().toLowerCase(), password)
      login(result)
      navigate(isOpen ? '/' : '/caixa/abrir', { replace: true })
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erro ao autenticar.')
    } finally {
      setLoading(false)
    }
  }

  const timeStr = now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
  const dateStr = now.toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' })

  return (
    <div className="min-h-screen bg-background flex flex-col items-center justify-center p-4 auth-enter">
      {/* Brand */}
      <div className="flex flex-col items-center gap-2 mb-10">
        <div className="flex items-center justify-center w-14 h-14 rounded-2xl bg-primary/15 text-primary">
          <ShoppingCart className="w-7 h-7" />
        </div>
        <h1 className="text-2xl font-bold tracking-tight">PDV Fashion</h1>
        <div className="text-center text-muted-foreground">
          <p className="text-3xl font-light tabular-nums">{timeStr}</p>
          <p className="text-sm capitalize mt-0.5">{dateStr}</p>
        </div>
      </div>

      {/* Login card */}
      <div className="w-full max-w-sm bg-card border rounded-2xl p-6 space-y-5 shadow-xl">
        <div>
          <h2 className="text-base font-semibold">Acesso ao sistema</h2>
          <p className="text-sm text-muted-foreground mt-0.5">Entre com suas credenciais de operador</p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          {/* E-mail */}
          <div className="space-y-1.5">
            <label className="text-xs font-medium tracking-wide uppercase text-muted-foreground">
              E-mail
            </label>
            <input
              type="email"
              value={email}
              onChange={(e) => { setEmail(e.target.value); setError(null) }}
              placeholder="operador@loja.com"
              autoComplete="username"
              autoFocus
              className={cn(
                'w-full h-10 rounded-lg border bg-background px-3 text-sm outline-none transition-all',
                'placeholder:text-muted-foreground/50',
                'focus:border-primary focus:ring-2 focus:ring-primary/20',
                error ? 'border-destructive' : 'border-input',
              )}
            />
          </div>

          {/* Senha */}
          <div className="space-y-1.5">
            <label className="text-xs font-medium tracking-wide uppercase text-muted-foreground">
              Senha
            </label>
            <div className="relative">
              <input
                type={showPwd ? 'text' : 'password'}
                value={password}
                onChange={(e) => { setPassword(e.target.value); setError(null) }}
                placeholder="••••••••"
                autoComplete="current-password"
                className={cn(
                  'w-full h-10 rounded-lg border bg-background px-3 pr-10 text-sm outline-none transition-all',
                  'placeholder:text-muted-foreground/50',
                  'focus:border-primary focus:ring-2 focus:ring-primary/20',
                  error ? 'border-destructive' : 'border-input',
                )}
              />
              <button
                type="button"
                tabIndex={-1}
                onClick={() => setShowPwd((v) => !v)}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
              >
                {showPwd ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
              </button>
            </div>
          </div>

          {/* Mensagem de erro */}
          {error && (
            <div className="flex items-center gap-2 rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2.5 text-sm text-destructive">
              <AlertCircle className="w-4 h-4 shrink-0" />
              <span>{error}</span>
            </div>
          )}

          {/* Botão */}
          <button
            type="submit"
            disabled={loading}
            className={cn(
              'w-full h-11 rounded-lg bg-primary text-primary-foreground text-sm font-semibold',
              'transition-all active:scale-[0.98] select-none',
              'flex items-center justify-center gap-2',
              loading && 'opacity-70 cursor-not-allowed',
            )}
          >
            {loading ? (
              <>
                <Loader2 className="w-4 h-4 animate-spin" />
                Entrando...
              </>
            ) : (
              'Entrar'
            )}
          </button>
        </form>
      </div>

      <p className="mt-6 text-xs text-muted-foreground/40">
        PDV Fashion — v{import.meta.env.VITE_APP_VERSION ?? '0.1'}
      </p>
    </div>
  )
}
