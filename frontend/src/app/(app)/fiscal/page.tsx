import type { Metadata } from 'next'
import Link from 'next/link'
import { FileText, Settings, ArrowRight } from 'lucide-react'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard }       from '@/components/shared/app-card'
import { ROUTES } from '@/constants'

export const metadata: Metadata = { title: 'Fiscal' }

const QUICK_LINKS = [
  {
    href:        ROUTES.FISCAL_DOCS,
    icon:        FileText,
    title:       'Documentos Fiscais',
    description: 'Consulte NFC-e e NF-e emitidas, retransmita rejeições e cancele documentos autorizados.',
    accent:      'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/40',
  },
  {
    href:        ROUTES.FISCAL_SETTINGS,
    icon:        Settings,
    title:       'Configurações Fiscais',
    description: 'Configure CNPJ, CSC, certificado digital e política de emissão automática de NFC-e.',
    accent:      'text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-950/40',
  },
]

export default function FiscalPage() {
  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Fiscal"
        description="Emissão e gestão de documentos fiscais eletrônicos."
      />

      <div className="grid gap-4 md:grid-cols-2">
        {QUICK_LINKS.map(({ href, icon: Icon, title, description, accent }) => (
          <Link key={href} href={href}>
            <AppCard className="group h-full cursor-pointer transition-shadow hover:shadow-md">
              <div className="flex items-start gap-4 p-6">
                <div className={`shrink-0 rounded-xl p-3 ${accent}`}>
                  <Icon className="h-6 w-6" />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="font-semibold text-sm">{title}</p>
                  <p className="mt-1 text-xs text-muted-foreground leading-relaxed">{description}</p>
                </div>
                <ArrowRight className="h-4 w-4 text-muted-foreground/50 group-hover:text-muted-foreground transition-colors shrink-0 mt-0.5" />
              </div>
            </AppCard>
          </Link>
        ))}
      </div>
    </div>
  )
}
