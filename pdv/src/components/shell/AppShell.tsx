import { Outlet } from 'react-router-dom'
import { Sidebar } from './Sidebar'
import { Topbar }  from './Topbar'

/**
 * AppShell — layout base de toda a aplicação.
 *
 * Estrutura:
 * ┌─────────────────────────────────────────┐
 * │ Sidebar │ Topbar                        │
 * │         ├───────────────────────────────│
 * │  [nav]  │ <Outlet /> (conteúdo da rota) │
 * └─────────┴───────────────────────────────┘
 *
 * Sidebar inicia colapsada (56px) → máximo espaço para o PDV.
 */
export function AppShell() {
  return (
    <div className="flex h-screen overflow-hidden bg-background">
      <Sidebar />

      <div className="flex flex-col flex-1 min-w-0 overflow-hidden">
        <Topbar />

        <main className="flex-1 overflow-hidden">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
