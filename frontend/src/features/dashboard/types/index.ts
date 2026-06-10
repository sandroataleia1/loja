export interface DashboardKpi {
  id:           string
  label:        string
  value:        string
  rawValue:     number
  change:       number
  changePeriod: string
  trend:        'up' | 'down' | 'neutral'
  icon:         string
  prefix?:      string
  suffix?:      string
}

export interface DashboardSalesChartPoint {
  date:     string
  revenue:  number
  orders:   number
}

export interface DashboardCategoryChartPoint {
  category: string
  revenue:  number
  orders:   number
}

export interface DashboardChannelChartPoint {
  channel: string
  value:   number
  color:   string
}

export interface DashboardAlert {
  id:        string
  type:      'warning' | 'error' | 'info' | 'success'
  title:     string
  message:   string
  createdAt: string
  link?:     string
}

export interface DashboardRecentSale {
  id:           string
  customerName: string
  customerEmail: string
  amount:       number
  status:       'completed' | 'pending' | 'cancelled'
  createdAt:    string
  channel:      string
}

export interface DashboardTopProduct {
  id:       string
  name:     string
  sku:      string
  revenue:  number
  quantity: number
  trend:    'up' | 'down' | 'neutral'
}

export interface DashboardStaleProduct {
  id:          string
  name:        string
  sku:         string
  stock:       number
  lastSoldAt:  string | null
  daysSinceSale: number
}

export interface DashboardData {
  kpis:           DashboardKpi[]
  salesChart:     DashboardSalesChartPoint[]
  categoryChart:  DashboardCategoryChartPoint[]
  channelChart:   DashboardChannelChartPoint[]
  alerts:         DashboardAlert[]
  recentSales:    DashboardRecentSale[]
  topProducts:    DashboardTopProduct[]
  staleProducts:  DashboardStaleProduct[]
}
