'use client'

import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  ResponsiveContainer,
  Tooltip,
} from 'recharts'
import type { DashboardSalesChartPoint } from '../types'

function fmtCurrency(v: number) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', maximumFractionDigits: 0 }).format(v)
}

interface SalesLineChartProps {
  data: DashboardSalesChartPoint[]
}

export function SalesLineChart({ data }: SalesLineChartProps) {
  return (
    <ResponsiveContainer width="100%" height={220}>
      <LineChart data={data} margin={{ top: 4, right: 8, left: 0, bottom: 0 }}>
        <CartesianGrid strokeDasharray="3 3" className="stroke-border" />
        <XAxis
          dataKey="date"
          tick={{ fontSize: 11 }}
          tickLine={false}
          axisLine={false}
          interval={4}
          className="fill-muted-foreground"
        />
        <YAxis
          yAxisId="revenue"
          orientation="left"
          tickFormatter={(v: number) => fmtCurrency(v)}
          tick={{ fontSize: 10 }}
          tickLine={false}
          axisLine={false}
          width={72}
          className="fill-muted-foreground"
        />
        <YAxis
          yAxisId="orders"
          orientation="right"
          tick={{ fontSize: 10 }}
          tickLine={false}
          axisLine={false}
          width={36}
          className="fill-muted-foreground"
        />
        <Tooltip
          formatter={(value: number, name: string) =>
            name === 'revenue'
              ? [fmtCurrency(value), 'Receita']
              : [value.toLocaleString('pt-BR'), 'Pedidos']
          }
          labelClassName="font-medium text-foreground"
          contentStyle={{
            borderRadius: '8px',
            fontSize: '12px',
            border: '1px solid hsl(var(--border))',
            backgroundColor: 'hsl(var(--background))',
            color: 'hsl(var(--foreground))',
          }}
        />
        <Line
          yAxisId="revenue"
          type="monotone"
          dataKey="revenue"
          stroke="hsl(var(--chart-1))"
          strokeWidth={2}
          dot={false}
          activeDot={{ r: 4 }}
        />
        <Line
          yAxisId="orders"
          type="monotone"
          dataKey="orders"
          stroke="hsl(var(--chart-2))"
          strokeWidth={2}
          dot={false}
          activeDot={{ r: 4 }}
          strokeDasharray="4 2"
        />
      </LineChart>
    </ResponsiveContainer>
  )
}
