'use client'

import { useForm, useFieldArray, Controller } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Plus, Trash2, MapPin, Phone, X, Loader2, Printer, Building2 } from 'lucide-react'
import { useState, useEffect } from 'react'
import { PatternFormat, NumericFormat } from 'react-number-format'
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

const commercialReferenceSchema = z.object({
  company_name:   z.string().min(1, 'Empresa obrigatória').max(200),
  contact_person: z.string().max(200).optional().or(z.literal('')),
  phone:          z.string().max(20).optional().or(z.literal('')),
  notes:          z.string().max(500).optional().or(z.literal('')),
})

const CIVIL_STATUS_OPTIONS = [
  { value: 'SINGLE',       label: 'Solteiro(a)' },
  { value: 'MARRIED',      label: 'Casado(a)' },
  { value: 'DIVORCED',     label: 'Divorciado(a)' },
  { value: 'WIDOWED',      label: 'Viúvo(a)' },
  { value: 'STABLE_UNION', label: 'União Estável' },
  { value: 'OTHER',        label: 'Outro' },
]

const EDUCATION_LEVEL_OPTIONS = [
  { value: 'ELEMENTARY',   label: 'Ensino Fundamental' },
  { value: 'MIDDLE',       label: 'Ensino Médio' },
  { value: 'TECHNICAL',    label: 'Técnico' },
  { value: 'UNDERGRADUATE', label: 'Superior' },
  { value: 'GRADUATE',     label: 'Pós-Graduação' },
  { value: 'OTHER',        label: 'Outro' },
]

const CARD_BRANDS = ['Visa', 'Mastercard', 'Hipercard', 'Amex', 'Diners', 'Elo', 'Outros']

const customerSchema = z.object({
  person_type:      z.enum(['INDIVIDUAL', 'COMPANY'] as const),
  name:             z.string().min(2, 'Nome deve ter ao menos 2 caracteres').max(200),
  trade_name:       z.string().max(150).optional().or(z.literal('')),
  document:         z.string().max(20).optional().or(z.literal('')),
  rg:               z.string().max(20).optional().or(z.literal('')),
  ie:               z.string().max(30).optional().or(z.literal('')),
  email:            z.string().email('E-mail inválido').optional().or(z.literal('')),
  birth_date:       z.string().optional().or(z.literal('')),
  civil_status:     z.string().optional().or(z.literal('')),
  // New Dados Gerais fields
  gender:              z.string().optional().or(z.literal('')),
  nationality:         z.string().max(100).optional().or(z.literal('')),
  birth_city:          z.string().max(100).optional().or(z.literal('')),
  birth_state:         z.string().max(2).optional().or(z.literal('')),
  website:             z.string().max(200).optional().or(z.literal('')),
  postal_box:          z.string().max(20).optional().or(z.literal('')),
  is_final_consumer:   z.boolean().optional(),
  is_free_zone:        z.boolean().optional(),
  is_public_entity:    z.boolean().optional(),
  is_store_chain:      z.boolean().optional(),
  im:                  z.string().max(20).optional().or(z.literal('')),
  capital_stock_cents: z.number().min(0).optional().nullable(),
  economic_activity:   z.string().max(200).optional().or(z.literal('')),
  // Dados Comerciais
  seller_id:                z.string().optional().or(z.literal('')),
  carrier_id:               z.string().optional().or(z.literal('')),
  price_list_id:            z.string().optional().or(z.literal('')),
  representative_id:        z.string().optional().or(z.literal('')),
  collection_bank_id:       z.string().optional().or(z.literal('')),
  credit_limit_cents:       z.number().min(0).optional().nullable(),
  discount_type:            z.string().optional().or(z.literal('')),
  calculates_icms_discount: z.boolean().optional(),
  // Crediário
  father_name:         z.string().max(200).optional().or(z.literal('')),
  mother_name:         z.string().max(200).optional().or(z.literal('')),
  profession:          z.string().max(200).optional().or(z.literal('')),
  professional_card:   z.string().max(50).optional().or(z.literal('')),
  employer:            z.string().max(200).optional().or(z.literal('')),
  employer_address:    z.string().max(500).optional().or(z.literal('')),
  employer_phone:      z.string().max(20).optional().or(z.literal('')),
  work_start_date:     z.string().optional().or(z.literal('')),
  monthly_income:      z.number().min(0).optional().nullable(),
  other_income:        z.number().min(0).optional().nullable(),
  other_income_source: z.string().max(200).optional().or(z.literal('')),
  housing_type:        z.string().optional().or(z.literal('')),
  rent_cents:          z.number().min(0).optional().nullable(),
  years_at_address:    z.number().min(0).max(99).optional().nullable(),
  education_level:     z.string().optional().or(z.literal('')),
  marital_status:      z.string().optional().or(z.literal('')),
  // Spouse
  spouse_name:           z.string().max(200).optional().or(z.literal('')),
  spouse_document:       z.string().max(20).optional().or(z.literal('')),
  spouse_cpf:            z.string().max(20).optional().or(z.literal('')),
  spouse_rg:             z.string().max(20).optional().or(z.literal('')),
  spouse_phone:          z.string().max(20).optional().or(z.literal('')),
  spouse_employer:       z.string().max(200).optional().or(z.literal('')),
  spouse_income:         z.number().min(0).optional().nullable(),
  spouse_monthly_income: z.number().min(0).optional().nullable(),
  spouse_profession:     z.string().max(200).optional().or(z.literal('')),
  spouse_birth_date:     z.string().optional().or(z.literal('')),
  spouse_gender:         z.string().optional().or(z.literal('')),
  // Guarantor
  guarantor_name:        z.string().max(200).optional().or(z.literal('')),
  guarantor_document:    z.string().max(20).optional().or(z.literal('')),
  guarantor_phone:       z.string().max(20).optional().or(z.literal('')),
  guarantor_address:     z.string().max(500).optional().or(z.literal('')),
  guarantor_income:      z.number().min(0).optional().nullable(),
  guarantor_profession:  z.string().max(200).optional().or(z.literal('')),
  guarantor_birth_date:  z.string().optional().or(z.literal('')),
  guarantor_gender:      z.string().optional().or(z.literal('')),
  // Cards
  cards: z.array(z.string()).optional(),
  // Other
  notes:                 z.string().max(2000).optional().or(z.literal('')),
  addresses:             z.array(addressSchema).optional(),
  contacts:              z.array(contactSchema).optional(),
  commercial_references: z.array(commercialReferenceSchema).optional(),
  purchase_references: z.array(z.object({
    person_type:   z.enum(['CUSTOMER', 'SPOUSE', 'GUARANTOR'] as const),
    company_name:  z.string().min(1).max(200),
    phone:         z.string().max(20).optional().or(z.literal('')),
    monthly_limit: z.number().min(0).optional().nullable(),
  })).optional(),
  tags: z.array(z.string()).optional(),
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

type CustomerFormValues      = z.infer<typeof customerSchema>
type AddressValues           = z.infer<typeof addressSchema>
type ContactValues           = z.infer<typeof contactSchema>
type CommercialRefValues     = z.infer<typeof commercialReferenceSchema>

// ── Constants ────────────────────────────────────────────────────────────────

const CONTACT_TYPE_LABELS: Record<ContactType, string> = {
  PHONE: 'Telefone', WHATSAPP: 'WhatsApp', EMAIL: 'E-mail',
  INSTAGRAM: 'Instagram', OTHER: 'Outro',
}

const SELECT_CLASS = 'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30'

// ── Tab helpers ───────────────────────────────────────────────────────────────

type TabId = 'principal' | 'enderecos' | 'comercial' | 'crediario' | 'avalista' | 'bens' | 'referencias' | 'observacoes'

const TABS: { id: TabId; label: string }[] = [
  { id: 'principal',   label: 'Dados Gerais' },
  { id: 'enderecos',   label: 'Contatos & Endereços' },
  { id: 'comercial',   label: 'Dados Comerciais' },
  { id: 'crediario',   label: 'Crediário' },
  { id: 'avalista',    label: 'Avalista' },
  { id: 'bens',        label: 'Bens & Cartões' },
  { id: 'referencias', label: 'Referências' },
  { id: 'observacoes', label: 'Observações' },
]

// ── Address Dialog ────────────────────────────────────────────────────────────

function AddressDialog({ isFirst, onAdd, onClose }: {
  isFirst: boolean
  onAdd:   (data: AddressValues) => void
  onClose: () => void
}) {
  const { register, handleSubmit, watch, setValue, control, formState: { errors } } = useForm<AddressValues>({
    resolver: zodResolver(addressSchema),
    defaultValues: { country: 'BR', is_default: isFirst },
  })

  const [cepLoading, setCepLoading] = useState(false)
  const [cepError,   setCepError]   = useState<string | null>(null)
  const [noNumber,   setNoNumber]   = useState(false)
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
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div className="space-y-1.5">
              <Label>CEP *</Label>
              <div className="relative">
                <Controller
                  control={control}
                  name="zipcode"
                  render={({ field }) => (
                    <PatternFormat
                      customInput={Input}
                      format="#####-###"
                      placeholder="00000-000"
                      inputMode="numeric"
                      className={cepLoading ? 'pr-8' : ''}
                      value={field.value ?? ''}
                      onValueChange={(vals) => field.onChange(vals.formattedValue)}
                    />
                  )}
                />
                {cepLoading && (
                  <Loader2 className="absolute right-2.5 top-1/2 -translate-y-1/2 h-4 w-4 animate-spin text-muted-foreground" />
                )}
              </div>
              {cepError
                ? <p className="text-xs text-destructive">{cepError}</p>
                : errors.zipcode && <p className="text-xs text-destructive">{errors.zipcode.message}</p>}
            </div>
            <div className="space-y-1.5">
              <div className="flex items-center justify-between">
                <Label>Número *</Label>
                <label className="flex items-center gap-1.5 cursor-pointer select-none">
                  <input
                    type="checkbox"
                    className="h-3.5 w-3.5 rounded"
                    checked={noNumber}
                    onChange={(e) => {
                      setNoNumber(e.target.checked)
                      setValue('number', e.target.checked ? 'S/N' : '', { shouldValidate: true })
                    }}
                  />
                  <span className="text-xs text-muted-foreground">Sem número</span>
                </label>
              </div>
              <Input
                placeholder="123"
                disabled={noNumber}
                className={noNumber ? 'bg-muted text-muted-foreground' : ''}
                {...register('number')}
              />
              {errors.number && <p className="text-xs text-destructive">{errors.number.message}</p>}
            </div>
          </div>

          <div className="space-y-1.5">
            <Label>Logradouro *</Label>
            <Input placeholder="Rua, Avenida…" {...register('street')} />
            {errors.street && <p className="text-xs text-destructive">{errors.street.message}</p>}
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
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

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
  const { register, handleSubmit, control, watch, formState: { errors } } = useForm<ContactValues>({
    resolver: zodResolver(contactSchema),
    defaultValues: { type: 'PHONE', value: '', label: '', is_primary: isFirst },
  })
  const contactType = watch('type')

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
              className={SELECT_CLASS}
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
            {(contactType === 'PHONE' || contactType === 'WHATSAPP') ? (
              <Controller
                control={control}
                name="value"
                render={({ field }) => (
                  <PatternFormat
                    customInput={Input}
                    format="(##) #####-####"
                    placeholder="(11) 99999-9999"
                    value={field.value ?? ''}
                    onValueChange={(vals) => field.onChange(vals.formattedValue)}
                  />
                )}
              />
            ) : (
              <Input placeholder={contactType === 'EMAIL' ? 'email@exemplo.com' : '@usuario ou link'} {...register('value')} />
            )}
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

// ── Commercial Reference Dialog ───────────────────────────────────────────────

function CommercialRefDialog({ onAdd, onClose }: {
  onAdd:   (data: CommercialRefValues) => void
  onClose: () => void
}) {
  const { register, handleSubmit, control, formState: { errors } } = useForm<CommercialRefValues>({
    resolver: zodResolver(commercialReferenceSchema),
    defaultValues: { company_name: '', contact_person: '', phone: '', notes: '' },
  })

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
      <div className="bg-card border rounded-2xl shadow-2xl w-full max-w-md flex flex-col">
        <div className="flex items-center justify-between px-5 py-4 border-b">
          <p className="font-bold">Nova Referência Comercial</p>
          <button type="button" onClick={onClose} className="text-muted-foreground hover:text-foreground">
            <X className="h-4 w-4" />
          </button>
        </div>

        <form
          id="commercial-ref-dialog-form"
          onSubmit={handleSubmit((data) => { onAdd(data); onClose() })}
          className="p-5 space-y-4"
        >
          <div className="space-y-1.5">
            <Label>Empresa *</Label>
            <Input placeholder="Nome da empresa" {...register('company_name')} />
            {errors.company_name && <p className="text-xs text-destructive">{errors.company_name.message}</p>}
          </div>

          <div className="space-y-1.5">
            <Label>Pessoa de Contato</Label>
            <Input placeholder="Nome do responsável" {...register('contact_person')} />
          </div>

          <div className="space-y-1.5">
            <Label>Telefone</Label>
            <Controller
              control={control}
              name="phone"
              render={({ field }) => (
                <PatternFormat
                  customInput={Input}
                  format="(##) #####-####"
                  placeholder="(11) 99999-9999"
                  value={field.value ?? ''}
                  onValueChange={(vals) => field.onChange(vals.formattedValue)}
                />
              )}
            />
          </div>

          <div className="space-y-1.5">
            <Label>Observação</Label>
            <Input placeholder="Observações sobre a referência" {...register('notes')} />
          </div>
        </form>

        <div className="flex justify-end gap-3 px-5 py-4 border-t">
          <Button type="button" variant="outline" onClick={onClose}>Cancelar</Button>
          <Button type="submit" form="commercial-ref-dialog-form">Adicionar referência</Button>
        </div>
      </div>
    </div>
  )
}

// ── Print ─────────────────────────────────────────────────────────────────────

function buildPrintHtml(values: CustomerFormValues, filled: boolean): string {
  const line = (width = 200) =>
    `<span style="border-bottom:1px solid #000;display:inline-block;width:${width}px;">&nbsp;</span>`

  const field = (value: string | number | null | undefined, width = 200) =>
    filled && value ? `<strong>${value}</strong>` : line(width)

  const civilLabel = CIVIL_STATUS_OPTIONS.find((o) => o.value === values.civil_status)?.label ?? ''

  const refs = (values.commercial_references ?? [])
    .map((ref, i) => `
      <tr>
        <td style="padding:4px 8px;border:1px solid #ccc;">${i + 1}</td>
        <td style="padding:4px 8px;border:1px solid #ccc;">${filled ? ref.company_name : line(150)}</td>
        <td style="padding:4px 8px;border:1px solid #ccc;">${filled ? (ref.contact_person ?? '') : line(120)}</td>
        <td style="padding:4px 8px;border:1px solid #ccc;">${filled ? (ref.phone ?? '') : line(100)}</td>
        <td style="padding:4px 8px;border:1px solid #ccc;">${filled ? (ref.notes ?? '') : line(150)}</td>
      </tr>`)
    .join('')

  const emptyRows = !filled || (values.commercial_references ?? []).length < 3
    ? Array.from({ length: Math.max(0, 3 - (values.commercial_references ?? []).length) })
        .map(() => `
          <tr>
            <td style="padding:4px 8px;border:1px solid #ccc;">&nbsp;</td>
            <td style="padding:4px 8px;border:1px solid #ccc;">${line(150)}</td>
            <td style="padding:4px 8px;border:1px solid #ccc;">${line(120)}</td>
            <td style="padding:4px 8px;border:1px solid #ccc;">${line(100)}</td>
            <td style="padding:4px 8px;border:1px solid #ccc;">${line(150)}</td>
          </tr>`)
        .join('')
    : ''

  const today = new Date().toLocaleDateString('pt-BR')

  return `<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"/>
<title>Ficha de Cadastro de Cliente</title>
<style>
  body { font-family: Arial, sans-serif; font-size: 11pt; margin: 20mm 15mm; color: #000; }
  h1 { font-size: 14pt; text-align: center; margin-bottom: 4px; }
  .subtitle { text-align: center; font-size: 9pt; color: #555; margin-bottom: 16px; }
  h2 { font-size: 11pt; background: #f0f0f0; padding: 4px 8px; border-left: 3px solid #333; margin: 16px 0 8px; }
  .row { display: flex; gap: 24px; margin-bottom: 10px; flex-wrap: wrap; }
  .field { display: flex; flex-direction: column; gap: 2px; }
  .field label { font-size: 8pt; color: #555; text-transform: uppercase; letter-spacing: 0.05em; }
  table { border-collapse: collapse; width: 100%; margin-bottom: 8px; font-size: 10pt; }
  th { background: #f0f0f0; padding: 4px 8px; border: 1px solid #ccc; text-align: left; font-size: 9pt; }
  @media print { body { margin: 10mm 12mm; } }
</style>
</head>
<body>
<h1>FICHA DE CADASTRO DE CLIENTE</h1>
<p class="subtitle">Data de impressão: ${today}</p>

<h2>Dados Pessoais</h2>
<div class="row">
  <div class="field" style="flex:2">
    <label>${values.person_type === 'COMPANY' ? 'Razão Social' : 'Nome Completo'}</label>
    ${field(values.name, 300)}
  </div>
  <div class="field" style="flex:1">
    <label>${values.person_type === 'COMPANY' ? 'CNPJ' : 'CPF'}</label>
    ${field(values.document, 160)}
  </div>
</div>
${values.person_type === 'INDIVIDUAL' ? `
<div class="row">
  <div class="field" style="flex:1">
    <label>RG</label>
    ${field(values.rg, 120)}
  </div>
  <div class="field" style="flex:1">
    <label>Data de Nascimento</label>
    ${field(values.birth_date, 120)}
  </div>
  <div class="field" style="flex:1">
    <label>Estado Civil</label>
    ${field(civilLabel, 130)}
  </div>
</div>` : `
<div class="row">
  <div class="field" style="flex:2">
    <label>Nome Fantasia</label>
    ${field(values.trade_name, 240)}
  </div>
  <div class="field" style="flex:1">
    <label>I.E.</label>
    ${field(values.ie, 140)}
  </div>
</div>`}
<div class="row">
  <div class="field" style="flex:2">
    <label>E-mail</label>
    ${field(values.email, 240)}
  </div>
</div>

${['MARRIED', 'STABLE_UNION'].includes(values.civil_status ?? '') || !filled ? `
<h2>Dados do Cônjuge</h2>
<div class="row">
  <div class="field" style="flex:2">
    <label>Nome</label>
    ${field(values.spouse_name, 260)}
  </div>
  <div class="field" style="flex:1">
    <label>CPF</label>
    ${field(values.spouse_document, 140)}
  </div>
</div>
<div class="row">
  <div class="field" style="flex:1">
    <label>Telefone</label>
    ${field(values.spouse_phone, 140)}
  </div>
  <div class="field" style="flex:2">
    <label>Empresa/Empregador</label>
    ${field(values.spouse_employer, 200)}
  </div>
  <div class="field" style="flex:1">
    <label>Renda Mensal</label>
    ${field(values.spouse_income != null ? `R$ ${Number(values.spouse_income).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}` : '', 120)}
  </div>
</div>` : ''}

<h2>Referências Comerciais</h2>
<table>
  <thead>
    <tr>
      <th style="width:30px">#</th>
      <th>Empresa</th>
      <th>Contato</th>
      <th>Telefone</th>
      <th>Observação</th>
    </tr>
  </thead>
  <tbody>
    ${refs}${emptyRows}
  </tbody>
</table>

<h2>Dados do Avalista</h2>
<div class="row">
  <div class="field" style="flex:2">
    <label>Nome</label>
    ${field(values.guarantor_name, 260)}
  </div>
  <div class="field" style="flex:1">
    <label>CPF</label>
    ${field(values.guarantor_document, 140)}
  </div>
</div>
<div class="row">
  <div class="field" style="flex:1">
    <label>Telefone</label>
    ${field(values.guarantor_phone, 140)}
  </div>
  <div class="field" style="flex:1">
    <label>Renda Mensal</label>
    ${field(values.guarantor_income != null ? `R$ ${Number(values.guarantor_income).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}` : '', 120)}
  </div>
</div>
<div class="row">
  <div class="field" style="flex:1">
    <label>Endereço</label>
    ${field(values.guarantor_address, 400)}
  </div>
</div>

<h2>Observações</h2>
<div style="min-height:60px;border:1px solid #ccc;padding:6px 8px;border-radius:4px;">
  ${filled && values.notes ? values.notes : '&nbsp;'}
</div>

<div style="margin-top:40px;display:flex;gap:60px;justify-content:center;">
  <div style="text-align:center;">
    <div style="border-top:1px solid #000;width:200px;padding-top:4px;font-size:9pt;">Assinatura do Cliente</div>
  </div>
  <div style="text-align:center;">
    <div style="border-top:1px solid #000;width:200px;padding-top:4px;font-size:9pt;">Responsável</div>
  </div>
</div>

<script>window.print()</script>
</body>
</html>`
}

// ── Purchase Reference Section ────────────────────────────────────────────────

function PurchaseRefSection({
  personType, label, fields, onAdd, onRemove,
}: {
  personType: 'CUSTOMER' | 'SPOUSE' | 'GUARANTOR'
  label:      string
  fields:     Array<{ id: string; person_type: string; company_name: string; phone?: string; monthly_limit?: number | null }>
  onAdd:      (data: { person_type: 'CUSTOMER' | 'SPOUSE' | 'GUARANTOR'; company_name: string; phone?: string; monthly_limit?: number | null }) => void
  onRemove:   (index: number) => void
}) {
  const [adding,       setAdding]       = useState(false)
  const [companyName,  setCompanyName]  = useState('')
  const [phone,        setPhone]        = useState('')
  const [monthlyLimit, setMonthlyLimit] = useState<number | null>(null)
  const [err,          setErr]          = useState('')

  const filtered = fields
    .map((f, i) => ({ ...f, _originalIndex: i }))
    .filter((f) => f.person_type === personType)

  function handleAdd() {
    if (!companyName.trim()) { setErr('Empresa obrigatória'); return }
    onAdd({ person_type: personType, company_name: companyName.trim(), phone: phone || undefined, monthly_limit: monthlyLimit })
    setCompanyName(''); setPhone(''); setMonthlyLimit(null); setAdding(false); setErr('')
  }

  return (
    <div className="space-y-3">
      <div className="flex items-center justify-between">
        <p className="text-sm font-medium">{label}</p>
        {!adding && (
          <Button type="button" size="sm" variant="outline" onClick={() => setAdding(true)}>
            <Plus className="h-3.5 w-3.5 mr-1" />Adicionar
          </Button>
        )}
      </div>

      {adding && (
        <div className="border rounded-lg p-3 space-y-3 bg-muted/20">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div className="col-span-2 space-y-1">
              <Label>Empresa/Loja *</Label>
              <Input
                placeholder="Nome da empresa"
                value={companyName}
                onChange={(e) => { setCompanyName(e.target.value); setErr('') }}
                autoFocus
              />
              {err && <p className="text-xs text-destructive">{err}</p>}
            </div>
            <div className="space-y-1">
              <Label>Telefone</Label>
              <PatternFormat
                customInput={Input}
                format="(##) #####-####"
                placeholder="(11) 99999-9999"
                value={phone}
                onValueChange={(vals) => setPhone(vals.formattedValue)}
              />
            </div>
            <div className="space-y-1">
              <Label>Limite mensal</Label>
              <NumericFormat
                customInput={Input}
                thousandSeparator="."
                decimalSeparator=","
                decimalScale={2}
                fixedDecimalScale
                prefix="R$ "
                placeholder="R$ 0,00"
                value={monthlyLimit ?? ''}
                onValueChange={(vals) => setMonthlyLimit(vals.floatValue ?? null)}
              />
            </div>
          </div>
          <div className="flex gap-2">
            <Button type="button" size="sm" onClick={handleAdd}>Adicionar</Button>
            <Button type="button" size="sm" variant="outline" onClick={() => { setAdding(false); setErr('') }}>Cancelar</Button>
          </div>
        </div>
      )}

      {filtered.length === 0 && !adding && (
        <p className="text-xs text-muted-foreground">Nenhuma referência adicionada.</p>
      )}

      {filtered.map((f) => (
        <div key={f.id} className="flex items-center gap-3 rounded-lg border bg-muted/30 px-4 py-2.5">
          <div className="flex-1 min-w-0 text-sm">
            <p className="font-medium">{f.company_name}</p>
            <p className="text-xs text-muted-foreground">
              {f.phone && <span>{f.phone}</span>}
              {f.monthly_limit != null && (
                <span className="ml-2">Limite: R$ {Number(f.monthly_limit).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</span>
              )}
            </p>
          </div>
          <button
            type="button"
            onClick={() => onRemove(f._originalIndex)}
            className="shrink-0 p-1 text-muted-foreground hover:text-destructive transition-colors"
          >
            <Trash2 className="h-3.5 w-3.5" />
          </button>
        </div>
      ))}
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
  const [activeTab,           setActiveTab]           = useState<TabId>('principal')
  const [showAddressDialog,   setShowAddressDialog]   = useState(false)
  const [showContactDialog,   setShowContactDialog]   = useState(false)
  const [showCommRefDialog,   setShowCommRefDialog]   = useState(false)
  const [ieIsento,            setIeIsento]            = useState(() => (defaultValues as any)?.ie === 'ISENTO')

  const { register, handleSubmit, control, watch, setValue, formState: { errors } } = useForm<CustomerFormValues>({
    resolver: zodResolver(customerSchema),
    defaultValues: {
      person_type:           (defaultValues as any)?.person_type          ?? 'INDIVIDUAL',
      name:                  defaultValues?.name                          ?? '',
      trade_name:            defaultValues?.trade_name                    ?? '',
      document:              defaultValues?.document                      ?? '',
      rg:                    (defaultValues as any)?.rg                   ?? '',
      ie:                    (defaultValues as any)?.ie                   ?? '',
      email:                 defaultValues?.email                         ?? '',
      birth_date:            defaultValues?.birth_date                    ?? '',
      civil_status:          (defaultValues as any)?.civil_status         ?? '',
      // New Dados Gerais
      gender:                (defaultValues as any)?.gender               ?? '',
      nationality:           (defaultValues as any)?.nationality          ?? '',
      birth_city:            (defaultValues as any)?.birth_city           ?? '',
      birth_state:           (defaultValues as any)?.birth_state          ?? '',
      website:               (defaultValues as any)?.website              ?? '',
      postal_box:            (defaultValues as any)?.postal_box           ?? '',
      is_final_consumer:     (defaultValues as any)?.is_final_consumer    ?? false,
      is_free_zone:          (defaultValues as any)?.is_free_zone         ?? false,
      is_public_entity:      (defaultValues as any)?.is_public_entity     ?? false,
      is_store_chain:        (defaultValues as any)?.is_store_chain       ?? false,
      im:                    (defaultValues as any)?.im                   ?? '',
      capital_stock_cents:   (defaultValues as any)?.capital_stock_cents  ?? null,
      economic_activity:     (defaultValues as any)?.economic_activity    ?? '',
      // Dados Comerciais
      seller_id:                (defaultValues as any)?.seller_id              ?? '',
      carrier_id:               (defaultValues as any)?.carrier_id             ?? '',
      price_list_id:            (defaultValues as any)?.price_list_id          ?? '',
      representative_id:        (defaultValues as any)?.representative_id      ?? '',
      collection_bank_id:       (defaultValues as any)?.collection_bank_id     ?? '',
      credit_limit_cents:       (defaultValues as any)?.credit_limit_cents     ?? null,
      discount_type:            (defaultValues as any)?.discount_type          ?? '',
      calculates_icms_discount: (defaultValues as any)?.calculates_icms_discount ?? false,
      // Crediário
      father_name:         (defaultValues as any)?.father_name        ?? '',
      mother_name:         (defaultValues as any)?.mother_name        ?? '',
      profession:          (defaultValues as any)?.profession         ?? '',
      professional_card:   (defaultValues as any)?.professional_card  ?? '',
      employer:            (defaultValues as any)?.employer           ?? '',
      employer_address:    (defaultValues as any)?.employer_address   ?? '',
      employer_phone:      (defaultValues as any)?.employer_phone     ?? '',
      work_start_date:     (defaultValues as any)?.work_start_date    ?? '',
      monthly_income:      (defaultValues as any)?.monthly_income     ?? null,
      other_income:        (defaultValues as any)?.other_income       ?? null,
      other_income_source: (defaultValues as any)?.other_income_source ?? '',
      housing_type:        (defaultValues as any)?.housing_type       ?? '',
      rent_cents:          (defaultValues as any)?.rent_cents         ?? null,
      years_at_address:    (defaultValues as any)?.years_at_address   ?? null,
      education_level:     (defaultValues as any)?.education_level    ?? '',
      marital_status:      (defaultValues as any)?.marital_status     ?? '',
      // Spouse
      spouse_name:           (defaultValues as any)?.spouse_name          ?? '',
      spouse_document:       (defaultValues as any)?.spouse_document      ?? '',
      spouse_cpf:            (defaultValues as any)?.spouse_cpf           ?? '',
      spouse_rg:             (defaultValues as any)?.spouse_rg            ?? '',
      spouse_phone:          (defaultValues as any)?.spouse_phone         ?? '',
      spouse_employer:       (defaultValues as any)?.spouse_employer      ?? '',
      spouse_income:         (defaultValues as any)?.spouse_income        ?? null,
      spouse_monthly_income: (defaultValues as any)?.spouse_monthly_income ?? null,
      spouse_profession:     (defaultValues as any)?.spouse_profession    ?? '',
      spouse_birth_date:     (defaultValues as any)?.spouse_birth_date    ?? '',
      spouse_gender:         (defaultValues as any)?.spouse_gender        ?? '',
      // Guarantor
      guarantor_name:        (defaultValues as any)?.guarantor_name       ?? '',
      guarantor_document:    (defaultValues as any)?.guarantor_document   ?? '',
      guarantor_phone:       (defaultValues as any)?.guarantor_phone      ?? '',
      guarantor_address:     (defaultValues as any)?.guarantor_address    ?? '',
      guarantor_income:      (defaultValues as any)?.guarantor_income     ?? null,
      guarantor_profession:  (defaultValues as any)?.guarantor_profession ?? '',
      guarantor_birth_date:  (defaultValues as any)?.guarantor_birth_date ?? '',
      guarantor_gender:      (defaultValues as any)?.guarantor_gender     ?? '',
      // Cards
      cards: (defaultValues as any)?.cards ?? [],
      // Other
      notes:                 defaultValues?.notes                         ?? '',
      addresses:             defaultValues?.addresses                     ?? [],
      contacts:              defaultValues?.contacts                      ?? [],
      commercial_references: (defaultValues as any)?.commercial_references ?? [],
      purchase_references:   (defaultValues as any)?.purchase_references  ?? [],
      tags:                  defaultValues?.tags                          ?? [],
    },
  })

  const personType   = watch('person_type')
  const civilStatus  = watch('civil_status')
  const housingType  = watch('housing_type')
  const cards        = watch('cards') ?? []
  const formValues   = watch()

  const { fields: addressFields,      append: appendAddress,     remove: removeAddress     } = useFieldArray({ control, name: 'addresses' })
  const { fields: contactFields,      append: appendContact,     remove: removeContact     } = useFieldArray({ control, name: 'contacts' })
  const { fields: commRefFields,      append: appendCommRef,     remove: removeCommRef     } = useFieldArray({ control, name: 'commercial_references' })
  const { fields: purchaseRefFields,  append: appendPurchaseRef, remove: removePurchaseRef } = useFieldArray({ control, name: 'purchase_references' })

  const showSpouseSection = personType === 'INDIVIDUAL' && (civilStatus === 'MARRIED' || civilStatus === 'STABLE_UNION')

  function handlePrint(filled: boolean) {
    const html = buildPrintHtml(formValues, filled)
    const win  = window.open('', '_blank', 'width=900,height=700')
    if (win) {
      win.document.write(html)
      win.document.close()
    }
  }

  function toggleCard(brand: string) {
    const current = cards
    if (current.includes(brand)) {
      setValue('cards', current.filter((c) => c !== brand))
    } else {
      setValue('cards', [...current, brand])
    }
  }

  function handleFormSubmit(values: CustomerFormValues) {
    onSubmit({
      person_type:           values.person_type,
      name:                  values.name,
      trade_name:            values.trade_name            || undefined,
      document:              values.document              || undefined,
      rg:                    values.rg                    || undefined,
      ie:                    values.ie                    || undefined,
      email:                 values.email                 || undefined,
      birth_date:            values.birth_date            || undefined,
      civil_status:          values.civil_status          || undefined,
      // New Dados Gerais
      ...(values.gender            && { gender: values.gender }),
      ...(values.nationality       && { nationality: values.nationality }),
      ...(values.birth_city        && { birth_city: values.birth_city }),
      ...(values.birth_state       && { birth_state: values.birth_state }),
      ...(values.website           && { website: values.website }),
      ...(values.postal_box        && { postal_box: values.postal_box }),
      ...(values.is_final_consumer !== undefined && { is_final_consumer: values.is_final_consumer }),
      ...(values.is_free_zone      !== undefined && { is_free_zone: values.is_free_zone }),
      ...(values.is_public_entity  !== undefined && { is_public_entity: values.is_public_entity }),
      ...(values.is_store_chain    !== undefined && { is_store_chain: values.is_store_chain }),
      ...(values.im                && { im: values.im }),
      ...(values.capital_stock_cents != null && { capital_stock_cents: values.capital_stock_cents }),
      ...(values.economic_activity && { economic_activity: values.economic_activity }),
      // Dados Comerciais
      ...(values.seller_id               && { seller_id: values.seller_id }),
      ...(values.carrier_id              && { carrier_id: values.carrier_id }),
      ...(values.price_list_id           && { price_list_id: values.price_list_id }),
      ...(values.representative_id       && { representative_id: values.representative_id }),
      ...(values.collection_bank_id      && { collection_bank_id: values.collection_bank_id }),
      ...(values.credit_limit_cents != null && { credit_limit_cents: values.credit_limit_cents }),
      ...(values.discount_type           && { discount_type: values.discount_type }),
      ...(values.calculates_icms_discount !== undefined && { calculates_icms_discount: values.calculates_icms_discount }),
      // Crediário
      ...(values.father_name         && { father_name: values.father_name }),
      ...(values.mother_name         && { mother_name: values.mother_name }),
      ...(values.profession          && { profession: values.profession }),
      ...(values.professional_card   && { professional_card: values.professional_card }),
      ...(values.employer            && { employer: values.employer }),
      ...(values.employer_address    && { employer_address: values.employer_address }),
      ...(values.employer_phone      && { employer_phone: values.employer_phone }),
      ...(values.work_start_date     && { work_start_date: values.work_start_date }),
      ...(values.monthly_income != null && { monthly_income: values.monthly_income }),
      ...(values.other_income   != null && { other_income: values.other_income }),
      ...(values.other_income_source && { other_income_source: values.other_income_source }),
      ...(values.housing_type        && { housing_type: values.housing_type }),
      ...(values.rent_cents     != null && { rent_cents: values.rent_cents }),
      ...(values.years_at_address != null && { years_at_address: values.years_at_address }),
      ...(values.education_level     && { education_level: values.education_level }),
      ...(values.marital_status      && { marital_status: values.marital_status }),
      // Spouse
      spouse_name:           values.spouse_name           || undefined,
      spouse_document:       values.spouse_document       || undefined,
      spouse_phone:          values.spouse_phone          || undefined,
      spouse_employer:       values.spouse_employer       || undefined,
      spouse_income:         values.spouse_income         ?? undefined,
      spouse_profession:     values.spouse_profession     || undefined,
      spouse_birth_date:     values.spouse_birth_date     || undefined,
      spouse_gender:         values.spouse_gender         || undefined,
      ...(values.spouse_cpf           && { spouse_cpf: values.spouse_cpf }),
      ...(values.spouse_rg            && { spouse_rg: values.spouse_rg }),
      ...(values.spouse_monthly_income != null && { spouse_monthly_income: values.spouse_monthly_income }),
      // Guarantor
      guarantor_name:        values.guarantor_name        || undefined,
      guarantor_document:    values.guarantor_document    || undefined,
      guarantor_phone:       values.guarantor_phone       || undefined,
      guarantor_address:     values.guarantor_address     || undefined,
      guarantor_income:      values.guarantor_income      ?? undefined,
      guarantor_profession:  values.guarantor_profession  || undefined,
      guarantor_birth_date:  values.guarantor_birth_date  || undefined,
      guarantor_gender:      values.guarantor_gender      || undefined,
      // Other
      notes:                 values.notes                 || undefined,
      addresses:             values.addresses?.length     ? values.addresses             : undefined,
      contacts:              values.contacts?.length      ? values.contacts              : undefined,
      commercial_references: values.commercial_references?.length ? values.commercial_references : undefined,
      purchase_references:   values.purchase_references?.length ? values.purchase_references.map(r => ({
        person_type:   r.person_type,
        company_name:  r.company_name,
        phone:         r.phone || undefined,
        monthly_limit: r.monthly_limit ?? undefined,
      })) : undefined,
      tags:                  values.tags?.length          ? values.tags                  : undefined,
    } as CreateCustomerRequest)
  }

  return (
    <>
      <form onSubmit={handleSubmit(handleFormSubmit)} className="space-y-6">

        {/* ── Actions bar ── */}
        <div className="flex items-center justify-between">
          <div className="flex gap-2">
            <Button
              type="button"
              size="sm"
              variant="outline"
              onClick={() => handlePrint(false)}
            >
              <Printer className="h-3.5 w-3.5 mr-1.5" />
              Imprimir em branco
            </Button>
            {mode === 'edit' && (
              <Button
                type="button"
                size="sm"
                variant="outline"
                onClick={() => handlePrint(true)}
              >
                <Printer className="h-3.5 w-3.5 mr-1.5" />
                Imprimir ficha
              </Button>
            )}
          </div>
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting ? 'Salvando…' : mode === 'create' ? 'Criar Cliente' : 'Salvar Alterações'}
          </Button>
        </div>

        {/* ── Tabs — desktop ── */}
        <div className="hidden md:flex border-b gap-0 overflow-x-auto">
          {TABS.map((tab) => (
            <button
              key={tab.id}
              type="button"
              onClick={() => setActiveTab(tab.id)}
              className={[
                'px-4 py-2 text-sm font-medium transition-colors whitespace-nowrap',
                activeTab === tab.id
                  ? 'border-b-2 border-primary text-primary'
                  : 'text-muted-foreground hover:text-foreground',
              ].join(' ')}
            >
              {tab.label}
            </button>
          ))}
        </div>

        {/* ── Tabs — mobile: select ── */}
        <div className="md:hidden mb-4">
          <select
            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
            value={activeTab}
            onChange={(e) => setActiveTab(e.target.value as TabId)}
          >
            {TABS.map((tab) => (
              <option key={tab.id} value={tab.id}>{tab.label}</option>
            ))}
          </select>
        </div>

        {/* ── Tab: Dados Gerais ── */}
        {activeTab === 'principal' && (
          <AppCard title="Dados Principais">
            <div className="grid gap-4 sm:grid-cols-2 max-w-2xl">
              {/* Tipo de Pessoa */}
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

              {/* Nome */}
              <div className="sm:col-span-2 space-y-1.5">
                <Label htmlFor="name">{personType === 'COMPANY' ? 'Razão Social *' : 'Nome Completo *'}</Label>
                <Input id="name" placeholder={personType === 'COMPANY' ? 'Razão social' : 'Nome completo'} {...register('name')} />
                {errors.name && <p className="text-xs text-destructive">{errors.name.message}</p>}
              </div>

              {/* Nome Fantasia (COMPANY only) */}
              {personType === 'COMPANY' && (
                <div className="sm:col-span-2 space-y-1.5">
                  <Label htmlFor="trade_name">Nome Fantasia</Label>
                  <Input id="trade_name" placeholder="Nome fantasia" {...register('trade_name')} />
                </div>
              )}

              {/* CPF/CNPJ */}
              <div className="space-y-1.5">
                <Label htmlFor="document">{personType === 'COMPANY' ? 'CNPJ' : 'CPF *'}</Label>
                <Controller
                  control={control}
                  name="document"
                  render={({ field }) => (
                    <PatternFormat
                      id="document"
                      customInput={Input}
                      format={personType === 'COMPANY' ? '##.###.###/####-##' : '###.###.###-##'}
                      placeholder={personType === 'COMPANY' ? '00.000.000/0000-00' : '000.000.000-00'}
                      inputMode="numeric"
                      value={field.value ?? ''}
                      onValueChange={(vals) => field.onChange(vals.formattedValue)}
                    />
                  )}
                />
                {errors.document && <p className="text-xs text-destructive">{errors.document.message}</p>}
              </div>

              {/* RG (INDIVIDUAL only) */}
              {personType === 'INDIVIDUAL' && (
                <div className="space-y-1.5">
                  <Label htmlFor="rg">RG</Label>
                  <Input id="rg" placeholder="0000000" {...register('rg')} />
                </div>
              )}

              {/* IE - somente PJ */}
              {personType === 'COMPANY' && (
                <div className="space-y-1.5">
                  <div className="flex items-center justify-between">
                    <Label htmlFor="ie">Inscrição Estadual</Label>
                    <label className="flex items-center gap-1.5 cursor-pointer select-none">
                      <input
                        type="checkbox"
                        className="h-3.5 w-3.5 rounded"
                        checked={ieIsento}
                        onChange={(e) => {
                          setIeIsento(e.target.checked)
                          setValue('ie', e.target.checked ? 'ISENTO' : '', { shouldValidate: true })
                        }}
                      />
                      <span className="text-xs text-muted-foreground">Isento</span>
                    </label>
                  </div>
                  <Input
                    id="ie"
                    placeholder="Inscrição Estadual"
                    readOnly={ieIsento}
                    className={ieIsento ? 'bg-muted text-muted-foreground cursor-default' : ''}
                    {...register('ie')}
                  />
                </div>
              )}

              {/* IM - somente PJ */}
              {personType === 'COMPANY' && (
                <div className="space-y-1.5">
                  <Label htmlFor="im">Inscrição Municipal</Label>
                  <Input id="im" placeholder="Inscrição Municipal" {...register('im')} />
                </div>
              )}

              {/* E-mail */}
              <div className="space-y-1.5">
                <Label htmlFor="email">E-mail</Label>
                <Input id="email" type="email" placeholder="email@exemplo.com" {...register('email')} />
                {errors.email && <p className="text-xs text-destructive">{errors.email.message}</p>}
              </div>

              {/* Data de Nascimento (INDIVIDUAL only) */}
              {personType === 'INDIVIDUAL' && (
                <div className="space-y-1.5">
                  <Label htmlFor="birth_date">Data de Nascimento</Label>
                  <Input id="birth_date" type="date" {...register('birth_date')} />
                </div>
              )}

              {/* Estado Civil (INDIVIDUAL only) */}
              {personType === 'INDIVIDUAL' && (
                <div className="space-y-1.5">
                  <Label htmlFor="civil_status">Estado Civil</Label>
                  <select
                    id="civil_status"
                    className={SELECT_CLASS}
                    {...register('civil_status')}
                  >
                    <option value="">Selecione…</option>
                    {CIVIL_STATUS_OPTIONS.map((opt) => (
                      <option key={opt.value} value={opt.value}>{opt.label}</option>
                    ))}
                  </select>
                </div>
              )}

              {/* Sexo (INDIVIDUAL only) */}
              {personType === 'INDIVIDUAL' && (
                <div className="space-y-1.5">
                  <Label htmlFor="gender">Sexo</Label>
                  <select id="gender" className={SELECT_CLASS} {...register('gender')}>
                    <option value="">Selecione…</option>
                    <option value="M">Masculino</option>
                    <option value="F">Feminino</option>
                    <option value="O">Outro</option>
                  </select>
                </div>
              )}

              {/* Naturalidade (INDIVIDUAL only) */}
              {personType === 'INDIVIDUAL' && (
                <>
                  <div className="space-y-1.5">
                    <Label htmlFor="nationality">Nacionalidade</Label>
                    <Input id="nationality" placeholder="Ex.: Brasileiro(a)" {...register('nationality')} />
                  </div>
                  <div className="space-y-1.5">
                    <Label htmlFor="birth_city">Cidade de Nascimento</Label>
                    <Input id="birth_city" placeholder="Cidade" {...register('birth_city')} />
                  </div>
                  <div className="space-y-1.5">
                    <Label htmlFor="birth_state">UF de Nascimento</Label>
                    <Input id="birth_state" placeholder="SP" maxLength={2} className="uppercase" {...register('birth_state')} />
                  </div>
                </>
              )}

              {/* Capital Social (COMPANY only) */}
              {personType === 'COMPANY' && (
                <div className="space-y-1.5">
                  <Label>Capital Social</Label>
                  <Controller
                    control={control}
                    name="capital_stock_cents"
                    render={({ field }) => (
                      <NumericFormat
                        customInput={Input}
                        prefix="R$ "
                        thousandSeparator="."
                        decimalSeparator=","
                        decimalScale={2}
                        fixedDecimalScale
                        placeholder="R$ 0,00"
                        value={field.value != null ? field.value / 100 : ''}
                        onValueChange={(vals) =>
                          field.onChange(vals.floatValue != null ? Math.round(vals.floatValue * 100) : null)
                        }
                      />
                    )}
                  />
                </div>
              )}

              {/* Atividade Econômica (COMPANY only) */}
              {personType === 'COMPANY' && (
                <div className="sm:col-span-2 space-y-1.5">
                  <Label htmlFor="economic_activity">Atividade Econômica</Label>
                  <Input id="economic_activity" placeholder="Ramo de atividade" {...register('economic_activity')} />
                </div>
              )}

              {/* Website e Caixa Postal (todos) */}
              <div className="space-y-1.5">
                <Label htmlFor="website">Website</Label>
                <Input id="website" placeholder="https://exemplo.com.br" {...register('website')} />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="postal_box">Caixa Postal</Label>
                <Input id="postal_box" placeholder="Caixa postal" {...register('postal_box')} />
              </div>

              {/* Checkboxes */}
              <div className="sm:col-span-2 space-y-2 pt-1">
                <label className="flex items-center gap-2 cursor-pointer">
                  <input type="checkbox" className="h-3.5 w-3.5 rounded" {...register('is_final_consumer')} />
                  <span className="text-sm">Consumidor Final</span>
                </label>
                <label className="flex items-center gap-2 cursor-pointer">
                  <input type="checkbox" className="h-3.5 w-3.5 rounded" {...register('is_free_zone')} />
                  <span className="text-sm">Zona Franca</span>
                </label>
                {personType === 'COMPANY' && (
                  <>
                    <label className="flex items-center gap-2 cursor-pointer">
                      <input type="checkbox" className="h-3.5 w-3.5 rounded" {...register('is_public_entity')} />
                      <span className="text-sm">Órgão Público</span>
                    </label>
                    <label className="flex items-center gap-2 cursor-pointer">
                      <input type="checkbox" className="h-3.5 w-3.5 rounded" {...register('is_store_chain')} />
                      <span className="text-sm">Rede de Lojas</span>
                    </label>
                  </>
                )}
              </div>
            </div>
          </AppCard>
        )}

        {/* ── Tab: Contatos & Endereços ── */}
        {activeTab === 'enderecos' && (
          <div className="space-y-6">
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
          </div>
        )}

        {/* ── Tab: Dados Comerciais (NEW) ── */}
        {activeTab === 'comercial' && (
          <AppCard title="Dados Comerciais">
            <div className="grid gap-4 sm:grid-cols-2 max-w-2xl">
              <div className="space-y-1.5">
                <Label htmlFor="seller_id">ID do Vendedor</Label>
                <Input id="seller_id" placeholder="ID do vendedor" {...register('seller_id')} />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="representative_id">ID do Representante</Label>
                <Input id="representative_id" placeholder="ID do representante" {...register('representative_id')} />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="carrier_id">ID da Transportadora</Label>
                <Input id="carrier_id" placeholder="ID da transportadora" {...register('carrier_id')} />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="price_list_id">ID da Tabela de Preços</Label>
                <Input id="price_list_id" placeholder="ID da tabela de preços" {...register('price_list_id')} />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="collection_bank_id">Portador/Banco Cobrador</Label>
                <Input id="collection_bank_id" placeholder="ID do banco cobrador" {...register('collection_bank_id')} />
              </div>
              <div className="sm:col-span-2 space-y-1.5">
                <Label>Limite de Crédito</Label>
                <Controller
                  control={control}
                  name="credit_limit_cents"
                  render={({ field }) => (
                    <NumericFormat
                      customInput={Input}
                      prefix="R$ "
                      thousandSeparator="."
                      decimalSeparator=","
                      decimalScale={2}
                      fixedDecimalScale
                      placeholder="R$ 0,00"
                      value={field.value != null ? field.value / 100 : ''}
                      onValueChange={(vals) =>
                        field.onChange(vals.floatValue != null ? Math.round(vals.floatValue * 100) : null)
                      }
                    />
                  )}
                />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="discount_type">Tipo de Desconto</Label>
                <select id="discount_type" className={SELECT_CLASS} {...register('discount_type')}>
                  <option value="">Nenhum</option>
                  <option value="PERCENTAGE">Percentual</option>
                  <option value="FIXED">Fixo</option>
                  <option value="PROGRESSIVE">Progressivo</option>
                </select>
              </div>
              <div className="sm:col-span-2 space-y-1.5 pt-1">
                <label className="flex items-center gap-2 cursor-pointer">
                  <input type="checkbox" className="h-3.5 w-3.5 rounded" {...register('calculates_icms_discount')} />
                  <span className="text-sm">Calcula desconto ICMS</span>
                </label>
              </div>
            </div>
          </AppCard>
        )}

        {/* ── Tab: Crediário (NEW) ── */}
        {activeTab === 'crediario' && (
          <div className="space-y-6">
            <AppCard title="Dados Pessoais Crediário">
              <div className="grid gap-4 sm:grid-cols-2 max-w-2xl">
                <div className="space-y-1.5">
                  <Label htmlFor="father_name">Nome do Pai</Label>
                  <Input id="father_name" placeholder="Nome do pai" {...register('father_name')} />
                </div>
                <div className="space-y-1.5">
                  <Label htmlFor="mother_name">Nome da Mãe</Label>
                  <Input id="mother_name" placeholder="Nome da mãe" {...register('mother_name')} />
                </div>
                <div className="space-y-1.5">
                  <Label htmlFor="profession">Profissão</Label>
                  <Input id="profession" placeholder="Profissão" {...register('profession')} />
                </div>
                <div className="space-y-1.5">
                  <Label htmlFor="professional_card">Carteira Profissional</Label>
                  <Input id="professional_card" placeholder="Número da carteira" {...register('professional_card')} />
                </div>
                <div className="space-y-1.5">
                  <Label htmlFor="employer">Empresa/Empregador</Label>
                  <Input id="employer" placeholder="Nome da empresa" {...register('employer')} />
                </div>
                <div className="space-y-1.5">
                  <Label htmlFor="employer_phone">Telefone do Empregador</Label>
                  <Controller
                    control={control}
                    name="employer_phone"
                    render={({ field }) => (
                      <PatternFormat
                        customInput={Input}
                        format="(##) #####-####"
                        placeholder="(11) 99999-9999"
                        value={field.value ?? ''}
                        onValueChange={(vals) => field.onChange(vals.formattedValue)}
                      />
                    )}
                  />
                </div>
                <div className="sm:col-span-2 space-y-1.5">
                  <Label htmlFor="employer_address">Endereço do Empregador</Label>
                  <Input id="employer_address" placeholder="Endereço completo" {...register('employer_address')} />
                </div>
                <div className="space-y-1.5">
                  <Label htmlFor="work_start_date">Data de Admissão</Label>
                  <Input id="work_start_date" type="date" {...register('work_start_date')} />
                </div>
                <div className="space-y-1.5">
                  <Label>Renda Mensal</Label>
                  <Controller
                    control={control}
                    name="monthly_income"
                    render={({ field }) => (
                      <NumericFormat
                        customInput={Input}
                        prefix="R$ "
                        thousandSeparator="."
                        decimalSeparator=","
                        decimalScale={2}
                        fixedDecimalScale
                        placeholder="R$ 0,00"
                        value={field.value ?? ''}
                        onValueChange={(vals) => field.onChange(vals.floatValue ?? null)}
                      />
                    )}
                  />
                </div>
                <div className="space-y-1.5">
                  <Label>Outras Rendas</Label>
                  <Controller
                    control={control}
                    name="other_income"
                    render={({ field }) => (
                      <NumericFormat
                        customInput={Input}
                        prefix="R$ "
                        thousandSeparator="."
                        decimalSeparator=","
                        decimalScale={2}
                        fixedDecimalScale
                        placeholder="R$ 0,00"
                        value={field.value ?? ''}
                        onValueChange={(vals) => field.onChange(vals.floatValue ?? null)}
                      />
                    )}
                  />
                </div>
                <div className="space-y-1.5">
                  <Label htmlFor="other_income_source">Fonte das Outras Rendas</Label>
                  <Input id="other_income_source" placeholder="Ex.: aluguel, aposentadoria…" {...register('other_income_source')} />
                </div>
                <div className="space-y-1.5">
                  <Label htmlFor="housing_type">Tipo de Moradia</Label>
                  <select id="housing_type" className={SELECT_CLASS} {...register('housing_type')}>
                    <option value="">Selecione…</option>
                    <option value="OWN">Própria</option>
                    <option value="RENTED">Alugada</option>
                    <option value="FINANCED">Financiada</option>
                    <option value="FAMILY">Familiar</option>
                  </select>
                </div>
                {housingType === 'RENTED' && (
                  <div className="space-y-1.5">
                    <Label>Valor do Aluguel</Label>
                    <Controller
                      control={control}
                      name="rent_cents"
                      render={({ field }) => (
                        <NumericFormat
                          customInput={Input}
                          prefix="R$ "
                          thousandSeparator="."
                          decimalSeparator=","
                          decimalScale={2}
                          fixedDecimalScale
                          placeholder="R$ 0,00"
                          value={field.value != null ? field.value / 100 : ''}
                          onValueChange={(vals) =>
                            field.onChange(vals.floatValue != null ? Math.round(vals.floatValue * 100) : null)
                          }
                        />
                      )}
                    />
                  </div>
                )}
                <div className="space-y-1.5">
                  <Label htmlFor="years_at_address">Anos no Endereço</Label>
                  <Input
                    id="years_at_address"
                    type="number"
                    min={0}
                    max={99}
                    placeholder="0"
                    {...register('years_at_address', { valueAsNumber: true })}
                  />
                </div>
                <div className="space-y-1.5">
                  <Label htmlFor="education_level">Escolaridade</Label>
                  <select id="education_level" className={SELECT_CLASS} {...register('education_level')}>
                    <option value="">Selecione…</option>
                    {EDUCATION_LEVEL_OPTIONS.map((opt) => (
                      <option key={opt.value} value={opt.value}>{opt.label}</option>
                    ))}
                  </select>
                </div>
                <div className="space-y-1.5">
                  <Label htmlFor="marital_status">Estado Civil (Crediário)</Label>
                  <select id="marital_status" className={SELECT_CLASS} {...register('marital_status')}>
                    <option value="">Selecione…</option>
                    {CIVIL_STATUS_OPTIONS.map((opt) => (
                      <option key={opt.value} value={opt.value}>{opt.label}</option>
                    ))}
                  </select>
                </div>
              </div>
            </AppCard>

            {/* Cônjuge */}
            {showSpouseSection && (
              <AppCard title="Cônjuge">
                <div className="grid gap-4 sm:grid-cols-2 max-w-2xl">
                  <div className="sm:col-span-2 space-y-1.5">
                    <Label>Nome do cônjuge</Label>
                    <Input placeholder="Nome completo" {...register('spouse_name')} />
                  </div>
                  <div className="space-y-1.5">
                    <Label>CPF do cônjuge</Label>
                    <Controller
                      control={control}
                      name="spouse_document"
                      render={({ field }) => (
                        <PatternFormat
                          customInput={Input}
                          format="###.###.###-##"
                          placeholder="000.000.000-00"
                          inputMode="numeric"
                          value={field.value ?? ''}
                          onValueChange={(vals) => field.onChange(vals.formattedValue)}
                        />
                      )}
                    />
                  </div>
                  <div className="space-y-1.5">
                    <Label>RG do cônjuge</Label>
                    <Input placeholder="RG" {...register('spouse_rg')} />
                  </div>
                  <div className="space-y-1.5">
                    <Label>Telefone do cônjuge</Label>
                    <Controller
                      control={control}
                      name="spouse_phone"
                      render={({ field }) => (
                        <PatternFormat
                          customInput={Input}
                          format="(##) #####-####"
                          placeholder="(11) 99999-9999"
                          value={field.value ?? ''}
                          onValueChange={(vals) => field.onChange(vals.formattedValue)}
                        />
                      )}
                    />
                  </div>
                  <div className="space-y-1.5">
                    <Label>Empresa/Empregador</Label>
                    <Input placeholder="Nome da empresa" {...register('spouse_employer')} />
                  </div>
                  <div className="space-y-1.5">
                    <Label>Renda mensal</Label>
                    <Controller
                      control={control}
                      name="spouse_income"
                      render={({ field }) => (
                        <NumericFormat
                          customInput={Input}
                          prefix="R$ "
                          thousandSeparator="."
                          decimalSeparator=","
                          decimalScale={2}
                          fixedDecimalScale
                          placeholder="R$ 0,00"
                          value={field.value ?? ''}
                          onValueChange={(vals) => field.onChange(vals.floatValue ?? null)}
                        />
                      )}
                    />
                  </div>
                  <div className="space-y-1.5">
                    <Label>Profissão</Label>
                    <Input placeholder="Profissão do cônjuge" {...register('spouse_profession')} />
                  </div>
                  <div className="space-y-1.5">
                    <Label>Data de Nascimento</Label>
                    <Input type="date" {...register('spouse_birth_date')} />
                  </div>
                  <div className="space-y-1.5">
                    <Label>Sexo</Label>
                    <select
                      className={SELECT_CLASS}
                      {...register('spouse_gender')}
                    >
                      <option value="">Selecione...</option>
                      <option value="M">Masculino</option>
                      <option value="F">Feminino</option>
                      <option value="O">Outro</option>
                    </select>
                  </div>
                </div>
              </AppCard>
            )}
          </div>
        )}

        {/* ── Tab: Avalista (migrated) ── */}
        {activeTab === 'avalista' && (
          <AppCard title="Dados do Avalista">
            <div className="grid gap-4 sm:grid-cols-2 max-w-2xl">
              <div className="sm:col-span-2 space-y-1.5">
                <Label>Nome do avalista</Label>
                <Input placeholder="Nome completo" {...register('guarantor_name')} />
              </div>

              <div className="space-y-1.5">
                <Label>CPF do avalista</Label>
                <Controller
                  control={control}
                  name="guarantor_document"
                  render={({ field }) => (
                    <PatternFormat
                      customInput={Input}
                      format="###.###.###-##"
                      placeholder="000.000.000-00"
                      inputMode="numeric"
                      value={field.value ?? ''}
                      onValueChange={(vals) => field.onChange(vals.formattedValue)}
                    />
                  )}
                />
              </div>

              <div className="space-y-1.5">
                <Label>Telefone</Label>
                <Controller
                  control={control}
                  name="guarantor_phone"
                  render={({ field }) => (
                    <PatternFormat
                      customInput={Input}
                      format="(##) #####-####"
                      placeholder="(11) 99999-9999"
                      value={field.value ?? ''}
                      onValueChange={(vals) => field.onChange(vals.formattedValue)}
                    />
                  )}
                />
              </div>

              <div className="space-y-1.5">
                <Label>Renda mensal</Label>
                <Controller
                  control={control}
                  name="guarantor_income"
                  render={({ field }) => (
                    <NumericFormat
                      customInput={Input}
                      prefix="R$ "
                      thousandSeparator="."
                      decimalSeparator=","
                      decimalScale={2}
                      fixedDecimalScale
                      placeholder="R$ 0,00"
                      value={field.value ?? ''}
                      onValueChange={(vals) => field.onChange(vals.floatValue ?? null)}
                    />
                  )}
                />
              </div>

              <div className="sm:col-span-2 space-y-1.5">
                <Label>Endereço</Label>
                <textarea
                  className="w-full min-h-16 rounded-md border bg-background px-3 py-2 text-sm outline-none ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 resize-y"
                  placeholder="Endereço completo do avalista…"
                  {...register('guarantor_address')}
                />
              </div>

              <div className="space-y-1.5">
                <Label>Profissão</Label>
                <Input placeholder="Profissão do avalista" {...register('guarantor_profession')} />
              </div>

              <div className="space-y-1.5">
                <Label>Data de Nascimento</Label>
                <Input type="date" {...register('guarantor_birth_date')} />
              </div>

              <div className="space-y-1.5">
                <Label>Sexo</Label>
                <select
                  className={SELECT_CLASS}
                  {...register('guarantor_gender')}
                >
                  <option value="">Selecione...</option>
                  <option value="M">Masculino</option>
                  <option value="F">Feminino</option>
                  <option value="O">Outro</option>
                </select>
              </div>
            </div>
          </AppCard>
        )}

        {/* ── Tab: Bens & Cartões (NEW) ── */}
        {activeTab === 'bens' && (
          <div className="space-y-6">
            <AppCard title="Cartões de Crédito">
              <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                {CARD_BRANDS.map((brand) => (
                  <label key={brand} className="flex items-center gap-2 cursor-pointer">
                    <input
                      type="checkbox"
                      className="h-3.5 w-3.5 rounded"
                      checked={cards.includes(brand)}
                      onChange={() => toggleCard(brand)}
                    />
                    <span className="text-sm">{brand}</span>
                  </label>
                ))}
              </div>
            </AppCard>

            <AppCard title="Bens Materiais">
              <p className="text-sm text-muted-foreground">
                Para cadastrar bens materiais (imóveis, veículos), utilize a aba de Bens disponível após salvar o cadastro do cliente.
              </p>
            </AppCard>
          </div>
        )}

        {/* ── Tab: Referências ── */}
        {activeTab === 'referencias' && (
          <div className="space-y-6">
            <AppCard
              title="Referências Comerciais"
              actions={
                <Button
                  type="button"
                  size="sm"
                  variant="outline"
                  onClick={() => setShowCommRefDialog(true)}
                  disabled={commRefFields.length >= 10}
                >
                  <Plus className="h-3.5 w-3.5 mr-1.5" />
                  Adicionar referência
                </Button>
              }
            >
              {commRefFields.length === 0 ? (
                <p className="text-sm text-muted-foreground">Nenhuma referência comercial cadastrada.</p>
              ) : (
                <div className="space-y-2">
                  {commRefFields.map((field, index) => (
                    <div key={field.id} className="flex items-start gap-3 rounded-xl border bg-muted/30 px-4 py-3">
                      <Building2 className="h-4 w-4 mt-0.5 shrink-0 text-muted-foreground" />
                      <div className="flex-1 min-w-0 text-sm">
                        <p className="font-medium">{field.company_name}</p>
                        <p className="text-muted-foreground text-xs mt-0.5">
                          {field.contact_person && `${field.contact_person}`}
                          {field.phone && ` · ${field.phone}`}
                          {field.notes && ` · ${field.notes}`}
                        </p>
                      </div>
                      <button
                        type="button"
                        onClick={() => removeCommRef(index)}
                        className="shrink-0 p-1 text-muted-foreground hover:text-destructive transition-colors"
                        aria-label="Remover referência"
                      >
                        <Trash2 className="h-3.5 w-3.5" />
                      </button>
                    </div>
                  ))}
                </div>
              )}
            </AppCard>

            <AppCard title="Referências de Compras">
              <div className="space-y-6">
                <PurchaseRefSection
                  personType="CUSTOMER"
                  label="Do Cliente"
                  fields={purchaseRefFields}
                  onAdd={appendPurchaseRef}
                  onRemove={removePurchaseRef}
                />
                <div className="border-t pt-4">
                  <PurchaseRefSection
                    personType="SPOUSE"
                    label="Do Cônjuge"
                    fields={purchaseRefFields}
                    onAdd={appendPurchaseRef}
                    onRemove={removePurchaseRef}
                  />
                </div>
                <div className="border-t pt-4">
                  <PurchaseRefSection
                    personType="GUARANTOR"
                    label="Do Avalista"
                    fields={purchaseRefFields}
                    onAdd={appendPurchaseRef}
                    onRemove={removePurchaseRef}
                  />
                </div>
              </div>
            </AppCard>
          </div>
        )}

        {/* ── Tab: Observações ── */}
        {activeTab === 'observacoes' && (
          <div className="space-y-6">
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

            <AppCard title="Observações">
              <textarea
                className="w-full min-h-24 rounded-md border bg-background px-3 py-2 text-sm outline-none ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 resize-y"
                placeholder="Observações gerais sobre o cliente…"
                {...register('notes')}
              />
              {errors.notes && <p className="text-xs text-destructive">{errors.notes.message}</p>}
            </AppCard>
          </div>
        )}

        {/* ── Bottom submit ── */}
        <div className="flex justify-end">
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting ? 'Salvando…' : mode === 'create' ? 'Criar Cliente' : 'Salvar Alterações'}
          </Button>
        </div>
        {/* ── Sticky bottom bar — mobile only ── */}
        <div className="md:hidden fixed bottom-0 left-0 right-0 z-30 bg-background border-t p-3 flex gap-2">
          <Button type="submit" className="flex-1" disabled={isSubmitting}>
            {isSubmitting ? 'Salvando…' : mode === 'create' ? 'Criar Cliente' : 'Salvar'}
          </Button>
        </div>
        {/* Spacer so content isn't hidden behind sticky bar */}
        <div className="md:hidden h-16" />

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

      {showCommRefDialog && (
        <CommercialRefDialog
          onAdd={(data) => appendCommRef(data)}
          onClose={() => setShowCommRefDialog(false)}
        />
      )}
    </>
  )
}
