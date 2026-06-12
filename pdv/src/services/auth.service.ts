import { API_URL } from '@/config'

export interface LoginResult {
  token:   string
  user:    { uuid: string; name: string; email: string }
  tenant:  { uuid: string } | null
  store:   { uuid: string } | null
  channel: { uuid: string } | null
}

export async function apiLogin(email: string, password: string): Promise<LoginResult> {
  let res: Response
  try {
    res = await fetch(`${API_URL}/auth/login`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body:    JSON.stringify({ email, password, device_name: 'PDV Desktop' }),
    })
  } catch {
    throw new Error('Não foi possível conectar ao servidor. Verifique a conexão.')
  }

  const json = await res.json().catch(() => ({}))

  if (!res.ok) {
    const msg = json?.message ?? json?.data?.message ?? 'Credenciais inválidas.'
    throw new Error(msg)
  }

  return json.data as LoginResult
}

export async function apiLogout(token: string): Promise<void> {
  try {
    await fetch(`${API_URL}/auth/logout`, {
      method:  'POST',
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
  } catch {
    // best-effort
  }
}
