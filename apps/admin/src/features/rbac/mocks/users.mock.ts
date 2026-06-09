import type { TenantUserData } from '@store/shared-types'

export const MOCK_USERS: TenantUserData[] = [
  {
    uuid:      'tu-001',
    tenant_id: 'tenant-001',
    is_active: true,
    joined_at: '2024-01-15T10:00:00Z',
    store_ids: [],
    role: {
      uuid:        'role-001',
      name:        'Owner',
      slug:        'owner',
      description: 'Acesso total ao sistema',
      is_system:   true,
      tenant_id:   null,
      permissions: [
        { uuid: 'p-001', slug: 'dashboard.view', name: 'Ver Dashboard',  module: 'dashboard', description: null },
        { uuid: 'p-002', slug: 'products.view',  name: 'Ver Produtos',   module: 'products',  description: null },
        { uuid: 'p-003', slug: 'users.view',     name: 'Ver Usuários',   module: 'users',     description: null },
        { uuid: 'p-004', slug: 'settings.view',  name: 'Ver Configurações', module: 'settings', description: null },
      ],
    },
  },
  {
    uuid:      'tu-002',
    tenant_id: 'tenant-001',
    is_active: true,
    joined_at: '2024-02-20T14:30:00Z',
    store_ids: ['store-001'],
    role: {
      uuid:        'role-002',
      name:        'Gerente',
      slug:        'manager',
      description: 'Gerencia operações da loja',
      is_system:   true,
      tenant_id:   null,
      permissions: [
        { uuid: 'p-001', slug: 'dashboard.view', name: 'Ver Dashboard', module: 'dashboard', description: null },
        { uuid: 'p-002', slug: 'products.view',  name: 'Ver Produtos',  module: 'products',  description: null },
        { uuid: 'p-005', slug: 'sales.view',     name: 'Ver Vendas',    module: 'sales',     description: null },
        { uuid: 'p-006', slug: 'inventory.view', name: 'Ver Estoque',   module: 'inventory', description: null },
      ],
    },
  },
  {
    uuid:      'tu-003',
    tenant_id: 'tenant-001',
    is_active: true,
    joined_at: '2024-03-10T09:00:00Z',
    store_ids: ['store-001', 'store-002'],
    role: {
      uuid:        'role-003',
      name:        'Vendedor',
      slug:        'salesperson',
      description: 'Realiza vendas',
      is_system:   true,
      tenant_id:   null,
      permissions: [
        { uuid: 'p-001', slug: 'dashboard.view',  name: 'Ver Dashboard',  module: 'dashboard', description: null },
        { uuid: 'p-005', slug: 'sales.view',      name: 'Ver Vendas',     module: 'sales',     description: null },
        { uuid: 'p-007', slug: 'customers.view',  name: 'Ver Clientes',   module: 'customers', description: null },
      ],
    },
  },
  {
    uuid:      'tu-004',
    tenant_id: 'tenant-001',
    is_active: false,
    joined_at: '2024-04-05T11:00:00Z',
    store_ids: [],
    role: {
      uuid:        'role-004',
      name:        'Financeiro',
      slug:        'financial',
      description: 'Acessa módulo financeiro',
      is_system:   true,
      tenant_id:   null,
      permissions: [
        { uuid: 'p-001', slug: 'dashboard.view',  name: 'Ver Dashboard',  module: 'dashboard', description: null },
        { uuid: 'p-008', slug: 'financial.view',  name: 'Ver Financeiro', module: 'financial', description: null },
      ],
    },
  },
  {
    uuid:      'tu-005',
    tenant_id: 'tenant-001',
    is_active: true,
    joined_at: '2024-05-18T16:00:00Z',
    store_ids: ['store-001'],
    role: {
      uuid:        'role-005',
      name:        'Operador de Estoque',
      slug:        'stock_operator',
      description: 'Gerencia estoque',
      is_system:   true,
      tenant_id:   null,
      permissions: [
        { uuid: 'p-001', slug: 'dashboard.view', name: 'Ver Dashboard', module: 'dashboard', description: null },
        { uuid: 'p-006', slug: 'inventory.view', name: 'Ver Estoque',   module: 'inventory', description: null },
      ],
    },
  },
]

// Simulated user names/emails for the table (would come from a User join in real API)
export interface MockUserProfile {
  uuid:   string
  name:   string
  email:  string
}

export const MOCK_USER_PROFILES: Record<string, MockUserProfile> = {
  'tu-001': { uuid: 'tu-001', name: 'Rafael Mendonça',     email: 'rafael@loomi.com.br'     },
  'tu-002': { uuid: 'tu-002', name: 'Camila Ferreira',     email: 'camila@loomi.com.br'     },
  'tu-003': { uuid: 'tu-003', name: 'Lucas Almeida',       email: 'lucas.almeida@gmail.com' },
  'tu-004': { uuid: 'tu-004', name: 'Mariana Costa',       email: 'mariana.costa@gmail.com' },
  'tu-005': { uuid: 'tu-005', name: 'João Pedro Oliveira', email: 'joao.pedro@loomi.com.br' },
}
