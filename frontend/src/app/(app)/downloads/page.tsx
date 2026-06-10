'use client'

import { Download, Monitor, Package, RefreshCw, ExternalLink, CheckCircle } from 'lucide-react'
import { AppPageHeader }  from '@/components/shared/app-page-header'
import { AppCard }        from '@/components/shared/app-card'
import { Badge }          from '@/components/ui/badge'
import { Button }         from '@/components/ui/button'
import { Skeleton }       from '@/components/ui/skeleton'
import { useLatestRelease, APP_VERSION } from '@/hooks/use-latest-release'
import type { ReleaseAsset } from '@/services/releases.service'

function formatBytes(bytes: number) {
  return `${(bytes / 1024 / 1024).toFixed(1)} MB`
}

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString('pt-BR', { day: '2-digit', month: 'long', year: 'numeric' })
}

function isWindows(asset: ReleaseAsset) {
  return asset.name.endsWith('.exe') || asset.name.endsWith('.msi')
}

function isLinux(asset: ReleaseAsset) {
  return asset.name.endsWith('.deb') || asset.name.endsWith('.AppImage')
}

export default function DownloadsPage() {
  const { data: release, isLoading, refetch } = useLatestRelease()

  const hasUpdate  = release && release.tag_name !== APP_VERSION
  const windowsAssets = release?.assets.filter(isWindows) ?? []
  const linuxAssets   = release?.assets.filter(isLinux)   ?? []

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Downloads & Atualizações"
        description="Baixe o aplicativo PDV para instalar nos computadores das lojas."
        actions={
          <Button variant="outline" size="sm" onClick={() => refetch()}>
            <RefreshCw className="h-4 w-4 mr-2" />
            Verificar atualizações
          </Button>
        }
      />

      {/* Status de versões */}
      <div className="grid gap-4 sm:grid-cols-2">
        <AppCard title="Painel Admin (este sistema)">
          <div className="flex items-center justify-between py-2">
            <div className="flex items-center gap-2">
              <Monitor className="h-4 w-4 text-muted-foreground" />
              <span className="text-sm">Versão instalada</span>
            </div>
            <Badge variant="outline" className="font-mono">{APP_VERSION}</Badge>
          </div>
          <div className="flex items-center justify-between py-2">
            <div className="flex items-center gap-2">
              <Package className="h-4 w-4 text-muted-foreground" />
              <span className="text-sm">Última versão disponível</span>
            </div>
            {isLoading ? (
              <Skeleton className="h-5 w-16" />
            ) : (
              <Badge
                variant={hasUpdate ? 'destructive' : 'default'}
                className="font-mono"
              >
                {release?.tag_name ?? '—'}
              </Badge>
            )}
          </div>
          {!isLoading && !hasUpdate && release && (
            <p className="text-xs text-green-600 flex items-center gap-1 mt-1">
              <CheckCircle className="h-3 w-3" /> Sistema atualizado
            </p>
          )}
          {hasUpdate && (
            <div className="mt-3 rounded-md bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 p-3">
              <p className="text-xs font-medium text-amber-800 dark:text-amber-400">
                Nova versão disponível!
              </p>
              <p className="text-xs text-amber-700 dark:text-amber-500 mt-1">
                Para atualizar o painel, execute no servidor:
              </p>
              <code className="block text-xs bg-amber-100 dark:bg-amber-900/50 rounded px-2 py-1 mt-1 font-mono">
                cd /var/www/loja/frontend && git pull && docker compose up -d --build
              </code>
            </div>
          )}
        </AppCard>

        <AppCard title="PDV Desktop">
          <div className="flex items-center justify-between py-2">
            <div className="flex items-center gap-2">
              <Monitor className="h-4 w-4 text-muted-foreground" />
              <span className="text-sm">Versão disponível</span>
            </div>
            {isLoading ? (
              <Skeleton className="h-5 w-16" />
            ) : (
              <Badge variant="outline" className="font-mono">
                {release?.tag_name ?? '—'}
              </Badge>
            )}
          </div>
          {release && (
            <div className="flex items-center justify-between py-2">
              <div className="flex items-center gap-2">
                <Package className="h-4 w-4 text-muted-foreground" />
                <span className="text-sm">Publicado em</span>
              </div>
              <span className="text-sm text-muted-foreground">
                {formatDate(release.published_at)}
              </span>
            </div>
          )}
          {release && (
            <Button variant="outline" size="sm" className="w-full mt-2" asChild>
              <a href={release.html_url} target="_blank" rel="noreferrer">
                <ExternalLink className="h-4 w-4 mr-2" />
                Ver notas da versão
              </a>
            </Button>
          )}
        </AppCard>
      </div>

      {/* Downloads do PDV */}
      <AppCard title="Instalar PDV" description="Baixe o instalador para o sistema operacional da loja.">
        {isLoading ? (
          <div className="space-y-3">
            {Array.from({ length: 3 }).map((_, i) => (
              <Skeleton key={i} className="h-14 w-full" />
            ))}
          </div>
        ) : !release || release.assets.length === 0 ? (
          <div className="text-center py-8 text-muted-foreground text-sm">
            <Package className="h-8 w-8 mx-auto mb-2 opacity-40" />
            <p>Nenhum instalador disponível ainda.</p>
            <p className="text-xs mt-1">Os instaladores aparecem aqui após a primeira publicação de versão.</p>
          </div>
        ) : (
          <div className="space-y-6">
            {windowsAssets.length > 0 && (
              <div>
                <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-2">
                  Windows
                </p>
                <div className="space-y-2">
                  {windowsAssets.map((asset) => (
                    <a
                      key={asset.name}
                      href={asset.browser_download_url}
                      className="flex items-center justify-between rounded-lg border p-3 hover:bg-muted/50 transition-colors group"
                    >
                      <div className="flex items-center gap-3">
                        <Download className="h-4 w-4 text-muted-foreground group-hover:text-foreground transition-colors" />
                        <div>
                          <p className="text-sm font-medium">{asset.name}</p>
                          <p className="text-xs text-muted-foreground">{formatBytes(asset.size)}</p>
                        </div>
                      </div>
                      <Badge variant="outline" className="text-xs">
                        {asset.name.endsWith('.exe') ? 'Instalador' : 'MSI'}
                      </Badge>
                    </a>
                  ))}
                </div>
              </div>
            )}

            {linuxAssets.length > 0 && (
              <div>
                <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-2">
                  Linux
                </p>
                <div className="space-y-2">
                  {linuxAssets.map((asset) => (
                    <a
                      key={asset.name}
                      href={asset.browser_download_url}
                      className="flex items-center justify-between rounded-lg border p-3 hover:bg-muted/50 transition-colors group"
                    >
                      <div className="flex items-center gap-3">
                        <Download className="h-4 w-4 text-muted-foreground group-hover:text-foreground transition-colors" />
                        <div>
                          <p className="text-sm font-medium">{asset.name}</p>
                          <p className="text-xs text-muted-foreground">{formatBytes(asset.size)}</p>
                        </div>
                      </div>
                      <Badge variant="outline" className="text-xs">
                        {asset.name.endsWith('.deb') ? 'Debian/Ubuntu' : 'AppImage'}
                      </Badge>
                    </a>
                  ))}
                </div>
              </div>
            )}
          </div>
        )}
      </AppCard>

      {/* Instruções */}
      <AppCard title="Como instalar">
        <div className="space-y-3 text-sm text-muted-foreground">
          <div className="flex gap-3">
            <span className="flex h-6 w-6 items-center justify-center rounded-full bg-primary text-primary-foreground text-xs font-bold shrink-0">1</span>
            <p>Baixe o arquivo <strong className="text-foreground">.exe</strong> (Windows) ou <strong className="text-foreground">.deb</strong> (Linux) acima.</p>
          </div>
          <div className="flex gap-3">
            <span className="flex h-6 w-6 items-center justify-center rounded-full bg-primary text-primary-foreground text-xs font-bold shrink-0">2</span>
            <p>Copie o instalador para o computador do caixa (pendrive, e-mail ou rede local).</p>
          </div>
          <div className="flex gap-3">
            <span className="flex h-6 w-6 items-center justify-center rounded-full bg-primary text-primary-foreground text-xs font-bold shrink-0">3</span>
            <p>Execute o instalador e siga as instruções. No Windows, clique em <strong className="text-foreground">"Executar assim mesmo"</strong> se aparecer aviso do SmartScreen.</p>
          </div>
          <div className="flex gap-3">
            <span className="flex h-6 w-6 items-center justify-center rounded-full bg-primary text-primary-foreground text-xs font-bold shrink-0">4</span>
            <p>Abra o PDV, faça login com as credenciais da loja e está pronto.</p>
          </div>
        </div>
      </AppCard>
    </div>
  )
}
