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

  const tabs: { key: Tab; label: string }[] = [
    { key: 'addresses', label: 'Endereços' },
    { key: 'contacts',  label: 'Contatos'  },
    { key: 'tags',      label: 'Tags'      },
    { key: 'notes',     label: 'Observações' },
  ]

  function handleDetachTag(tagUuid: string) {
    detachTag(tagUuid, {
      onSuccess: () => toast.success('Tag removida.'),
      onError:   (err) => toast.error(err instanceof Error ? err.message : 'Erro ao remover tag.'),
    })
    setRemoveTagUuid(null)
  }

  return (
    <div className="space-y-6">
      <CustomerDetailsCard customer={customer} />

      {/* Tabs */}
      <div>
        <div className="border-b flex gap-1 mb-4">
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
