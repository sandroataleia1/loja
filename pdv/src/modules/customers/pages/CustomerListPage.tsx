import { Users } from 'lucide-react'

export function CustomerListPage() {
  return (
    <div className="p-6 space-y-4">
      <div className="flex items-center gap-3">
        <Users className="w-5 h-5 text-muted-foreground" />
        <div>
          <h1 className="text-xl font-semibold">Clientes</h1>
          <p className="text-sm text-muted-foreground">Cadastro e histórico de clientes</p>
        </div>
      </div>

      <div className="rounded-lg border border-dashed p-12 text-center text-muted-foreground">
        <Users className="w-10 h-10 mx-auto mb-3 opacity-30" />
        <p className="text-sm">Módulo de clientes em desenvolvimento</p>
      </div>
    </div>
  )
}
