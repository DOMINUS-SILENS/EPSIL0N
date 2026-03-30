import { useState } from 'react';
import { Button } from '@/design-system/primitives/Button/Button';
import { useCreateOrder } from '../hooks/useErp';
import { User as UserIcon, CheckCircle, PackageSearch, CreditCard } from 'lucide-react';

interface OrderCreateWizardProps {
  onClose: () => void;
}

export function OrderCreateWizard({ onClose }: OrderCreateWizardProps) {
  const [step, setStep] = useState(1);
  const [customerId, setCustomerId] = useState<number | null>(null);
  const [items, setItems] = useState<{product_id: number; name: string; quantity: number; price: number}[]>([]);
  
  const createOrder = useCreateOrder();

  const handleCreate = () => {
    if (!customerId) return;
    createOrder.mutate(
      { customer_id: customerId, items: items.map(i => ({ product_id: i.product_id, quantity: i.quantity })) },
      { onSuccess: onClose }
    );
  };

  const steps = [
    { title: 'Customer', icon: UserIcon },
    { title: 'Products', icon: PackageSearch },
    { title: 'Review', icon: CheckCircle },
  ];

  return (
    <div className="fixed inset-0 z-50 flex mb-20">
      <div className="absolute inset-0 bg-black/30 backdrop-blur-sm" onClick={onClose} />
      
      <div className="relative ml-auto w-[600px] h-full bg-white dark:bg-neutral-900 shadow-2xl flex flex-col animate-in slide-in-from-right duration-300">
        <div className="p-6 border-b border-neutral-200 dark:border-neutral-800">
          <h2 className="text-xl font-semibold">New Sales Order</h2>
          <p className="text-sm text-neutral-500">CQRS CreateOrderCommand Wizard</p>
          
          <div className="flex gap-4 mt-6">
            {steps.map((s, i) => (
              <div key={i} className={`flex items-center gap-2 text-sm ${step === i + 1 ? 'text-primary font-medium' : 'text-neutral-400'}`}>
                <div className={`w-8 h-8 rounded-full flex items-center justify-center border ${step === i + 1 ? 'border-primary bg-primary/10' : 'border-neutral-200'}`}>
                  <s.icon className="w-4 h-4" />
                </div>
                {s.title}
              </div>
            ))}
          </div>
        </div>

        <div className="flex-1 p-6 overflow-y-auto">
          {step === 1 && (
            <div className="space-y-4">
              <h3 className="font-medium">Select Customer</h3>
              {/* Mock Customer Selection */}
              <div className="grid gap-3">
                {[1, 2].map(id => (
                  <label key={id} className={`flex items-center gap-3 p-4 border rounded-lg cursor-pointer transition-colors ${customerId === id ? 'border-primary bg-primary/5' : 'border-neutral-200 hover:border-neutral-300'}`}>
                    <input type="radio" name="customer" checked={customerId === id} onChange={() => setCustomerId(id)} className="sr-only" />
                    <div className={`w-5 h-5 rounded-full border flex items-center justify-center ${customerId === id ? 'border-primary' : 'border-neutral-300'}`}>
                      {customerId === id && <div className="w-2.5 h-2.5 rounded-full bg-primary" />}
                    </div>
                    <div>
                      <div className="font-medium">Customer #{id} {id === 1 ? '(Acme Corp)' : '(Global Inc)'}</div>
                      <div className="text-sm text-neutral-500">Balance: {id === 1 ? '$15,000' : '$0'}</div>
                    </div>
                  </label>
                ))}
              </div>
            </div>
          )}

          {step === 2 && (
            <div className="space-y-4">
              <h3 className="font-medium">Add Products</h3>
              {/* Mock Product Selection */}
              <div className="space-y-3">
                <Button variant="outline" className="w-full justify-start text-neutral-500" onClick={() => setItems([...items, { product_id: items.length + 1, name: 'Sample Product', quantity: 1, price: 500 }])}>
                  <PackageSearch className="w-4 h-4 mr-2" /> Add Mock Product
                </Button>
                {items.map((item, idx) => (
                  <div key={idx} className="flex items-center justify-between p-3 border border-neutral-200 rounded-lg">
                    <span className="font-medium">{item.name} #{item.product_id}</span>
                    <div className="flex items-center gap-4">
                      <span className="text-neutral-500">${item.price}</span>
                      <div className="flex items-center gap-2 border border-neutral-200 rounded-md p-1">
                        <Button variant="ghost" size="sm" className="h-6 w-6 p-0" onClick={() => {
                          const newItems = [...items];
                          newItems[idx].quantity = Math.max(1, item.quantity - 1);
                          setItems(newItems);
                        }}>-</Button>
                        <span className="w-4 text-center text-sm">{item.quantity}</span>
                        <Button variant="ghost" size="sm" className="h-6 w-6 p-0" onClick={() => {
                          const newItems = [...items];
                          newItems[idx].quantity += 1;
                          setItems(newItems);
                        }}>+</Button>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {step === 3 && (
            <div className="space-y-6">
              <h3 className="font-medium mb-4">Review Overview</h3>
              
              <div className="p-4 bg-neutral-50 dark:bg-neutral-800/50 rounded-lg space-y-3">
                <div className="flex justify-between text-sm">
                  <span className="text-neutral-500">Customer ID</span>
                  <span className="font-medium">{customerId}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-neutral-500">Total Items</span>
                  <span className="font-medium">{items.reduce((s, i) => s + i.quantity, 0)}</span>
                </div>
                <div className="pt-3 border-t border-neutral-200 dark:border-neutral-700 flex justify-between">
                  <span className="font-medium">Total Amount</span>
                  <span className="font-semibold text-primary">
                    {new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(items.reduce((s, i) => s + (i.price * i.quantity), 0))}
                  </span>
                </div>
              </div>
              
              <div className="bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-200 p-3 rounded-lg text-sm flex gap-2">
                <CreditCard className="w-4 h-4 mt-0.5 flex-shrink-0" />
                Credit check passed. Customer is within safe limits.
              </div>
            </div>
          )}
        </div>

        <div className="p-6 border-t border-neutral-200 dark:border-neutral-800 flex justify-between bg-neutral-50 dark:bg-neutral-900">
          <Button variant="ghost" onClick={step === 1 ? onClose : () => setStep(step - 1)}>
            {step === 1 ? 'Cancel' : 'Back'}
          </Button>
          <Button 
            disabled={(step === 1 && !customerId) || (step === 2 && items.length === 0) || createOrder.isPending}
            onClick={step === 3 ? handleCreate : () => setStep(step + 1)}
          >
            {step === 3 ? (createOrder.isPending ? 'Dispatching...' : 'Confirm Order') : 'Next Step'}
          </Button>
        </div>
      </div>
    </div>
  );
}
