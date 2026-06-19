'use client'

import { useForm, useFieldArray, Controller } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Plus, Trash2, MapPin, Phone, X, Loader2 } from 'lucide-react'
import { useState, useEffect } from 'react'
import { Button }   from '@/components/ui/button'
import { Input }    from '@/components/ui/input'
import { Label }    from '@/components/ui/label'
import { AppCard }  from '@/components/shared/app-card'
import { CustomerTagSelector } from './customer-tag-selector'
import type { CreateCustomerRequest } from '@store/contracts'
import type { ContactType } from '@store/shared-types'

// ── Validadores de documento ──────────────────────────────────────────────────

function isValidCPF(digits: string): boolean {
  if (digits.length !== 11 || /^(\d)\1{10}$/.test(digits)) return false
  const calc = (len: number) => {
    const sum = digits.slice(0, len).split('').reduce((acc, d, i) => acc + Number(d) * (len + 1 - i), 0)
    const rem = (sum * 10) % 11
    return rem === 10 ? 0 : rem
  }
  return calc(9) === Number(digits[9]) && calc(10) === Number(digits[10])
}

function isValidCNPJ(digits: string): boolean {
  if (digits.length !== 14 || /^(\d)\1{13}$/.test(digits)) return false
  const calc = (weights: number[]) => {
    const sum = weights.reduce((acc, w, i) => acc + Number(digits[i]) * w, 0)
    const rem = sum % 11
    return rem < 2 ? 0 : 11 - rem
  }
  return (
    calc([5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]) === Number(digits[12]) &&
    calc([6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]) === Number(digits[13])
  )
}

// ── Schemas ──────────────────────────────────────────────────────────────────

const addressSchema = z.object({
  zipcode:    z.string().min(1, 'CEP obrigatório'),
  street:     z.string().min(1, 'Logradouro obrigatório'),
  number:     z.string().min(1, 'Número obrigatório'),
  complement: z.string().optional(),
  district:   z.string().min(1, 'Bairro obrigatório'),
  city:       z.string().min(1, 'Cidade obrigatória'),
  state:      z.string().min(2, 'UF obrigatória').max(2, 'Use a sigla (2 letras)'),
  country:    z.string().optional(),
  is_default: z.boolean().optional(),
})

const contactSchema = z.object({
  type:       z.enum(['PHONE', 'WHATSAPP', 'EMAIL', 'INSTAGRAM', 'OTHER'] as const),
  value:      z.string().min(1, 'Valor obrigatório'),
  label:      z.string().optional(),
  is_primary: z.boolean().optional(),
})

const customerSchema = z.object({
  person_type: z.enum(['INDIVIDUAL', 'COMPANY'] as const),
  name:        z.string().min(2, 'Nome deve ter ao menos 2 caracteres').max(200),
  trade_name:  z.string().max(150).optional().or(z.literal('')),
  document:    z.string().max(20).optional().or(z.literal('')),
  email:       z.string().email('E-mail inválido').optional().or(z.literal('')),
  birth_date:  z.string().optional().or(z.literal('')),
  notes:       z.string().max(2000).optional().or(z.literal('')),
  addresses:   z.array(addressSchema).optional(),
  contacts:    z.array(contactSchema).optional(),
  tags:        z.array(z.string()).optional(),
}).superRefine((data, ctx) => {
  const digits = (data.document ?? '').replace(/\D/g, '')
  if (data.person_type === 'INDIVIDUAL') {
    if (!digits) {
      ctx.addIssue({ code: z.ZodIssueCode.custom, path: ['document'], message: 'CPF obrigatório.' })
    } else if (!isValidCPF(digits)) {
      ctx.addIssue({ code: z.ZodIssueCode.custom, path: ['document'], message: 'CPF inválido.' })
    }
  } else if (data.person_type === 'COMPANY' && digits) {
    if (!isValidCNPJ(digits)) {
      ctx.addIssue({ code: z.ZodIssueCode.custom, path: ['document'], message: 'CNPJ inválido.' })
    }
  }
})

type CustomerFormValues  = z.infer<typeof customerSchema>
type AddressValues       = z.infer<typeof addressSchema>
type ContactValues       = z.infer<typeof contactSchema>

// ── Constants ────────────────────────────────────────────────────────────────

const CONTACT_TYPE_LABELS: Record<ContactType, string> = {
  PHONE: 'Telefone', WHATSAPP: 'WhatsApp', EMAIL: 'E-mail',
  INSTAGRAM: 'Instagram', OTHER: 'Outro',
}

// ── Address Dialog ────────────────────────────────────────────────────────────

function AddressDialog({ isFirst, onAdd, onClose }: {
  isFirst: boolean
  onAdd:   (data: AddressValues) => void
  onClose: () => void
}) {
  const { register, handleSubmit, watch, setValue, formState: { errors } } = useForm<AddressValues>({
    resolver: zodResolver(addressSchema),
    defaultValues: { country: 'BR', is_default: isFirst },
  })

  const [cepLoading, setCepLoading] = useState(false)
  const [cepError,   setCepError]   = useState<string | null>(null)
  const zipcode = watch('zipcode')

  useEffect(() => {
    const digits = (zipcode ?? '').replace(/\D/g, '')
    if (digits.length !== 8) { setCepError(null); return }

    const controller = new AbortController()
    setCepLoading(true)
    setCepError(null)

    fetch(`https://viacep.com.br/ws/${digits}/json/`, { signal: controller.signal })
      .then((r) => r.json())
      .then((data) => {
        if (data.erro) { setCepError('CEP não encontrado.'); return }
        setValue('street',   data.logradouro ?? '', { shouldValidate: true })
        setValue('district', data.bairro     ?? '', { shouldValidate: true })
        setValue('city',     data.localidade ?? '', { shouldValidate: true })
        setValue('state',    data.uf         ?? '', { shouldValidate: true })
      })
      .catch((e) => { if (e.name !== 'AbortError') setCepError('Erro ao buscar CEP.') })
      .finally(() => setCepLoading(false))

    return () => controller.abort()
  }, [zipcode, setValue])

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
      <div className="bg-card border rounded-2xl shadow-2xl w-full max-w-lg flex flex-col max-h-[90vh]">
        <div className="flex items-center justify-between px-5 py-4 border-b shrink-0">
          <p className="font-bold">Novo Endereço</p>
          <button type="button" onClick={onClose} className="text-muted-foreground hover:text-foreground">
            <X className="h-4 w-4" />
          </button>
        </div>

        <form
          id="address-dialog-form"
          onSubmit={handleSubmit((data) => { onAdd(data); onClose() })}
          className="overflow-y-auto flex-1 p-5 space-y-4"
        >
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5">
              <Label>CEP *</Label>
              <div className="relative">
                <Input placeholder="00000-000" {...register('zipcode')} className={cepLoading ? 'pr-8' : ''} />
                {cepLoading && (
                  <Loader2 className="absolute right-2.5 top-1/2 -translate-y-1/2 h-4 w-4 animate-spin text-muted-foreground" />
                )}
              </div>
              {cepError
                ? <p className="text-xs text-destructive">{cepError}</p>
                : errors.zipcode && <p className="text-xs text-destructive">{errors.zipcode.message}</p>}
            </div>
            <div className="space-y-1.5">
              <Label>Número *</Label>
              <Input placeholder="123" {...register('number')} />
              {errors.number && <p className="text-xs text-destructive">{errors.number.message}</p>}
            </div>
          </div>

          <div className="space-y-1.5">
            <Label>Logradouro *</Label>
            <Input placeholder="Rua, Avenida…" {...register('street')} />
            {errors.street && <p className="text-xs text-destructive">{errors.street.message}</p>}
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5">
              <Label>Complemento</Label>
              <Input placeholder="Apto, Bloco…" {...register('complement')} />
            </div>
            <div className="space-y-1.5">
              <Label>Bairro *</Label>
              <Input placeholder="Bairro" {...register('district')} />
              {errors.district && <p className="text-xs text-destructive">{errors.district.message}</p>}
            </div>
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5">
              <Label>Cidade *</Label>
              <Input placeholder="Cidade" {...register('city')} />
              {errors.city && <p className="text-xs text-destructive">{errors.city.message}</p>}
            </div>
            <div className="space-y-1.5">
              <Label>UF *</Label>
              <Input placeholder="SP" maxLength={2} className="uppercase" {...register('state')} />
              {errors.state && <p className="text-xs text-destructive">{errors.state.message}</p>}
            </div>
          </div>

          <label className="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" className="h-4 w-4 rounded" {...register('is_default')} />
            <span className="text-sm">Definir como endereço padrão</span>
          </label>
        </form>

        <div className="flex justify-end gap-3 px-5 py-4 border-t shrink-0">
          <Button type="button" variant="outline" onClick={onClose}>Cancelar</Button>
          <Button type="submit" form="address-dialog-form">Adicionar endereço</Button>
        </div>
      </div>
    </div>
  )
}

// ── Contact Dialog ────────────────────────────────────────────────────────────

function ContactDialog({ isFirst, onAdd, onClose }: {
  isFirst: boolean
  onAdd:   (data: ContactValues) => void
  onClose: () => void
}) {
  const { register, handleSubmit, formState: { errors } } = useForm<ContactValues>({
    resolver: zodResolver(contactSchema),
    defaultValues: { type: 'PHONE', value: '', label: '', is_primary: isFirst },
  })

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
      <div className="bg-card border rounded-2xl shadow-2xl w-full max-w-md flex flex-col">
        <div className="flex items-center justify-between px-5 py-4 border-b">
          <p className="font-bold">Novo Contato</p>
          <button type="button" onClick={onClose} className="text-muted-foreground hover:text-foreground">
            <X className="h-4 w-4" />
          </button>
        </div>

        <form
          id="contact-dialog-form"
          onSubmit={handleSubmit((data) => { onAdd(data); onClose() })}
          className="p-5 space-y-4"
        >
          <div className="space-y-1.5">
            <Label>Tipo *</Label>
            <select
              className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
              {...register('type')}
            >
              {(Object.entries(CONTACT_TYPE_LABELS) as [ContactType, string][]).map(([val, label]) => (
                <option key={val} value={val}>{label}</option>
              ))}
            </select>
            {errors.type && <p className="text-xs text-destructive">{errors.type.message}</p>}
          </div>

          <div className="space-y-1.5">
            <Label>Valor *</Label>
            <Input placeholder="(11) 99999-9999" {...register('value')} />
            {errors.value && <p className="text-xs text-destructive">{errors.value.message}</p>}
          </div>

          <div className="space-y-1.5">
            <Label>Rótulo</Label>
            <Input placeholder="Ex.: Pessoal, Comercial" {...register('label')} />
          </div>

          <label className="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" className="h-4 w-4 rounded" {...register('is_primary')} />
            <span className="text-sm">Marcar como contato principal</span>
          </label>
        </form>

        <div className="flex justify-end gap-3 px-5 py-4 border-t">
          <Button type="button" variant="outline" onClick={onClose}>Cancelar</Button>
          <Button type="submit" form="contact-dialog-form">Adicionar contato</Button>
        </div>
      </div>
    </div>
  )
}

// ── Props ─────────────────────────────────────────────────────────────────────

interface CustomerFormProps {
  defaultValues?: Partial<CreateCustomerRequest>
  onSubmit:       (data: CreateCustomerRequest) => void
  isSubmitting:   boolean
  mode:           'create' | 'edit'
}

// ── Main Form ─────────────────────────────────────────────────────────────────

export function CustomerForm({ defaultValues, onSubmit, isSubmitting, mode }: CustomerFormProps) {
  const [showAddressDialog, setShowAddressDialog] = useState(false)
  const [showContactDialog, setShowContactDialog] = useState(false)

  const { register, handleSubmit, control, watch, formState: { errors } } = useForm<CustomerFormValues>({
    resolver: zodResolver(customerSchema),
    defaultValues: {
      person_type: defaultValues?.person_type ?? 'INDIVIDUAL',
      name:        defaultValues?.name        ?? '',
      trade_name:  defaultValues?.trade_name  ?? '',
      document:    defaultValues?.document    ?? '',
      email:       defaultValues?.email       ?? '',
      birth_date:  defaultValues?.birth_date  ?? '',
      notes:       defaultValues?.notes       ?? '',
      addresses:   defaultValues?.addresses   ?? [],
      contacts:    defaultValues?.contacts    ?? [],
      tags:        defaultValues?.tags        ?? [],
    },
  })

  const personType = watch('person_type')

  const { fields: addressFields, append: appendAddress, remove: removeAddress } =
    useFieldArray({ control, name: 'addresses' })

  const { fields: contactFields, append: appendContact, remove: removeContact } =
    useFieldArray({ control, name: 'contacts' })

  function handleFormSubmit(values: CustomerFormValues) {
    onSubmit({
      person_type: values.person_type,
      name:        values.name,
      trade_name:  values.trade_name  || undefined,
      document:    values.document    || undefined,
      email:       values.email       || undefined,
      birth_date:  values.birth_date  || undefined,
      notes:       values.notes       || undefined,
      addresses:   values.addresses?.length ? values.addresses : undefined,
      contacts:    values.contacts?.length  ? values.contacts  : undefined,
      tags:        values.tags?.length      ? values.tags      : undefined,
    })
  }

  return (
    <>
      <form onSubmit={handleSubmit(handleFormSubmit)} className="space-y-6">

        {/* ── Dados Principais ── */}
        <AppCard title="Dados Principais">
          <div className="grid gap-4 sm:grid-cols-2 max-w-2xl">
            <div className="sm:col-span-2 space-y-1.5">
              <Label>Tipo de Pessoa *</Label>
              <Controller
                control={control}
                name="person_type"
                render={({ field }) => (
                  <div className="flex gap-4">
                    {(['INDIVIDUAL', 'COMPANY'] as const).map((pt) => (
                      <label key={pt} className="flex items-center gap-2 cursor-pointer">
                        <input
                          type="radio"
                          value={pt}
                          checked={field.value === pt}
                          onChange={() => field.onChange(pt)}
                          className="accent-primary"
                        />
                        <span className="text-sm">{pt === 'INDIVIDUAL' ? 'Pessoa Física' : 'Pessoa Jurídica'}</span>
                      </label>
                    ))}
                  </div>
                )}
              />
              {errors.person_type && <p className="text-xs text-destructive">{errors.person_type.message}</p>}
            </div>

            <div className="sm:col-span-2 space-y-1.5">
              <Label htmlFor="name">Nome *</Label>
              <Input id="name" placeholder="Nome completo" {...register('name')} />
              {errors.name && <p className="text-xs text-destructive">{errors.name.message}</p>}
            </div>

            {personType === 'COMPANY' && (
              <div className="sm:col-span-2 space-y-1.5">
                <Label htmlFor="trade_name">Nome Fantasia</Label>
                <Input id="trade_name" placeholder="Nome fantasia" {...register('trade_name')} />
              </div>
            )}

            <div className="space-y-1.5">
              <Label htmlFor="document">{personType === 'COMPANY' ? 'CNPJ' : 'CPF'}</Label>
              <Input
                id="document"
                placeholder={personType === 'COMPANY' ? '00.000.000/0000-00' : '000.000.000-00'}
                {...register('document')}
              />
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="email">E-mail</Label>
              <Input id="email" type="email" placeholder="email@exemplo.com" {...register('email')} />
              {errors.email && <p className="text-xs text-destructive">{errors.email.message}</p>}
            </div>

            {personType === 'INDIVIDUAL' && (
              <div className="space-y-1.5">
                <Label htmlFor="birth_date">Data de Nascimento</Label>
                <Input id="birth_date" type="date" {...register('birth_date')} />
              </div>
            )}
          </div>
        </AppCard>

        {/* ── Endereços ── */}
        <AppCard
          title="Endereços"
          actions={
            <Button type="button" size="sm" variant="outline" onClick={() => setShowAddressDialog(true)}>
              <Plus className="h-3.5 w-3.5 mr-1.5" />
              Adicionar endereço
            </Button>
          }
        >
          {addressFields.length === 0 ? (
            <p className="text-sm text-muted-foreground">Nenhum endereço cadastrado.</p>
          ) : (
            <div className="space-y-2">
              {addressFields.map((field, index) => (
                <div key={field.id} className="flex items-start gap-3 rounded-xl border bg-muted/30 px-4 py-3">
                  <MapPin className="h-4 w-4 mt-0.5 shrink-0 text-muted-foreground" />
                  <div className="flex-1 min-w-0 text-sm">
                    <p className="font-medium">{field.street}, {field.number}{field.complement ? ` — ${field.complement}` : ''}</p>
                    <p className="text-muted-foreground text-xs mt-0.5">
                      {field.district} · {field.city}/{field.state} · {field.zipcode}
                      {field.is_default && <span className="ml-2 inline-flex items-center rounded-full bg-primary/10 px-1.5 py-0.5 text-[10px] font-medium text-primary">Padrão</span>}
                    </p>
                  </div>
                  <button
                    type="button"
                    onClick={() => removeAddress(index)}
                    className="shrink-0 p-1 text-muted-foreground hover:text-destructive transition-colors"
                    aria-label="Remover endereço"
                  >
                    <Trash2 className="h-3.5 w-3.5" />
                  </button>
                </div>
              ))}
            </div>
          )}
        </AppCard>

        {/* ── Contatos ── */}
        <AppCard
          title="Contatos"
          actions={
            <Button type="button" size="sm" variant="outline" onClick={() => setShowContactDialog(true)}>
              <Plus className="h-3.5 w-3.5 mr-1.5" />
              Adicionar contato
            </Button>
          }
        >
          {contactFields.length === 0 ? (
            <p className="text-sm text-muted-foreground">Nenhum contato cadastrado.</p>
          ) : (
            <div className="space-y-2">
              {contactFields.map((field, index) => (
                <div key={field.id} className="flex items-center gap-3 rounded-xl border bg-muted/30 px-4 py-3">
                  <Phone className="h-4 w-4 shrink-0 text-muted-foreground" />
                  <div className="flex-1 min-w-0 text-sm">
                    <p className="font-medium">{field.value}</p>
                    <p className="text-muted-foreground text-xs mt-0.5">
                      {CONTACT_TYPE_LABELS[field.type as ContactType]}
                      {field.label ? ` · ${field.label}` : ''}
                      {field.is_primary && <span className="ml-2 inline-flex items-center rounded-full bg-primary/10 px-1.5 py-0.5 text-[10px] font-medium text-primary">Principal</span>}
                    </p>
                  </div>
                  <button
                    type="button"
                    onClick={() => removeContact(index)}
                    className="shrink-0 p-1 text-muted-foreground hover:text-destructive transition-colors"
                    aria-label="Remover contato"
                  >
                    <Trash2 className="h-3.5 w-3.5" />
                  </button>
                </div>
              ))}
            </div>
          )}
        </AppCard>

        {/* ── Tags ── */}
        <AppCard title="Tags">
          <Controller
            control={control}
            name="tags"
            render={({ field }) => (
              <CustomerTagSelector
                value={field.value ?? []}
                onChange={field.onChange}
                disabled={isSubmitting}
              />
            )}
          />
        </AppCard>

        {/* ── Observações ── */}
        <AppCard title="Observações">
          <textarea
            className="w-full min-h-24 rounded-md border bg-background px-3 py-2 text-sm outline-none ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 resize-y"
            placeholder="Observações gerais sobre o cliente…"
            {...register('notes')}
          />
          {errors.notes && <p className="text-xs text-destructive">{errors.notes.message}</p>}
        </AppCard>

        {/* ── Actions ── */}
        <div className="flex gap-2">
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting ? 'Salvando…' : mode === 'create' ? 'Criar Cliente' : 'Salvar Alterações'}
          </Button>
        </div>
      </form>

      {/* ── Modais ── */}
      {showAddressDialog && (
        <AddressDialog
          isFirst={addressFields.length === 0}
          onAdd={(data) => appendAddress(data)}
          onClose={() => setShowAddressDialog(false)}
        />
      )}

      {showContactDialog && (
        <ContactDialog
          isFirst={contactFields.length === 0}
          onAdd={(data) => appendContact(data)}
          onClose={() => setShowContactDialog(false)}
        />
      )}
    </>
  )
}
