'use client'

import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { toast } from 'sonner'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Skeleton } from '@/components/ui/skeleton'
import { useFiscalSettings, useUpdateFiscalSettings } from '@/features/sales/hooks'

const schema = z.object({
  policy_mode:            z.enum(['always', 'never', 'ask', 'above_amount']),
  min_sale_amount_cents:  z.number().int().min(0).optional(),
  auto_issue_nfce:        z.boolean(),
  allow_manual_nfce:      z.boolean(),
  company_name:           z.string().max(200).optional(),
  cnpj:                   z.string().max(18).optional(),
})

type FormValues = z.infer<typeof schema>

const MODE_OPTIONS = [
  { value: 'always',       label: 'Sempre emitir NFC-e' },
  { value: 'never',        label: 'Nunca emitir NFC-e' },
  { value: 'ask',          label: 'Perguntar ao operador' },
  { value: 'above_amount', label: 'Emitir acima de um valor' },
] as const

export default function FiscalSettingsPage() {
  const { data: settings, isLoading } = useFiscalSettings()
  const { mutate: update, isPending }  = useUpdateFiscalSettings()

  const { register, handleSubmit, watch, reset, formState: { errors } } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { policy_mode: 'ask', auto_issue_nfce: true, allow_manual_nfce: true },
  })

  useEffect(() => {
    if (settings) {
      reset({
        policy_mode:           settings.policy_mode ?? 'ask',
        min_sale_amount_cents: settings.min_sale_amount_cents ?? 0,
        auto_issue_nfce:       settings.auto_issue_nfce ?? true,
        allow_manual_nfce:     settings.allow_manual_nfce ?? true,
        company_name:          settings.company_name ?? '',
        cnpj:                  settings.cnpj ?? '',
      })
    }
  }, [settings, reset])

  const policyMode = watch('policy_mode')

  function onSubmit(data: FormValues) {
    update(data as any, {
      onSuccess: () => toast.success('Configurações fiscais salvas.'),
      onError:   (err) => toast.error(err instanceof Error ? err.message : 'Erro ao salvar.'),
    })
  }

  if (isLoading) return (
    <div className="space-y-4 max-w-lg">
      <Skeleton className="h-8 w-48" />
      {Array.from({ length: 5 }).map((_, i) => <Skeleton key={i} className="h-10 w-full" />)}
    </div>
  )

  return (
    <div className="space-y-6 max-w-lg">
      <AppPageHeader
        title="Configurações Fiscais"
        description="Defina a política de emissão de documentos fiscais."
      />

      <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
        {/* Política de emissão */}
        <div className="space-y-3">
          <Label className="text-base font-semibold">Política de Emissão</Label>
          <div className="space-y-2">
            {MODE_OPTIONS.map(({ value, label }) => (
              <label key={value} className="flex items-center gap-3 cursor-pointer">
                <input type="radio" value={value} {...register('policy_mode')} className="rounded-full" />
                <span className="text-sm">{label}</span>
              </label>
            ))}
          </div>
          {policyMode === 'above_amount' && (
            <div className="ml-6 space-y-1">
              <Label className="text-sm">Valor mínimo (em centavos)</Label>
              <Input
                type="number"
                min={0}
                {...register('min_sale_amount_cents', { valueAsNumber: true })}
                placeholder="Ex: 5000 = R$ 50,00"
                className="w-48"
              />
              <p className="text-xs text-muted-foreground">
                Insira o valor em centavos. 5000 = R$&nbsp;50,00
              </p>
            </div>
          )}
        </div>

        {/* NFC-e */}
        <div className="space-y-3">
          <Label className="text-base font-semibold">NFC-e</Label>
          <div className="space-y-2">
            <label className="flex items-center gap-3 cursor-pointer">
              <input type="checkbox" {...register('auto_issue_nfce')} className="rounded" />
              <span className="text-sm">Emitir automaticamente ao concluir a venda</span>
            </label>
            <label className="flex items-center gap-3 cursor-pointer">
              <input type="checkbox" {...register('allow_manual_nfce')} className="rounded" />
              <span className="text-sm">Permitir emissão manual pelo operador</span>
            </label>
          </div>
        </div>

        {/* Empresa */}
        <div className="space-y-3">
          <Label className="text-base font-semibold">Dados da Empresa</Label>
          <div className="space-y-2">
            <div className="space-y-1">
              <Label>Razão Social</Label>
              <Input {...register('company_name')} placeholder="Empresa LTDA" />
            </div>
            <div className="space-y-1">
              <Label>CNPJ</Label>
              <Input {...register('cnpj')} placeholder="00.000.000/0000-00" />
            </div>
          </div>
        </div>

        <Button type="submit" disabled={isPending}>
          {isPending ? 'Salvando...' : 'Salvar Configurações'}
        </Button>
      </form>
    </div>
  )
}
