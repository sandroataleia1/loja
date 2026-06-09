import { createHashRouter, Navigate } from 'react-router-dom'
import { AppShell, PosShell } from '@/components/shell'
import { AuthGuard, RegisterGuard } from './guards'

// Auth
import { LoginPage }         from '@/modules/auth/pages/LoginPage'
// Register
import { OpenRegisterPage }  from '@/modules/register/pages/OpenRegisterPage'
import { CloseRegisterPage } from '@/modules/register/pages/CloseRegisterPage'
// PDV
import { PosPage }           from '@/modules/sales/pages/PosPage'
// Catálogo
import { ProductListPage }   from '@/modules/catalog/pages/ProductListPage'
// Vendas
import { SaleListPage }      from '@/modules/sales/pages/SaleListPage'
// Clientes
import { CustomerListPage }  from '@/modules/customers/pages/CustomerListPage'
// Config
import { SettingsPage }      from '@/modules/settings/pages/SettingsPage'

/**
 * Hash router — obrigatório para Tauri (sem HTTP server para pushState).
 *
 * Fluxo de navegação:
 *   /login → /caixa/abrir → / (PDV)
 *                         ↕
 *              /sales, /products … (admin, sidebar)
 *                         ↕
 *            /caixa/fechar → /login
 */
export const router = createHashRouter([
  // ── Pública ──────────────────────────────────────────────────────────
  { path: 'login', element: <LoginPage /> },

  // ── Requer login ─────────────────────────────────────────────────────
  {
    element: <AuthGuard />,
    children: [
      { path: 'caixa/abrir', element: <OpenRegisterPage /> },

      // ── Requer caixa aberto ─────────────────────────────────────────
      {
        element: <RegisterGuard />,
        children: [
          { path: 'caixa/fechar', element: <CloseRegisterPage /> },

          // POS shell — sem sidebar, topbar compacta
          {
            element: <PosShell />,
            children: [
              { index: true, element: <PosPage /> },
            ],
          },

          // Admin shell — com sidebar
          {
            element: <AppShell />,
            children: [
              { path: 'products',  element: <ProductListPage /> },
              { path: 'sales',     element: <SaleListPage /> },
              { path: 'customers', element: <CustomerListPage /> },
              { path: 'settings',  element: <SettingsPage /> },
            ],
          },
        ],
      },
    ],
  },

  { path: '*', element: <Navigate to="/login" replace /> },
])
