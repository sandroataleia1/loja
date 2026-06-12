'use client'

import { useState, useEffect, useCallback, FormEvent } from 'react'
import { useNavigate } from 'react-router-dom'
import { ShoppingCart, Eye, EyeOff, Loader2, AlertCircle, WifiOff, KeyRound, Check } from 'lucide-react'
import { useSessionStore } from '@/stores/sessionStore'
import { apiLogin }        from '@/services/auth.service'
import { cn }              from '@/lib/utils'

// ─── Tipos de tela ────────────────────────────────────────────────────────────
type Screen = 'online' | 'pin-login' | 'no-connection' | 'set-pin'

export function LoginPage() {
  const navigate = useNavigate()

  const {
    isLoggedIn, isOpen,
    login, hasOfflinePin, userName: cachedName,
    setOfflinePin, verifyOfflinePin,
  } = useSessionStore()

  // ── Auto-resume: se já autenticado, pula login ────────────────────────────
  useEffect(() => {
    if (isLoggedIn) {
      navigate(isOpen ? '/' : '/caixa/abrir', { replace: true })
    }
  }, [isLoggedIn, isOpen, navigate])

  // ── Detecta conectividade ─────────────────────────────────────────────────
  const [isOnline, setIsOnline] = useState(navigator.onLine)
  useEffect(() => {
    const onOnline  = () => setIsOnline(true)
    const onOffline = () => setIsOnline(false)
    window.addEventListener('online',  onOnline)
    window.addEventListener('offline', onOffline)
    return () => {
      window.removeEventListener('online',  onOnline)
      window.removeEventListener('offline', onOffline)
    }
  }, [])

  // ── Decide tela inicial ───────────────────────────────────────────────────
  const [screen, setScreen] = useState<Screen>(() => {
    if (navigator.onLine)  return 'online'
    if (hasOfflinePin)     return 'pin-login'
    return 'no-connection'
  })

  // Atualiza tela ao mudar conectividade
  useEffect(() => {
    if (screen === 'set-pin') return
    if (isOnline) {
      setScreen('online')
    } else {
      setScreen(hasOfflinePin ? 'pin-login' : 'no-connection')
    }
  }, [isOnline, hasOfflinePin, screen])

  // ── Relógio ───────────────────────────────────────────────────────────────
  const [now, setNow] = useState(new Date())
  useEffect(() => {
    const t = setInterval(() => setNow(new Date()), 1000)
    return () => clearInterval(t)
  }, [])
  const timeStr = now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
  const dateStr = now.toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' })

  const goToDashboard = useCallback(() => {
    navigate(isOpen ? '/' : '/caixa/abrir', { replace: true })
  }, [isOpen, navigate])

  return (
    <div className="min-h-screen bg-background flex flex-col items-center justify-center p-4 auth-enter">
      {/* Brand + relógio */}
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

      {/* Tela principal */}
      {screen === 'online'         && <OnlineForm      goToDashboard={goToDashboard} login={login} setScreen={setScreen} />}
      {screen === 'pin-login'      && <PinLoginScreen  goToDashboard={goToDashboard} cachedName={cachedName} verifyOfflinePin={verifyOfflinePin} setScreen={setScreen} isOnline={isOnline} />}
      {screen === 'no-connection'  && <NoConnectionScreen setScreen={setScreen} isOnline={isOnline} />}
      {screen === 'set-pin'        && <SetPinScreen    goToDashboard={goToDashboard} setOfflinePin={setOfflinePin} />}

      <p className="mt-6 text-xs text-muted-foreground/40">
        PDV Fashion — v{import.meta.env.VITE_APP_VERSION ?? '0.1'}
      </p>
    </div>
  )
}

// ─── Tela 1: Login online (email + senha) ─────────────────────────────────────
function OnlineForm({
  goToDashboard,
  login,
  setScreen,
}: {
  goToDashboard:  () => void
  login:          ReturnType<typeof useSessionStore>['login']
  setScreen:      (s: Screen) => void
}) {
  const { hasOfflinePin } = useSessionStore()
  const [email,    setEmail]    = useState('')
  const [password, setPassword] = useState('')
  const [showPwd,  setShowPwd]  = useState(false)
  const [loading,  setLoading]  = useState(false)
  const [error,    setError]    = useState<string | null>(null)

  async function handleSubmit(e: FormEvent) {
    e.preventDefault()
    if (!email || !password) { setError('Informe e-mail e senha.'); return }
    setLoading(true)
    setError(null)
    try {
      const result = await apiLogin(email.trim().toLowerCase(), password)
      login(result)
      if (!hasOfflinePin) {
        setScreen('set-pin')
      } else {
        goToDashboard()
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erro ao autenticar.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="w-full max-w-sm bg-card border rounded-2xl p-6 space-y-5 shadow-xl">
      <div>
        <h2 className="text-base font-semibold">Acesso ao sistema</h2>
        <p className="text-sm text-muted-foreground mt-0.5">Entre com suas credenciais de operador</p>
      </div>

      <form onSubmit={handleSubmit} className="space-y-4">
        <div className="space-y-1.5">
          <label className="text-xs font-medium tracking-wide uppercase text-muted-foreground">E-mail</label>
          <input
            type="email"
            value={email}
            onChange={(e) => { setEmail(e.target.value); setError(null) }}
            placeholder="operador@loja.com"
            autoComplete="username"
            autoFocus
            className={inputCls(!!error)}
          />
        </div>

        <div className="space-y-1.5">
          <label className="text-xs font-medium tracking-wide uppercase text-muted-foreground">Senha</label>
          <div className="relative">
            <input
              type={showPwd ? 'text' : 'password'}
              value={password}
              onChange={(e) => { setPassword(e.target.value); setError(null) }}
              placeholder="••••••••"
              autoComplete="current-password"
              className={cn(inputCls(!!error), 'pr-10')}
            />
            <button type="button" tabIndex={-1} onClick={() => setShowPwd(v => !v)}
              className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors">
              {showPwd ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
            </button>
          </div>
        </div>

        {error && <ErrorBanner message={error} />}

        <button type="submit" disabled={loading} className={submitCls(loading)}>
          {loading ? <><Loader2 className="w-4 h-4 animate-spin" />Entrando...</> : 'Entrar'}
        </button>
      </form>
    </div>
  )
}

// ─── Tela 2: PIN offline ──────────────────────────────────────────────────────
function PinLoginScreen({
  goToDashboard,
  cachedName,
  verifyOfflinePin,
  setScreen,
  isOnline,
}: {
  goToDashboard:    () => void
  cachedName:       string
  verifyOfflinePin: (pin: string) => Promise<boolean>
  setScreen:        (s: Screen) => void
  isOnline:         boolean
}) {
  const { isLoggedIn } = useSessionStore()
  const [pin,   setPin]   = useState('')
  const [shake, setShake] = useState(false)

  const validate = useCallback(async (p: string) => {
    const ok = await verifyOfflinePin(p)
    if (ok) {
      goToDashboard()
    } else {
      setShake(true)
      setTimeout(() => { setPin(''); setShake(false) }, 600)
    }
  }, [verifyOfflinePin, goToDashboard])

  useEffect(() => {
    if (pin.length === 4) void validate(pin)
  }, [pin, validate])

  // Suporte teclado
  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      if (/^\d$/.test(e.key)) setPin(p => p.length < 4 ? p + e.key : p)
      if (e.key === 'Backspace') setPin(p => p.slice(0, -1))
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [])

  const addDigit = (d: string) => setPin(p => p.length < 4 ? p + d : p)
  const delDigit = () => setPin(p => p.slice(0, -1))

  return (
    <div className="w-full max-w-xs bg-card border rounded-2xl p-6 space-y-5 shadow-xl">
      <div className="flex items-center gap-2 text-yellow-500 text-sm">
        <WifiOff className="w-4 h-4 shrink-0" />
        <span>Modo offline — acesso por PIN</span>
      </div>

      {cachedName && (
        <p className="text-sm text-center font-medium">{cachedName}</p>
      )}

      {/* PIN dots */}
      <div className="flex flex-col items-center gap-3">
        <p className="text-xs text-muted-foreground">Digite seu PIN de 4 dígitos</p>
        <div className={cn('flex gap-3', shake && 'animate-[shake_0.4s_ease-in-out]')}>
          {[0, 1, 2, 3].map((i) => (
            <span key={i} className={cn('pin-dot',
              shake ? 'pin-dot-error' : pin.length > i ? 'pin-dot-filled' : 'pin-dot-empty'
            )} />
          ))}
        </div>
      </div>

      {/* Teclado */}
      <div className="grid grid-cols-3 gap-2">
        {[7,8,9,4,5,6,1,2,3].map(d => (
          <PinBtn key={d} label={String(d)} onClick={() => addDigit(String(d))} />
        ))}
        <PinBtn label="⌫" onClick={delDigit} variant="ghost" />
        <PinBtn label="0" onClick={() => addDigit('0')} />
        <div />
      </div>

      {isOnline && (
        <button onClick={() => setScreen('online')}
          className="w-full text-xs text-center text-muted-foreground hover:text-primary transition-colors">
          Entrar com e-mail e senha →
        </button>
      )}
    </div>
  )
}

// ─── Tela 3: Sem conexão e sem PIN ────────────────────────────────────────────
function NoConnectionScreen({ setScreen, isOnline }: { setScreen: (s: Screen) => void; isOnline: boolean }) {
  useEffect(() => {
    if (isOnline) setScreen('online')
  }, [isOnline, setScreen])

  return (
    <div className="w-full max-w-sm bg-card border rounded-2xl p-6 space-y-4 shadow-xl text-center">
      <div className="flex justify-center">
        <div className="flex items-center justify-center w-12 h-12 rounded-full bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30">
          <WifiOff className="w-6 h-6" />
        </div>
      </div>
      <div>
        <h2 className="font-semibold">Sem conexão com o servidor</h2>
        <p className="text-sm text-muted-foreground mt-1">
          O PIN offline ainda não foi configurado. Conecte-se à internet para fazer o primeiro acesso e definir seu PIN.
        </p>
      </div>
      <p className="text-xs text-muted-foreground/60">Aguardando conexão...</p>
    </div>
  )
}

// ─── Tela 4: Definir PIN offline (aparece após 1º login online) ───────────────
function SetPinScreen({
  goToDashboard,
  setOfflinePin,
}: {
  goToDashboard: () => void
  setOfflinePin: (pin: string) => Promise<void>
}) {
  const [pin,     setPin]     = useState('')
  const [confirm, setConfirm] = useState('')
  const [step,    setStep]    = useState<'set' | 'confirm' | 'done'>('set')
  const [error,   setError]   = useState<string | null>(null)

  // Suporte teclado
  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      if (step === 'done') return
      if (/^\d$/.test(e.key)) {
        if (step === 'set')     setPin(p => p.length < 4 ? p + e.key : p)
        if (step === 'confirm') setConfirm(p => p.length < 4 ? p + e.key : p)
      }
      if (e.key === 'Backspace') {
        if (step === 'set')     setPin(p => p.slice(0, -1))
        if (step === 'confirm') setConfirm(p => p.slice(0, -1))
      }
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [step])

  // Avança ao 4º dígito
  useEffect(() => {
    if (step === 'set' && pin.length === 4) {
      setStep('confirm'); setError(null)
    }
  }, [pin, step])

  useEffect(() => {
    if (step === 'confirm' && confirm.length === 4) {
      if (confirm !== pin) {
        setError('PINs diferentes. Tente novamente.')
        setConfirm(''); setPin(''); setStep('set')
      } else {
        setOfflinePin(pin).then(() => setStep('done'))
      }
    }
  }, [confirm, pin, step, setOfflinePin])

  const current = step === 'confirm' ? confirm : pin
  const addDigit = (d: string) => {
    if (step === 'set')     setPin(p => p.length < 4 ? p + d : p)
    if (step === 'confirm') setConfirm(p => p.length < 4 ? p + d : p)
  }
  const delDigit = () => {
    if (step === 'set')     setPin(p => p.slice(0, -1))
    if (step === 'confirm') setConfirm(p => p.slice(0, -1))
  }

  return (
    <div className="w-full max-w-xs bg-card border rounded-2xl p-6 space-y-5 shadow-xl">
      <div className="flex items-center gap-2">
        <KeyRound className="w-4 h-4 text-primary" />
        <h2 className="font-semibold text-sm">Configurar PIN offline</h2>
      </div>

      {step !== 'done' ? (
        <>
          <p className="text-xs text-muted-foreground">
            {step === 'set'
              ? 'Escolha um PIN de 4 dígitos para acessar o sistema sem internet.'
              : 'Digite o PIN novamente para confirmar.'}
          </p>

          {error && <ErrorBanner message={error} />}

          {/* Dots */}
          <div className="flex justify-center gap-3">
            {[0,1,2,3].map(i => (
              <span key={i} className={cn('pin-dot', current.length > i ? 'pin-dot-filled' : 'pin-dot-empty')} />
            ))}
          </div>

          {/* Teclado */}
          <div className="grid grid-cols-3 gap-2">
            {[7,8,9,4,5,6,1,2,3].map(d => (
              <PinBtn key={d} label={String(d)} onClick={() => addDigit(String(d))} />
            ))}
            <PinBtn label="⌫" onClick={delDigit} variant="ghost" />
            <PinBtn label="0" onClick={() => addDigit('0')} />
            <div />
          </div>

          <button onClick={goToDashboard}
            className="w-full text-xs text-center text-muted-foreground hover:text-primary transition-colors">
            Pular por agora →
          </button>
        </>
      ) : (
        <div className="flex flex-col items-center gap-3 py-2">
          <div className="flex items-center justify-center w-12 h-12 rounded-full bg-green-100 text-green-600 dark:bg-green-900/30">
            <Check className="w-6 h-6" />
          </div>
          <p className="text-sm text-center font-medium">PIN configurado com sucesso!</p>
          <p className="text-xs text-center text-muted-foreground">Agora você pode acessar o sistema sem internet.</p>
          <button onClick={goToDashboard} className={submitCls(false)}>
            Entrar no sistema
          </button>
        </div>
      )}
    </div>
  )
}

// ─── Componentes auxiliares ───────────────────────────────────────────────────
function PinBtn({ label, onClick, variant = 'default' }: {
  label: string; onClick: () => void; variant?: 'default' | 'ghost' | 'primary'
}) {
  return (
    <button onClick={onClick} className={cn(
      'h-12 rounded-lg text-lg font-semibold transition-all active:scale-95 select-none',
      variant === 'default' && 'bg-muted hover:bg-accent text-foreground',
      variant === 'ghost'   && 'bg-transparent hover:bg-accent text-muted-foreground',
      variant === 'primary' && 'bg-primary hover:bg-primary/90 text-primary-foreground',
    )}>
      {label}
    </button>
  )
}

function ErrorBanner({ message }: { message: string }) {
  return (
    <div className="flex items-center gap-2 rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2.5 text-sm text-destructive">
      <AlertCircle className="w-4 h-4 shrink-0" />
      <span>{message}</span>
    </div>
  )
}

function inputCls(hasError: boolean) {
  return cn(
    'w-full h-10 rounded-lg border bg-background px-3 text-sm outline-none transition-all',
    'placeholder:text-muted-foreground/50',
    'focus:border-primary focus:ring-2 focus:ring-primary/20',
    hasError ? 'border-destructive' : 'border-input',
  )
}

function submitCls(loading: boolean) {
  return cn(
    'w-full h-11 rounded-lg bg-primary text-primary-foreground text-sm font-semibold',
    'transition-all active:scale-[0.98] select-none flex items-center justify-center gap-2',
    loading && 'opacity-70 cursor-not-allowed',
  )
}
