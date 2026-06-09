'use client'

import * as React from 'react'
import * as RechartsPrimitive from 'recharts'
import { cn } from '@/lib/utils'

export type ChartConfig = {
  [k in string]: {
    label?: React.ReactNode
    icon?:  React.ComponentType
    color?: string
  }
}

type ChartContextProps = {
  config: ChartConfig
}

const ChartContext = React.createContext<ChartContextProps | null>(null)

function useChart() {
  const context = React.useContext(ChartContext)
  if (!context) throw new Error('useChart must be used within a <ChartContainer />')
  return context
}

function ChartContainer({
  id,
  className,
  children,
  config,
  ...props
}: React.ComponentProps<'div'> & { config: ChartConfig; children: React.ComponentProps<typeof RechartsPrimitive.ResponsiveContainer>['children'] }) {
  const uniqueId = React.useId()
  const chartId  = `chart-${id ?? uniqueId.replace(/:/g, '')}`

  return (
    <ChartContext.Provider value={{ config }}>
      <div
        data-chart={chartId}
        className={cn('flex aspect-video justify-center text-xs', className)}
        {...props}
      >
        <ChartStyle id={chartId} config={config} />
        <RechartsPrimitive.ResponsiveContainer>
          {children}
        </RechartsPrimitive.ResponsiveContainer>
      </div>
    </ChartContext.Provider>
  )
}

function ChartStyle({ id, config }: { id: string; config: ChartConfig }) {
  const colorConfig = Object.entries(config).filter(([, cfg]) => cfg.color)
  if (!colorConfig.length) return null
  return (
    <style
      dangerouslySetInnerHTML={{
        __html: Object.entries(colorConfig)
          .map(([, [key, cfg]]) => `[data-chart="${id}"] { --color-${key}: ${cfg.color}; }`)
          .join('\n'),
      }}
    />
  )
}

const ChartTooltip = RechartsPrimitive.Tooltip

interface ChartTooltipContentProps {
  active?:         boolean
  payload?:        Array<{ name?: string; dataKey?: string; value?: unknown; color?: string }>
  label?:          string
  className?:      string
  formatter?:      (value: number, name: string, item: unknown, index: number, payload: unknown[]) => React.ReactNode
  labelFormatter?: (value: string, payload: unknown[]) => React.ReactNode
  hideLabel?:      boolean
  hideIndicator?:  boolean
  indicator?:      'dot' | 'line' | 'dashed'
}

function ChartTooltipContent({
  active,
  payload,
  label,
  className,
  formatter,
  labelFormatter,
  hideLabel,
  hideIndicator,
  indicator = 'dot',
}: ChartTooltipContentProps) {
  const { config } = useChart()

  if (!active || !payload?.length) return null

  const tooltipLabel =
    !hideLabel && payload.length > 0
      ? (labelFormatter ? labelFormatter(label as string, payload) : label)
      : null

  return (
    <div className={cn('grid min-w-[8rem] items-start gap-1.5 rounded-lg border border-border/50 bg-background px-2.5 py-1.5 text-xs shadow-xl', className)}>
      {tooltipLabel && <div className="font-medium">{tooltipLabel}</div>}
      <div className="grid gap-1.5">
        {payload.map((item, index) => {
          const key   = `${item.name ?? item.dataKey ?? 'value'}`
          const cfg   = config[key as keyof typeof config]
          const color = cfg?.color ?? item.color
          return (
            <div key={index} className="flex w-full flex-wrap items-stretch gap-2 [&>svg]:h-2.5 [&>svg]:w-2.5 [&>svg]:text-muted-foreground">
              {cfg?.icon ? (
                <cfg.icon />
              ) : (
                !hideIndicator && (
                  <div
                    className={cn('shrink-0 rounded-[2px] border-[--color-border] bg-[--color-bg]', indicator === 'dot' && 'h-2.5 w-2.5 rounded-full', indicator === 'line' && 'w-1', indicator === 'dashed' && 'w-0 border-[1.5px] border-dashed bg-transparent')}
                    style={{ '--color-bg': color, '--color-border': color } as React.CSSProperties}
                  />
                )
              )}
              <div className={cn('flex flex-1 justify-between leading-none', hideIndicator ? 'items-end' : 'items-center')}>
                <span className="text-muted-foreground">{cfg?.label ?? item.name}</span>
                {item.value != null && (
                  <span className="font-mono font-medium tabular-nums text-foreground">
                    {formatter ? formatter(item.value as number, item.name as string, item, index, payload) : typeof item.value === 'number' ? item.value.toLocaleString('pt-BR') : String(item.value)}
                  </span>
                )}
              </div>
            </div>
          )
        })}
      </div>
    </div>
  )
}

const ChartLegend = RechartsPrimitive.Legend

function ChartLegendContent({
  className,
  hideIcon = false,
  payload,
  verticalAlign = 'bottom',
}: React.ComponentProps<'div'> & {
  hideIcon?:     boolean
  payload?:      Array<{ value: string; color?: string }>
  verticalAlign?: 'top' | 'bottom'
}) {
  const { config } = useChart()
  if (!payload?.length) return null

  return (
    <div className={cn('flex items-center justify-center gap-4', verticalAlign === 'top' ? 'pb-3' : 'pt-3', className)}>
      {payload.map((item) => {
        const key   = item.value
        const cfg   = config[key]
        const color = cfg?.color ?? item.color
        return (
          <div key={item.value} className="flex items-center gap-1.5 [&>svg]:h-3 [&>svg]:w-3 [&>svg]:text-muted-foreground">
            {cfg?.icon && !hideIcon ? (
              <cfg.icon />
            ) : (
              <div className="h-2 w-2 shrink-0 rounded-[2px]" style={{ backgroundColor: color }} />
            )}
            {cfg?.label ?? item.value}
          </div>
        )
      })}
    </div>
  )
}

export {
  ChartContainer,
  ChartTooltip,
  ChartTooltipContent,
  ChartLegend,
  ChartLegendContent,
  ChartStyle,
}
