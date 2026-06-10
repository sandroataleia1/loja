export const ROUTES = {
  // Auth
  LOGIN:           '/login',
  REGISTER:        '/register',
  FORGOT_PASSWORD: '/forgot-password',
  RESET_PASSWORD:  '/reset-password',

  // App
  HOME:        '/dashboard',
  DASHBOARD:   '/dashboard',
  SETTINGS:    '/settings',
  CUSTOMERS:   '/customers',
  PRODUCTS:    '/products',
  INVENTORY:              '/inventory',
  INVENTORY_MOVEMENTS:    '/inventory/movements',
  INVENTORY_ADJUSTMENTS:  '/inventory/adjustments',
  INVENTORY_TRANSFERS:    '/inventory/transfers',
  CONDITIONALS: '/conditionals',
  SALES:           '/sales',
  CASH_REGISTERS:  '/sales/registers',
  CASH_SESSIONS:   '/sales/sessions',
  FISCAL_SETTINGS: '/fiscal/settings',
  FISCAL_DOCS:     '/fiscal/documents',
  FINANCIAL:   '/financial',
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

  // Sistema
  DOWNLOADS: '/downloads',
} as const

export type AppRoute = (typeof ROUTES)[keyof typeof ROUTES]
