"use client"

// Production Cut: Simple in-memory store with SWR-like patterns
// This is a minimal viable data layer - no complex sagas, no event sourcing frontend
// Just predictable CRUD operations that work

export interface Article {
  id: string
  sku: string
  name: string
  category: string
  price: number
  cost: number
  unit: string
  minStock: number
  createdAt: string
}

export interface Client {
  id: string
  code: string
  name: string
  email: string
  phone: string
  address: string
  city: string
  type: "retail" | "wholesale" | "distributor"
  creditLimit: number
  balance: number
  status: "active" | "inactive" | "blocked"
  createdAt: string
}

export interface StockItem {
  articleId: string
  depotId: string
  quantity: number
  reserved: number
  available: number
  lastUpdated: string
}

export interface Depot {
  id: string
  code: string
  name: string
  address: string
  type: "main" | "regional" | "transit"
  capacity: number
  utilization: number
}

export interface OrderLine {
  articleId: string
  articleSku: string
  articleName: string
  quantity: number
  unitPrice: number
  total: number
}

export interface Order {
  id: string
  reference: string
  clientId: string
  clientName: string
  depotId: string
  depotName: string
  lines: OrderLine[]
  subtotal: number
  tax: number
  total: number
  status: "draft" | "confirmed" | "processing" | "shipped" | "delivered" | "cancelled"
  createdAt: string
  updatedAt: string
}

// Initial seed data - minimal but realistic
const initialArticles: Article[] = [
  { id: "ART001", sku: "SKU-001", name: "Industrial Pump A200", category: "Equipment", price: 1250.00, cost: 875.00, unit: "unit", minStock: 5, createdAt: "2024-01-15" },
  { id: "ART002", sku: "SKU-002", name: "Hydraulic Valve Set", category: "Parts", price: 340.00, cost: 210.00, unit: "set", minStock: 20, createdAt: "2024-01-18" },
  { id: "ART003", sku: "SKU-003", name: "Steel Pipe 50mm x 3m", category: "Materials", price: 85.00, cost: 52.00, unit: "piece", minStock: 100, createdAt: "2024-02-01" },
  { id: "ART004", sku: "SKU-004", name: "Electric Motor 5HP", category: "Equipment", price: 890.00, cost: 620.00, unit: "unit", minStock: 8, createdAt: "2024-02-10" },
  { id: "ART005", sku: "SKU-005", name: "Bearing Assembly Kit", category: "Parts", price: 156.00, cost: 98.00, unit: "kit", minStock: 50, createdAt: "2024-02-15" },
  { id: "ART006", sku: "SKU-006", name: "Control Panel Unit", category: "Equipment", price: 2100.00, cost: 1450.00, unit: "unit", minStock: 3, createdAt: "2024-02-20" },
  { id: "ART007", sku: "SKU-007", name: "Copper Wire 2.5mm 100m", category: "Materials", price: 245.00, cost: 165.00, unit: "roll", minStock: 30, createdAt: "2024-03-01" },
  { id: "ART008", sku: "SKU-008", name: "Pneumatic Cylinder", category: "Parts", price: 420.00, cost: 280.00, unit: "unit", minStock: 15, createdAt: "2024-03-05" },
]

const initialClients: Client[] = [
  { id: "CLI001", code: "C-001", name: "Acme Industrial Corp", email: "orders@acme.com", phone: "+1 555-0101", address: "123 Industrial Ave", city: "Chicago", type: "wholesale", creditLimit: 50000, balance: 12500, status: "active", createdAt: "2024-01-10" },
  { id: "CLI002", code: "C-002", name: "TechStart Manufacturing", email: "purchasing@techstart.com", phone: "+1 555-0102", address: "456 Tech Park", city: "Austin", type: "distributor", creditLimit: 75000, balance: 8750, status: "active", createdAt: "2024-01-15" },
  { id: "CLI003", code: "C-003", name: "Global Trade Solutions", email: "info@globaltrade.com", phone: "+1 555-0103", address: "789 Commerce Blvd", city: "Miami", type: "wholesale", creditLimit: 100000, balance: 45200, status: "active", createdAt: "2024-01-20" },
  { id: "CLI004", code: "C-004", name: "Summit Engineering Group", email: "orders@summit-eng.com", phone: "+1 555-0104", address: "321 Summit Road", city: "Denver", type: "retail", creditLimit: 25000, balance: 6800, status: "active", createdAt: "2024-02-01" },
  { id: "CLI005", code: "C-005", name: "Prime Industrial Services", email: "procurement@prime-ind.com", phone: "+1 555-0105", address: "654 Prime Lane", city: "Seattle", type: "wholesale", creditLimit: 60000, balance: 22100, status: "active", createdAt: "2024-02-10" },
]

const initialDepots: Depot[] = [
  { id: "DEP001", code: "MAIN", name: "Main Warehouse", address: "1000 Central Logistics Way", type: "main", capacity: 10000, utilization: 72 },
  { id: "DEP002", code: "WEST", name: "West Regional Hub", address: "500 Pacific Distribution Center", type: "regional", capacity: 5000, utilization: 58 },
  { id: "DEP003", code: "EAST", name: "East Regional Hub", address: "800 Atlantic Logistics Park", type: "regional", capacity: 5000, utilization: 65 },
]

const initialStock: StockItem[] = [
  { articleId: "ART001", depotId: "DEP001", quantity: 24, reserved: 4, available: 20, lastUpdated: "2024-03-27" },
  { articleId: "ART001", depotId: "DEP002", quantity: 8, reserved: 2, available: 6, lastUpdated: "2024-03-27" },
  { articleId: "ART002", depotId: "DEP001", quantity: 156, reserved: 20, available: 136, lastUpdated: "2024-03-27" },
  { articleId: "ART002", depotId: "DEP003", quantity: 45, reserved: 5, available: 40, lastUpdated: "2024-03-27" },
  { articleId: "ART003", depotId: "DEP001", quantity: 420, reserved: 50, available: 370, lastUpdated: "2024-03-27" },
  { articleId: "ART003", depotId: "DEP002", quantity: 180, reserved: 0, available: 180, lastUpdated: "2024-03-27" },
  { articleId: "ART004", depotId: "DEP001", quantity: 18, reserved: 3, available: 15, lastUpdated: "2024-03-27" },
  { articleId: "ART005", depotId: "DEP001", quantity: 245, reserved: 30, available: 215, lastUpdated: "2024-03-27" },
  { articleId: "ART005", depotId: "DEP002", quantity: 80, reserved: 10, available: 70, lastUpdated: "2024-03-27" },
  { articleId: "ART005", depotId: "DEP003", quantity: 65, reserved: 5, available: 60, lastUpdated: "2024-03-27" },
  { articleId: "ART006", depotId: "DEP001", quantity: 7, reserved: 2, available: 5, lastUpdated: "2024-03-27" },
  { articleId: "ART007", depotId: "DEP001", quantity: 85, reserved: 15, available: 70, lastUpdated: "2024-03-27" },
  { articleId: "ART008", depotId: "DEP001", quantity: 42, reserved: 8, available: 34, lastUpdated: "2024-03-27" },
  { articleId: "ART008", depotId: "DEP003", quantity: 28, reserved: 0, available: 28, lastUpdated: "2024-03-27" },
]

const initialOrders: Order[] = [
  {
    id: "ORD001",
    reference: "ORD-2024-001",
    clientId: "CLI001",
    clientName: "Acme Industrial Corp",
    depotId: "DEP001",
    depotName: "Main Warehouse",
    lines: [
      { articleId: "ART001", articleSku: "SKU-001", articleName: "Industrial Pump A200", quantity: 2, unitPrice: 1250.00, total: 2500.00 },
      { articleId: "ART002", articleSku: "SKU-002", articleName: "Hydraulic Valve Set", quantity: 10, unitPrice: 340.00, total: 3400.00 },
    ],
    subtotal: 5900.00,
    tax: 590.00,
    total: 6490.00,
    status: "delivered",
    createdAt: "2024-03-20",
    updatedAt: "2024-03-25",
  },
  {
    id: "ORD002",
    reference: "ORD-2024-002",
    clientId: "CLI002",
    clientName: "TechStart Manufacturing",
    depotId: "DEP001",
    depotName: "Main Warehouse",
    lines: [
      { articleId: "ART004", articleSku: "SKU-004", articleName: "Electric Motor 5HP", quantity: 3, unitPrice: 890.00, total: 2670.00 },
      { articleId: "ART006", articleSku: "SKU-006", articleName: "Control Panel Unit", quantity: 1, unitPrice: 2100.00, total: 2100.00 },
    ],
    subtotal: 4770.00,
    tax: 477.00,
    total: 5247.00,
    status: "shipped",
    createdAt: "2024-03-24",
    updatedAt: "2024-03-26",
  },
  {
    id: "ORD003",
    reference: "ORD-2024-003",
    clientId: "CLI003",
    clientName: "Global Trade Solutions",
    depotId: "DEP002",
    depotName: "West Regional Hub",
    lines: [
      { articleId: "ART003", articleSku: "SKU-003", articleName: "Steel Pipe 50mm x 3m", quantity: 50, unitPrice: 85.00, total: 4250.00 },
      { articleId: "ART007", articleSku: "SKU-007", articleName: "Copper Wire 2.5mm 100m", quantity: 15, unitPrice: 245.00, total: 3675.00 },
    ],
    subtotal: 7925.00,
    tax: 792.50,
    total: 8717.50,
    status: "processing",
    createdAt: "2024-03-26",
    updatedAt: "2024-03-26",
  },
]

// Simple store with localStorage persistence
class ERPStore {
  private articles: Article[] = []
  private clients: Client[] = []
  private depots: Depot[] = []
  private stock: StockItem[] = []
  private orders: Order[] = []
  private listeners: Set<() => void> = new Set()

  constructor() {
    if (typeof window !== "undefined") {
      this.load()
    }
  }

  private load() {
    try {
      const saved = localStorage.getItem("erp_data")
      if (saved) {
        const data = JSON.parse(saved)
        this.articles = data.articles || initialArticles
        this.clients = data.clients || initialClients
        this.depots = data.depots || initialDepots
        this.stock = data.stock || initialStock
        this.orders = data.orders || initialOrders
      } else {
        this.reset()
      }
    } catch {
      this.reset()
    }
  }

  private save() {
    if (typeof window !== "undefined") {
      localStorage.setItem("erp_data", JSON.stringify({
        articles: this.articles,
        clients: this.clients,
        depots: this.depots,
        stock: this.stock,
        orders: this.orders,
      }))
      this.notify()
    }
  }

  private notify() {
    this.listeners.forEach(fn => fn())
  }

  subscribe(fn: () => void) {
    this.listeners.add(fn)
    return () => this.listeners.delete(fn)
  }

  reset() {
    this.articles = [...initialArticles]
    this.clients = [...initialClients]
    this.depots = [...initialDepots]
    this.stock = [...initialStock]
    this.orders = [...initialOrders]
    this.save()
  }

  // Articles
  getArticles() { return this.articles }
  getArticle(id: string) { return this.articles.find(a => a.id === id) }

  // Clients
  getClients() { return this.clients }
  getClient(id: string) { return this.clients.find(c => c.id === id) }

  // Depots
  getDepots() { return this.depots }
  getDepot(id: string) { return this.depots.find(d => d.id === id) }

  // Stock
  getStock() { return this.stock }
  getStockByArticle(articleId: string) { return this.stock.filter(s => s.articleId === articleId) }
  getStockByDepot(depotId: string) { return this.stock.filter(s => s.depotId === depotId) }
  getStockItem(articleId: string, depotId: string) {
    return this.stock.find(s => s.articleId === articleId && s.depotId === depotId)
  }

  // Aggregated stock view
  getStockOverview() {
    const overview: Record<string, { article: Article; totalQty: number; totalReserved: number; totalAvailable: number; depots: { depot: Depot; qty: number; reserved: number; available: number }[] }> = {}
    
    this.stock.forEach(s => {
      const article = this.getArticle(s.articleId)
      const depot = this.getDepot(s.depotId)
      if (!article || !depot) return
      
      if (!overview[s.articleId]) {
        overview[s.articleId] = {
          article,
          totalQty: 0,
          totalReserved: 0,
          totalAvailable: 0,
          depots: [],
        }
      }
      overview[s.articleId].totalQty += s.quantity
      overview[s.articleId].totalReserved += s.reserved
      overview[s.articleId].totalAvailable += s.available
      overview[s.articleId].depots.push({
        depot,
        qty: s.quantity,
        reserved: s.reserved,
        available: s.available,
      })
    })
    
    return Object.values(overview)
  }

  // Orders
  getOrders() { return this.orders }
  getOrder(id: string) { return this.orders.find(o => o.id === id) }

  createOrder(data: { clientId: string; depotId: string; lines: { articleId: string; quantity: number }[] }): Order {
    const client = this.getClient(data.clientId)
    const depot = this.getDepot(data.depotId)
    if (!client || !depot) throw new Error("Invalid client or depot")

    const lines: OrderLine[] = data.lines.map(line => {
      const article = this.getArticle(line.articleId)
      if (!article) throw new Error(`Article ${line.articleId} not found`)
      return {
        articleId: article.id,
        articleSku: article.sku,
        articleName: article.name,
        quantity: line.quantity,
        unitPrice: article.price,
        total: line.quantity * article.price,
      }
    })

    const subtotal = lines.reduce((sum, l) => sum + l.total, 0)
    const tax = subtotal * 0.1
    const total = subtotal + tax

    const order: Order = {
      id: `ORD${String(this.orders.length + 1).padStart(3, "0")}`,
      reference: `ORD-2024-${String(this.orders.length + 1).padStart(3, "0")}`,
      clientId: client.id,
      clientName: client.name,
      depotId: depot.id,
      depotName: depot.name,
      lines,
      subtotal,
      tax,
      total,
      status: "draft",
      createdAt: new Date().toISOString().split("T")[0],
      updatedAt: new Date().toISOString().split("T")[0],
    }

    this.orders = [...this.orders, order]
    this.save()
    return order
  }

  confirmOrder(orderId: string): Order | null {
    const orderIndex = this.orders.findIndex(o => o.id === orderId)
    if (orderIndex === -1) return null

    const order = this.orders[orderIndex]
    if (order.status !== "draft") return null

    // Reserve stock
    order.lines.forEach(line => {
      const stockIndex = this.stock.findIndex(s => s.articleId === line.articleId && s.depotId === order.depotId)
      if (stockIndex !== -1) {
        this.stock[stockIndex] = {
          ...this.stock[stockIndex],
          reserved: this.stock[stockIndex].reserved + line.quantity,
          available: this.stock[stockIndex].available - line.quantity,
          lastUpdated: new Date().toISOString().split("T")[0],
        }
      }
    })

    const updatedOrder = { ...order, status: "confirmed" as const, updatedAt: new Date().toISOString().split("T")[0] }
    this.orders = [...this.orders.slice(0, orderIndex), updatedOrder, ...this.orders.slice(orderIndex + 1)]
    this.save()
    return updatedOrder
  }

  updateOrderStatus(orderId: string, status: Order["status"]): Order | null {
    const orderIndex = this.orders.findIndex(o => o.id === orderId)
    if (orderIndex === -1) return null

    const updatedOrder = { ...this.orders[orderIndex], status, updatedAt: new Date().toISOString().split("T")[0] }
    this.orders = [...this.orders.slice(0, orderIndex), updatedOrder, ...this.orders.slice(orderIndex + 1)]
    this.save()
    return updatedOrder
  }
}

// Singleton instance
let store: ERPStore | null = null

export function getStore(): ERPStore {
  if (!store) {
    store = new ERPStore()
  }
  return store
}

// React hook for subscribing to store updates
export function useStore() {
  const [, forceUpdate] = React.useState({})
  
  React.useEffect(() => {
    const s = getStore()
    return s.subscribe(() => forceUpdate({}))
  }, [])
  
  return getStore()
}
