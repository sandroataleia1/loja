'use client'

import { useForm, useFieldArray, Controller } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Plus, Trash2, ChevronDown, ChevronUp } from 'lucide-react'
import { useState } from 'react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { AppCard } from '@/components/shared/app-card'
import { CustomerTagSelector } from './customer-tag-selector'
import type { CreateCustomerRequest } from '@store/contracts'
import type { ContactType } from '@store/shared-types'

// ── Zod schema ───────────────────────────────────────────────────────────────

const addressSchema = z.object({
  zipcode:    z.string().min(1, 'CEP obrigatório'),
  street:     z.string().min(1, 'Logradouro obrigatório'),
  number:     z.string().min(1, 'Número obrigatório'),
  complement: z.string().optional(),
  district:   z.string().min(1, 'Bairro obrigatório'),
  city:       z.string().min(1, 'Cidade obrigatória'),
  state:      z.string().min(2, 'UF obrigatória').max(2, 'Use a sigla do estado (2 letras)'),
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
  person_type: z.enum(['INDIVIDUAL', 'COMPANY'] as const, { required_error: 'Tipo de pessoa obrigatório' }),
  name:        z.string().min(2, 'Nome deve ter ao menos 2 caracteres').max(200, 'Máximo 200 caracteres'),
  trade_name:  z.string().max(150, 'Máximo 150 caracteres').optional().or(z.literal('')),
  document:    z.string().max(20, 'Máximo 20 caracteres').optional().or(z.literal('')),
  email:       z.string().email('E-mail inválido').optional().or(z.literal('')),
  birth_date:  z.string().optional().or(z.literal('')),
  notes:       z.string().max(2000, 'Máximo 2000 caracteres').optional().or(z.literal('')),
  addresses:   z.array(addressSchema).optional(),
  contacts:    z.array(contactSchema).optional(),
  tags:        z.array(z.string()).optional(),
})

type CustomerFormValues = z.infer<typeof customerSchema>

// ── Helpers ──────────────────────────────────────────────────────────────────

const CONTACT_TYPE_LABELS: Record<ContactType, string> = {
  PHONE:     'Telefone',
  WHATSAPP:  'WhatsApp',
  EMAIL:     'E-mail',
  INSTAGRAM: 'Instagram',
  OTHER:     'Outro',
}

// ── Props ─────────────────────────────────────────────────────────────────────

interface CustomerFormProps {
  defaultValues?: Partial<CreateCustomerRequest>
  onSubmit:       (data: CreateCustomerRequest) => void
  isSubmitting:   boolean
  mode:           'create' | 'edit'
}

// ── Component ────────────────────────────────────────────────────────────────

export function CustomerForm({ defaultValues, onSubmit, isSubmitting, mode }: CustomerFormProps) {
  const [addressesOpen, setAddressesOpen] = useState(false)
  const [contactsOpen,  setContactsOpen]  = useState(false)

  const {
    register,
    handleSubmit,
    control,
    watch,
    formState: { errors },
  } = useForm<CustomerFormValues>({
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

  const {
    fields: addressFields,
    append: appendAddress,
    remove: removeAddress,
  } = useFieldArray({ control, name: 'addresses' })

  const {
    fields: contactFields,
    append: appendContact,
    remove: removeContact,
  } = useFieldArray({ control, name: 'contacts' })

  function handleFormSubmit(values: CustomerFormValues) {
    const payload: CreateCustomerRequest = {
      person_type: values.person_type,
      name:        values.name,
      trade_name:  values.trade_name  || undefined,
      document:    values.document    || undefined,
      email:       values.email       || undefined,
      birth_date:  values.birth_date  || undefined,
      notes:       values.notes       || undefined,
      addresses:   values.addresses?.length  ? values.addresses  : undefined,
      contacts:    values.contacts?.length   ? values.contacts   : undefined,
      tags:        values.tags?.length       ? values.tags       : undefined,
    }
    onSubmit(payload)
  }

  return (
    <form onSubmit={handleSubmit(handleFormSubmit)} className="space-y-6">

      {/* ── Section 1: Dados Principais ── */}
      <AppCard title="Dados Principais">
        <div className="grid gap-4 sm:grid-cols-2 max-w-2xl">

          {/* person_type */}
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

          {/* name */}
          <div className="sm:col-span-2 space-y-1.5">
            <Label htmlFor="name">Nome *</Label>
            <Input id="name" placeholder="Nome completo" {...register('name')} />
            {errors.name && <p className="text-xs text-destructive">{errors.name.message}</p>}
          </div>

          {/* trade_name — only for COMPANY */}
          {personType === 'COMPANY' && (
            <div className="sm:col-span-2 space-y-1.5">
              <Label htmlFor="trade_name">Nome Fantasia</Label>
              <Input id="trade_name" placeholder="Nome fantasia" {...register('trade_name')} />
              {errors.trade_name && <p className="text-xs text-destructive">{errors.trade_name.message}</p>}
            </div>
          )}

          {/* document */}
          <div className="space-y-1.5">
            <Label htmlFor="document">
              {personType === 'COMPANY' ? 'CNPJ' : 'CPF'}
            </Label>
            <Input
              id="document"
              placeholder={personType === 'COMPANY' ? '00.000.000/0000-00' : '000.000.000-00'}
              {...register('document')}
            />
            {errors.document && <p className="text-xs text-destructive">{errors.document.message}</p>}
          </div>

          {/* email */}
          <div className="space-y-1.5">
            <Label htmlFor="email">E-mail</Label>
            <Input id="email" type="email" placeholder="email@exemplo.com" {...register('email')} />
            {errors.email && <p className="text-xs text-destructive">{errors.email.message}</p>}
          </div>

          {/* birth_date */}
          {personType === 'INDIVIDUAL' && (
            <div className="space-y-1.5">
              <Label htmlFor="birth_date">Data de Nascimento</Label>
              <Input id="birth_date" type="date" {...register('birth_date')} />
              {errors.birth_date && <p className="text-xs text-destructive">{errors.birth_date.message}</p>}
            </div>
          )}
        </div>
      </AppCard>

      {/* ── Section 2: Endereços ── */}
      <AppCard
        title="Endereços"
        actions={
          <Button type="button" variant="ghost" size="icon" onClick={() => setAddressesOpen((v) => !v)}>
            {addressesOpen ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
          </Button>
        }
      >
        {addressesOpen && (
          <div className="space-y-4">
            {addressFields.map((field, index) => (
              <div key={field.id} className="rounded-md border p-4 space-y-3 relative">
                <div className="flex items-center justify-between mb-1">
                  <span className="text-sm font-medium text-muted-foreground">Endereço {index + 1}</span>
                  <Button type="button" variant="ghost" size="icon" onClick={() => removeAddress(index)}>
                    <Trash2 className="h-4 w-4 text-destructive" />
                  </Button>
                </div>
                <div className="grid gap-3 sm:grid-cols-2">
                  <div className="space-y-1.5">
                    <Label>CEP *</Label>
                    <Input placeholder="00000-000" {...register(`addresses.${index}.zipcode`)} />
                    {errors.addresses?.[index]?.zipcode && (
                      <p className="text-xs text-destructive">{errors.addresses[index]?.zipcode?.message}</p>
                    )}
                  </div>
                  <div className="space-y-1.5">
                    <Label>Número *</Label>
                    <Input placeholder="123" {...register(`addresses.${index}.number`)} />
                  </div>
                  <div className="sm:col-span-2 space-y-1.5">
                    <Label>Logradouro *</Label>
                    <Input placeholder="Rua, Avenida…" {...register(`addresses.${index}.street`)} />
                  </div>
                  <div className="space-y-1.5">
                    <Label>Complemento</Label>
                    <Input placeholder="Apto, Bloco…" {...register(`addresses.${index}.complement`)} />
                  </div>
                  <div className="space-y-1.5">
                    <Label>Bairro *</Label>
                    <Input placeholder="Bairro" {...register(`addresses.${index}.district`)} />
                  </div>
                  <div className="space-y-1.5">
                    <Label>Cidade *</Label>
                    <Input placeholder="Cidade" {...register(`addresses.${index}.city`)} />
                  </div>
                  <div className="space-y-1.5">
                    <Label>UF *</Label>
                    <Input placeholder="SP" maxLength={2} {...register(`addresses.${index}.state`)} />
                  </div>
                  <div className="sm:col-span-2 flex items-center gap-2">
                    <input type="checkbox" id={`addr_default_${index}`} {...register(`addresses.${index}.is_default`)} />
                    <Label htmlFor={`addr_default_${index}`} className="cursor-pointer">Endereço padrão</Label>
                  </div>
                </div>
              </div>
            ))}
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={() => appendAddress({
                zipcode: '', street: '', number: '', complement: '',
                district: '', city: '', state: '', country: 'BR', is_default: addressFields.length === 0,
              })}
            >
              <Plus className="mr-1.5 h-3.5 w-3.5" />
              Adicionar Endereço
            </Button>
          </div>
        )}
        {!addressesOpen && (
          <p className="text-sm text-muted-foreground">
            {addressFields.length === 0 ? 'Nenhum endereço cadastrado.' : `${addressFields.length} endereço(s) cadastrado(s).`}
          </p>
        )}
      </AppCard>

      {/* ── Section 3: Contatos ── */}
      <AppCard
        title="Contatos"
        actions={
          <Button type="button" variant="ghost" size="icon" onClick={() => setContactsOpen((v) => !v)}>
            {contactsOpen ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
          </Button>
        }
      >
        {contactsOpen && (
          <div className="space-y-4">
            {contactFields.map((field, index) => (
              <div key={field.id} className="rounded-md border p-4 space-y-3">
                <div className="flex items-center justify-between mb-1">
                  <span className="text-sm font-medium text-muted-foreground">Contato {index + 1}</span>
                  <Button type="button" variant="ghost" size="icon" onClick={() => removeContact(index)}>
                    <Trash2 className="h-4 w-4 text-destructive" />
                  </Button>
                </div>
                <div className="grid gap-3 sm:grid-cols-2">
                  <div className="space-y-1.5">
                    <Label>Tipo *</Label>
                    <select
                      className="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2"
                      {...register(`contacts.${index}.type`)}
                    >
                      {(Object.entries(CONTACT_TYPE_LABELS) as [ContactType, string][]).map(([val, label]) => (
                        <option key={val} value={val}>{label}</option>
                      ))}
                    </select>
                    {errors.contacts?.[index]?.type && (
                      <p className="text-xs text-destructive">{errors.contacts[index]?.type?.message}</p>
                    )}
                  </div>
                  <div className="space-y-1.5">
                    <Label>Valor *</Label>
                    <Input placeholder="(11) 99999-9999" {...register(`contacts.${index}.value`)} />
                    {errors.contacts?.[index]?.value && (
                      <p className="text-xs text-destructive">{errors.contacts[index]?.value?.message}</p>
                    )}
                  </div>
                  <div className="space-y-1.5">
                    <Label>Rótulo</Label>
                    <Input placeholder="Ex.: Pessoal, Comercial" {...register(`contacts.${index}.label`)} />
                  </div>
                  <div className="flex items-center gap-2 self-end pb-1">
                    <input type="checkbox" id={`contact_primary_${index}`} {...register(`contacts.${index}.is_primary`)} />
                    <Label htmlFor={`contact_primary_${index}`} className="cursor-pointer">Contato principal</Label>
                  </div>
                </div>
              </div>
            ))}
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={() => appendContact({
                type: 'PHONE', value: '', label: '', is_primary: contactFields.length === 0,
              })}
            >
              <Plus className="mr-1.5 h-3.5 w-3.5" />
              Adicionar Contato
            </Button>
          </div>
        )}
        {!contactsOpen && (
          <p className="text-sm text-muted-foreground">
            {contactFields.length === 0 ? 'Nenhum contato cadastrado.' : `${contactFields.length} contato(s) cadastrado(s).`}
          </p>
        )}
      </AppCard>

      {/* ── Section 4: Tags ── */}
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

      {/* ── Section 5: Observações ── */}
      <AppCard title="Observações">
        <div className="space-y-1.5">
          <textarea
            className="w-full min-h-24 rounded-md border bg-background px-3 py-2 text-sm outline-none ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 resize-y"
            placeholder="Observações gerais sobre o cliente…"
            {...register('notes')}
          />
          {errors.notes && <p className="text-xs text-destructive">{errors.notes.message}</p>}
        </div>
      </AppCard>

      {/* ── Actions ── */}
      <div className="flex gap-2">
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting ? 'Salvando…' : mode === 'create' ? 'Criar Cliente' : 'Salvar Alterações'}
        </Button>
      </div>
    </form>
  )
}
