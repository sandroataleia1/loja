export const ROUTES = {
  // Auth
  LOGIN:           '/login',
  REGISTER:        '/register',
  FORGOT_PASSWORD: '/forgot-password',
  RESET_PASSWORD:  '/reset-password',

  // App
  HOME:        '/dashboard',
  DASHBOARD:   '/dashboard',
  SETTINGS:            '/settings',
  SETTINGS_GATEWAYS:   '/settings/gateways',
  CUSTOMERS:   '/customers',
  PRODUCTS:    '/products',
  INVENTORY:              '/inventory',
  INVENTORY_MOVEMENTS:    '/inventory/movements',
  INVENTORY_ADJUSTMENTS:  '/inventory/adjustments',
  INVENTORY_TRANSFERS:    '/inventory/transfers',
  SALES:           '/sales',
  CASH_REGISTERS:  '/sales/registers',
  CASH_SESSIONS:   '/sales/sessions',
  FISCAL_SETTINGS: '/fiscal/settings',
  FISCAL_DOCS:     '/fiscal/documents',
  FINANCIAL:              '/financial',
  FINANCIAL_PAYABLE:      '/financial/payable',
  FINANCIAL_RECEIVABLE:   '/financial/receivable',
  FINANCIAL_ACCOUNTS:     '/financial/accounts',
  FINANCIAL_CATEGORIES:   '/financial/categories',
  FINANCIAL_TRANSFERS:    '/financial/transfers',
  FISCAL:      '/fiscal',
  CRM:         '/crm',

  // Catalog
  BRANDS:      '/catalog/brands',
  CATEGORIES:  '/catalog/categories',
  ATTRIBUTES:  '/catalog/attributes',
  GRIDS:       '/catalog/grids',

  // RBAC
  USERS:        '/users',
  ROLES:        '/roles',
  PERMISSIONS:  '/permissions',
  STORE_ACCESS: '/store-access',
  AUDIT_LOGS:   '/audit-logs',

  // PDV Web
  PDV:              '/pdv',
  PDV_CAIXA:        '/pdv/caixa',
  PDV_CAIXA_ABRIR:  '/pdv/caixa/abrir',
  PDV_CAIXA_FECHAR: '/pdv/caixa/fechar',
  PDV_VENDA:        '/pdv/venda',

  // Compras
  PURCHASING:           '/purchasing',
  PURCHASING_CREATE:    '/purchasing/create',
  SUPPLIERS:            '/suppliers',

  // Cadastros Mestres
  CARRIERS:     '/carriers',
  SELLERS:      '/sellers',
  PARTNERS:     '/partners',
  COST_CENTERS: '/cost-centers',

  // Pedidos e Orçamentos
  QUOTES:       '/quotes',
  QUOTES_NEW:   '/quotes/new',
  ORDERS:       '/orders',
  ORDERS_NEW:   '/orders/new',

  // Pagamentos
  PAYMENT_CONDITIONS:   '/settings/payment-conditions',
  PAYMENT_METHODS:      '/settings/payment-methods',
  REPORTS_PAYMENTS:     '/reports/payments',

  // Sistema
  DOWNLOADS:       '/downloads',
  SYSTEM_SETTINGS: '/settings/system',
} as const

export type AppRoute = (typeof ROUTES)[keyof typeof ROUTES]
