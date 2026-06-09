import { Settings } from 'lucide-react'

export function SettingsPage() {
  return (
    <div className="p-6 space-y-4">
      <div className="flex items-center gap-3">
        <Settings className="w-5 h-5 text-muted-foreground" />
        <div>
          <h1 className="text-xl font-semibold">Configurações</h1>
          <p className="text-sm text-muted-foreground">
            Loja, caixa, impressora, sincronização
          </p>
        </div>
      </div>

      <div className="rounded-lg border border-dashed p-12 text-center text-muted-foreground">
        <Settings className="w-10 h-10 mx-auto mb-3 opacity-30" />
        <p className="text-sm">Configurações em desenvolvimento</p>
      </div>
    </div>
  )
}
