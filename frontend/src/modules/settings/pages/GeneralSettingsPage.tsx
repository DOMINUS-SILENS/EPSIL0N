import { useState } from 'react'
import { Save, Building2, Globe, Mail, MapPin, DollarSign, Languages } from 'lucide-react'
import { usePermissions } from '@/core/auth/useAuth'

export function GeneralSettingsPage() {
  const { can } = usePermissions()
  const [formData, setFormData] = useState({
    companyName: 'Alpha Corporation',
    taxId: 'US-991823901',
    email: 'contact@alpha-erp.com',
    phone: '+1 (555) 019-2093',
    address: '100 Innovation Drive, Silicon Valley, CA 94025',
    currency: 'USD',
    locale: 'en-US',
    timezone: 'America/Los_Angeles',
  })

  return (
    <div className="p-6 max-w-[1200px] mx-auto space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-neutral-200 dark:border-neutral-800 pb-6">
        <div>
          <h1 className="text-2xl font-bold text-neutral-900 dark:text-neutral-100 flex items-center gap-2">
            <Building2 className="h-6 w-6 text-primary" /> General Configuration
          </h1>
          <p className="text-sm text-neutral-500 dark:text-neutral-400 mt-1">Manage core company identity, localization, and default accounting parameters</p>
        </div>
        
        <div className="flex items-center gap-2">
          {can('settings.edit') && (
            <button className="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors font-medium text-sm shadow-sm">
              <Save className="h-4 w-4" />
              Save Changes
            </button>
          )}
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
        
        {/* Company Identity */}
        <div className="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-xl p-6 shadow-sm">
          <h2 className="text-lg font-bold text-neutral-900 dark:text-neutral-100 flex items-center gap-2 mb-6">
            Company Identity
          </h2>
          
          <div className="space-y-5">
            <div>
              <label className="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">Legal Company Name</label>
              <input type="text" value={formData.companyName} onChange={e => setFormData({...formData, companyName: e.target.value})} className="w-full px-3 py-2 bg-neutral-50 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50 text-sm font-medium" />
            </div>
            
            <div>
              <label className="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5 flex items-center gap-1.5">Tax ID / VAT Number</label>
              <input type="text" value={formData.taxId} onChange={e => setFormData({...formData, taxId: e.target.value})} className="w-full px-3 py-2 bg-neutral-50 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50 text-sm font-mono" />
            </div>
          </div>
        </div>

        {/* Contact Information */}
        <div className="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-xl p-6 shadow-sm">
          <h2 className="text-lg font-bold text-neutral-900 dark:text-neutral-100 flex items-center gap-2 mb-6">
            Contact Details
          </h2>
          
          <div className="space-y-5">
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5 flex items-center gap-1.5"><Mail className="h-4 w-4 text-neutral-400" /> Primary Email</label>
                <input type="email" value={formData.email} onChange={e => setFormData({...formData, email: e.target.value})} className="w-full px-3 py-2 bg-neutral-50 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50 text-sm font-medium" />
              </div>
              <div>
                <label className="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">Business Phone</label>
                <input type="text" value={formData.phone} onChange={e => setFormData({...formData, phone: e.target.value})} className="w-full px-3 py-2 bg-neutral-50 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50 text-sm font-medium" />
              </div>
            </div>
            
            <div>
              <label className="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5 flex items-center gap-1.5"><MapPin className="h-4 w-4 text-neutral-400" /> Registered Address</label>
              <textarea value={formData.address} onChange={e => setFormData({...formData, address: e.target.value})} className="w-full h-20 px-3 py-2 bg-neutral-50 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50 text-sm font-medium resize-none" />
            </div>
          </div>
        </div>

        {/* Localization & Defaults */}
        <div className="md:col-span-2 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-xl p-6 shadow-sm">
          <h2 className="text-lg font-bold text-neutral-900 dark:text-neutral-100 flex items-center gap-2 mb-6">
            Localization & Defaults
          </h2>
          
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div>
              <label className="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5 flex items-center gap-1.5"><DollarSign className="h-4 w-4 text-neutral-400" /> Base Currency</label>
              <select value={formData.currency} onChange={e => setFormData({...formData, currency: e.target.value})} className="w-full px-3 py-2 bg-neutral-50 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50 text-sm font-medium text-neutral-900 dark:text-neutral-100">
                <option value="USD">USD - US Dollar</option>
                <option value="EUR">EUR - Euro</option>
                <option value="GBP">GBP - British Pound</option>
              </select>
              <p className="text-xs text-neutral-500 mt-1.5">Warning: Changing base currency affects historical financial reporting.</p>
            </div>
            
            <div>
              <label className="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5 flex items-center gap-1.5"><Languages className="h-4 w-4 text-neutral-400" /> System Language</label>
              <select value={formData.locale} onChange={e => setFormData({...formData, locale: e.target.value})} className="w-full px-3 py-2 bg-neutral-50 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50 text-sm font-medium text-neutral-900 dark:text-neutral-100">
                <option value="en-US">English (US)</option>
                <option value="fr-FR">Français</option>
                <option value="es-ES">Español</option>
              </select>
            </div>

            <div>
              <label className="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5 flex items-center gap-1.5"><Globe className="h-4 w-4 text-neutral-400" /> Timezone</label>
              <select value={formData.timezone} onChange={e => setFormData({...formData, timezone: e.target.value})} className="w-full px-3 py-2 bg-neutral-50 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/50 text-sm font-medium text-neutral-900 dark:text-neutral-100">
                <option value="America/Los_Angeles">Pacific Time (PT)</option>
                <option value="America/New_York">Eastern Time (ET)</option>
                <option value="Europe/London">Greenwich Mean Time (GMT)</option>
                <option value="Europe/Paris">Central European Time (CET)</option>
              </select>
            </div>
          </div>
        </div>

      </div>
    </div>
  )
}

export default GeneralSettingsPage;
