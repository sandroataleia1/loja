import type {
  DashboardData,
  DashboardKpi,
  DashboardSalesChartPoint,
  DashboardCategoryChartPoint,
  DashboardChannelChartPoint,
  DashboardAlert,
  DashboardRecentSale,
  DashboardTopProduct,
  DashboardStaleProduct,
} from '../types'

function fmtCurrency(value: number): string {
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
    minimumFractionDigits: 2,
  }).format(value)
}

const MOCK_KPIS: DashboardKpi[] = [
  {
    id:           'revenue',
    label:        'Receita do Mês',
    value:        fmtCurrency(148_320.5),
    rawValue:     148320.5,
    change:       12.4,
    changePeriod: 'vs mês anterior',
    trend:        'up',
    icon:         'DollarSign',
  },
  {
    id:           'orders',
    label:        'Pedidos',
    value:        '1.284',
    rawValue:     1284,
    change:       8.7,
    changePeriod: 'vs mês anterior',
    trend:        'up',
    icon:         'ShoppingCart',
  },
  {
    id:           'ticket',
    label:        'Ticket Médio',
    value:        fmtCurrency(115.5),
    rawValue:     115.5,
    change:       3.4,
    changePeriod: 'vs mês anterior',
    trend:        'up',
    icon:         'Receipt',
  },
  {
    id:           'customers',
    label:        'Clientes Ativos',
    value:        '892',
    rawValue:     892,
    change:       -2.1,
    changePeriod: 'vs mês anterior',
    trend:        'down',
    icon:         'Users',
  },
  {
    id:           'conversion',
    label:        'Taxa de Conversão',
    value:        '4,8%',
    rawValue:     4.8,
    change:       0.3,
    changePeriod: 'vs mês anterior',
    trend:        'up',
    icon:         'TrendingUp',
    suffix:       '%',
  },
  {
    id:           'stock',
    label:        'Itens em Estoque',
    value:        '3.421',
    rawValue:     3421,
    change:       -5.2,
    changePeriod: 'vs mês anterior',
    trend:        'down',
    icon:         'Package',
  },
]

function generateSalesChart(): DashboardSalesChartPoint[] {
  const points: DashboardSalesChartPoint[] = []
  const now = new Date(2026, 4, 30)
  for (let i = 29; i >= 0; i--) {
    const d = new Date(now)
    d.setDate(d.getDate() - i)
    const label = d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })
    const base  = 4000 + Math.random() * 3000
    const weekday = d.getDay()
    const factor  = weekday === 0 || weekday === 6 ? 0.6 : 1
    points.push({
      date:    label,
      revenue: Math.round(base * factor * 10) / 10,
      orders:  Math.round(30 + Math.random() * 60 * factor),
    })
  }
  return points
}

const MOCK_SALES_CHART: DashboardSalesChartPoint[] = generateSalesChart()

const MOCK_CATEGORY_CHART: DashboardCategoryChartPoint[] = [
  { category: 'Roupas',       revenue: 52_400, orders: 420 },
  { category: 'Calçados',     revenue: 38_100, orders: 310 },
  { category: 'Acessórios',   revenue: 21_800, orders: 280 },
  { category: 'Eletrônicos',  revenue: 19_600, orders: 95  },
  { category: 'Casa & Deco',  revenue: 16_420, orders: 179 },
]

const MOCK_CHANNEL_CHART: DashboardChannelChartPoint[] = [
  { channel: 'Loja Física',  value: 45, color: 'hsl(var(--chart-1))' },
  { channel: 'E-commerce',   value: 32, color: 'hsl(var(--chart-2))' },
  { channel: 'WhatsApp',     value: 13, color: 'hsl(var(--chart-3))' },
  { channel: 'Marketplace',  value: 10, color: 'hsl(var(--chart-4))' },
]

const MOCK_ALERTS: DashboardAlert[] = [
  {
    id:        'a1',
    type:      'warning',
    title:     'Estoque crítico',
    message:   '12 produtos com estoque abaixo do mínimo definido.',
    createdAt: '2026-05-30T08:00:00Z',
    link:      '/inventory',
  },
  {
    id:        'a2',
    type:      'error',
    title:     'Pedido pendente há +48h',
    message:   '3 pedidos aguardam aprovação de pagamento há mais de 2 dias.',
    createdAt: '2026-05-29T14:30:00Z',
    link:      '/sales',
  },
  {
    id:        'a3',
    type:      'info',
    title:     'Relatório mensal disponível',
    message:   'O relatório de Abril/2026 está pronto para download.',
    createdAt: '2026-05-01T09:00:00Z',
    link:      '/financial',
  },
  {
    id:        'a4',
    type:      'success',
    title:     'Meta atingida',
    message:   'A meta de receita de Maio foi atingida com 15 dias de antecedência.',
    createdAt: '2026-05-15T17:45:00Z',
  },
]

const MOCK_RECENT_SALES: DashboardRecentSale[] = [
  { id: 's1', customerName: 'Ana Souza',      customerEmail: 'ana@email.com',    amount: 459.9,  status: 'completed', createdAt: '2026-05-30T10:12:00Z', channel: 'E-commerce'  },
  { id: 's2', customerName: 'Bruno Lima',     customerEmail: 'bruno@email.com',  amount: 189.5,  status: 'completed', createdAt: '2026-05-30T09:48:00Z', channel: 'WhatsApp'    },
  { id: 's3', customerName: 'Carla Mendes',   customerEmail: 'carla@email.com',  amount: 820.0,  status: 'pending',   createdAt: '2026-05-30T09:20:00Z', channel: 'Loja Física' },
  { id: 's4', customerName: 'Diego Ferreira', customerEmail: 'diego@email.com',  amount: 314.75, status: 'completed', createdAt: '2026-05-30T08:55:00Z', channel: 'Marketplace' },
  { id: 's5', customerName: 'Eva Costa',      customerEmail: 'eva@email.com',    amount: 99.9,   status: 'cancelled', createdAt: '2026-05-30T08:30:00Z', channel: 'E-commerce'  },
  { id: 's6', customerName: 'Fábio Rocha',    customerEmail: 'fabio@email.com',  amount: 1250.0, status: 'completed', createdAt: '2026-05-30T07:10:00Z', channel: 'Loja Física' },
]

const MOCK_TOP_PRODUCTS: DashboardTopProduct[] = [
  { id: 'p1', name: 'Tênis Casual Conforto',   sku: 'TEN-001', revenue: 18_200, quantity: 182, trend: 'up'      },
  { id: 'p2', name: 'Camiseta Premium Branca',  sku: 'CAM-012', revenue: 12_400, quantity: 310, trend: 'up'      },
  { id: 'p3', name: 'Calça Jeans Slim',         sku: 'CAL-008', revenue: 9_800,  quantity: 98,  trend: 'neutral' },
  { id: 'p4', name: 'Bolsa Couro Feminina',     sku: 'BOL-003', revenue: 8_650,  quantity: 55,  trend: 'up'      },
  { id: 'p5', name: 'Óculos de Sol Polarizado', sku: 'OCS-021', revenue: 6_300,  quantity: 126, trend: 'down'    },
]

const MOCK_STALE_PRODUCTS: DashboardStaleProduct[] = [
  { id: 'sp1', name: 'Blusa Tricô Inverno',    sku: 'BLU-044', stock: 28, lastSoldAt: '2026-03-10', daysSinceSale: 81  },
  { id: 'sp2', name: 'Chapéu Panamá',          sku: 'CHA-007', stock: 15, lastSoldAt: '2026-03-22', daysSinceSale: 69  },
  { id: 'sp3', name: 'Meias Femininas Kit 6',  sku: 'MEI-002', stock: 52, lastSoldAt: '2026-04-01', daysSinceSale: 59  },
  { id: 'sp4', name: 'Cinto Clássico Marrom',  sku: 'CIN-011', stock: 9,  lastSoldAt: '2026-04-05', daysSinceSale: 55  },
  { id: 'sp5', name: 'Perfume Importado 50ml', sku: 'PER-019', stock: 6,  lastSoldAt: null,          daysSinceSale: 120 },
]

export const MOCK_DASHBOARD_DATA: DashboardData = {
  kpis:          MOCK_KPIS,
  salesChart:    MOCK_SALES_CHART,
  categoryChart: MOCK_CATEGORY_CHART,
  channelChart:  MOCK_CHANNEL_CHART,
  alerts:        MOCK_ALERTS,
  recentSales:   MOCK_RECENT_SALES,
  topProducts:   MOCK_TOP_PRODUCTS,
  staleProducts: MOCK_STALE_PRODUCTS,
}
