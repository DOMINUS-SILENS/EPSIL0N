"use client"

import {
  Area,
  AreaChart,
  Bar,
  BarChart,
  Line,
  LineChart,
  ResponsiveContainer,
  XAxis,
  YAxis,
  Tooltip,
  CartesianGrid,
  PieChart,
  Pie,
  Cell,
} from "recharts"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"

interface ChartCardProps {
  title: string
  subtitle?: string
  children: React.ReactNode
  className?: string
}

export function ChartCard({ title, subtitle, children, className }: ChartCardProps) {
  return (
    <Card className={className}>
      <CardHeader className="pb-2">
        <CardTitle className="text-base font-semibold">{title}</CardTitle>
        {subtitle && <p className="text-xs text-muted-foreground">{subtitle}</p>}
      </CardHeader>
      <CardContent className="pt-0">{children}</CardContent>
    </Card>
  )
}

interface RevenueChartProps {
  data: Array<{ month: string; revenue: number; orders: number }>
}

export function RevenueChart({ data }: RevenueChartProps) {
  return (
    <ResponsiveContainer width="100%" height={280}>
      <AreaChart data={data}>
        <defs>
          <linearGradient id="colorRevenue" x1="0" y1="0" x2="0" y2="1">
            <stop offset="5%" stopColor="oklch(0.72 0.15 160)" stopOpacity={0.3} />
            <stop offset="95%" stopColor="oklch(0.72 0.15 160)" stopOpacity={0} />
          </linearGradient>
        </defs>
        <CartesianGrid strokeDasharray="3 3" stroke="oklch(0.28 0.01 260)" vertical={false} />
        <XAxis
          dataKey="month"
          stroke="oklch(0.65 0 0)"
          fontSize={12}
          tickLine={false}
          axisLine={false}
        />
        <YAxis
          stroke="oklch(0.65 0 0)"
          fontSize={12}
          tickLine={false}
          axisLine={false}
          tickFormatter={(value) => `$${value / 1000}k`}
        />
        <Tooltip
          contentStyle={{
            backgroundColor: "oklch(0.16 0.008 260)",
            border: "1px solid oklch(0.28 0.01 260)",
            borderRadius: "8px",
            color: "oklch(0.95 0 0)",
          }}
          formatter={(value: number) => [`$${value.toLocaleString()}`, "Revenue"]}
        />
        <Area
          type="monotone"
          dataKey="revenue"
          stroke="oklch(0.72 0.15 160)"
          strokeWidth={2}
          fill="url(#colorRevenue)"
        />
      </AreaChart>
    </ResponsiveContainer>
  )
}

interface OrdersChartProps {
  data: Array<{ month: string; orders: number; completed: number }>
}

export function OrdersChart({ data }: OrdersChartProps) {
  return (
    <ResponsiveContainer width="100%" height={280}>
      <BarChart data={data}>
        <CartesianGrid strokeDasharray="3 3" stroke="oklch(0.28 0.01 260)" vertical={false} />
        <XAxis
          dataKey="month"
          stroke="oklch(0.65 0 0)"
          fontSize={12}
          tickLine={false}
          axisLine={false}
        />
        <YAxis
          stroke="oklch(0.65 0 0)"
          fontSize={12}
          tickLine={false}
          axisLine={false}
        />
        <Tooltip
          contentStyle={{
            backgroundColor: "oklch(0.16 0.008 260)",
            border: "1px solid oklch(0.28 0.01 260)",
            borderRadius: "8px",
            color: "oklch(0.95 0 0)",
          }}
        />
        <Bar dataKey="orders" fill="oklch(0.65 0.18 200)" radius={[4, 4, 0, 0]} />
        <Bar dataKey="completed" fill="oklch(0.72 0.15 160)" radius={[4, 4, 0, 0]} />
      </BarChart>
    </ResponsiveContainer>
  )
}

interface ProductionChartProps {
  data: Array<{ name: string; value: number; color: string }>
}

export function ProductionPieChart({ data }: ProductionChartProps) {
  return (
    <ResponsiveContainer width="100%" height={200}>
      <PieChart>
        <Pie
          data={data}
          cx="50%"
          cy="50%"
          innerRadius={50}
          outerRadius={80}
          paddingAngle={2}
          dataKey="value"
        >
          {data.map((entry, index) => (
            <Cell key={`cell-${index}`} fill={entry.color} />
          ))}
        </Pie>
        <Tooltip
          contentStyle={{
            backgroundColor: "oklch(0.16 0.008 260)",
            border: "1px solid oklch(0.28 0.01 260)",
            borderRadius: "8px",
            color: "oklch(0.95 0 0)",
          }}
        />
      </PieChart>
    </ResponsiveContainer>
  )
}

interface DistributionChartProps {
  data: Array<{ day: string; outgoing: number; incoming: number }>
}

export function DistributionChart({ data }: DistributionChartProps) {
  return (
    <ResponsiveContainer width="100%" height={280}>
      <LineChart data={data}>
        <CartesianGrid strokeDasharray="3 3" stroke="oklch(0.28 0.01 260)" vertical={false} />
        <XAxis
          dataKey="day"
          stroke="oklch(0.65 0 0)"
          fontSize={12}
          tickLine={false}
          axisLine={false}
        />
        <YAxis
          stroke="oklch(0.65 0 0)"
          fontSize={12}
          tickLine={false}
          axisLine={false}
        />
        <Tooltip
          contentStyle={{
            backgroundColor: "oklch(0.16 0.008 260)",
            border: "1px solid oklch(0.28 0.01 260)",
            borderRadius: "8px",
            color: "oklch(0.95 0 0)",
          }}
        />
        <Line
          type="monotone"
          dataKey="outgoing"
          stroke="oklch(0.72 0.15 160)"
          strokeWidth={2}
          dot={false}
        />
        <Line
          type="monotone"
          dataKey="incoming"
          stroke="oklch(0.70 0.15 80)"
          strokeWidth={2}
          dot={false}
        />
      </LineChart>
    </ResponsiveContainer>
  )
}
