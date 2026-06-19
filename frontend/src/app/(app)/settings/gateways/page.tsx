'use client'

import { useState, useEffect } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Save, Loader2, CheckCircle2, AlertCircle, Eye, EyeOff, Info } from 'lucide-react'
import Link from 'next/link'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard } from '@/components/shared/app-card'
import { ROUTES } from '@/constants/routes'
import { pixService, type GatewayConfig, type UpdateGatewayConfigParams } from '@/services/pix.service'

const PIX_KEY_TYPE_LABELS: Record<string, string> = {
  cpf:    'CPF',
  cnpj:   'CNPJ',
  email:  'E-mail',
  phone:  'Telefone',
  random: 'Chave Aleatória',
}

export default function GatewaySettingsPage() {
  const qc = useQueryClient()

  const { data: config, isLoading } = useQuery<GatewayConfig | null>({
    queryKey: ['pix', 'gateway-config'],
    queryFn:  pixService.getGatewayConfig,
  })

  const [apiKey,      setApiKey]      = useState('')
  const [environment, setEnvironment] = useState<'sandbox' | 'production'>('sandbox')
  const [isActive,    setIsActive]    = useState(false)
  const [pixKey,      setPixKey]      = useState('')
  const [pixKeyType,  setPixKeyType]  = useState('cnpj')
  const [showKey,     setShowKey]     = useState(false)
  const [saved,       setSaved]       = useState(false)

  useEffect(() => {
    if (!config) return
    setEnvironment(config.environment ?? 'sandbox')
    setIsActive(config.is_active ?? false)
    setPixKey(config.pix_key ?? '')
    setPixKeyType(config.pix_key_type ?? 'cnpj')
  }, [config])

  const save = useMutation<GatewayConfig, Error, UpdateGatewayConfigParams>({
    mutationFn: pixService.updateGatewayConfig,
    onSuccess: (data) => {
      qc.setQueryData(['pix', 'gateway-config'], data)
      qc.invalidateQueries({ queryKey: ['pix', 'public-info'] })
      setSaved(true)
      setTimeout(() => setSaved(false), 3000)
    },
  })

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    const params: UpdateGatewayConfigParams = {
      gateway:     'asaas',
      environment,
      is_active:   isActive,
      pix_key:     pixKey || null,
      pix_key_type: pixKey ? pixKeyType : null,
    }
    if (apiKey.trim()) {
      params.api_key = apiKey.trim()
    }
    save.mutate(params)
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3 mb-2">
        <Link
          href={ROUTES.SETTINGS}
          className="text-muted-foreground hover:text-foreground transition-colors text-sm"
        >
          ← Configurações
        </Link>
      </div>
      <AppPageHeader
        title="Gateway PIX"
        description="Configure sua conta Asaas para geração de QR Codes e confirmação automática de pagamentos PIX."
      />

      {isLoading ? (
        <div className="flex items-center justify-center py-16 text-muted-foreground gap-2">
          <Loader2 className="w-5 h-5 animate-spin" />
          Carregando configurações…
        </div>
      ) : (
        <form onSubmit={handleSubmit} className="space-y-6 max-w-2xl">

          {/* Status banner */}
          {config?.has_api_key && (
            <div className={`flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium border ${
              config.is_active
                ? 'bg-green-50 dark:bg-green-950/30 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800'
                : 'bg-muted/50 text-muted-foreground border-border'
            }`}>
              {config.is_active
                ? <CheckCircle2 className="w-4 h-4 shrink-0" />
                : <AlertCircle className="w-4 h-4 shrink-0" />}
              {config.is_active
                ? `Gateway Asaas ativo (${config.environment})`
                : 'Gateway configurado mas desativado'}
            </div>
          )}

          {/* Credenciais Asaas */}
          <AppCard
            title="Credenciais Asaas"
            description="Obtenha a API Key em sua conta Asaas: Menu → Integrações → Chave de API."
          >
            <div className="space-y-4">
              {/* Ambiente */}
              <div>
                <label className="text-sm font-medium">Ambiente</label>
                <div className="flex gap-3 mt-2">
                  {(['sandbox', 'production'] as const).map((env) => (
                    <label
                      key={env}
                      className={`flex items-center gap-2 px-4 py-2.5 rounded-xl border cursor-pointer transition-colors ${
                        environment === env
                          ? 'border-primary bg-primary/5 text-primary'
                          : 'border-border hover:bg-accent'
                      }`}
                    >
                      <input
                        type="radio"
                        name="environment"
                        value={env}
                        checked={environment === env}
                        onChange={() => setEnvironment(env)}
                        className="sr-only"
                      />
                      <span className="text-sm font-medium capitalize">{env}</span>
                    </label>
                  ))}
                </div>
                {environment === 'sandbox' && (
                  <p className="text-xs text-muted-foreground mt-1.5 flex items-center gap-1">
                    <Info className="w-3 h-3" />
                    Sandbox: use a API key do ambiente de testes Asaas. Pagamentos não são reais.
                  </p>
                )}
              </div>

              {/* API Key */}
              <div>
                <label className="text-sm font-medium">API Key</label>
                {config?.has_api_key && (
                  <p className="text-xs text-muted-foreground mb-1.5">
                    Chave atual: <span className="font-mono">{config.api_key_masked}</span> — deixe em branco para manter.
                  </p>
                )}
                <div className="relative">
                  <input
                    type={showKey ? 'text' : 'password'}
                    value={apiKey}
                    onChange={(e) => setApiKey(e.target.value)}
                    placeholder={config?.has_api_key ? '••••••••• (não alterado)' : 'Cole aqui a API Key do Asaas'}
                    autoComplete="off"
                    className="w-full h-10 rounded-xl border bg-background px-3 pr-10 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                  />
                  <button
                    type="button"
                    onClick={() => setShowKey((v) => !v)}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                  >
                    {showKey ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                  </button>
                </div>
              </div>

              {/* Ativar gateway */}
              <label className="flex items-center gap-3 cursor-pointer">
                <input
                  type="checkbox"
                  checked={isActive}
                  onChange={(e) => setIsActive(e.target.checked)}
                  className="w-4 h-4 rounded accent-primary"
                />
                <div>
                  <p className="text-sm font-medium">Ativar gateway PIX</p>
                  <p className="text-xs text-muted-foreground">O PDV mostrará o modo QR Code dinâmico quando ativado.</p>
                </div>
              </label>
            </div>
          </AppCard>

          {/* Chave PIX estática */}
          <AppCard
            title="Chave PIX da Loja"
            description="Exibida no PDV quando o operador escolhe o modo Chave PIX. Independente do gateway Asaas."
          >
            <div className="space-y-4">
              <div>
                <label className="text-sm font-medium">Tipo de chave</label>
                <select
                  value={pixKeyType}
                  onChange={(e) => setPixKeyType(e.target.value)}
                  className="mt-1 w-full h-10 rounded-xl border bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                >
                  {Object.entries(PIX_KEY_TYPE_LABELS).map(([value, label]) => (
                    <option key={value} value={value}>{label}</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="text-sm font-medium">Chave PIX</label>
                <input
                  type="text"
                  value={pixKey}
                  onChange={(e) => setPixKey(e.target.value)}
                  placeholder={
                    pixKeyType === 'cnpj'   ? '00.000.000/0000-00' :
                    pixKeyType === 'cpf'    ? '000.000.000-00' :
                    pixKeyType === 'email'  ? 'contato@minhaloja.com' :
                    pixKeyType === 'phone'  ? '+55 11 99999-9999' :
                    'Chave aleatória (UUID)'
                  }
                  className="mt-1 w-full h-10 rounded-xl border bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                />
              </div>
            </div>
          </AppCard>

          {/* Webhook info */}
          {config && (
            <AppCard title="Webhook Asaas" description="Configure no painel Asaas para confirmação automática de pagamentos.">
              <div className="bg-muted/40 rounded-xl p-3 space-y-1.5">
                <p className="text-xs text-muted-foreground font-medium uppercase tracking-wide">URL do Webhook</p>
                <p className="text-sm font-mono break-all">
                  {`${typeof window !== 'undefined' ? window.location.origin : ''}/api/v1/webhooks/pix/{seu-tenant-uuid}`}
                </p>
                <p className="text-xs text-muted-foreground">
                  Substitua <code className="bg-muted px-1 rounded">{'{seu-tenant-uuid}'}</code> pelo UUID do seu tenant.
                  Configure o mesmo em <strong>Asaas → Integrações → Webhooks</strong>.
                </p>
              </div>
            </AppCard>
          )}

          {/* Actions */}
          <div className="flex items-center gap-3">
            <button
              type="submit"
              disabled={save.isPending}
              className="h-11 px-6 rounded-xl bg-primary text-primary-foreground font-medium hover:bg-primary/90 disabled:opacity-50 flex items-center gap-2 transition-colors"
            >
              {save.isPending
                ? <Loader2 className="w-4 h-4 animate-spin" />
                : saved
                  ? <CheckCircle2 className="w-4 h-4 text-green-400" />
                  : <Save className="w-4 h-4" />}
              {saved ? 'Salvo!' : 'Salvar configurações'}
            </button>

            {save.isError && (
              <p className="text-sm text-destructive flex items-center gap-1.5">
                <AlertCircle className="w-4 h-4" />
                {save.error.message}
              </p>
            )}
          </div>
        </form>
      )}
    </div>
  )
}
