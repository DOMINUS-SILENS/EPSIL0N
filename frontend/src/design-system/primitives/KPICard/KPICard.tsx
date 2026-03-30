import { cn } from '@/lib/utils'
import { Card, CardContent } from '@/components/ui/card'
import { TrendingUp, TrendingDown, Minus } from 'lucide-react'

export interface KPICardProps {
  title: string
  value: string
  change?: number
  changeLabel?: string
  icon?: React.ReactNode
  trend?: 'up' | 'down' | 'neutral'
  className?: string
}

export function KPICard({
  title,
  value,
  change,
  changeLabel = 'vs last period',
  icon,
  trend,
  className,
}: KPICardProps) {
  const TrendIcon = trend === 'up' ? TrendingUp : trend === 'down' ? TrendingDown : Minus
  const trendColor =
    trend === 'up'
      ? 'text-emerald-600 dark:text-emerald-400'
      : trend === 'down'
        ? 'text-red-600 dark:text-red-400'
        : 'text-neutral-500'

  return (
    <Card className={cn('border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900', className)}>
      <CardContent className="p-6">
        <div className="flex items-start justify-between">
          <div className="space-y-1">
            <p className="text-sm font-medium text-neutral-500 dark:text-neutral-400">{title}</p>
            <p className="text-2xl font-bold text-neutral-900 dark:text-neutral-100">{value}</p>
            {change !== undefined && (
              <div className={cn('flex items-center gap-1 text-xs', trendColor)}>
                <TrendIcon className="h-3 w-3" />
                <span>
                  {change > 0 ? '+' : ''}
                  {change}%
                </span>
                <span className="text-neutral-500">{changeLabel}</span>
              </div>
            )}
          </div>
          {icon && (
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
              {icon}
            </div>
          )}
        </div>
      </CardContent>
    </Card>
  )
}
