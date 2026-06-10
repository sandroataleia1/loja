'use client'

import Link from 'next/link'
import { Building2, Users, ShieldCheck, FileText, User, ChevronRight, Store } from 'lucide-react'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard } from '@/components/shared/app-card'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { useAuth } from '@/hooks/use-auth'
import { ROUTES } from '@/constants/routes'

interface InfoRowProps {
  label: string
  value: string | null | undefined
}

function InfoRow({ label, value }: InfoRowProps) {
  return (
    <div className="flex items-start justify-between py-3">
      <span className="text-sm text-muted-foreground w-40 shrink-0">{label}</span>
      <span className="text-sm font-medium text-right">{value || <span className="text-muted-foreground italic">Não informado</span>}</span>
    </div>
  )
}

interface QuickLinkProps {
  icon: React.ComponentType<{ className?: string }>
  title: string
  description: string
  href: string
  permission?: string
  hasPermission?: (slug: string) => boolean
}

function QuickLink({ icon: Icon, title, description, href, permission, hasPermission }: QuickLinkProps) {
  if (permission && hasPermission && !hasPermission(permission)) return null
  return (
    <Link href={href} className="flex items-center gap-4 p-4 rounded-lg border hover:bg-muted/50 transition-colors group">
      <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary shrink-0">
        <Icon className="h-5 w-5" />
      </div>
      <div className="flex-1 min-w-0">
        <p className="text-sm font-medium">{title}</p>
        <p className="text-xs text-muted-foreground mt-0.5">{description}</p>
      </div>
      <ChevronRight className="h-4 w-4 text-muted-foreground group-hover:text-foreground transition-colors shrink-0" />
    </Link>
  )
}

export default function SettingsPage() {
  const { user, tenant, store, hasPermission } = useAuth()

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Configurações"
        description="Gerencie as informações da sua empresa e preferências do sistema."
      />

      <div className="grid gap-6 lg:grid-cols-2">
        {/* Dados da Empresa */}
        <AppCard
          title="Dados da Empresa"
          description="Informações do seu tenant."
          actions={
            <Badge variant="outline" className="text-xs text-muted-foreground">
              Somente leitura
            </Badge>
          }
        >
          <Separator className="mb-1" />
          <InfoRow label="Nome fantasia" value={tenant?.trade_name} />
          <Separator />
          <InfoRow label="Razão social" value={tenant?.legal_name} />
          <Separator />
          <InfoRow label="CNPJ / Documento" value={tenant?.document} />
          <Separator />
          <InfoRow label="E-mail" value={tenant?.email} />
          <Separator />
          <InfoRow label="Telefone" value={tenant?.phone} />
          <Separator />
          <InfoRow label="Código" value={tenant?.code} />
        </AppCard>

        {/* Loja Ativa + Minha Conta */}
        <div className="flex flex-col gap-6">
          <AppCard title="Loja Ativa">
            {store ? (
              <>
                <Separator className="mb-1" />
                <InfoRow label="Nome" value={store.name} />
                <Separator />
                <InfoRow label="Código" value={store.code} />
                <Separator />
                <InfoRow label="Tipo" value={store.type} />
                <Separator />
                <InfoRow label="E-mail" value={store.email} />
                <Separator />
                <InfoRow label="Telefone" value={store.phone} />
              </>
            ) : (
              <p className="text-sm text-muted-foreground py-4 text-center italic">
                Nenhuma loja selecionada.
              </p>
            )}
          </AppCard>

          <AppCard title="Minha Conta">
            <Separator className="mb-1" />
            <InfoRow label="Nome" value={user?.name} />
            <Separator />
            <InfoRow label="E-mail" value={user?.email} />
          </AppCard>
        </div>
      </div>

      {/* Atalhos de Administração */}
      <AppCard title="Administração" description="Acesso rápido às configurações do sistema.">
        <div className="grid gap-3 sm:grid-cols-2">
          <QuickLink
            icon={Users}
            title="Usuários"
            description="Gerencie quem tem acesso ao sistema."
            href={ROUTES.USERS}
            permission="users.view"
            hasPermission={hasPermission}
          />
          <QuickLink
            icon={ShieldCheck}
            title="Perfis de Acesso"
            description="Configure papéis e permissões por perfil."
            href={ROUTES.ROLES}
            permission="users.view"
            hasPermission={hasPermission}
          />
          <QuickLink
            icon={Store}
            title="Config. de Lojas"
            description="Gerencie lojas e canais de venda."
            href={ROUTES.STORE_ACCESS}
            permission="settings.view"
            hasPermission={hasPermission}
          />
          <QuickLink
            icon={FileText}
            title="Configurações Fiscais"
            description="SEFAZ, séries de NF-e, certificado digital."
            href={ROUTES.FISCAL_SETTINGS}
            permission="fiscal.view"
            hasPermission={hasPermission}
          />
          <QuickLink
            icon={User}
            title="Permissões"
            description="Visualize todas as permissões disponíveis."
            href={ROUTES.PERMISSIONS}
            permission="users.view"
            hasPermission={hasPermission}
          />
          <QuickLink
            icon={Building2}
            title="Acesso por Loja"
            description="Restrinja usuários a lojas específicas."
            href={ROUTES.STORE_ACCESS}
            permission="users.view"
            hasPermission={hasPermission}
          />
        </div>
      </AppCard>
    </div>
  )
}
