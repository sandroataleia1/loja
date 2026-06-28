'use client'

import { use, useState } from 'react'
import Link from 'next/link'
import { ChevronLeft, Pencil, Trash2 } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard } from '@/components/shared/app-card'
import { ConfirmDialog } from '@/components/shared/confirm-dialog'
import { CustomerDetailsCard } from '@/features/customers/components/customer-details-card'
import { PrintButton } from '@/features/customers/components/print-button'
import { LGPDActions } from '@/features/customers/components/lgpd-actions'
import { useCustomer, useDetachTag } from '@/features/customers/hooks'
import { ROUTES } from '@/constants'

type Tab = 'addresses' | 'contacts' | 'tags' | 'notes'

interface CustomerDetailPageProps {
  params: Promise<{ uuid: string }>
}

function CustomerDetailContent({ uuid }: { uuid: string }) {
  const { data: customer, isLoading, isError } = useCustomer(uuid)
  const { mutate: detachTag, isPending: isDetaching } = useDetachTag(uuid)
  const [activeTab, setActiveTab]               = useState<Tab>('addresses')
  const [removeTagUuid, setRemoveTagUuid]       = useState<string | null>(null)

  if (isLoading) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-28 w-full rounded-lg" />
        <Skeleton className="h-48 w-full rounded-lg" />
        <Skeleton className="h-64 w-full rounded-lg" />
      </div>
    )
  }

  if (isError || !customer) {
    return (
      <p className="text-center text-sm text-muted-foreground py-12">
        Cliente não encontrado.
      </p>
    )
  }

  const c = customer as any
  const customerStatus: string = c.status ?? (customer.is_active ? 'active' : 'inactive')

  const tabs: { key: Tab; label: string }[] = [
    { key: 'addresses', label: 'Endereços' },
    { key: 'contacts',  label: 'Contatos'  },
    { key: 'tags',      label: 'Tags'      },
    { key: 'notes',     label: 'Observações' },
  ]

  function handleDetachTag(tagUuid: string) {
    detachTag(tagUuid, {
      onSuccess: () => {
        toast.success('Tag removida.')
        setRemoveTagUuid(null)
      },
      onError: (err) => {
        toast.error(err instanceof Error ? err.message : 'Erro ao remover tag.')
        setRemoveTagUuid(null)
      },
    })
  }

  return (
    <div className="space-y-6">
      {/* ── Header card ── */}
      <AppCard>
        <div className="flex flex-col sm:flex-row items-start sm:items-center gap-4">
          {/* Avatar com iniciais */}
          <div className="h-14 w-14 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
            <span className="text-primary font-bold text-xl">
              {customer.name.slice(0, 2).toUpperCase()}
            </span>
          </div>

          {/* Info */}
          <div className="flex-1 min-w-0">
            <div className="flex flex-wrap items-center gap-2">
              <h2 className="text-lg font-bold">{customer.name}</h2>
              <Badge variant="outline" className="text-xs">
                {customer.person_type === 'INDIVIDUAL' ? 'PF' : 'PJ'}
              </Badge>
              {customerStatus === 'blocked' && (
                <Badge className="bg-red-100 text-red-700 border-red-200 text-xs">Bloqueado</Badge>
              )}
              {customerStatus === 'inactive' && (
                <Badge variant="secondary" className="text-xs">Inativo</Badge>
              )}
              {(!customerStatus || customerStatus === 'active') && (
                <Badge className="bg-green-100 text-green-700 border-green-200 text-xs">Ativo</Badge>
              )}
            </div>
            <p className="text-sm text-muted-foreground mt-0.5">
              Cód: {c.code ?? '—'}
              {customer.email && ` · ${customer.email}`}
            </p>
          </div>

          {/* Actions — hidden on mobile */}
          <div className="hidden sm:flex items-center gap-2 flex-wrap">
            <PrintButton label="Ficha" customerUuid={customer.uuid} type="registration-card" />
            <PrintButton label="Contrato" customerUuid={customer.uuid} type="contract" />
            <LGPDActions customerUuid={customer.uuid} />
          </div>
        </div>

        {/* Mobile actions */}
        <div className="sm:hidden flex gap-2 flex-wrap mt-4 pt-4 border-t">
          <PrintButton label="Ficha" customerUuid={customer.uuid} type="registration-card" />
          <PrintButton label="Contrato" customerUuid={customer.uuid} type="contract" />
          <LGPDActions customerUuid={customer.uuid} />
        </div>
      </AppCard>

      {/* Dados do cliente */}
      <CustomerDetailsCard customer={customer} />

      {/* Tabs */}
      <div>
        {/* Desktop tabs */}
        <div className="hidden md:flex border-b gap-1 mb-4">
          {tabs.map((tab) => (
            <button
              key={tab.key}
              onClick={() => setActiveTab(tab.key)}
              className={[
                'px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px',
                activeTab === tab.key
                  ? 'border-primary text-primary'
                  : 'border-transparent text-muted-foreground hover:text-foreground',
              ].join(' ')}
            >
              {tab.label}
            </button>
          ))}
        </div>

        {/* Mobile tabs — select */}
        <div className="md:hidden mb-4">
          <select
            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
            value={activeTab}
            onChange={(e) => setActiveTab(e.target.value as Tab)}
          >
            {tabs.map((tab) => (
              <option key={tab.key} value={tab.key}>{tab.label}</option>
            ))}
          </select>
        </div>

        {/* Addresses tab */}
        {activeTab === 'addresses' && (
          <AppCard title="Endereços">
            {!customer.addresses || customer.addresses.length === 0 ? (
              <p className="text-sm text-muted-foreground">Nenhum endereço cadastrado.</p>
            ) : (
              <div className="space-y-3">
                {customer.addresses.map((addr) => (
                  <div key={addr.uuid} className="rounded-md border p-3 text-sm space-y-1">
                    <div className="flex items-center gap-2">
                      <span className="font-medium">{addr.street}, {addr.number}</span>
                      {addr.complement && <span className="text-muted-foreground">— {addr.complement}</span>}
                      {addr.is_default && <Badge variant="secondary" className="text-xs">Padrão</Badge>}
                    </div>
                    <p className="text-muted-foreground">{addr.district} — {addr.city}/{addr.state} — {addr.zipcode}</p>
                  </div>
                ))}
              </div>
            )}
          </AppCard>
        )}

        {/* Contacts tab */}
        {activeTab === 'contacts' && (
          <AppCard title="Contatos">
            {!customer.contacts || customer.contacts.length === 0 ? (
              <p className="text-sm text-muted-foreground">Nenhum contato cadastrado.</p>
            ) : (
              <div className="space-y-2">
                {customer.contacts.map((contact) => (
                  <div key={contact.uuid} className="flex items-center gap-3 rounded-md border px-3 py-2 text-sm">
                    <Badge variant="outline" className="text-xs shrink-0">{contact.type_label}</Badge>
                    <span className="font-medium">{contact.value}</span>
                    {contact.label && <span className="text-muted-foreground text-xs">({contact.label})</span>}
                    {contact.is_primary && <Badge variant="secondary" className="text-xs ml-auto">Principal</Badge>}
                  </div>
                ))}
              </div>
            )}
          </AppCard>
        )}

        {/* Tags tab */}
        {activeTab === 'tags' && (
          <AppCard title="Tags">
            {!customer.tags || customer.tags.length === 0 ? (
              <p className="text-sm text-muted-foreground">Nenhuma tag associada.</p>
            ) : (
              <div className="flex flex-wrap gap-2">
                {customer.tags.map((tag) => (
                  <span
                    key={tag.uuid}
                    className="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium"
                  >
                    <span className="h-2 w-2 rounded-full" style={{ backgroundColor: tag.color ?? '#94a3b8' }} />
                    {tag.name}
                    <button
                      type="button"
                      className="ml-1 text-muted-foreground hover:text-destructive transition-colors"
                      onClick={() => setRemoveTagUuid(tag.uuid)}
                      aria-label={`Remover tag ${tag.name}`}
                    >
                      <Trash2 className="h-3 w-3" />
                    </button>
                  </span>
                ))}
              </div>
            )}
          </AppCard>
        )}

        {/* Notes tab */}
        {activeTab === 'notes' && (
          <AppCard title="Observações">
            {customer.notes ? (
              <p className="text-sm whitespace-pre-wrap">{customer.notes}</p>
            ) : (
              <p className="text-sm text-muted-foreground">Nenhuma observação registrada.</p>
            )}
          </AppCard>
        )}
      </div>

      {/* Detach tag confirmation */}
      <ConfirmDialog
        open={Boolean(removeTagUuid)}
        onOpenChange={(open) => { if (!open) setRemoveTagUuid(null) }}
        onConfirm={() => removeTagUuid && handleDetachTag(removeTagUuid)}
        loading={isDetaching}
        title="Remover tag?"
        description="A tag será desassociada deste cliente."
        confirmLabel="Remover"
      />
    </div>
  )
}

export default function CustomerDetailPage({ params }: CustomerDetailPageProps) {
  const { uuid } = use(params)

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Detalhes do Cliente"
        actions={
          <div className="flex items-center gap-2">
            <Button variant="outline" asChild>
              <Link href={ROUTES.CUSTOMERS}>
                <ChevronLeft className="mr-1.5 h-4 w-4" />
                Voltar
              </Link>
            </Button>
            <Button asChild>
              <Link href={`${ROUTES.CUSTOMERS}/${uuid}/edit`}>
                <Pencil className="mr-1.5 h-4 w-4" />
                Editar
              </Link>
            </Button>
          </div>
        }
      />
      <CustomerDetailContent uuid={uuid} />
    </div>
  )
}
