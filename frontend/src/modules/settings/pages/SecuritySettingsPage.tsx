
import { ShieldAlert, Key, Smartphone, Fingerprint, Activity, Clock } from 'lucide-react'

export function SecuritySettingsPage() {

  const MOCK_AUDIT = [
    { time: '2 mins ago', ip: '192.168.1.45', event: 'Failed login attempt', user: 'admin@alpha-erp.com', status: 'Blocked' },
    { time: '1 hour ago', ip: '10.0.0.12', event: 'Password reset requested', user: 'j.doe@alpha-erp.com', status: 'Success' },
    { time: '3 hours ago', ip: '10.0.0.8', event: 'MFA Disabled by Administrator', user: 'Admin', status: 'Warning' },
  ]

  return (
    <div className="p-6 max-w-[1200px] mx-auto space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-neutral-200 dark:border-neutral-800 pb-6">
        <div>
          <h1 className="text-2xl font-bold text-neutral-900 dark:text-neutral-100 flex items-center gap-2">
            <ShieldAlert className="h-6 w-6 text-red-600 dark:text-red-500" /> Security & Compliance
          </h1>
          <p className="text-sm text-neutral-500 dark:text-neutral-400 mt-1">Enforce password policies, manage Single Sign-On, and review audit logs</p>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
        
        {/* Auth Policies */}
        <div className="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-xl p-6 shadow-sm">
          <h2 className="text-lg font-bold text-neutral-900 dark:text-neutral-100 flex items-center gap-2 mb-6">
            Authentication Policies
          </h2>
          
          <div className="space-y-6">
            <div className="flex items-center justify-between">
              <div>
                <h4 className="text-sm font-bold text-neutral-900 dark:text-neutral-100 flex items-center gap-1.5"><Key className="h-4 w-4 text-neutral-400" /> Password Expiration</h4>
                <p className="text-xs text-neutral-500 mt-1">Force users to rotate passwords every 90 days</p>
              </div>
              <label className="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" className="sr-only peer" defaultChecked />
                <div className="w-11 h-6 bg-neutral-200 peer-focus:outline-none rounded-full peer dark:bg-neutral-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-neutral-600 peer-checked:bg-primary"></div>
              </label>
            </div>

            <div className="flex items-center justify-between">
              <div>
                <h4 className="text-sm font-bold text-neutral-900 dark:text-neutral-100 flex items-center gap-1.5"><Smartphone className="h-4 w-4 text-neutral-400" /> Enforce 2FA (MFA)</h4>
                <p className="text-xs text-neutral-500 mt-1">Require Authenticator app for all admin-level roles</p>
              </div>
              <label className="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" className="sr-only peer" defaultChecked />
                <div className="w-11 h-6 bg-neutral-200 peer-focus:outline-none rounded-full peer dark:bg-neutral-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-neutral-600 peer-checked:bg-primary"></div>
              </label>
            </div>

            <div className="flex items-center justify-between">
              <div>
                <h4 className="text-sm font-bold text-neutral-900 dark:text-neutral-100 flex items-center gap-1.5"><Fingerprint className="h-4 w-4 text-neutral-400" /> Single Sign-On (SAML)</h4>
                <p className="text-xs text-neutral-500 mt-1">Enable Microsoft Entra ID / Okta login routing</p>
              </div>
              <button className="text-xs font-bold text-primary bg-primary/10 hover:bg-primary/20 transition-colors px-3 py-1.5 rounded-md">Configure Provider</button>
            </div>
          </div>
        </div>

        {/* Audit Log Preview */}
        <div className="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-xl flex flex-col shadow-sm">
          <div className="p-6 border-b border-neutral-200 dark:border-neutral-800 flex justify-between items-center">
            <h2 className="text-lg font-bold text-neutral-900 dark:text-neutral-100 flex items-center gap-2">
              <Activity className="h-5 w-5 text-neutral-400" /> Security Audit Log
            </h2>
            <button className="text-xs text-neutral-500 hover:text-primary font-semibold transition-colors">View All Logs</button>
          </div>
          
          <div className="flex-1 p-0 overflow-y-auto">
             <table className="w-full text-left text-sm whitespace-nowrap">
                <tbody className="divide-y divide-neutral-100 dark:divide-neutral-800">
                  {MOCK_AUDIT.map((log, i) => (
                    <tr key={i} className="hover:bg-neutral-50 dark:hover:bg-neutral-800/50">
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-2 text-neutral-500 dark:text-neutral-400 text-xs font-medium">
                          <Clock className="h-3 w-3" /> {log.time}
                        </div>
                      </td>
                      <td className="px-4 py-3 font-semibold text-neutral-900 dark:text-neutral-100 text-xs">
                        {log.event}
                      </td>
                      <td className="px-4 py-3 font-mono text-neutral-500 text-xs">
                        {log.ip}
                      </td>
                      <td className="px-4 py-3 text-right">
                        <span className={`inline-flex px-2 py-0.5 rounded text-[10px] uppercase font-bold tracking-wider ${log.status === 'Success' ? 'bg-green-100 text-green-700' : log.status === 'Blocked' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'}`}>
                          {log.status}
                        </span>
                      </td>
                    </tr>
                  ))}
                </tbody>
             </table>
          </div>
        </div>

      </div>
    </div>
  )
}

export default SecuritySettingsPage;
