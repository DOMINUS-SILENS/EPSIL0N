"use client"

import * as React from "react"
import Link from "next/link"
import { usePathname } from "next/navigation"
import {
  LayoutDashboard,
  Users,
  ShoppingCart,
  Package,
  Factory,
  Truck,
  BarChart3,
  Settings,
  Building2,
  FileText,
  Target,
  Briefcase,
  Warehouse,
  ClipboardList,
  TrendingUp,
  Calendar,
  ChevronDown,
} from "lucide-react"
import { cn } from "@/lib/utils"
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from "@/components/ui/collapsible"
import { ScrollArea } from "@/components/ui/scroll-area"

interface NavItem {
  title: string
  href: string
  icon: React.ElementType
  badge?: string
  children?: NavItem[]
}

const navigation: NavItem[] = [
  {
    title: "Dashboard",
    href: "/",
    icon: LayoutDashboard,
  },
  {
    title: "Commercial",
    href: "/commercial",
    icon: Briefcase,
    children: [
      { title: "Customers", href: "/commercial/customers", icon: Users },
      { title: "Leads", href: "/commercial/leads", icon: Target },
      { title: "Opportunities", href: "/commercial/opportunities", icon: TrendingUp },
      { title: "Orders", href: "/commercial/orders", icon: ShoppingCart },
      { title: "Quotes", href: "/commercial/quotes", icon: FileText },
      { title: "Contracts", href: "/commercial/contracts", icon: ClipboardList },
    ],
  },
  {
    title: "Production",
    href: "/production",
    icon: Factory,
    children: [
      { title: "Planning", href: "/production/planning", icon: Calendar },
      { title: "Work Orders", href: "/production/work-orders", icon: ClipboardList },
      { title: "Inventory", href: "/production/inventory", icon: Package },
      { title: "BOM", href: "/production/bom", icon: FileText },
      { title: "Quality", href: "/production/quality", icon: BarChart3 },
    ],
  },
  {
    title: "Distribution",
    href: "/distribution",
    icon: Truck,
    children: [
      { title: "Shipments", href: "/distribution/shipments", icon: Truck },
      { title: "Warehouses", href: "/distribution/warehouses", icon: Warehouse },
      { title: "Routes", href: "/distribution/routes", icon: TrendingUp },
      { title: "Deliveries", href: "/distribution/deliveries", icon: Package },
    ],
  },
  {
    title: "Analytics",
    href: "/analytics",
    icon: BarChart3,
  },
  {
    title: "Settings",
    href: "/settings",
    icon: Settings,
  },
]

function NavItemComponent({ item, depth = 0 }: { item: NavItem; depth?: number }) {
  const pathname = usePathname()
  const [isOpen, setIsOpen] = React.useState(
    item.children?.some(child => pathname.startsWith(child.href)) || pathname === item.href
  )
  const isActive = pathname === item.href
  const isChildActive = item.children?.some(child => pathname === child.href)
  const Icon = item.icon

  if (item.children) {
    return (
      <Collapsible open={isOpen} onOpenChange={setIsOpen}>
        <CollapsibleTrigger asChild>
          <button
            className={cn(
              "flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors",
              "hover:bg-sidebar-accent hover:text-sidebar-accent-foreground",
              (isActive || isChildActive) && "bg-sidebar-accent text-sidebar-accent-foreground"
            )}
          >
            <Icon className="h-4 w-4 shrink-0" />
            <span className="flex-1 text-left">{item.title}</span>
            <ChevronDown
              className={cn(
                "h-4 w-4 shrink-0 transition-transform",
                isOpen && "rotate-180"
              )}
            />
          </button>
        </CollapsibleTrigger>
        <CollapsibleContent className="pl-4 pt-1">
          {item.children.map((child) => (
            <NavItemComponent key={child.href} item={child} depth={depth + 1} />
          ))}
        </CollapsibleContent>
      </Collapsible>
    )
  }

  return (
    <Link
      href={item.href}
      className={cn(
        "flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors",
        "hover:bg-sidebar-accent hover:text-sidebar-accent-foreground",
        isActive
          ? "bg-sidebar-accent text-primary"
          : "text-sidebar-foreground"
      )}
    >
      <Icon className="h-4 w-4 shrink-0" />
      <span>{item.title}</span>
      {item.badge && (
        <span className="ml-auto rounded-full bg-primary px-2 py-0.5 text-xs font-semibold text-primary-foreground">
          {item.badge}
        </span>
      )}
    </Link>
  )
}

export function AppSidebar() {
  return (
    <aside className="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-sidebar-border bg-sidebar">
      <div className="flex h-16 items-center gap-2 border-b border-sidebar-border px-6">
        <Building2 className="h-7 w-7 text-primary" />
        <span className="text-lg font-bold tracking-tight text-sidebar-foreground">
          NexusERP
        </span>
      </div>

      <ScrollArea className="flex-1 px-3 py-4">
        <nav className="flex flex-col gap-1">
          {navigation.map((item) => (
            <NavItemComponent key={item.href} item={item} />
          ))}
        </nav>
      </ScrollArea>

      <div className="border-t border-sidebar-border p-4">
        <div className="flex items-center gap-3">
          <div className="flex h-9 w-9 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-foreground">
            JD
          </div>
          <div className="flex-1 truncate">
            <p className="text-sm font-medium text-sidebar-foreground">John Doe</p>
            <p className="text-xs text-muted-foreground">Administrator</p>
          </div>
        </div>
      </div>
    </aside>
  )
}
